<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VietQR payment methods
    |--------------------------------------------------------------------------
    |
    | Secrets (STK) live ONLY in `.env`. This file only maps env keys to a
    | shared structure. Three banks share one VietQR renderer/template,
    | MoMo is text-only (no QR, no API, no webhook).
    |
    */

    'default' => env('VIETQR_DEFAULT', 'mb'),

    'account_name' => env('VIETQR_ACCOUNT_NAME', ''),

    'template' => env('VIETQR_TEMPLATE', 'compact2'),

    'max_add_info' => 25,

    'methods' => [
        'mb' => [
            'label' => 'MB Bank',
            'type' => 'bank',
            'bank_id' => env('VIETQR_MB_BANK', ''),
            'account_no' => env('VIETQR_MB_ACCOUNT', ''),
        ],
        'tcb' => [
            'label' => 'Techcombank',
            'type' => 'bank',
            'bank_id' => env('VIETQR_TCB_BANK', ''),
            'account_no' => env('VIETQR_TCB_ACCOUNT', ''),
        ],
        'vpb' => [
            'label' => 'VPBank',
            'type' => 'bank',
            'bank_id' => env('VIETQR_VPB_BANK', ''),
            'account_no' => env('VIETQR_VPB_ACCOUNT', ''),
        ],
        'momo' => [
            'label' => 'MoMo',
            'type' => 'wallet',
            'account_no' => env('VIETQR_MOMO_ACCOUNT', ''),
        ],
    ],

];
