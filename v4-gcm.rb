require 'openssl'
require 'securerandom'

def ovkf_delete_gcm(filepath)
  return false unless File.exist?(filepath)
  size = File.size(filepath)

  File.open(filepath, 'r+b') do |f|
    # 1. XOR scramble
    xor_key = SecureRandom.random_bytes(1).ord
    while !f.eof?
      pos = f.pos
      data = f.read(64 * 1024)
      xored = data.bytes.map { |b| (b ^ xor_key).chr }.join
      f.seek(pos)
      f.write(xored)
    end
    f.flush

    # 2. AES-GCM encryption
    key = SecureRandom.random_bytes(32)
    iv = SecureRandom.random_bytes(12)
    cipher = OpenSSL::Cipher.new('aes-256-gcm')
    cipher.encrypt
    cipher.key = key
    cipher.iv = iv
    data = File.read(filepath, mode: 'rb')
    encrypted = cipher.update(data) + cipher.final
    f.seek(0)
    f.write(encrypted)
    f.flush

    # 3. Overwrite
    [0x00, 0xFF].each do |byte|
      f.seek(0)
      f.write(byte.chr * size)
      f.flush
    end
    f.seek(0)
    f.write(SecureRandom.random_bytes(size))
    f.flush

    # 4. Truncate & delete
    f.truncate(0)
  end
  File.delete(filepath)
  true
end
