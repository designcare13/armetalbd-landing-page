<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use RuntimeException;

abstract class Controller
{
    /** Row counts an admin can pick for a list; the first is the default. */
    public const PER_PAGE_OPTIONS = [15, 30, 50, 100];

    /**
     * Rows per page for an admin list, taken from ?per_page= when it is one of the allowed counts.
     */
    protected function perPage(Request $request): int
    {
        $requested = (int) $request->query('per_page');

        return in_array($requested, self::PER_PAGE_OPTIONS, true) ? $requested : self::PER_PAGE_OPTIONS[0];
    }

    /**
     * Store an uploaded image on the uploads disk and return its relative path.
     *
     * When $maxSide is given and the photo is larger, it is scaled down so its longer side
     * equals $maxSide. This keeps phone photos light without asking the admin to resize them.
     *
     * When the server cannot write the file (folder permissions, full disk) the admin gets an error
     * on $field instead of a blank server error page.
     *
     * @throws ValidationException
     */
    protected function storeImage(UploadedFile $file, string $directory, ?int $maxSide = null, string $field = 'image'): string
    {
        $disk = config('landing.image_disk');
        $resized = $maxSide !== null ? $this->downscaledImage($file, $maxSide) : null;

        $failure = null;

        try {
            if ($resized === null) {
                $stored = $file->store($directory, $disk);
            } else {
                $stored = $directory.'/'.Str::random(40).'.'.$resized['extension'];
                $stored = Storage::disk($disk)->put($stored, $resized['contents'], 'public') ? $stored : false;
            }
        } catch (FilesystemException $exception) {
            $stored = false;
            $failure = $exception;
        }

        if (! is_string($stored) || $stored === '') {
            report($failure ?? new RuntimeException("Could not write the uploaded image to the '{$disk}' disk, folder '{$directory}'. Check that public/uploads/{$directory} exists and is writable."));

            throw ValidationException::withMessages([
                $field => 'ছবিটি সার্ভারে সেভ করা যায়নি। ছবির ফোল্ডারের অনুমতি বা সার্ভারের জায়গা পরীক্ষা করুন।',
            ]);
        }

        return $stored;
    }

    /**
     * Return the scaled-down copy of the photo, or null when it should be stored untouched:
     * already small enough, GD is missing, memory is short, or the rotation cannot be preserved.
     *
     * @return array{contents: string, extension: string}|null
     */
    private function downscaledImage(UploadedFile $file, int $maxSide): ?array
    {
        $path = $file->getRealPath();
        $info = $path ? @getimagesize($path) : false;

        if (! extension_loaded('gd') || $info === false || max($info[0], $info[1]) <= $maxSide) {
            return null;
        }

        // GD holds the decoded photo plus a smaller copy in memory; skip when that would not fit.
        if (! $this->hasMemoryFor($info[0] * $info[1] * 5)) {
            return null;
        }

        $type = $info[2];
        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => false,
        };

        if ($source === false) {
            return null;
        }

        if ($type === IMAGETYPE_JPEG) {
            // Phones store the rotation in EXIF data, which is lost on re-encoding, so apply it first.
            // Without EXIF support the rotation is unknown, so the original is kept.
            if (! function_exists('exif_read_data')) {
                return null;
            }

            $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);

            if (in_array($orientation, [2, 4, 5, 7], true)) {
                return null; // mirrored photos are rare; keep the original rather than risk a wrong result
            }

            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

            if ($angle !== 0) {
                $source = imagerotate($source, $angle, 0);
            }
        }

        imagealphablending($source, true);
        imagesavealpha($source, true);

        $ratio = $maxSide / max(imagesx($source), imagesy($source));
        $scaled = imagescale($source, (int) round(imagesx($source) * $ratio), (int) round(imagesy($source) * $ratio), IMG_BICUBIC);

        if ($scaled === false) {
            return null;
        }

        imagesavealpha($scaled, true);

        ob_start();
        $extension = match ($type) {
            IMAGETYPE_PNG => (imagepng($scaled, null, 6) ? 'png' : null),
            IMAGETYPE_WEBP => (imagewebp($scaled, null, 85) ? 'webp' : null),
            default => (imagejpeg($scaled, null, 85) ? 'jpg' : null),
        };
        $contents = (string) ob_get_clean();

        // Never replace the upload with something heavier than what was sent.
        if ($extension === null || $contents === '' || strlen($contents) >= $file->getSize()) {
            return null;
        }

        return ['contents' => $contents, 'extension' => $extension];
    }

    private function hasMemoryFor(int $bytes): bool
    {
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));

        return $limit < 0 || $bytes < $limit - memory_get_usage(true);
    }
}
