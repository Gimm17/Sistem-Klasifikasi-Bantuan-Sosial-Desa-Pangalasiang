<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Populasi dan sampel penelitian
    |--------------------------------------------------------------------------
    |
    | Data lapangan Desa Pangalasiang mencatat 1.041 kepala keluarga pada
    | delapan dusun. Sampel 91 KK dialokasikan secara proporsional per dusun.
    |
    */
    'populasi_kk' => 1041,
    'target_sampel' => 91,
    'dusun' => [
        'Dusun I' => ['populasi_kk' => 222, 'target_sampel' => 19],
        'Dusun II' => ['populasi_kk' => 123, 'target_sampel' => 11],
        'Dusun III' => ['populasi_kk' => 224, 'target_sampel' => 20],
        'Dusun IV' => ['populasi_kk' => 91, 'target_sampel' => 8],
        'Dusun V' => ['populasi_kk' => 118, 'target_sampel' => 10],
        'Dusun VI' => ['populasi_kk' => 84, 'target_sampel' => 7],
        'Dusun VII' => ['populasi_kk' => 89, 'target_sampel' => 8],
        'Dusun VIII' => ['populasi_kk' => 90, 'target_sampel' => 8],
    ],
];
