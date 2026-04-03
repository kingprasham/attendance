<?php
// api/helpers/encryption.php

require_once __DIR__ . '/../config/constants.php';

function aes_encrypt($plaintext) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_METHOD));
    $ciphertext = openssl_encrypt($plaintext, AES_METHOD, AES_KEY, 0, $iv);
    return base64_encode($iv . '::' . $ciphertext);
}

function aes_decrypt($encrypted) {
    $data = base64_decode($encrypted);
    $parts = explode('::', $data, 2);
    if (count($parts) !== 2) {
        return null;
    }
    return openssl_decrypt($parts[1], AES_METHOD, AES_KEY, 0, $parts[0]);
}
