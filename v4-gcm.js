const fs = require('fs');
const crypto = require('crypto');

function OvkfDeleteeGCM(filepath) {
    if (!fs.existsSync(filepath)) return false;
    const stat = fs.statSync(filepath);
    const size = stat.size;
    const fd = fs.openSync(filepath, 'r+');

    // 1. XOR scramble
    const xorKey = crypto.randomBytes(1)[0];
    const blockSize = 64 * 1024;
    let buffer = Buffer.alloc(blockSize);
    let offset = 0;
    while (offset < size) {
        const len = fs.readSync(fd, buffer, 0, blockSize, offset);
        for (let i = 0; i < len; i++) buffer[i] ^= xorKey;
        fs.writeSync(fd, buffer.slice(0, len), 0, len, offset);
        offset += len;
    }

    // 2. AES-GCM encrypt
    const key = crypto.randomBytes(32);
    const iv = crypto.randomBytes(12);
    const data = fs.readFileSync(filepath);
    const cipher = crypto.createCipheriv('aes-256-gcm', key, iv);
    const encrypted = Buffer.concat([cipher.update(data), cipher.final()]);
    fs.writeSync(fd, encrypted, 0, encrypted.length, 0);

    // 3. Overwrite
    const patterns = [Buffer.alloc(size, 0x00), Buffer.alloc(size, 0xFF), crypto.randomBytes(size)];
    for (const pattern of patterns) {
        fs.writeSync(fd, pattern, 0, size, 0);
    }

    // 4. Truncate & remove
    fs.ftruncateSync(fd, 0);
    fs.closeSync(fd);
    fs.unlinkSync(filepath);
    return true;
}
