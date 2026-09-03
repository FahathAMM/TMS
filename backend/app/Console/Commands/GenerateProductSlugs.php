<?php

namespace App\Console\Commands;

use App\Models\Inventory\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('products:generate-slugs {--force : Regenerate slugs even for products that already have one}')]
#[Description('Generate URL slugs for all products that are missing one')]
class GenerateProductSlugs extends Command
{
    public function handle(): int
    {
        $query = $this->option('force')
            ? Product::withTrashed()
            : Product::withTrashed()->whereNull('slug');

        $total = $query->count();

        if ($total === 0) {
            $this->info('All products already have slugs.');
            return self::SUCCESS;
        }

        $this->info("Generating slugs for {$total} product(s)...");

        $bar     = $this->output->createProgressBar($total);
        $updated = 0;

        $query->each(function (Product $product) use ($bar, &$updated) {
            $slug = Product::generateUniqueSlug($product->name, $product->id);
            // updateQuietly skips model events to avoid infinite loops
            $product->updateQuietly(['slug' => $slug]);
            $bar->advance();
            $updated++;
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$updated} slug(s) generated.");

        return self::SUCCESS;
    }
}
