<?php

namespace App\Services\NaiveBayes;

use App\Models\DataTraining;
use App\Models\ModelEvaluasi;
use RuntimeException;

/**
 * Evaluasi model Naive Bayes — dua metode (hold-out & k-fold stratified).
 *
 * Metodologi JUJUR: model dilatih ULANG pada train-split lalu diuji pada test-split,
 * BUKAN diuji pada data training sendiri. Semua split deterministik (order by md5(id))
 * -> reproducible.
 *
 * Kelas positif = 'layak'. Metrik: accuracy, precision, recall, F1 + confusion matrix.
 * model_version diisi utk referensi (bukan sebagai "model ini akurasinya X").
 *
 * TAMBAHAN terhadap proposal — dua metode utk perbandingan di Bab IV.
 */
class ModelEvaluator
{
    public function __construct(
        protected ProbabilityTableBuilder $builder,
        protected NaiveBayesService $nb,
    ) {}

    /**
     * Jalankan evaluasi sesuai metode yang dipilih.
     *
     * @param  array{metode:string, test_ratio?:float, k?:int}  $opts
     */
    public function run(array $opts = []): ModelEvaluasi
    {
        $metode = $opts['metode'] ?? 'holdout';

        return $metode === 'kfold'
            ? $this->runKfold((int) ($opts['k'] ?? 5))
            : $this->runHoldout((float) ($opts['test_ratio'] ?? 0.2));
    }

    /**
     * Hold-out (train/test split) — metodologi asal, diperbaiki atribusinya.
     */
    public function runHoldout(float $testRatio = 0.2): ModelEvaluasi
    {
        $data = DataTraining::all()->sortBy(fn ($r) => md5((string) $r->id))->values();
        $total = $data->count();
        if ($total < 2) {
            throw new RuntimeException('Data training kurang dari 2 baris, tidak bisa evaluasi.');
        }

        $testSize = (int) ceil($total * $testRatio);
        $testSize = max(1, min($testSize, $total - 1));
        $train = $data->slice(0, $total - $testSize);
        $test = $data->slice($total - $testSize);

        $table = $this->builder->computeTable($train);
        $cm = $this->testOneSplit($test, $table);

        return $this->saveResult([
            'metode' => 'holdout',
            'jumlah_data_uji' => $test->count(),
            'cm' => $cm + [
                'test_ratio' => $testRatio,
                'train_size' => $train->count(),
                'test_size' => $testSize,
                'test_ids' => $test->pluck('id')->all(),
            ],
        ]);
    }

    /**
     * k-Fold Cross-Validation stratified.
     *
     * Tiap data diuji tepat 1×. Mengakumulasi confusion matrix dari k iterasi.
     * Split deterministik & stratified per kelas (round-robin via md5 order).
     */
    public function runKfold(int $k = 5): ModelEvaluasi
    {
        $data = DataTraining::all();
        $total = $data->count();
        if ($total < $k) {
            throw new RuntimeException("Data training ({$total}) kurang dari k ({$k}).");
        }
        if ($k < 2) {
            throw new RuntimeException('k minimal 2.');
        }

        // stratified fold assignment
        $folds = [];
        for ($i = 0; $i < $k; $i++) { $folds[$i] = collect(); }
        foreach (['layak', 'tidak_layak'] as $kelas) {
            $items = $data->where('label_kelas', $kelas)
                ->sortBy(fn ($r) => md5((string) $r->id))
                ->values();
            foreach ($items as $i => $item) {
                $foldIdx = $i % $k;
                $folds[$foldIdx]->push($item);
            }
        }

        // accumulate confusion matrix across folds
        $cm = ['TP' => 0, 'TN' => 0, 'FP' => 0, 'FN' => 0];
        $foldSizes = [];
        foreach ($folds as $foldIdx => $testFold) {
            $foldSizes[] = $testFold->count();
            $testIds = $testFold->pluck('id')->all();
            $train = $data->filter(fn ($item) => ! in_array($item->id, $testIds))->values();
            $table = $this->builder->computeTable($train);
            $foldCm = $this->testOneSplit($testFold, $table);
            foreach ($cm as $key => &$val) {
                $val += $foldCm[$key];
            }
        }

        return $this->saveResult([
            'metode' => 'kfold',
            'k' => $k,
            'jumlah_data_uji' => $total,
            'cm' => $cm + [
                'k' => $k,
                'total_data' => $total,
                'fold_distribution' => $foldSizes,
                'train_size_avg' => ($total - array_sum($foldSizes) / $k),
            ],
        ]);
    }

    /**
     * Uji satu split: predict tiap baris test, akumulasi TP/TN/FP/FN.
     */
    protected function testOneSplit($testData, array $table): array
    {
        $TP = $TN = $FP = $FN = 0;
        foreach ($testData as $row) {
            $input = [
                'penghasilan' => $row->penghasilan_kategori,
                'pekerjaan' => $row->pekerjaan_kategori,
                'tanggungan' => $row->tanggungan_kategori,
                'kondisi_rumah' => $row->kondisi_rumah_kategori,
            ];
            $pred = $this->nb->predictWithTable($input, $table)['prediksi_kelas'];
            $actual = $row->label_kelas;

            if ($pred === 'layak' && $actual === 'layak') {
                $TP++;
            } elseif ($pred === 'tidak_layak' && $actual === 'tidak_layak') {
                $TN++;
            } elseif ($pred === 'layak' && $actual === 'tidak_layak') {
                $FP++;
            } else {
                $FN++;
            }
        }

        return compact('TP', 'TN', 'FP', 'FN');
    }

    /**
     * Hitung metrik dari CM & simpan ke DB.
     */
    protected function saveResult(array $ctx): ModelEvaluasi
    {
        $cm = $ctx['cm'];
        $tested = $cm['TP'] + $cm['TN'] + $cm['FP'] + $cm['FN'];
        $accuracy = $tested > 0 ? ($cm['TP'] + $cm['TN']) / $tested : 0;
        $precision = ($cm['TP'] + $cm['FP']) > 0 ? $cm['TP'] / ($cm['TP'] + $cm['FP']) : 0;
        $recall = ($cm['TP'] + $cm['FN']) > 0 ? $cm['TP'] / ($cm['TP'] + $cm['FN']) : 0;
        $f1 = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0;

        return ModelEvaluasi::create([
            'model_version' => $this->nb->activeModelVersion() ?? 'eval',
            'metode' => $ctx['metode'],
            'k' => $ctx['k'] ?? null,
            'jumlah_data_uji' => $ctx['jumlah_data_uji'],
            'accuracy' => $accuracy,
            'precision' => $precision,
            'recall' => $recall,
            'f1_score' => $f1,
            'confusion_matrix_json' => $cm,
        ]);
    }
}
