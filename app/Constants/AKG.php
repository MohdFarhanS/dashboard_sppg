<?php

namespace App\Constants;

class AKG
{
    // AKG Anak Sekolah (7-12 tahun) — MBG target utama
    public const HARIAN = [
        'energi' => 1850,  // kkal
        'protein' => 49,    // gram
        'lemak' => 62,    // gram
        'karbohidrat' => 254,   // gram
        'serat' => 22,    // gram
        'kalsium' => 1000,  // mg
        'besi' => 10,    // mg
        'vit_c' => 45,    // mg
    ];

    // Kontribusi makan siang ~35% dari total harian
    public const MAKAN_SIANG = [
        'energi' => 578,  // 35% × 1.650 kkal
        'protein' => 14,   // 35% × 40g
        'lemak' => 19,   // 35% × 55g
        'karbohidrat' => 88,   // 35% × 250g
        'serat' => 8,    // 35% × 22g
        'kalsium' => 350,  // 35% × 1000mg
        'besi' => 4,    // 35% × 10mg
        'vit_c' => 16,   // 35% × 45mg
    ];

    public const LABEL = [
        'energi' => ['label' => 'Energi',      'satuan' => 'kkal', 'icon' => 'fa-fire'],
        'protein' => ['label' => 'Protein',     'satuan' => 'g',    'icon' => 'fa-drumstick-bite'],
        'lemak' => ['label' => 'Lemak',        'satuan' => 'g',    'icon' => 'fa-droplet'],
        'karbohidrat' => ['label' => 'Karbohidrat',  'satuan' => 'g',    'icon' => 'fa-wheat-awn'],
        'serat' => ['label' => 'Serat',        'satuan' => 'g',    'icon' => 'fa-leaf'],
        'kalsium' => ['label' => 'Kalsium',      'satuan' => 'mg',   'icon' => 'fa-bone'],
        'besi' => ['label' => 'Zat Besi',     'satuan' => 'mg',   'icon' => 'fa-circle-dot'],
        'vit_c' => ['label' => 'Vitamin C',    'satuan' => 'mg',   'icon' => 'fa-lemon'],
    ];

    public const PCT_PAGI = 0.225;

    public const PCT_SIANG = 0.325;

    public const KELOMPOK = [
        'TK_PAUD' => ['label' => 'TK / PAUD',
            'energi' => 1400, 'protein' => 25, 'lemak' => 50, 'karbohidrat' => 220],
        'SD_1_3' => ['label' => 'SD Kelas 1–3',
            'energi' => 1650, 'protein' => 40, 'lemak' => 55, 'karbohidrat' => 250],
        'SD_4_6' => ['label' => 'SD Kelas 4–6',
            'energi' => 1950, 'protein' => 52.5, 'lemak' => 65, 'karbohidrat' => 290],
        'SMP' => ['label' => 'SMP',
            'energi' => 2225, 'protein' => 67.5, 'lemak' => 75, 'karbohidrat' => 325],
        'SMA' => ['label' => 'SMA',
            'energi' => 2375, 'protein' => 70, 'lemak' => 77.5, 'karbohidrat' => 350],
        'BALITA_1_3' => ['label' => 'Anak Balita 1–3 Tahun',
            'energi' => 1350, 'protein' => 20, 'lemak' => 45, 'karbohidrat' => 215],
        'BALITA_4_6' => ['label' => 'Anak Balita 4–6 Tahun',
            'energi' => 1400, 'protein' => 25, 'lemak' => 50, 'karbohidrat' => 220],
        'HAMIL_T1' => ['label' => 'Ibu Hamil Trimester I',
            'energi' => 2330, 'protein' => 61, 'lemak' => 62.3, 'karbohidrat' => 365],
        'HAMIL_T2' => ['label' => 'Ibu Hamil Trimester II',
            'energi' => 2450, 'protein' => 70, 'lemak' => 62.3, 'karbohidrat' => 380],
        'HAMIL_T3' => ['label' => 'Ibu Hamil Trimester III',
            'energi' => 2450, 'protein' => 90, 'lemak' => 62.3, 'karbohidrat' => 380],
        'MENYUSUI_6BLN_1' => ['label' => 'Ibu Menyusui 6 Bln Pertama',
            'energi' => 2580, 'protein' => 80, 'lemak' => 67.2, 'karbohidrat' => 405],
        'MENYUSUI_6BLN_2' => ['label' => 'Ibu Menyusui 6 Bln Kedua',
            'energi' => 2650, 'protein' => 75, 'lemak' => 67.2, 'karbohidrat' => 415],
    ];

    public static function targetSajian(string $key, string $mealType = 'siang'): array
    {
        $pct = $mealType === 'pagi' ? self::PCT_PAGI : self::PCT_SIANG;
        $akg = self::KELOMPOK[$key] ?? self::KELOMPOK['SD_4_6'];

        return [
            'energi' => round($akg['energi'] * $pct, 1),
            'protein' => round($akg['protein'] * $pct, 1),
            'lemak' => round($akg['lemak'] * $pct, 1),
            'karbohidrat' => round($akg['karbohidrat'] * $pct, 1),
        ];
    }

    public static function cascadeOptions(): array
    {
        return [
            'Peserta Didik' => ['TK_PAUD', 'SD_1_3', 'SD_4_6', 'SMP', 'SMA'],
            'Ibu Hamil' => ['HAMIL_T1', 'HAMIL_T2', 'HAMIL_T3'],
            'Ibu Menyusui' => ['MENYUSUI_6BLN_1', 'MENYUSUI_6BLN_2'],
            'Anak Balita' => ['BALITA_1_3', 'BALITA_4_6'],
        ];
    }

    // Mapping kelompok_sasaran ke kelompok anggaran (balita_sd3 / sd4_ibu_menyusui)
    public static function toAnggaranKelompok(string $key): string
    {
        $balitaSd3 = ['TK_PAUD', 'SD_1_3', 'BALITA_1_3', 'BALITA_4_6'];

        return in_array($key, $balitaSd3) ? 'balita_sd3' : 'sd4_ibu_menyusui';
    }
}
