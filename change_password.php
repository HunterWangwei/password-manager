<?php
session_start();

// 检查用户是否已登录
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

// 定义变量并初始化
$new_password = $confirm_password = $current_password = "";
$new_password_err = $confirm_password_err = $current_password_err = "";
$success_msg = "";

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 验证当前密码
    if (empty(trim($_POST["current_password"]))) {
        $current_password_err = "请输入当前密码";
    } else {
        $current_password = trim($_POST["current_password"]);
        
        // 验证当前密码是否正确
        $sql = "SELECT password FROM users WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $_SESSION["id"];
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (!password_verify($current_password, $hashed_password)) {
                            $current_password_err = "当前密码不正确";
                        }
                    }
                } else {
                    $current_password_err = "发生错误，请重试";
                }
            } else {
                $current_password_err = "发生错误，请重试";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // 验证新密码
    if (empty(trim($_POST["new_password"]))) {
        $new_password_err = "请输入新密码";
    } elseif (strlen(trim($_POST["new_password"])) < 6) {
        $new_password_err = "密码长度至少为6个字符";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // 验证确认密码
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "请确认新密码";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "两次输入的密码不匹配";
        }
    }
    
    // 检查输入错误
    if (empty($new_password_err) && empty($confirm_password_err) && empty($current_password_err)) {
        // 更新密码
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $param_password, $param_id);
            
            // 设置参数
            $param_password = password_hash($new_password, PASSWORD_DEFAULT);
            $param_id = $_SESSION["id"];
            
            // 执行语句
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "密码修改成功！";
            } else {
                $new_password_err = "发生错误，请重试";
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
    <title>修改密码 - 账号密码管理器</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            text-align: center;
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
        .error {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
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
        
        <h2>修改密码</h2>
        
        <?php if (!empty($success_msg)): ?>
            <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>当前密码</label>
                <input type="password" name="current_password" required>
                <?php if (!empty($current_password_err)): ?>
                    <span class="error"><?php echo $current_password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>新密码</label>
                <input type="password" name="new_password" required>
                <?php if (!empty($new_password_err)): ?>
                    <span class="error"><?php echo $new_password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>确认新密码</label>
                <input type="password" name="confirm_password" required>
                <?php if (!empty($confirm_password_err)): ?>
                    <span class="error"><?php echo $confirm_password_err; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="buttons">
                <button type="submit" class="btn">修改密码</button>
                <a href="index.php"><button type="button" class="btn back-btn">返回首页</button></a>
            </div>
        </form>
    </div>
</body>
</html> 