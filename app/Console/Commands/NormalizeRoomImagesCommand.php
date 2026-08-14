<?php

namespace App\Console\Commands;

use App\Services\RoomImageStorageService;
use Illuminate\Console\Command;

class NormalizeRoomImagesCommand extends Command
{
    protected $signature = 'rooms:normalize-images';

    protected $description = '客室画像ファイル名を room_{id}_{n} 形式へ揃える';

    public function handle(RoomImageStorageService $storage): int
    {
        $result = $storage->normalizeExistingRoomImages();

        $this->info("客室画像を {$result['updated']} 件更新しました。");

        if ($result['skipped'] !== []) {
            $this->warn('以下の画像ファイルは見つからなかったためスキップしました:');

            foreach ($result['skipped'] as $message) {
                $this->line("  - {$message}");
            }
        }

        return self::SUCCESS;
    }
}
