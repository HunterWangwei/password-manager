<?php
session_start();

// 检查用户是否已登录
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'config.php';
require_once 'password_manager.php';

// 创建密码管理器实例
$manager = new PasswordManager($conn);

// 处理表单提交
$message = '';
$error = '';

// 获取账号ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];
$account = $manager->getAccount($id);

if (!$account) {
    header('Location: index.php');
    exit;
}

// 处理更新请求
if (isset($_POST['update'])) {
    $platform = $_POST['platform'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $url = $_POST['url'];
    $notes = $_POST['notes'];
    $totp_secret = $_POST['totp_secret'] ?? '';
    $backup_codes = $_POST['backup_codes'] ?? '';
    
    if (empty($platform) || empty($username) || empty($password)) {
        $error = "平台、用户名和密码不能为空！";
    } else {
        if ($manager->updateAccount($id, $platform, $username, $password, $url, $notes, $totp_secret, $backup_codes)) {
            $message = "账号更新成功！";
            $account = $manager->getAccount($id); // 重新获取更新后的数据
        } else {
            // 检查是否因为重复记录导致更新失败
            if ($manager->recordExists($platform, $username) && $account['platform'] != $platform || $account['username'] != $username) {
                $error = "该平台下已存在相同用户名的记录！";
            } else {
                $error = "更新失败，请重试！";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑账号 - 账号密码管理器</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"], textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        /* 对于长密码输入框的特殊处理 */
        #password, #username {
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .form-row {
            display: flex;
            justify-content: space-between;
        }
        .form-group {
            flex: 1;
            margin-right: 10px;
        }
        .form-group:last-child {
            margin-right: 0;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            white-space: nowrap;
        }
        button:hover {
            background-color: #45a049;
        }
        .back-btn {
            background-color: #607D8B;
        }
        .back-btn:hover {
            background-color: #546E7A;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
        }
        .navbar-brand {
            font-size: 20px;
            font-weight: bold;
        }
        .navbar-menu {
            display: flex;
            gap: 15px;
        }
        .navbar-menu a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .navbar-menu a:hover {
            background-color: #555;
        }
        .copyable {
            cursor: pointer;
            position: relative;
        }
        .copyable:hover {
            background-color: #e9ecef;
        }
        .copy-tooltip {
            position: absolute;
            background-color: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        .copyable:hover .copy-tooltip {
            opacity: 0.9;
        }
        .input-wrapper {
            position: relative;
            width: 100%;
        }
        .input-copy-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 3px 8px;
            font-size: 12px;
            cursor: pointer;
        }
        .input-copy-btn:hover {
            background-color: #5a6268;
        }
        /* 2FA相关样式 */
        .two-fa-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .two-fa-section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .current-totp-display {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .totp-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 16px;
            color: #007bff;
            background-color: #f0f8ff;
            padding: 5px 10px;
            border-radius: 3px;
            min-width: 80px;
            text-align: center;
        }
        small {
            color: #666;
            font-size: 12px;
            display: block;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navbar">
            <div class="navbar-brand">账号密码管理器</div>
            <div class="navbar-menu">
                <a href="index.php">首页</a>
                <a href="change_password.php">修改密码</a>
                <a href="admin_settings.php">管理员设置</a>
                <a href="logout.php">退出登录</a>
            </div>
        </div>
        
        <h1>编辑账号</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="platform">平台名称 *</label>
                    <input type="text" id="platform" name="platform" value="<?php echo htmlspecialchars($account['platform']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">用户名 *</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($account['username']); ?>" required>
                        <button type="button" class="input-copy-btn" onclick="copyInputValue('username')">复制</button>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">密码 *</label>
                    <div class="input-wrapper">
                        <input type="text" id="password" name="password" value="<?php echo htmlspecialchars($account['password']); ?>" required>
                        <button type="button" class="input-copy-btn" onclick="copyInputValue('password')">复制</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="url">网址</label>
                    <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($account['url']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">备注</label>
                <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($account['notes']); ?></textarea>
            </div>
            
            <div class="two-fa-section">
                <h3>二步验证设置（可选）</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="totp_secret">TOTP密钥</label>
                        <div class="input-wrapper">
                            <input type="text" id="totp_secret" name="totp_secret" 
                                   value="<?php echo htmlspecialchars($account['totp_secret'] ?? ''); ?>" 
                                   placeholder="32位Base32密钥">
                            <button type="button" class="input-copy-btn" onclick="copyInputValue('totp_secret')">复制</button>
                        </div>
                        <small>用于验证器应用的密钥，如Google Authenticator</small>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" onclick="generateTotpForEdit()">生成新2FA密钥</button>
                    </div>
                </div>
                
                <?php if (!empty($account['totp_secret'])): ?>
                <div class="form-group">
                    <label>当前验证码</label>
                    <div class="current-totp-display">
                        <span class="totp-code"><?php echo $manager->getCurrentTotpCode($account['totp_secret']); ?></span>
                        <small>验证码每30秒更新一次</small>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="backup_codes">备用验证码</label>
                    <div class="input-wrapper">
                        <textarea id="backup_codes" name="backup_codes" rows="5" 
                                  placeholder="每行一个备用验证码"><?php echo htmlspecialchars($account['totp_backup_codes'] ?? ''); ?></textarea>
                        <button type="button" class="input-copy-btn" onclick="copyTextareaValue('backup_codes')">复制</button>
                    </div>
                    <small>当无法使用验证器应用时的备用验证码</small>
                </div>
            </div>
            
            <button type="submit" name="update">更新账号</button>
            <a href="index.php"><button type="button" class="back-btn">返回列表</button></a>
        </form>
    </div>
    <script>
        // 复制输入框值到剪贴板
        function copyInputValue(inputId) {
            const input = document.getElementById(inputId);
            input.select();
            navigator.clipboard.writeText(input.value).then(function() {
                const copyBtn = input.nextElementSibling;
                const originalText = copyBtn.textContent;
                copyBtn.textContent = '已复制!';
                
                // 2秒后恢复原始文本
                setTimeout(function() {
                    copyBtn.textContent = originalText;
                }, 2000);
            }).catch(function(err) {
                console.error('复制失败: ', err);
            });
        }

        function copyTextareaValue(textareaId) {
            const textarea = document.getElementById(textareaId);
            textarea.select();
            navigator.clipboard.writeText(textarea.value).then(function() {
                const copyBtn = textarea.nextElementSibling;
                const originalText = copyBtn.textContent;
                copyBtn.textContent = '已复制!';
                
                // 2秒后恢复原始文本
                setTimeout(function() {
                    copyBtn.textContent = originalText;
                }, 2000);
            }).catch(function(err) {
                console.error('复制失败: ', err);
            });
        }

        function generateTotpForEdit() {
            // 生成32位Base32密钥
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            let secret = '';
            for (let i = 0; i < 32; i++) {
                secret += chars[Math.floor(Math.random() * chars.length)];
            }
            
            // 填充到表单
            document.getElementById('totp_secret').value = secret;
            
            // 生成备用验证码
            let backupCodes = '';
            for (let i = 0; i < 10; i++) {
                const code1 = Math.floor(Math.random() * 9000) + 1000;
                const code2 = Math.floor(Math.random() * 9000) + 1000;
                backupCodes += code1 + '-' + code2 + '\n';
            }
            document.getElementById('backup_codes').value = backupCodes.trim();
            
            alert('新的2FA密钥已生成！请记住更新您的验证器应用。');
        }
    </script>
</body>
</html> 