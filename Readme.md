# Secure Delete Function (AES-CBC & AES-GCM)

## Tujuan

Menghapus file secara aman dengan:

* XOR scrambling
* Enkripsi AES (CBC/GCM)
* Multi-pass overwrite
* Truncate dan delete

---

## Fitur Umum

* XOR blok per 64KB
* AES-256 (CBC atau GCM)
* Overwrite bertahap (null, 0xFF, random)
* Truncate file
* Hapus file dari sistem

---

## Bahasa & Mode

### PHP

* CBC: `openssl_encrypt(..., 'aes-256-cbc', ...)`
* GCM: `openssl_encrypt(..., 'aes-256-gcm', ..., $tag)`

### Python

* CBC: `Cipher(algorithms.AES(key), modes.CBC(iv))`
* GCM: `AESGCM(key).encrypt(nonce, data, aad)`

### Ruby

* CBC: `OpenSSL::Cipher.new('aes-256-cbc')`
* GCM: `OpenSSL::Cipher.new('aes-256-gcm')`

### Node.js

* CBC: `createCipheriv('aes-256-cbc', key, iv)`
* GCM: `createCipheriv('aes-256-gcm', key, iv)`

---

## Urutan Proses

1. Buka file mode read/write
2. XOR data (blok 64KB)
3. Enkripsi AES (GCM atau CBC)
4. Overwrite dengan 0x00, 0xFF, dan data acak
5. Truncate file ke ukuran 0
6. Hapus file

---

## Catatan

* AES-GCM lebih aman dari sisi integritas (dengan authentication tag).
* AES-CBC lebih luas didukung, tetapi rentan padding oracle jika tak hati-hati.
* XOR sebelum enkripsi mencegah pola data dikenali saat forensic.

---

## Penggunaan Lanjut

* Tambahkan logging (opsional)
* Tambah CLI/tool GUI
* Adaptasi ke mode XTS untuk disk-level wiping
