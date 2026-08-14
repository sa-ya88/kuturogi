<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class RoomImageStorageService
{
    /** @var list<string> */
    private const ALLOWED_EXTENSIONS = ['webp', 'png', 'jpeg', 'jpg'];

    public function imagesDirectory(): string
    {
        return public_path('images');
    }

    private function tempDirectory(): string
    {
        $directory = storage_path('app/room-image-tmp');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return $directory;
    }

    public function filename(int $roomId, int $sequence, string $extension): string
    {
        return sprintf('room_%d_%d.%s', $roomId, $sequence, $this->normalizeExtension($extension));
    }

    public function publicPath(string $filename): string
    {
        return '/images/'.$filename;
    }

    /**
     * @param  list<UploadedFile|TemporaryUploadedFile|string>  $items
     * @return list<string>
     */
    public function syncImages(Room $room, array $items): array
    {
        if (count($items) > 5) {
            throw new RuntimeException('画像は最大5枚までです。');
        }

        $roomId = $room->id;
        $directory = $this->imagesDirectory();

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $previousImages = $room->images ?? [];
        $tempFiles = [];

        foreach ($items as $index => $item) {
            $sequence = $index + 1;
            $extension = $this->resolveExtension($item);
            $this->assertAllowedExtension($extension);

            $finalName = $this->filename($roomId, $sequence, $extension);
            $tempPath = $this->tempDirectory().'/'.Str::uuid().'.'.$extension;
            $finalPath = $directory.'/'.$finalName;

            if ($item instanceof UploadedFile || $item instanceof TemporaryUploadedFile) {
                File::put($tempPath, $item->get());
            } else {
                $sourcePath = $this->resolveSourcePath($directory, (string) $item);

                if (! File::exists($sourcePath)) {
                    throw new RuntimeException("画像ファイルが見つかりません: {$item}");
                }

                if ($sourcePath === $finalPath) {
                    $tempFiles[] = [
                        'temp' => null,
                        'final' => $finalPath,
                        'public' => $this->publicPath($finalName),
                    ];

                    continue;
                }

                File::move($sourcePath, $tempPath);
            }

            $tempFiles[] = [
                'temp' => $tempPath,
                'final' => $finalPath,
                'public' => $this->publicPath($finalName),
            ];
        }

        $finalNames = array_map(
            fn (array $file): string => basename($file['final']),
            $tempFiles
        );

        $this->deleteRoomImageFiles($roomId, $finalNames);

        $publicPaths = [];

        foreach ($tempFiles as $file) {
            if ($file['temp'] === null) {
                $publicPaths[] = $file['public'];

                continue;
            }

            if (File::exists($file['final'])) {
                File::delete($file['final']);
            }

            File::move($file['temp'], $file['final']);
            $publicPaths[] = $file['public'];
        }

        foreach ($previousImages as $oldPath) {
            if (in_array($oldPath, $publicPaths, true)) {
                continue;
            }

            $oldFile = $directory.'/'.basename($oldPath);

            if (File::exists($oldFile)) {
                File::delete($oldFile);
            }
        }

        return $publicPaths;
    }

    public function deleteRoomImages(Room $room): void
    {
        $this->deleteRoomImageFiles($room->id);

        foreach ($room->images ?? [] as $path) {
            $file = $this->imagesDirectory().'/'.basename($path);

            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }

    /**
     * 既存の画像ファイル名を room_{id}_{n} 形式へ揃える。
     *
     * @return array{updated: int, skipped: list<string>}
     */
    public function normalizeExistingRoomImages(): array
    {
        $updated = 0;
        $skipped = [];
        $directory = $this->imagesDirectory();

        Room::query()->orderBy('id')->each(function (Room $room) use (&$updated, &$skipped, $directory): void {
            $images = $room->images ?? [];

            if ($images === []) {
                return;
            }

            $existingItems = [];

            foreach ($images as $path) {
                $basename = basename($path);
                $sourcePath = $directory.'/'.$basename;

                if (File::exists($sourcePath)) {
                    $existingItems[] = $basename;

                    continue;
                }

                $skipped[] = "客室 #{$room->id} ({$room->name}): {$path}";
            }

            if ($existingItems === []) {
                $room->update(['images' => []]);
                $updated++;

                return;
            }

            $normalized = $this->syncImages($room, $existingItems);

            if ($normalized !== $images) {
                $room->update(['images' => $normalized]);
                $updated++;
            }
        });

        return [
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  list<string>  $keepFilenames
     */
    private function deleteRoomImageFiles(int $roomId, array $keepFilenames = []): void
    {
        $directory = $this->imagesDirectory();

        if (! File::isDirectory($directory)) {
            return;
        }

        foreach (File::glob($directory.'/room_'.$roomId.'_*') ?: [] as $path) {
            $basename = basename($path);

            if (! in_array($basename, $keepFilenames, true)) {
                File::delete($path);
            }
        }

        $this->cleanupLegacyTempFilesInImagesDirectory($roomId);
    }

    private function cleanupLegacyTempFilesInImagesDirectory(int $roomId): void
    {
        $directory = $this->imagesDirectory();

        foreach (File::glob($directory.'/room_'.$roomId.'_*_tmp_*') ?: [] as $path) {
            File::delete($path);
        }
    }

    private function resolveSourcePath(string $directory, string $item): string
    {
        $basename = $this->normalizeBasename($item);

        return $directory.'/'.$basename;
    }

    private function normalizeBasename(string $item): string
    {
        $basename = basename($item);

        return ltrim(str_replace('/images/', '', $basename), '/');
    }

    private function resolveExtension(UploadedFile|TemporaryUploadedFile|string $item): string
    {
        if ($item instanceof UploadedFile || $item instanceof TemporaryUploadedFile) {
            $extension = $item->getClientOriginalExtension()
                ?: $item->extension()
                ?: 'webp';

            return $this->normalizeExtension($extension);
        }

        $extension = pathinfo($this->normalizeBasename($item), PATHINFO_EXTENSION);

        if ($extension === '') {
            throw new RuntimeException("画像の拡張子を判定できません: {$item}");
        }

        return $this->normalizeExtension($extension);
    }

    private function normalizeExtension(string $extension): string
    {
        return strtolower(ltrim($extension, '.'));
    }

    private function assertAllowedExtension(string $extension): void
    {
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('webp / png / jpeg のみアップロードできます。');
        }
    }
}
