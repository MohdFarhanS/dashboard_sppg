<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use App\Models\MenuHarian;
use App\Models\PesanMasuk;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer(['partials.navbar', 'partials.sidebar'], function ($view) {
            if (!Auth::check()) return;
            if (!Schema::hasTable('menu_harians')) {
                $view->with('navAlertCount', 0);
                $view->with('navAlerts', []);
                return;
            }

            $user  = Auth::user();
            $today = today();
        
            $query = MenuHarian::with('detailBahans.bahanPangan')
                ->where('status', 'final')
                ->whereYear('tanggal',  $today->year)
                ->whereMonth('tanggal', $today->month);

            $totalAlert   = 0;
            $navAlerts    = [];
            $dismissedIds = session('dismissed_alert_ids', []);

            foreach ($query->get() as $menu) {
                $s = $menu->statusAnggaran();
                if (!in_array($s, ['over', 'warning'])) continue;
                if (in_array($menu->id, $dismissedIds)) continue;

                $totalAlert++;
                $navAlerts[] = [
                    'type'    => $s === 'over' ? 'danger' : 'warning',
                    'msg'     => 'Menu ' . ($menu->nama_menu ?? $menu->tanggal->format('d/m/Y'))
                                 . ($s === 'over' ? ' melebihi anggaran' : ' mendekati batas anggaran'),
                    'time'    => $menu->tanggal->format('d/m/Y'),
                    'menu_id' => $menu->id,
                ];
            }
        
            $view->with('navAlertCount', $totalAlert);
            $view->with('navAlerts', array_slice($navAlerts, 0, 5));

            // Jumlah pesan masuk yang belum dibaca (hanya untuk ketua_sppg)
            $pesanMasukCount = 0;
            if ($user->isKetuaSppg() && Schema::hasTable('pesan_masuks')) {
                $pesanMasukCount = PesanMasuk::unread()->count();
            }
            $view->with('pesanMasukCount', $pesanMasukCount);
        });
    }
}
