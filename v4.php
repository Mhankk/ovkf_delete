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

    // 1a. Gunakan XOR dengan kunci acak 1-byte untuk scramble data awal (per blok)
    $xorKey = random_int(1, 255);
    $blockSize = 64 * 1024; // 64KB
    fseek($hndl, 0);

    while (!feof($hndl)) {
        $pos = ftell($hndl);
        $data = fread($hndl, $blockSize);
        if ($data === false || strlen($data) === 0) break;

        $xored = '';
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $xored .= chr(ord($data[$i]) ^ $xorKey);
        }

        fseek($hndl, $pos);
        fwrite($hndl, $xored);
    }
    fflush($hndl);

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

    // Helper function for overwrite
    function overwrite_with_pattern($handle, $pattern, $filesize) {
        fseek($handle, 0);
        $blockSize = 64 * 1024;
        $fullBlocks = intdiv($filesize, $blockSize);
        $remainder = $filesize % $blockSize;
        $block = str_repeat($pattern, $blockSize);

        for ($i = 0; $i < $fullBlocks; $i++) {
            fwrite($handle, $block);
        }
        fwrite($handle, str_repeat($pattern, $remainder));
        fflush($handle);
    }

    // Pass 1: Zero fill
    overwrite_with_pattern($hndl, chr(0x00), $filesize);

    // Pass 2: One fill
    overwrite_with_pattern($hndl, chr(0xFF), $filesize);

    // Pass 3: Random data
    fseek($hndl, 0);
    fwrite($hndl, random_bytes($filesize));
    fflush($hndl);

    // Pass 4-6: DoD 5220.22-M pattern
    overwrite_with_pattern($hndl, chr(0x00), $filesize);
    overwrite_with_pattern($hndl, chr(0xFF), $filesize);
    fseek($hndl, 0);
    fwrite($hndl, random_bytes($filesize));
    fflush($hndl);

    // Pass 7-10: Gutmann style
    $gutmann_patterns = [
        chr(0x55), // pola 01010101
        chr(0xAA), // pola 10101010
        chr(0x92), // pola historis
    ];

    foreach ($gutmann_patterns as $pattern) {
        overwrite_with_pattern($hndl, $pattern, $filesize);
    }

    // Tambahan pass random
    fseek($hndl, 0);
    fwrite($hndl, random_bytes($filesize));
    fflush($hndl);

    // =============================
    // LANGKAH 3: POTONG & HAPUS
    // =============================
    ftruncate($hndl, 0);
    fclose($hndl);
    return unlink($filepath);
}
