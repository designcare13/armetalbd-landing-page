<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VideoSectionTest extends TestCase
{
    use RefreshDatabase;

    private const ID = 'BqT6m1pDlmo';

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');
    }

    /**
     * @return array<string, string>
     */
    private function settingsPayload(array $overrides = []): array
    {
        $values = [];

        foreach (Setting::schemaFields() as $key => $field) {
            if ($field['type'] !== 'image') {
                $values[$key] = (string) ($field['default'] ?? '');
            }
        }

        return array_merge($values, $overrides);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function youtubeLinks(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=BqT6m1pDlmo'],
            'watch with extra parts' => ['https://www.youtube.com/watch?feature=share&v=BqT6m1pDlmo&t=12s'],
            'mobile' => ['https://m.youtube.com/watch?v=BqT6m1pDlmo'],
            'short link' => ['https://youtu.be/BqT6m1pDlmo?si=abc'],
            'embed' => ['https://www.youtube.com/embed/BqT6m1pDlmo'],
            'shorts' => ['https://www.youtube.com/shorts/BqT6m1pDlmo'],
            'live' => ['https://www.youtube.com/live/BqT6m1pDlmo'],
            'no www' => ['http://youtube.com/watch?v=BqT6m1pDlmo'],
        ];
    }

    #[DataProvider('youtubeLinks')]
    public function test_every_common_youtube_link_gives_the_video_id(string $link): void
    {
        $this->assertSame(self::ID, Video::youtubeId($link));
    }

    public function test_other_links_and_broken_ids_are_not_accepted(): void
    {
        foreach ([
            '',
            null,
            'https://vimeo.com/123456789',
            'https://www.youtube.com/watch?v=short',
            'https://www.youtube.com/watch?v=BqT6m1pDlmoTOOLONG',
            'https://evil.example/https://www.youtube.com/watch?v=BqT6m1pDlmo',
            'javascript:alert(1)//youtube.com/watch?v=BqT6m1pDlmo',
            'https://www.youtube.com.evil.example/watch?v=BqT6m1pDlmo',
            'BqT6m1pDlmo',
        ] as $link) {
            $this->assertNull(Video::youtubeId($link), (string) $link);
        }
    }

    public function test_landing_page_shows_the_video_cover_right_above_the_order_form(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="videoSection"', $html);
        $this->assertStringContainsString('data-embed-url="https://www.youtube-nocookie.com/embed/'.self::ID.'?autoplay=1', $html);
        $this->assertStringContainsString('https://i.ytimg.com/vi/'.self::ID.'/maxresdefault.jpg', $html);
        $this->assertStringContainsString('ভিডিওতে দেখুন প্রোডাক্টের আসল মান', $html);
        $this->assertLessThan(strpos($html, 'id="checkoutSection"'), strpos($html, 'id="videoSection"'), 'video comes before the order form');
        $this->assertGreaterThan(strpos($html, 'spotlight-section'), strpos($html, 'id="videoSection"'), 'video comes after the spotlight');
    }

    public function test_the_player_itself_is_not_in_the_page_until_the_visitor_taps_the_cover(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/<iframe[^>]*youtube/i', $html);
    }

    public function test_clearing_the_link_hides_the_whole_section(): void
    {
        Setting::set('video_url', '');

        $this->get('/')->assertOk()->assertDontSee('id="videoSection"', false)->assertDontSee('VideoObject', false);
    }

    public function test_a_link_that_is_not_youtube_hides_the_section_instead_of_breaking_the_page(): void
    {
        Setting::set('video_url', 'https://example.com/clip.mp4');

        $this->get('/')->assertOk()->assertDontSee('id="videoSection"', false);
    }

    public function test_a_cover_picture_chosen_by_the_admin_replaces_the_youtube_one(): void
    {
        Setting::set('video_thumb', 'site/my-cover.jpg');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('/uploads/site/my-cover.jpg', $html);
        $this->assertStringNotContainsString('maxresdefault', $html);
    }

    public function test_search_engines_get_the_video_with_its_upload_date(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);
        $graph = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR)['@graph'];
        $video = collect($graph)->firstWhere('@type', 'VideoObject');

        $this->assertNotNull($video);
        $this->assertSame('2025-09-13', $video['uploadDate']);
        $this->assertSame('https://www.youtube.com/watch?v='.self::ID, $video['contentUrl']);
        $this->assertSame('https://www.youtube.com/embed/'.self::ID, $video['embedUrl']);
        $this->assertSame(['https://i.ytimg.com/vi/'.self::ID.'/hqdefault.jpg'], $video['thumbnailUrl']);
    }

    public function test_without_an_upload_date_the_video_is_left_out_of_the_markup_but_still_shown(): void
    {
        Setting::set('video_upload_date', '');

        $this->get('/')->assertOk()->assertSee('id="videoSection"', false)->assertDontSee('VideoObject', false);
    }

    public function test_admin_can_change_the_video_and_its_texts(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.settings.update'), $this->settingsPayload([
            'video_url' => 'https://youtu.be/AAAAAAAAAAA',
            'video_title' => 'আমাদের কারখানা',
            'video_upload_date' => '2026-01-05',
        ]))->assertSessionHasNoErrors();

        $this->get('/')->assertOk()
            ->assertSee('আমাদের কারখানা')
            ->assertSee('youtube-nocookie.com/embed/AAAAAAAAAAA', false)
            ->assertSee('"uploadDate":"2026-01-05"', false);
    }

    public function test_admin_cannot_save_a_link_that_is_not_youtube_or_a_future_upload_date(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.settings.update'), $this->settingsPayload(['video_url' => 'https://vimeo.com/123456789']))
            ->assertSessionHasErrors('video_url');

        $this->actingAs($admin)->post(route('admin.settings.update'), $this->settingsPayload(['video_upload_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('video_upload_date');

        $this->actingAs($admin)->post(route('admin.settings.update'), $this->settingsPayload(['video_upload_date' => '13/09/2025']))
            ->assertSessionHasErrors('video_upload_date');
    }

    public function test_admin_can_clear_the_link_to_hide_the_section(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.settings.update'), $this->settingsPayload(['video_url' => '']))
            ->assertSessionHasNoErrors();

        $this->get('/')->assertOk()->assertDontSee('id="videoSection"', false);
    }

    public function test_admin_can_upload_a_cover_picture_and_the_settings_page_has_no_broken_preview_before_that(): void
    {
        Storage::fake('uploads');
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('id="preview_video_thumb"', false)
            ->assertSee('padding: 4px; display: none;', false);

        $this->actingAs($admin)->post(route('admin.settings.update'), $this->settingsPayload() + ['video_thumb' => UploadedFile::fake()->image('cover.jpg', 1280, 720)])
            ->assertSessionHasNoErrors();

        $stored = (string) Setting::get('video_thumb');
        $this->assertStringStartsWith('site/', $stored);
        Storage::disk('uploads')->assertExists($stored);
    }

    public function test_hero_points_with_the_letter_o_of_bangla_are_not_cut_in_the_middle(): void
    {
        // অ is the bytes E0 A6 85; a line splitter that works on bytes treats 0x85 as a line end.
        Setting::set('hero_points', "ক্যাশ অন ডেলিভারি সুবিধা\nঅতিরিক্ত মজবুত ফ্রেম");

        $response = $this->get('/')->assertOk();

        $response->assertSee('ক্যাশ অন ডেলিভারি সুবিধা')->assertSee('অতিরিক্ত মজবুত ফ্রেম');
        $this->assertTrue(mb_check_encoding($response->getContent(), 'UTF-8'), 'the page must be valid UTF-8');
    }
}
