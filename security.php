<?php
class Security {
    // 使用更安全的随机密钥，实际应用中应存储在配置文件或环境变量中
    private static $encryptionKey = "ZxR7Hn9Q2sK5Lp3FvTyUbE8WaD6GcM4J"; 
    
    // 加密函数
    public static function encrypt($data) {
        $method = "AES-256-CBC";
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($data, $method, self::$encryptionKey, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    // 解密函数
    public static function decrypt($data) {
        $method = "AES-256-CBC";
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, $method, self::$encryptionKey, 0, $iv);
    }
    
    // 生成随机密码
    public static function generatePassword($length = 12) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = "";
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }
}
?> 