<?php

/*
|--------------------------------------------------------------------------
| Baris Bahasa Validasi (Indonesia)
|--------------------------------------------------------------------------
| Locale app masih 'en', jadi file ini menimpa terjemahan bawaan vendor
| yang berbahasa Inggris. Pesan validasi tampil di UI (form checkout
| storefront & form back-office) dan sekarang konsisten Indonesia.
| Key yang tidak didefinisikan di sini tetap memakai fallback vendor.
|
*/

return [
    'required' => ':attribute wajib diisi.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'string' => ':attribute harus berupa teks.',
    'array' => ':attribute harus berupa daftar.',
    'numeric' => ':attribute harus berupa angka.',
    'integer' => ':attribute harus berupa angka bulat.',
    'exists' => ':attribute tidak terdaftar.',

    'min' => [
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
        'array' => ':attribute minimal :min item.',
    ],

    'max' => [
        'numeric' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
        'array' => ':attribute maksimal :max item.',
    ],

    'attributes' => [
        'name' => 'Nama lengkap',
        'email' => 'Email',
        'phone' => 'No. telepon',
        'address' => 'Alamat pengiriman',
        'items' => 'Daftar barang',
        'product_id' => 'Produk',
        'qty' => 'Jumlah',
    ],
];
