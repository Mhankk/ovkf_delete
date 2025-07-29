const fs = require('fs');
const crypto = require('crypto');

function ovkf_delete(filepath) {
  if (!fs.existsSync(filepath)) return;

  const size = fs.statSync(filepath).size;
  if (size === 0) throw new Error("File size is zero");

  // Pass 1: Encrypt-before-delete (AES-256-CBC)
  const key = crypto.randomBytes(32);
  const iv = crypto.randomBytes(16);
  const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
  const data = fs.readFileSync(filepath);
  const encrypted = Buffer.concat([cipher.update(data), cipher.final()]);
  fs.writeFileSync(filepath, encrypted.slice(0, size));

  // Pass 2: Zero fill
  fs.writeFileSync(filepath, Buffer.alloc(size, 0x00));

  // Pass 3: 0xFF fill
  fs.writeFileSync(filepath, Buffer.alloc(size, 0xFF));

  // Pass 4: Random fill
  fs.writeFileSync(filepath, crypto.randomBytes(size));

  // Pass 5: Pattern fill (Gutmann-inspired, 0x55)
  fs.writeFileSync(filepath, Buffer.alloc(size, 0x55));

  // Final deletion
  fs.unlinkSync(filepath);
}
