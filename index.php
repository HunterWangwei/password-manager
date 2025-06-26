<?php
session_start();

// 检查用户是否已登录，如果没有则重定向到登录页面
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

// 添加新账号
if (isset($_POST['add'])) {
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
        // 检查是否已存在相同记录
        if ($manager->recordExists($platform, $username)) {
            $error = "该平台下已存在相同用户名的记录！";
        } else {
            if ($manager->addAccount($platform, $username, $password, $url, $notes, $totp_secret, $backup_codes)) {
                $message = "账号添加成功！";
            } else {
                $error = "添加失败，请重试！";
            }
        }
    }
}

// 删除账号
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($manager->deleteAccount($id)) {
        $message = "账号删除成功！";
    } else {
        $error = "删除失败，请重试！";
    }
}

// 搜索账号
$searchTerm = '';
$accounts = [];
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search_term'];
    $accounts = $manager->searchByPlatform($searchTerm);
} else {
    // 获取所有账号
    $accounts = $manager->getAllAccounts();
}

// 生成随机密码
$randomPassword = '';
if (isset($_POST['generate'])) {
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 12;
    $randomPassword = $manager->generatePassword($length);
}

// 生成2FA密钥
$totpSecret = '';
$backupCodes = '';
$qrCodeUrl = '';
if (isset($_POST['generate_2fa'])) {
    $totpSecret = $manager->generateTotpSecret();
    $backupCodes = $manager->generateBackupCodes();
    $platform = $_POST['platform_2fa'] ?? '示例平台';
    $username = $_POST['username_2fa'] ?? '用户';
    $qrCodeUrl = $manager->generateQrCodeUrl($platform, $username, $totpSecret);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账号密码管理器</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
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
        }
        button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        /* 针对用户名和密码列的特殊处理 */
        td:nth-child(2), td:nth-child(3) {
            max-width: 160px; /* 大约20个数字的宽度 */
        }
        /* 确保可复制的span也遵循省略规则 */
        .copyable {
            display: block;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            position: relative;
            padding: 5px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }
        .copyable:hover {
            background-color: #e9ecef;
        }
        /* 改善网址列的显示 */
        td:nth-child(5) a {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        /* 备注列也应用省略显示 */
        td:nth-child(6) {
            max-width: 120px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
            white-space: nowrap;
        }
        .action-buttons a {
            padding: 5px 10px;
            text-decoration: none;
            color: white;
            border-radius: 3px;
        }
        .edit-btn {
            background-color: #2196F3;
        }
        .delete-btn {
            background-color: #f44336;
        }
        .toggle-password {
            padding: 3px 8px;
            margin-left: 5px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .toggle-password:hover {
            background-color: #5a6268;
        }
        .search-box {
            display: flex;
            margin-bottom: 20px;
        }
        .search-box input {
            flex: 1;
            margin-right: 10px;
        }
        .password-generator {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid #ddd;
            background-color: #f1f1f1;
            margin-right: 5px;
        }
        .tab.active {
            background-color: #fff;
            border-bottom: none;
        }
        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .tab-content.active {
            display: block;
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
        .user-info {
            font-size: 14px;
            color: #888;
            text-align: center;
            margin-bottom: 15px;
        }
        .copy-message {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
            white-space: nowrap;
        }
        .show-message {
            opacity: 0.9;
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
        .totp-container {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
        }
        .totp-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 16px;
            color: #007bff;
            background-color: #f0f8ff;
            padding: 3px 6px;
            border-radius: 3px;
            min-width: 60px;
            text-align: center;
        }
        .copy-2fa-btn, .show-2fa-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 3px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            position: relative;
        }
        .copy-2fa-btn:hover, .show-2fa-btn:hover {
            background-color: #5a6268;
        }
        .show-2fa-btn {
            background-color: #17a2b8;
        }
        .show-2fa-btn:hover {
            background-color: #138496;
        }
        .no-2fa {
            color: #6c757d;
            font-style: italic;
        }
        .totp-secret-display {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .totp-secret-display input {
            flex: 1;
            font-family: 'Courier New', monospace;
        }
        .qr-code-container {
            text-align: center;
            margin: 10px 0;
        }
        .qr-code {
            max-width: 200px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .two-fa-results {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .two-fa-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .two-fa-modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 500px;
            position: relative;
        }
        .two-fa-modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
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
        
        <div class="user-info">
            欢迎，<?php echo htmlspecialchars($_SESSION["username"]); ?>！
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="openTab(event, 'accounts')">账号列表</div>
            <div class="tab" onclick="openTab(event, 'add-account')">添加账号</div>
            <div class="tab" onclick="openTab(event, 'password-generator')">密码生成器</div>
            <div class="tab" onclick="openTab(event, 'two-fa-generator')">2FA生成器</div>
        </div>
        
        <div id="accounts" class="tab-content active">
            <div class="search-box">
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group" style="flex: 3;">
                            <input type="text" name="search_term" placeholder="输入平台名称搜索..." value="<?php echo $searchTerm; ?>">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <button type="submit" name="search">搜索</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="12%">平台</th>
                        <th width="18%">用户名</th>
                        <th width="18%">密码</th>
                        <th width="10%">2FA</th>
                        <th width="17%">网址</th>
                        <th width="12%">备注</th>
                        <th width="13%">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($accounts) > 0): ?>
                        <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($account['platform']); ?></td>
                                <td>
                                    <span class="copyable" 
                                          title="<?php echo htmlspecialchars($account['username']); ?>"
                                          onclick="copyText(this, '<?php echo htmlspecialchars(addslashes($account['username'])); ?>')">
                                        <?php echo htmlspecialchars($account['username']); ?>
                                        <span class="copy-message">已复制!</span>
                                    </span>
                                </td>
                                <td>
                                    <span class="copyable" 
                                          title="<?php echo htmlspecialchars($account['password']); ?>"
                                          onclick="copyText(this, '<?php echo htmlspecialchars(addslashes($account['password'])); ?>')">
                                        <?php echo htmlspecialchars($account['password']); ?>
                                        <span class="copy-message">已复制!</span>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($account['totp_secret'])): ?>
                                        <div class="totp-container">
                                            <span class="totp-code" id="totp-<?php echo $account['id']; ?>">
                                                <?php echo $manager->getCurrentTotpCode($account['totp_secret']); ?>
                                            </span>
                                            <button class="copy-2fa-btn" onclick="copyText(this, '<?php echo $manager->getCurrentTotpCode($account['totp_secret']); ?>')">
                                                复制
                                                <span class="copy-message">已复制!</span>
                                            </button>
                                            <button class="show-2fa-btn" onclick="show2FADetails(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars(addslashes($account['totp_secret'])); ?>', '<?php echo htmlspecialchars(addslashes($account['totp_backup_codes'])); ?>')">
                                                详情
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-2fa">未设置</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($account['url'])): ?>
                                        <a href="<?php echo htmlspecialchars($account['url']); ?>" target="_blank"><?php echo htmlspecialchars($account['url']); ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($account['notes']); ?></td>
                                <td class="action-buttons">
                                    <a href="edit.php?id=<?php echo $account['id']; ?>" class="edit-btn">编辑</a>
                                    <a href="index.php?delete=<?php echo $account['id']; ?>" class="delete-btn" onclick="return confirm('确定要删除这个账号吗？')">删除</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">没有找到账号记录</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div id="add-account" class="tab-content">
            <h2>添加新账号</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="platform">平台名称 *</label>
                        <input type="text" id="platform" name="platform" required>
                    </div>
                    <div class="form-group">
                        <label for="username">用户名 *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">密码 *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="url">网址</label>
                        <input type="text" id="url" name="url">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">备注</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="two-fa-section">
                    <h3>二步验证设置（可选）</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="totp_secret">TOTP密钥</label>
                            <input type="text" id="totp_secret" name="totp_secret" placeholder="32位Base32密钥">
                            <small>用于验证器应用的密钥，如Google Authenticator</small>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" onclick="generateTotpForForm()">生成2FA密钥</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="backup_codes">备用验证码</label>
                        <textarea id="backup_codes" name="backup_codes" rows="5" placeholder="每行一个备用验证码"></textarea>
                        <small>当无法使用验证器应用时的备用验证码</small>
                    </div>
                </div>
                
                <button type="submit" name="add">添加账号</button>
            </form>
        </div>
        
        <div id="password-generator" class="tab-content">
            <h2>密码生成器</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="length">密码长度</label>
                        <input type="number" id="length" name="length" value="12" min="6" max="32">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" name="generate">生成密码</button>
                    </div>
                </div>
                
                <?php if (!empty($randomPassword)): ?>
                <div class="form-group">
                    <label>生成的密码</label>
                    <input type="text" value="<?php echo $randomPassword; ?>" readonly onclick="this.select()">
                </div>
                <?php endif; ?>
            </form>
        </div>
        
        <div id="two-fa-generator" class="tab-content">
            <h2>2FA生成器</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="platform_2fa">平台名称</label>
                        <input type="text" id="platform_2fa" name="platform_2fa" placeholder="例如：Google" required>
                    </div>
                    <div class="form-group">
                        <label for="username_2fa">用户名</label>
                        <input type="text" id="username_2fa" name="username_2fa" placeholder="例如：user@example.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="generate_2fa">生成2FA密钥</button>
                </div>
                
                <?php if (!empty($totpSecret)): ?>
                <div class="two-fa-results">
                    <div class="form-group">
                        <label>TOTP密钥</label>
                        <div class="totp-secret-display">
                            <input type="text" value="<?php echo $totpSecret; ?>" readonly onclick="this.select()">
                            <button type="button" onclick="copyText(this, '<?php echo $totpSecret; ?>')">
                                复制
                                <span class="copy-message">已复制!</span>
                            </button>
                        </div>
                        <small>将此密钥输入到您的验证器应用中</small>
                    </div>
                    
                    <div class="form-group">
                        <label>二维码</label>
                        <div class="qr-code-container">
                            <img src="<?php echo $qrCodeUrl; ?>" alt="2FA二维码" class="qr-code">
                            <p>用验证器应用扫描此二维码</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>备用验证码</label>
                        <textarea readonly><?php echo $backupCodes; ?></textarea>
                        <button type="button" onclick="copyText(this, '<?php echo $backupCodes; ?>')">
                            复制备用验证码
                            <span class="copy-message">已复制!</span>
                        </button>
                        <small>请安全保存这些备用验证码，当无法使用验证器应用时可以使用</small>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <script>
        // 复制文本到剪贴板
        function copyText(element, text) {
            // 检查是否支持现代剪贴板API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopyMessage(element);
                }).catch(function(err) {
                    console.error('现代剪贴板API复制失败: ', err);
                    // 回退到传统方法
                    fallbackCopyText(text, element);
                });
            } else {
                // 使用传统的复制方法
                fallbackCopyText(text, element);
            }
        }
        
        // 传统的复制方法（兼容性更好）
        function fallbackCopyText(text, element) {
            // 创建临时的textarea元素
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.top = '-9999px';
            textArea.style.left = '-9999px';
            textArea.style.opacity = '0';
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopyMessage(element);
                } else {
                    console.error('传统复制方法失败');
                    alert('复制失败，请手动复制文本');
                }
            } catch (err) {
                console.error('复制操作失败: ', err);
                alert('复制失败，请手动复制文本');
            } finally {
                document.body.removeChild(textArea);
            }
        }
        
        // 显示复制成功消息
        function showCopyMessage(element) {
            const message = element.querySelector('.copy-message');
            if (message) {
                message.classList.add('show-message');
                
                // 2秒后隐藏提示
                setTimeout(function() {
                    message.classList.remove('show-message');
                }, 2000);
            }
        }
        
        // 切换标签页
        function openTab(evt, tabName) {
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            document.getElementById(tabName).classList.add('active');
            evt.currentTarget.classList.add('active');
        }
        
        // 为表单生成TOTP密钥
        function generateTotpForForm() {
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
        }
        
        // 显示2FA详情模态框
        function show2FADetails(accountId, secret, backupCodes) {
            // 创建模态框
            const modal = document.createElement('div');
            modal.className = 'two-fa-modal';
            modal.id = 'modal-' + accountId;
            
            const modalContent = `
                <div class="two-fa-modal-content">
                    <span class="two-fa-modal-close" onclick="close2FAModal(${accountId})">&times;</span>
                    <h3>2FA详情</h3>
                    
                    <div class="form-group">
                        <label>TOTP密钥</label>
                        <div class="totp-secret-display">
                            <input type="text" value="${secret}" readonly onclick="this.select()">
                            <button type="button" onclick="copyText(this, '${secret}')">
                                复制
                                <span class="copy-message">已复制!</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>当前验证码</label>
                        <div class="totp-code" id="current-totp-${accountId}">
                            加载中...
                        </div>
                        <small>验证码每30秒更新一次</small>
                    </div>
                    
                    ${backupCodes ? `
                    <div class="form-group">
                        <label>备用验证码</label>
                        <textarea readonly style="height: 120px;">${backupCodes}</textarea>
                        <button type="button" onclick="copyText(this, '${backupCodes}')">
                            复制备用验证码
                            <span class="copy-message">已复制!</span>
                        </button>
                    </div>
                    ` : ''}
                </div>
            `;
            
            modal.innerHTML = modalContent;
            document.body.appendChild(modal);
            modal.style.display = 'block';
            
            // 开始更新TOTP验证码
            updateTotpCode(accountId, secret);
            window['totpInterval' + accountId] = setInterval(() => {
                updateTotpCode(accountId, secret);
            }, 1000);
        }
        
        // 关闭2FA详情模态框
        function close2FAModal(accountId) {
            const modal = document.getElementById('modal-' + accountId);
            if (modal) {
                modal.style.display = 'none';
                document.body.removeChild(modal);
            }
            
            // 清除定时器
            if (window['totpInterval' + accountId]) {
                clearInterval(window['totpInterval' + accountId]);
                delete window['totpInterval' + accountId];
            }
        }
        
        // 更新TOTP验证码（通过AJAX从服务器获取）
        function updateTotpCode(accountId, secret) {
            fetch('get_totp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    secret: secret
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const element = document.getElementById('current-totp-' + accountId);
                    if (element) {
                        element.textContent = data.code + ' (' + data.timeLeft + 's)';
                    }
                } else {
                    console.error('获取TOTP验证码失败:', data.error);
                }
            })
            .catch(error => {
                console.error('AJAX请求失败:', error);
            });
        }
        
        // 页面关闭时清理定时器
        window.addEventListener('beforeunload', function() {
            for (let key in window) {
                if (key.startsWith('totpInterval')) {
                    clearInterval(window[key]);
                }
            }
        });
    </script>
</body>
</html> 