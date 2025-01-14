<?php
// Encryption key and method
define('ENCRYPTION_KEY', 'Kj9#mP2$nX5@vL8*qR4&hT7!wC3^yB6'); // Secure 32-character key
define('ENCRYPTION_METHOD', 'AES-256-CBC');

function encrypt($data) {
    $key = ENCRYPTION_KEY;
    $ivlen = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decrypt($data) {
    if (empty($data)) return "";
    
    $key = ENCRYPTION_KEY;
    $data = base64_decode($data);
    $ivlen = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = substr($data, 0, $ivlen);
    $encrypted = substr($data, $ivlen);
    return openssl_decrypt($encrypted, ENCRYPTION_METHOD, $key, 0, $iv);
}

// Function to encrypt specific customer fields
function encryptCustomerData($data) {
    // Fields to encrypt
    $sensitive_fields = ['name', 'email', 'phone', 'ic_number'];
    
    foreach ($sensitive_fields as $field) {
        if (isset($data[$field])) {
            $data[$field] = encrypt($data[$field]);
        }
    }
    
    return $data;
}

// Function to decrypt specific customer fields
function decryptCustomerData($data) {
    // Fields to decrypt
    $sensitive_fields = ['name', 'email', 'phone', 'ic_number'];
    
    foreach ($sensitive_fields as $field) {
        if (isset($data[$field])) {
            $data[$field] = decrypt($data[$field]);
        }
    }
    
    return $data;
}
?>
