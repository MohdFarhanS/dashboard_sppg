<?php

namespace App\Http\Controllers;

use App\Models\AnggaranPorsi;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnggaranController extends Controller
{
    // Route dilindungi middleware role:ketua_sppg — tidak perlu cek manual
    public function __construct() {}

    public function index()
    {
        $riwayat = AnggaranPorsi::with('createdBy')
            ->orderByDesc('berlaku_mulai')
            ->orderBy('kelompok')
            ->paginate(20);

        return view('anggaran.index', compact('riwayat'));
    }

    public function create()
    {
        return view('anggaran.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'anggaran_balita_sd3' => 'required|numeric|min:1000',
            'anggaran_sd4_ibu_menyusui' => 'required|numeric|min:1000',
            'berlaku_mulai' => 'required|date',
            'keterangan' => 'nullable|string|max:200',
        ]);

        $mulaiStr = Carbon::parse($data['berlaku_mulai'])->toDateString();

        $kelompokList = [
            'balita_sd3' => (float) $data['anggaran_balita_sd3'],
            'sd4_ibu_menyusui' => (float) $data['anggaran_sd4_ibu_menyusui'],
        ];

        try {
            // Kedua kelompok ditetapkan bersamaan: kalau salah satu gagal, jangan sampai
            // kelompok lain terlanjur tersimpan (periode lama tertutup tanpa pengganti).
            // Aturan penutupan/backdate ada di trait PeriodeBerlaku (dipakai bersama HargaBahan).
            DB::transaction(function () use ($kelompokList, $data, $mulaiStr) {
                foreach ($kelompokList as $kelompok => $anggaran) {
                    AnggaranPorsi::tetapkanPeriode(
                        ['kelompok' => $kelompok],
                        $mulaiStr,
                        [
                            'anggaran_per_porsi' => $anggaran,
                            'keterangan' => $data['keterangan'] ?? null,
                            'created_by' => auth()->id(),
                        ]
                    );
                }
            });
        } catch (UniqueConstraintViolationException) {
            // Dua ketua submit tanggal yang sama secara bersamaan: pre-check di
            // tetapkanPeriode() lolos, unique index DB yang menahannya. Jawab sebagai
            // error validasi, bukan HTTP 500.
            throw ValidationException::withMessages([
                'berlaku_mulai' => 'Sudah ada anggaran yang berlaku mulai tanggal ini. Pilih tanggal lain.',
            ]);
        }

        return redirect()->route('anggaran.index')
            ->with('success', 'Anggaran baru berhasil ditetapkan untuk kedua kelompok.');
    }
}
