<?php
session_start();

// 设置返回JSON格式
header('Content-Type: application/json');

// 检查用户是否已登录
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => '未授权访问', 'success' => false]);
    exit;
}

require_once 'config.php';
require_once 'password_manager.php';

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '方法不允许', 'success' => false]);
    exit;
}

// 获取POST数据
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['secret']) || empty($input['secret'])) {
    http_response_code(400);
    echo json_encode(['error' => '缺少密钥参数', 'success' => false]);
    exit;
}

$secret = $input['secret'];

// 创建密码管理器实例
$manager = new PasswordManager($conn);

try {
    // 获取当前TOTP验证码
    $totpCode = $manager->getCurrentTotpCode($secret);
    
    // 计算剩余时间
    $timeLeft = 30 - (time() % 30);
    
    // 返回JSON响应
    echo json_encode([
        'code' => $totpCode,
        'timeLeft' => $timeLeft,
        'success' => true
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '生成验证码失败: ' . $e->getMessage(), 'success' => false]);
}
?> 