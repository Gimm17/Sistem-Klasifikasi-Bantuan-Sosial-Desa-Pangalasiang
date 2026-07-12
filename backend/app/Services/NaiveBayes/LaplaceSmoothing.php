<?php

namespace App\Services\NaiveBayes;

/**
 * Laplace (add-one) smoothing.
 *
 * P(xi | C) = (count(xi, C) + 1) / (count(C) + jumlahKategoriUnik_i)
 *
 * Mencegah probabilitas nol saat kombinasi (kategori, kelas) tak muncul di training.
 * Mengikuti docs/implementation_plan.md §4.2.
 */
class LaplaceSmoothing
{
    public static function likelihood(int $count, int $classCount, int $uniqueCount): float
    {
        return ($count + 1) / ($classCount + $uniqueCount);
    }
}
