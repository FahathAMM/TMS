<?php

namespace App\Console\Commands;

use App\Models\Inventory\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SeedProductImages extends Command
{
    protected $signature   = 'products:seed-images {--force : Overwrite existing images}';
    protected $description = 'Generate branded placeholder images for all products';

    private array $brandColors = [
        'Apple'   => [28,  28,  30],
        'Samsung' => [20,  40, 180],
        'Xiaomi'  => [255, 103,  0],
        'OnePlus' => [220,  20,  40],
        'Oppo'    => [0,  120, 200],
    ];

    private array $categoryBg = [
        'Smartphones' => [10,  10,  20],
        'Tablets'     => [8,   25,  45],
        'Earphones'   => [22,  8,   40],
        'Chargers'    => [5,   30,  18],
        'Accessories' => [28,  18,   8],
    ];

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('GD extension is not available.');
            return 1;
        }

        $font = 'C:/Windows/Fonts/arial.ttf';
        if (! file_exists($font)) {
            $font = null;
            $this->warn('Arial not found — falling back to built-in GD font.');
        }

        $products = Product::with(['category', 'brand'])->get();
        $bar      = $this->output->createProgressBar($products->count());
        $bar->start();

        $generated = 0;
        foreach ($products as $product) {
            if (! $this->option('force') && $product->image) {
                $bar->advance();
                continue;
            }

            $path = $this->makeImage($product, $font);
            if ($path) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $product->updateQuietly(['image' => $path]);
                $generated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Generated {$generated} images.");

        return 0;
    }

    private function makeImage(Product $product, ?string $font): ?string
    {
        $w = 400;
        $h = 400;

        $im = imagecreatetruecolor($w, $h);

        $cat   = $product->category?->name ?? 'Accessories';
        $brand = $product->brand?->name ?? '';

        [$bgR, $bgG, $bgB] = $this->categoryBg[$cat] ?? [20, 20, 30];
        [$acR, $acG, $acB] = $this->brandColors[$brand] ?? $this->accentFromCategory($cat);

        // Vertical gradient background
        for ($y = 0; $y < $h; $y++) {
            $frac = $y / $h;
            $r    = (int) min(255, $bgR + ($bgR + 35) * $frac);
            $g    = (int) min(255, $bgG + ($bgG + 35) * $frac);
            $b    = (int) min(255, $bgB + ($bgB + 35) * $frac);
            $line = imagecolorallocate($im, $r, $g, $b);
            imageline($im, 0, $y, $w, $y, $line);
        }

        $barH  = 72;
        $acRgb = imagecolorallocate($im, $acR, $acG, $acB);

        // Accent bar at bottom
        imagefilledrectangle($im, 0, $h - $barH, $w, $h, $acRgb);

        // Diagonal highlight on accent bar
        $light = imagecolorallocate($im, min(255, $acR + 50), min(255, $acG + 50), min(255, $acB + 50));
        for ($i = 0; $i < 3; $i++) {
            imageline($im, $i * 2, $h - $barH, $w / 3 + $i * 2, $h, $light);
        }

        // Category icon in upper area
        $iconDim = imagecolorallocatealpha($im, $acR, $acG, $acB, 55);
        $this->drawIcon($im, $cat, $acRgb, $iconDim, $w, $h - $barH);

        $white    = imagecolorallocate($im, 255, 255, 255);
        $subWhite = imagecolorallocate($im, 185, 185, 200);

        if ($font) {
            // Category label top-left
            imagettftext($im, 9, 0, 18, 32, $subWhite, $font, strtoupper($cat));

            // Brand name top-right (if any)
            if ($brand) {
                [$bw] = $this->textSize($font, 9, $brand);
                imagettftext($im, 9, 0, $w - $bw - 18, 32, $subWhite, $font, $brand);
            }

            // Product name centred in accent bar
            $lines  = $this->wrap($product->name, $font, 13, $w - 32);
            $lineH  = 20;
            $totalH = count($lines) * $lineH;
            $startY = (int)($h - $barH + ($barH - $totalH) / 2 + 14);

            foreach ($lines as $i => $line) {
                [$lw] = $this->textSize($font, 13, $line);
                $lx   = (int)(($w - $lw) / 2);
                imagettftext($im, 13, 0, $lx, $startY + $i * $lineH, $white, $font, $line);
            }
        } else {
            $lines  = $this->wrapSimple($product->name, 22);
            $startY = $h - $barH + 12;
            foreach ($lines as $i => $line) {
                $fw = imagefontwidth(4) * strlen($line);
                imagestring($im, 4, (int)(($w - $fw) / 2), $startY + $i * 18, $line, $white);
            }
        }

        // Save as JPEG
        $dir = storage_path('app/public/products');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'products/' . uniqid('img_', true) . '.jpg';
        $fullPath = storage_path('app/public/' . $filename);
        imagejpeg($im, $fullPath, 92);
        imagedestroy($im);

        return $filename;
    }

    private function drawIcon($im, string $cat, $solid, $dim, int $w, int $areaH): void
    {
        $cx = (int)($w / 2);
        $cy = (int)($areaH / 2);

        switch (true) {
            case str_contains($cat, 'Smart') || str_contains($cat, 'Phone'):
                // Phone body
                $pw = 72; $ph = 124; $r = 12;
                $this->roundRect($im, $cx - $pw / 2, $cy - $ph / 2, $pw, $ph, $r, $dim);
                // Camera notch
                imagefilledellipse($im, $cx, (int)($cy - $ph / 2 + 13), 10, 10, $solid);
                // Home bar
                imagefilledrectangle($im, $cx - 20, (int)($cy + $ph / 2 - 15), $cx + 20, (int)($cy + $ph / 2 - 9), $solid);
                break;

            case str_contains($cat, 'Tablet'):
                $tw = 108; $th = 140; $r = 10;
                $this->roundRect($im, $cx - $tw / 2, $cy - $th / 2, $tw, $th, $r, $dim);
                imagefilledellipse($im, $cx, (int)($cy + $th / 2 - 12), 10, 10, $solid);
                break;

            case str_contains($cat, 'Ear'):
                // Left earbud
                imagefilledellipse($im, $cx - 32, $cy - 8, 50, 50, $dim);
                imagefilledellipse($im, $cx - 32, $cy - 8, 22, 22, $solid);
                imagefilledrectangle($im, $cx - 36, $cy + 18, $cx - 28, $cy + 44, $solid);
                // Right earbud
                imagefilledellipse($im, $cx + 32, $cy - 8, 50, 50, $dim);
                imagefilledellipse($im, $cx + 32, $cy - 8, 22, 22, $solid);
                imagefilledrectangle($im, $cx + 28, $cy + 18, $cx + 36, $cy + 44, $solid);
                // Case arc hint
                imagearc($im, $cx, $cy + 20, 80, 40, 0, 180, $solid);
                break;

            case str_contains($cat, 'Charg'):
                // Plug body
                $this->roundRect($im, $cx - 24, $cy - 38, 48, 60, 6, $dim);
                // Prongs
                imagefilledrectangle($im, $cx - 16, $cy - 58, $cx - 8,  $cy - 38, $solid);
                imagefilledrectangle($im, $cx + 8,  $cy - 58, $cx + 16, $cy - 38, $solid);
                // Cable
                imagefilledrectangle($im, $cx - 6,  $cy + 22, $cx + 6,  $cy + 48, $solid);
                break;

            default:
                // Box / package
                $this->roundRect($im, $cx - 44, $cy - 44, 88, 88, 8, $dim);
                imageline($im, $cx - 44, $cy, $cx + 44, $cy, $solid);
                imageline($im, $cx, $cy - 44, $cx, $cy + 44, $solid);
                break;
        }
    }

    private function roundRect($im, $x, $y, $w, $h, $r, $color): void
    {
        $x = (int)$x; $y = (int)$y; $w = (int)$w; $h = (int)$h; $r = (int)$r;
        imagefilledrectangle($im, $x + $r,     $y,         $x + $w - $r, $y + $h,     $color);
        imagefilledrectangle($im, $x,           $y + $r,    $x + $w,      $y + $h - $r, $color);
        imagefilledellipse($im, $x + $r,       $y + $r,       $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x + $w - $r,  $y + $r,       $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x + $r,       $y + $h - $r,  $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x + $w - $r,  $y + $h - $r,  $r * 2, $r * 2, $color);
    }

    private function wrap(string $text, string $font, int $size, int $maxPx): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $line  = '';

        foreach ($words as $word) {
            $test  = $line ? "$line $word" : $word;
            [$tw]  = $this->textSize($font, $size, $test);
            if ($tw > $maxPx && $line) {
                $lines[] = $line;
                $line    = $word;
            } else {
                $line = $test;
            }
        }

        if ($line) {
            $lines[] = $line;
        }

        return array_slice($lines, 0, 3);
    }

    private function textSize(string $font, int $size, string $text): array
    {
        $box = imagettfbbox($size, 0, $font, $text);
        return [abs($box[4] - $box[0]), abs($box[5] - $box[1])];
    }

    private function wrapSimple(string $text, int $maxChars): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $line  = '';
        foreach ($words as $word) {
            if (strlen("$line $word") > $maxChars && $line) {
                $lines[] = $line;
                $line    = $word;
            } else {
                $line = $line ? "$line $word" : $word;
            }
        }
        if ($line) {
            $lines[] = $line;
        }
        return array_slice($lines, 0, 2);
    }

    private function accentFromCategory(string $cat): array
    {
        return match(true) {
            str_contains($cat, 'Smart') || str_contains($cat, 'Phone') => [50, 100, 220],
            str_contains($cat, 'Tablet')  => [0, 160, 190],
            str_contains($cat, 'Ear')     => [140, 40, 220],
            str_contains($cat, 'Charg')   => [0, 170,  80],
            default                        => [100, 80,  200],
        };
    }
}
