import os
from cryptography.hazmat.primitives.ciphers.aead import AESGCM

def secure_delete_gcm(filepath):
    if not os.path.isfile(filepath): return False
    filesize = os.path.getsize(filepath)

    with open(filepath, 'r+b') as f:
        # 1. XOR scramble
        xor_key = os.urandom(1)[0]
        block_size = 64 * 1024
        f.seek(0)
        while True:
            pos = f.tell()
            data = f.read(block_size)
            if not data:
                break
            xored = bytes([b ^ xor_key for b in data])
            f.seek(pos)
            f.write(xored)
        f.flush()

        # 2. AES-GCM encryption
        key = AESGCM.generate_key(bit_length=256)
        nonce = os.urandom(12)
        f.seek(0)
        data = f.read(filesize)
        aesgcm = AESGCM(key)
        ciphertext = aesgcm.encrypt(nonce, data, None)
        f.seek(0)
        f.write(ciphertext)
        f.flush()

        # 3. Overwrite
        for pattern in [b'\x00', b'\xFF', os.urandom(filesize)]:
            f.seek(0)
            f.write(pattern * filesize if len(pattern) == 1 else pattern)
            f.flush()

        # 4. Truncate & delete
        f.truncate(0)
    os.remove(filepath)
    return True
