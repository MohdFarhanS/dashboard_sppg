<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportTkpiValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_dengan_ekstensi_csv_tapi_konten_bukan_csv_ditolak(): void
    {
        $ketua = User::factory()->ketuaSppg()->create();

        $fakeFile = UploadedFile::fake()->create('malicious.csv', 10, 'application/pdf');

        $response = $this->actingAs($ketua)
            ->post(route('import-tkpi.preview'), ['csv_file' => $fakeFile]);

        $response->assertSessionHasErrors('csv_file');
    }

    public function test_upload_csv_valid_lolos_validasi_mimes(): void
    {
        $ketua = User::factory()->ketuaSppg()->create();

        $fakeFile = UploadedFile::fake()->createWithContent(
            'bahan.csv',
            "nama_bahan,kategori\nBeras Putih,Serealia\n"
        );

        $response = $this->actingAs($ketua)
            ->post(route('import-tkpi.preview'), ['csv_file' => $fakeFile]);

        $response->assertSessionDoesntHaveErrors('csv_file');
    }

    // C4: nama file temp harus unik per preview supaya dua preview bersamaan
    // (mis. dua tab dengan user yang sama) tidak saling menimpa.
    public function test_dua_preview_bersamaan_pakai_file_temp_berbeda(): void
    {
        $ketua = User::factory()->ketuaSppg()->create();

        $file1 = UploadedFile::fake()->createWithContent(
            'a.csv', "nama_bahan,kategori\nBeras Putih,Serealia\n"
        );
        $file2 = UploadedFile::fake()->createWithContent(
            'b.csv', "nama_bahan,kategori\nTelur Ayam,Lauk\n"
        );

        // Preview pertama.
        $this->actingAs($ketua)
            ->post(route('import-tkpi.preview'), ['csv_file' => $file1])
            ->assertSessionHas('import_tmp');
        $tmp1 = session('import_tmp');

        // Simulasikan tab/sesi terpisah: buang session lama supaya preview kedua
        // tidak menganggap $tmp1 sebagai miliknya (dan tidak menghapusnya).
        $this->flushSession();

        $this->actingAs($ketua)
            ->post(route('import-tkpi.preview'), ['csv_file' => $file2])
            ->assertSessionHas('import_tmp');
        $tmp2 = session('import_tmp');

        $this->assertNotNull($tmp1);
        $this->assertNotNull($tmp2);
        $this->assertNotSame($tmp1, $tmp2, 'File temp dua preview harus berbeda.');
        $this->assertFileExists($tmp1);
        $this->assertFileExists($tmp2);

        @unlink($tmp1);
        @unlink($tmp2);
    }

    // C4: preview baru pada session yang sama membuang file temp preview lama
    // (cegah orphan menumpuk di storage).
    public function test_preview_baru_menghapus_file_temp_preview_lama(): void
    {
        $ketua = User::factory()->ketuaSppg()->create();

        $file1 = UploadedFile::fake()->createWithContent(
            'a.csv', "nama_bahan,kategori\nBeras Putih,Serealia\n"
        );

        $this->actingAs($ketua)
            ->post(route('import-tkpi.preview'), ['csv_file' => $file1])
            ->assertSessionHas('import_tmp');
        $tmp1 = session('import_tmp');

        $this->assertFileExists($tmp1);

        $file2 = UploadedFile::fake()->createWithContent(
            'b.csv', "nama_bahan,kategori\nTelur Ayam,Lauk\n"
        );

        // Session masih membawa import_tmp lama → preview kedua harus menghapusnya.
        $this->withSession(['import_tmp' => $tmp1])->actingAs($ketua)
            ->post(route('import-tkpi.preview'), ['csv_file' => $file2])
            ->assertSessionHas('import_tmp');
        $tmp2 = session('import_tmp');

        $this->assertNotSame($tmp1, $tmp2);
        $this->assertFileDoesNotExist($tmp1);
        $this->assertFileExists($tmp2);

        @unlink($tmp2);
    }

    // C7: header CSV dengan karakter multibyte (mis. "µg", BOM UTF-8) tidak boleh
    // korup jadi kosong/hilang — hanya BOM & karakter kontrol ASCII yang dibuang.
    public function test_header_csv_multibyte_dan_bom_tidak_korup(): void
    {
        $ketua = User::factory()->ketuaSppg()->create();

        $bom = "\xEF\xBB\xBF";
        $content = $bom."nama_bahan,kategori (µg),catatan é\nBeras Putih,Serealia,ok\n";
        $file = UploadedFile::fake()->createWithContent('bahan.csv', $content);

        $response = $this->actingAs($ketua)
            ->post(route('import-tkpi.preview'), ['csv_file' => $file]);

        $response->assertSessionDoesntHaveErrors('csv_file');
        $headers = session('import_headers');

        $this->assertSame('nama_bahan', $headers[0]);
        $this->assertStringContainsString('µg', $headers[1]);
        $this->assertStringContainsString('é', $headers[2]);

        @unlink(session('import_tmp'));
    }
}
