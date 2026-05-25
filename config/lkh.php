<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Penandatangan rekap LKH (PDF)
    |--------------------------------------------------------------------------
    | jabatan_label / subbagian_label mengacu ke config('master.app.jabatan')
    | dan config('master.app.subbagian').
    */
    'signatory' => [
        'sekretaris' => [
            'jabatan_label' => 'Sekretaris',
            'title' => 'Sekretaris',
        ],
        'kasubbag' => [
            'jabatan_label' => 'Kepala Subbagian',
            'subbagian_label' => 'Partisipasi Hubungan Masyarakat dan Sumber Daya Manusia',
            'title' => 'Kepala Subbagian',
            'subtitle' => 'Partisipasi Hubungan Masyarakat dan Sumber Daya Manusia',
        ],
    ],
];
