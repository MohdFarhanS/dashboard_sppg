<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulanValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * B9 regression: raw `bulan` input previously reached explode('-', $bulan)
     * / Carbon::createFromFormat('Y-m') unvalidated, causing a 500 on malformed
     * input instead of a clean 422 validation error.
     */
    public function test_dashboard_rejects_invalid_bulan_format(): void
    {
        $user = User::factory()->ketuaSppg()->create();

        $response = $this->actingAs($user)->get('/dashboard?bulan=foo');

        $response->assertSessionHasErrors('bulan');
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    public function test_biaya_dashboard_rejects_invalid_bulan_format(): void
    {
        $user = User::factory()->akuntan()->create();

        $response = $this->actingAs($user)->get('/biaya/dashboard?bulan=2026-13');

        $response->assertSessionHasErrors('bulan');
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    public function test_budget_alert_rejects_invalid_bulan_format(): void
    {
        $user = User::factory()->ketuaSppg()->create();

        $response = $this->actingAs($user)->get('/budget-alert?bulan=not-a-month');

        $response->assertSessionHasErrors('bulan');
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    public function test_laporan_rejects_invalid_bulan_format(): void
    {
        $user = User::factory()->ketuaSppg()->create();

        $response = $this->actingAs($user)->get('/laporan?bulan=2026/07');

        $response->assertSessionHasErrors('bulan');
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    public function test_dashboard_accepts_valid_bulan_format(): void
    {
        $user = User::factory()->ketuaSppg()->create();

        $response = $this->actingAs($user)->get('/dashboard?bulan=2026-07');

        $response->assertOk();
    }

    /**
     * Laravel's validator skips non-implicit rules (incl. date_format) when the
     * value is an empty string, so `bulan=` (present but empty) passes validation.
     * Controllers must not then feed that empty string to Carbon/explode — they
     * must fall back to the default month instead.
     */
    public function test_dashboard_falls_back_to_default_when_bulan_is_empty_string(): void
    {
        $user = User::factory()->ketuaSppg()->create();

        $response = $this->actingAs($user)->get('/dashboard?bulan=');

        $response->assertOk();
    }

    public function test_biaya_dashboard_falls_back_to_default_when_bulan_is_empty_string(): void
    {
        $user = User::factory()->akuntan()->create();

        $response = $this->actingAs($user)->get('/biaya/dashboard?bulan=');

        $response->assertOk();
    }

    public function test_budget_alert_falls_back_to_default_when_bulan_is_empty_string(): void
    {
        $user = User::factory()->ketuaSppg()->create();

        $response = $this->actingAs($user)->get('/budget-alert?bulan=');

        $response->assertOk();
    }

    public function test_laporan_export_pdf_falls_back_to_default_when_bulan_is_empty_string(): void
    {
        $user = User::factory()->ketuaSppg()->create();

        $response = $this->actingAs($user)->get('/laporan/export-pdf?bulan=');

        $response->assertOk();
    }
}
