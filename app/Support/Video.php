<?php

namespace App\Support;

/**
 * Reads the video link an admin pastes into the settings. Only YouTube links are supported.
 */
final class Video
{
    /** Watch, short, embed, shorts and live links of YouTube; the first group is the 11 character video id. */
    public const YOUTUBE_PATTERN = '~^https?://(?:(?:www|m)\.)?(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})(?![A-Za-z0-9_-])~';

    public static function youtubeId(?string $url): ?string
    {
        return preg_match(self::YOUTUBE_PATTERN, trim((string) $url), $match) === 1 ? $match[1] : null;
    }

    public static function watchUrl(string $id): string
    {
        return 'https://www.youtube.com/watch?v='.$id;
    }

    /**
     * Player address that starts playing at once. It is only loaded after the visitor taps the cover,
     * and the privacy friendly domain does not set YouTube cookies before that.
     */
    public static function embedUrl(string $id): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$id.'?autoplay=1&rel=0&playsinline=1&modestbranding=1';
    }

    /**
     * YouTube's own cover picture. The "maxres" one is missing for some videos, "hq" always exists.
     */
    public static function thumbnailUrl(string $id, bool $highest = true): string
    {
        return 'https://i.ytimg.com/vi/'.$id.'/'.($highest ? 'maxresdefault' : 'hqdefault').'.jpg';
    }
}
