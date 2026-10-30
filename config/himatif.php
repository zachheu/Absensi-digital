<?php

return [

    /*
    |--------------------------------------------------------------------------
    | QR Code Absensi
    |--------------------------------------------------------------------------
    |
    | Token QR diperbarui setiap 15 detik dan tidak pernah berlaku lebih dari
    | 20 detik. Nilai ini menjadi acuan tunggal untuk pembuatan dan validasi.
    |
    */

    'qr_ttl_seconds' => 15,

    'qr_max_ttl_seconds' => 20,

    /*
    |--------------------------------------------------------------------------
    | Rate limit
    |--------------------------------------------------------------------------
    */

    'login_attempts_per_minute' => 5,

    'scan_attempts_per_minute' => 12,

];
