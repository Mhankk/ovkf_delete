import os
import random
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.backends import default_backend

def ovkf_delete(filepath):
    if not os.path.isfile(filepath):
        print("File tidak ditemukan:", filepath)
        return False

    file_size = os.path.getsize(filepath)

    # ============================
    # Tahap 1: Encrypt-before-Delete (AES)
    # ============================
    key = os.urandom(32)  # AES-256
    iv = os.urandom(16)
    cipher = Cipher(algorithms.AES(key), modes.CFB(iv), backend=default_backend())
    encryptor = cipher.encryptor()

    with open(filepath, "rb+") as f:
        data = f.read()
        f.seek(0)
        encrypted_data = encryptor.update(data) + encryptor.finalize()
        f.write(encrypted_data)
        f.flush()

    # ============================
    # Tahap 2: Zero-fill (isi dengan 0x00)
    # ============================
    with open(filepath, "rb+") as f:
        f.write(b'\x00' * file_size)
        f.flush()

    # ============================
    # Tahap 3: Random Overwrite (acak)
    # ============================
    with open(filepath, "rb+") as f:
        f.write(os.urandom(file_size))
        f.flush()

    # ============================
    # Tahap 4: Gutmann sample (pola klasik)
    # ============================
    gutmann_patterns = [b'\x55', b'\xAA', b'\x92', b'\x49']
    with open(filepath, "rb+") as f:
        for pattern in gutmann_patterns:
            f.seek(0)
            f.write(pattern * file_size)
            f.flush()

    # ============================
    # Tahap 5: DoD 3-pass style
    # ============================
    dod_patterns = [b'\xFF', b'\x00', os.urandom(1)]
    with open(filepath, "rb+") as f:
        for pat in dod_patterns:
            if isinstance(pat, bytes):
                f.seek(0)
                f.write(pat * file_size)
            else:
                f.seek(0)
                f.write(bytes([random.randint(0, 255)]) * file_size)
            f.flush()

    # ============================
    # Tahap 6: Random final pass (1–3x)
    # ============================
    final_pass = random.randint(1, 3)
    with open(filepath, "rb+") as f:
        for _ in range(final_pass):
            f.seek(0)
            f.write(os.urandom(file_size))
            f.flush()

    # ============================
    # Tahap 7: Hapus file
    # ============================
    os.remove(filepath)
    print("File berhasil dihapus secara aman:", filepath)
    return True
