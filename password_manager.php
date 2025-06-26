<?php
require_once 'config.php';
require_once 'security.php';

class PasswordManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // 检查记录是否已存在
    public function recordExists($platform, $username) {
        $stmt = $this->conn->prepare("SELECT id FROM accounts WHERE platform = ? AND username = ?");
        $stmt->bind_param("ss", $platform, $username);
        $stmt->execute();
        $stmt->store_result();
        
        return $stmt->num_rows > 0;
    }
    
    // 添加新账号
    public function addAccount($platform, $username, $password, $url = '', $notes = '', $totp_secret = '', $backup_codes = '') {
        // 检查记录是否已存在
        if ($this->recordExists($platform, $username)) {
            return false;
        }
        
        // 加密密码和2FA密钥
        $encryptedPassword = Security::encrypt($password);
        $encryptedTotpSecret = !empty($totp_secret) ? Security::encrypt($totp_secret) : '';
        $encryptedBackupCodes = !empty($backup_codes) ? Security::encrypt($backup_codes) : '';
        $has2fa = !empty($totp_secret) ? 1 : 0;
        
        $stmt = $this->conn->prepare("INSERT INTO accounts (platform, username, password, url, notes, totp_secret, totp_backup_codes, has_2fa) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $platform, $username, $encryptedPassword, $url, $notes, $encryptedTotpSecret, $encryptedBackupCodes, $has2fa);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
    
    // 获取所有账号
    public function getAllAccounts() {
        $result = $this->conn->query("SELECT * FROM accounts ORDER BY platform");
        $accounts = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // 解密密码和2FA密钥
                $row['password'] = Security::decrypt($row['password']);
                if (!empty($row['totp_secret'])) {
                    $row['totp_secret'] = Security::decrypt($row['totp_secret']);
                }
                if (!empty($row['totp_backup_codes'])) {
                    $row['totp_backup_codes'] = Security::decrypt($row['totp_backup_codes']);
                }
                $accounts[] = $row;
            }
        }
        
        return $accounts;
    }
    
    // 按平台搜索账号
    public function searchByPlatform($platform) {
        $stmt = $this->conn->prepare("SELECT * FROM accounts WHERE platform LIKE ?");
        $searchTerm = "%" . $platform . "%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $accounts = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['password'] = Security::decrypt($row['password']);
                if (!empty($row['totp_secret'])) {
                    $row['totp_secret'] = Security::decrypt($row['totp_secret']);
                }
                if (!empty($row['totp_backup_codes'])) {
                    $row['totp_backup_codes'] = Security::decrypt($row['totp_backup_codes']);
                }
                $accounts[] = $row;
            }
        }
        
        return $accounts;
    }
    
    // 更新账号信息
    public function updateAccount($id, $platform, $username, $password, $url, $notes, $totp_secret = '', $backup_codes = '') {
        // 检查是否存在相同平台和用户名的其他记录（排除当前ID）
        $stmt = $this->conn->prepare("SELECT id FROM accounts WHERE platform = ? AND username = ? AND id != ?");
        $stmt->bind_param("ssi", $platform, $username, $id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            return false; // 已存在相同记录
        }
        
        $encryptedPassword = Security::encrypt($password);
        $encryptedTotpSecret = !empty($totp_secret) ? Security::encrypt($totp_secret) : '';
        $encryptedBackupCodes = !empty($backup_codes) ? Security::encrypt($backup_codes) : '';
        $has2fa = !empty($totp_secret) ? 1 : 0;
        
        $stmt = $this->conn->prepare("UPDATE accounts SET platform = ?, username = ?, password = ?, url = ?, notes = ?, totp_secret = ?, totp_backup_codes = ?, has_2fa = ? WHERE id = ?");
        $stmt->bind_param("sssssssii", $platform, $username, $encryptedPassword, $url, $notes, $encryptedTotpSecret, $encryptedBackupCodes, $has2fa, $id);
        
        return $stmt->execute();
    }
    
    // 删除账号
    public function deleteAccount($id) {
        $stmt = $this->conn->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    // 获取单个账号详情
    public function getAccount($id) {
        $stmt = $this->conn->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $account = $result->fetch_assoc();
            $account['password'] = Security::decrypt($account['password']);
            if (!empty($account['totp_secret'])) {
                $account['totp_secret'] = Security::decrypt($account['totp_secret']);
            }
            if (!empty($account['totp_backup_codes'])) {
                $account['totp_backup_codes'] = Security::decrypt($account['totp_backup_codes']);
            }
            return $account;
        }
        
        return null;
    }
    
    // 生成随机密码
    public function generatePassword($length = 12) {
        return Security::generatePassword($length);
    }
    
    // 生成TOTP密钥
    public function generateTotpSecret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32字符集
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    // 生成备用验证码
    public function generateBackupCodes($count = 10) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = sprintf('%04d-%04d', random_int(1000, 9999), random_int(1000, 9999));
        }
        return implode("\n", $codes);
    }
    
    // 生成TOTP二维码URL
    public function generateQrCodeUrl($platform, $username, $secret) {
        $issuer = urlencode('密码管理器');
        $label = urlencode($platform . ' (' . $username . ')');
        $otpauthUrl = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}";
        
        // 使用Google Charts API生成二维码
        return "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=" . urlencode($otpauthUrl);
    }
    
    // 获取当前TOTP验证码（用于显示）
    public function getCurrentTotpCode($secret) {
        if (empty($secret)) return '';
        
        $timeSlice = floor(time() / 30);
        $secretkey = $this->base32Decode($secret);
        $time = pack("N*", 0, $timeSlice);
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack("N", $hashpart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;
        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }
    
    // Base32解码（用于TOTP计算）
    private function base32Decode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $val = strpos($alphabet, $data[$i]);
            if ($val === false) continue;
            $v <<= 5;
            $v += $val;
            $vbits += 5;
            if ($vbits >= 8) {
                $output .= chr(($v >> ($vbits - 8)) & 255);
                $vbits -= 8;
            }
        }
        return $output;
    }
}
?> 