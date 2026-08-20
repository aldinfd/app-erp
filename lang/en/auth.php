<?php

/*
|--------------------------------------------------------------------------
| Baris Bahasa Autentikasi (Indonesia)
|--------------------------------------------------------------------------
| Dipakai Fortify untuk pesan kegagalan login. Locale app masih 'en',
| jadi file ini menimpa terjemahan bawaan vendor yang berbahasa
| Inggris agar pesan error di halaman login konsisten berbahasa
| Indonesia. Jika APP_LOCALE diubah ke 'id', pindahkan ke lang/id/.
|
*/

return [
    'failed' => 'Email atau password tidak cocok dengan data kami.',
    'throttle' => 'Terlalu banyak percobaan masuk. Coba lagi dalam :seconds detik.',
];
