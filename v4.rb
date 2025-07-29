require 'openssl'
require 'securerandom'

def ovkf_delete(filepath)
  return unless File.exist?(filepath)

  size = File.size(filepath)
  raise "File size unknown" if size == 0

  # Pass 1: Encrypt-before-delete (AES-256-CBC)
  aes_key = SecureRandom.random_bytes(32)
  aes_iv = SecureRandom.random_bytes(16)
  cipher = OpenSSL::Cipher.new('AES-256-CBC')
  cipher.encrypt
  cipher.key = aes_key
  cipher.iv = aes_iv
  encrypted = cipher.update(File.read(filepath)) + cipher.final
  File.open(filepath, 'wb') { |f| f.write(encrypted[0, size]) }

  # Pass 2: Zero fill
  File.open(filepath, 'wb') { |f| f.write("\x00" * size) }

  # Pass 3: 0xFF fill
  File.open(filepath, 'wb') { |f| f.write("\xFF" * size) }

  # Pass 4: Random fill
  File.open(filepath, 'wb') { |f| f.write(SecureRandom.random_bytes(size)) }

  # Pass 5: Pattern fill (Gutmann-inspired, 0x55)
  File.open(filepath, 'wb') { |f| f.write("\x55" * size) }

  # Final deletion
  File.delete(filepath)
end
