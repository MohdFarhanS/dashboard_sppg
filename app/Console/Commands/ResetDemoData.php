<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetDemoData extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset database ke kondisi demo bersih (migrate:fresh + seed dummy) — hanya untuk instance demo/portfolio';

    public function handle(): int
    {
        if (! config('app.demo_mode')) {
            $this->error('DEMO_MODE belum aktif di .env. Command dibatalkan untuk mencegah wipe data tidak sengaja.');

            return self::FAILURE;
        }

        $this->info('Mereset data demo...');

        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => 'BahanPanganSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'DemoUserSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'MenuDummySeeder', '--force' => true]);

        $this->info('Demo data berhasil direset.');

        return self::SUCCESS;
    }
}
