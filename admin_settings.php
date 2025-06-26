<?php
session_start();

// 检查用户是否已登录
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

// 定义变量并初始化
$username = $email = "";
$username_err = $email_err = $success_msg = $error_msg = "";

// 获取当前管理员信息
$admin_id = $_SESSION["id"];
$sql = "SELECT username, email FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $db_username, $db_email);
            if (mysqli_stmt_fetch($stmt)) {
                $username = $db_username;
                $email = $db_email ?: "";
            }
        }
    }
    mysqli_stmt_close($stmt);
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 验证用户名
    if (empty(trim($_POST["username"]))) {
        $username_err = "请输入用户名";
    } elseif (strlen(trim($_POST["username"])) < 3) {
        $username_err = "用户名长度至少为3个字符";
    } else {
        $username = trim($_POST["username"]);
        
        // 检查用户名是否已被使用（排除当前用户）
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $param_username, $admin_id);
            $param_username = $username;
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $username_err = "该用户名已被使用";
                }
            } else {
                $error_msg = "发生错误，请重试";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // 验证邮箱
    if (!empty(trim($_POST["email"]))) {
        if (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "请输入有效的邮箱地址";
        } else {
            $email = trim($_POST["email"]);
        }
    }
    
    // 检查输入错误
    if (empty($username_err) && empty($email_err)) {
        // 更新管理员信息
        $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $param_username, $param_email, $admin_id);
            $param_username = $username;
            $param_email = $email;
            
            if (mysqli_stmt_execute($stmt)) {
                // 更新会话中的用户名
                $_SESSION["username"] = $username;
                $success_msg = "管理员信息更新成功！";
            } else {
                $error_msg = "发生错误，请重试";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员设置 - 账号密码管理器</title>
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
        h1, h2 {
            color: #333;
            text-align: center;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .back-btn {
            background-color: #607D8B;
        }
        .back-btn:hover {
            background-color: #546E7A;
        }
        .error-text {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
        .buttons {
            display: flex;
            justify-content: flex-start;
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
        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .card-title {
            margin: 0;
            color: #333;
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
        
        <h1>管理员设置</h1>
        
        <?php if (!empty($success_msg)): ?>
            <div class="message success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="message error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">管理员信息</h2>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    <?php if (!empty($username_err)): ?>
                        <span class="error-text"><?php echo $username_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>邮箱 (可选)</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <?php if (!empty($email_err)): ?>
                        <span class="error-text"><?php echo $email_err; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="buttons">
                    <button type="submit" class="btn">保存更改</button>
                    <a href="index.php"><button type="button" class="btn back-btn">返回首页</button></a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">安全设置</h2>
            </div>
            
            <p>修改您的密码以保护账号安全。</p>
            <div class="buttons">
                <a href="change_password.php"><button type="button" class="btn">修改密码</button></a>
            </div>
        </div>
    </div>
</body>
</html> 