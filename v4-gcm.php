<?php
function ovkf_delete_gcm($filepath) {
    if (!file_exists($filepath)) return false;
    $filesize = filesize($filepath);
    $hndl = fopen($filepath, 'r+');
    if (!$hndl) return false;

    // 1. XOR scramble (64KB block)
    $xorKey = random_int(1, 255);
    $blockSize = 64 * 1024;
    fseek($hndl, 0);
    while (!feof($hndl)) {
        $pos = ftell($hndl);
        $data = fread($hndl, $blockSize);
        if ($data === false) break;
        $xored = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $xored .= chr(ord($data[$i]) ^ $xorKey);
        }
        fseek($hndl, $pos);
        fwrite($hndl, $xored);
    }
    fflush($hndl);

    // 2. AES-GCM encryption
    $key = random_bytes(32);
    $iv = random_bytes(12); // GCM uses 96-bit nonce
    $tag = '';
    fseek($hndl, 0);
    $data = fread($hndl, $filesize);
    $ciphertext = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    fseek($hndl, 0);
    fwrite($hndl, $ciphertext);
    fflush($hndl);

    // 3. Overwrite (same as sebelumnya)
    foreach ([chr(0x00), chr(0xFF), random_bytes($filesize)] as $pattern) {
        fseek($hndl, 0);
        fwrite($hndl, is_string($pattern) ? str_repeat($pattern, $filesize) : $pattern);
        fflush($hndl);
    }

    // 4. Truncate & delete
    ftruncate($hndl, 0);
    fclose($hndl);
    return unlink($filepath);
}
