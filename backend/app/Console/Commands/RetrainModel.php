<?php

namespace App\Console\Commands;

use App\Services\NaiveBayes\ProbabilityTableBuilder;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Training ulang model Naive Bayes dari seluruh data_training berlabel.
 * Pemakaian: php artisan nb:retrain
 */
class RetrainModel extends Command
{
    protected $signature = 'nb:retrain';

    protected $description = 'Training ulang model Naive Bayes dari data_training';

    public function handle(ProbabilityTableBuilder $builder): int
    {
        $modelVersion = 'v'.now()->format('Ymd-His');

        try {
            $version = $builder->build($modelVersion);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Model aktif: {$version->model_version}");
        $this->info("Total data training: {$version->total_data} (layak {$version->count_layak} / tidak_layak {$version->count_tidak_layak})");

        return self::SUCCESS;
    }
}
