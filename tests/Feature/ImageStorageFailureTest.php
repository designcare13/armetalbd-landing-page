<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageStorageFailureTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        // A plain file where a folder should be: the upload folder can never be created, like one the server may not write to.
        $this->root = sys_get_temp_dir().'/unwritable-uploads-'.uniqid();
        mkdir($this->root);
        file_put_contents($this->root.'/site', 'not a folder');
        file_put_contents($this->root.'/products', 'not a folder');
        config([
            'filesystems.disks.unwritable' => ['driver' => 'local', 'root' => $this->root, 'throw' => false, 'report' => false],
            'landing.image_disk' => 'unwritable',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->root.'/site');
        @unlink($this->root.'/products');
        @rmdir($this->root);

        parent::tearDown();
    }

    /**
     * @return array<string, string>
     */
    private function settingsPayload(): array
    {
        $values = [];

        foreach (Setting::schemaFields() as $key => $field) {
            if ($field['type'] !== 'image') {
                $values[$key] = (string) ($field['default'] ?? '');
            }
        }

        return $values;
    }

    public function test_a_site_image_that_cannot_be_written_shows_an_error_instead_of_a_server_error(): void
    {
        $admin = User::factory()->create();
        $payload = $this->settingsPayload();
        $payload['site_title'] = 'নতুন টাইটেল যা সেভ হবে না';
        $payload['meta_image'] = UploadedFile::fake()->image('share.png', 800, 600);

        $this->actingAs($admin)->post(route('admin.settings.update'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors(['meta_image' => 'ছবিটি সার্ভারে সেভ করা যায়নি। ছবির ফোল্ডারের অনুমতি বা সার্ভারের জায়গা পরীক্ষা করুন।']);

        $this->assertNull(Setting::get('site_title'), 'nothing is saved when the image could not be stored');
        $this->assertNull(Setting::get('meta_image'));
    }

    public function test_the_error_is_shown_on_the_settings_page_after_the_redirect(): void
    {
        $admin = User::factory()->create();
        $payload = $this->settingsPayload() + ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)];

        $this->actingAs($admin)->from(route('admin.settings.index'))->followingRedirects()->post(route('admin.settings.update'), $payload)
            ->assertOk()
            ->assertSee('ছবিটি সার্ভারে সেভ করা যায়নি');
    }

    public function test_a_product_photo_that_cannot_be_written_shows_an_error_instead_of_a_server_error(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'টেস্ট',
            'price' => 5000,
            'stock' => 10,
            'rating' => 4.5,
            'reviews_count' => 3,
            'image' => UploadedFile::fake()->image('photo.jpg', 900, 900),
        ])->assertSessionHasErrors('image');

        $this->assertSame(0, Product::count());
    }
}
