<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsListFieldTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        $values = [];

        foreach (Setting::schemaFields() as $key => $field) {
            if ($field['type'] !== 'image') {
                $values[$key] = (string) ($field['default'] ?? '');
            }
        }

        return array_merge($values, $overrides);
    }

    public function test_one_point_per_line_keeps_every_bangla_letter_intact(): void
    {
        $admin = User::factory()->create();

        // অ is the bytes E0 A6 85, and 0x85 is also what a byte-wise "new line" pattern matches.
        $this->actingAs($admin)
            ->post(route('admin.settings.update'), $this->payload(['hero_points' => "অগ্নিনিরোধক রঙ\r\n  অতিরিক্ত মজবুত  \n\nঅর্ডারে ক্যাশ অন ডেলিভারি"]))
            ->assertSessionHasNoErrors();

        $stored = (string) Setting::get('hero_points');

        $this->assertTrue(mb_check_encoding($stored, 'UTF-8'), 'the saved text must stay valid UTF-8');
        $this->assertSame("অগ্নিনিরোধক রঙ\nঅতিরিক্ত মজবুত\nঅর্ডারে ক্যাশ অন ডেলিভারি", $stored);
    }

    public function test_the_default_settings_can_be_saved_unchanged(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertTrue(mb_check_encoding((string) Setting::get('hero_points'), 'UTF-8'));
    }
}
