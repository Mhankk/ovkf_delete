<?php

function ovkf_delete($filepath) {
    // Cek apakah file benar-benar ada
    if (!file_exists($filepath)) {
        return false;
    }

    $filesize = filesize($filepath);

    // Buka file dalam mode baca-tulis biner
    $hndl = fopen($filepath, 'r+');
    if (!$hndl) {
        return false;
    }

    // =============================
    // LANGKAH 1: ENKRIPSI SEBELUM HAPUS
    // =============================
    // 1a. Gunakan XOR dengan kunci acak 1-byte untuk scramble data awal
    $xorKey = random_int(1, 255);
    fseek($hndl, 0);
    for ($i = 0; $i < $filesize; $i++) {
        $char = fgetc($hndl);
        $xored = chr(ord($char) ^ $xorKey);
        fseek($hndl, -1, SEEK_CUR);
        fwrite($hndl, $xored);
    }

    // 1b. Enkripsi dengan algoritma AES-256-CBC (opsional tambahan layer)
    $aesKey = openssl_random_pseudo_bytes(32); // 256 bit
    $aesIV  = openssl_random_pseudo_bytes(16); // 128 bit IV
    fseek($hndl, 0);
    $data = fread($hndl, $filesize);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $aesIV);
    fseek($hndl, 0);
    fwrite($hndl, $encrypted);
    fflush($hndl); // pastikan ditulis ke disk

    // =============================
    // LANGKAH 2: MULTI-PASS OVERWRITE
    // =============================

    // Pass 1: Zero fill
    fseek($hndl, 0);
    fwrite($hndl, str_repeat(chr(0x00), $filesize));
    fflush($hndl);

    // Pass 2: One fill
    fseek($hndl, 0);
    fwrite($hndl, str_repeat(chr(0xFF), $filesize));
    fflush($hndl);

    // Pass 3: Random data
    fseek($hndl, 0);
    fwrite($hndl, random_bytes($filesize));
    fflush($hndl);

    // Pass 4-6: DoD 5220.22-M pattern (0x00, 0xFF, Random)
    fseek($hndl, 0);
    fwrite($hndl, str_repeat(chr(0x00), $filesize));
    fflush($hndl);

    fseek($hndl, 0);
    fwrite($hndl, str_repeat(chr(0xFF), $filesize));
    fflush($hndl);

    fseek($hndl, 0);
    fwrite($hndl, random_bytes($filesize));
    fflush($hndl);

    // Pass 7-10: Gutmann style (pola acak)
    $gutmann_patterns = [
        str_repeat("\x55", $filesize), // pola 01010101
        str_repeat("\xAA", $filesize), // pola 10101010
        str_repeat("\x92", $filesize), // pola acak historis
        random_bytes($filesize)        // pure random
    ];

    foreach ($gutmann_patterns as $pattern) {
        fseek($hndl, 0);
        fwrite($hndl, $pattern);
        fflush($hndl);
    }

    // =============================
    // LANGKAH 3: POTONG & HAPUS
    // =============================

    // Potong isi file jadi kosong
    ftruncate($hndl, 0);
    fclose($hndl);

    // Hapus file dari sistem
    return unlink($filepath);
}
