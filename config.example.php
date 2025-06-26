<?php
// 数据库连接配置示例
// 请复制此文件为 config.php 并修改以下配置

define('DB_SERVER', 'localhost');        // 数据库服务器地址
define('DB_USERNAME', 'your_username');  // 数据库用户名
define('DB_PASSWORD', 'your_password');  // 数据库密码
define('DB_NAME', 'password_manager');   // 数据库名称

// 尝试连接到数据库
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// 检查连接
if (!$conn) {
    die("连接失败: " . mysqli_connect_error());
}

// 创建数据库（如果不存在）
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    // 选择数据库
    mysqli_select_db($conn, DB_NAME);
    
    // 创建账号密码表
    $sql = "CREATE TABLE IF NOT EXISTS accounts (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        platform VARCHAR(255) NOT NULL,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        url VARCHAR(255),
        notes TEXT,
        totp_secret VARCHAR(255),
        totp_backup_codes TEXT,
        has_2fa TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        echo "创建表失败: " . mysqli_error($conn);
    }
    
    // 检查是否需要添加2FA字段（为现有表升级）
    $columns_to_add = [
        'totp_secret' => 'VARCHAR(255)',
        'totp_backup_codes' => 'TEXT',
        'has_2fa' => 'TINYINT(1) DEFAULT 0'
    ];
    
    foreach ($columns_to_add as $column => $type) {
        $check_column = "SHOW COLUMNS FROM accounts LIKE '$column'";
        $result = mysqli_query($conn, $check_column);
        if (mysqli_num_rows($result) == 0) {
            $add_column = "ALTER TABLE accounts ADD COLUMN $column $type";
            mysqli_query($conn, $add_column);
        }
    }
    
    // 创建用户表
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $sql)) {
        echo "创建用户表失败: " . mysqli_error($conn);
    }
    
    // 检查是否存在默认管理员账号
    $result = mysqli_query($conn, "SELECT * FROM users LIMIT 1");
    if (mysqli_num_rows($result) == 0) {
        // 创建默认管理员账号 (用户名: admin, 密码: admin123)
        // 请在首次登录后立即修改默认密码！
        $admin_username = 'admin';
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password) VALUES ('$admin_username', '$admin_password')";
        mysqli_query($conn, $sql);
    }
} else {
    echo "创建数据库失败: " . mysqli_error($conn);
}
?> 