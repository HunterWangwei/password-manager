<?php
session_start();

// 如果已经登录，则重定向到首页
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: index.php");
    exit;
}

require_once "config.php";

// 定义变量并初始化
$username = $password = "";
$username_err = $password_err = $login_err = "";

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 检查用户名
    if (empty(trim($_POST["username"]))) {
        $username_err = "请输入用户名";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // 检查密码
    if (empty(trim($_POST["password"]))) {
        $password_err = "请输入密码";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // 验证凭据
    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // 密码正确，启动新会话
                            session_start();
                            
                            // 存储数据到会话变量
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            
                            // 更新最后登录时间
                            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                                mysqli_stmt_bind_param($update_stmt, "i", $id);
                                mysqli_stmt_execute($update_stmt);
                                mysqli_stmt_close($update_stmt);
                            }
                            
                            // 重定向到首页
                            header("location: index.php");
                        } else {
                            $login_err = "用户名或密码无效";
                        }
                    }
                } else {
                    $login_err = "用户名或密码无效";
                }
            } else {
                $login_err = "发生错误，请重试";
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
    <title>登录 - 账号密码管理器</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
        }
        .login-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 350px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
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
            width: 100%;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
        }
        .help-text {
            font-size: 14px;
            color: #6c757d;
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>账号密码管理器</h2>
        <p style="text-align: center;">请登录以访问您的账号密码</p>
        
        <?php if (!empty($login_err)): ?>
            <div class="error"><?php echo $login_err; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" value="<?php echo $username; ?>" required>
                <?php if (!empty($username_err)): ?>
                    <span class="error"><?php echo $username_err; ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" required>
                <?php if (!empty($password_err)): ?>
                    <span class="error"><?php echo $password_err; ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <button type="submit" class="btn">登录</button>
            </div>
        </form>
        
        <div class="help-text">
            <p>默认管理员账号: admin / admin123</p>
            <p>首次登录后请修改默认密码</p>
        </div>
    </div>
</body>
</html> 