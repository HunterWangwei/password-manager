# 密码管理器 (Password Manager)

🔒 一个功能完整的Web密码管理器，支持账号密码存储、2FA管理和安全加密。

## ✨ 功能特性

### 🔐 核心功能
- **账号密码管理**: 安全存储和管理各种平台的账号密码
- **分类管理**: 按平台分类组织账号信息
- **搜索功能**: 快速搜索特定平台的账号
- **数据加密**: 所有敏感数据采用AES加密存储
- **一键复制**: 点击即可复制用户名、密码到剪贴板

### 🛡️ 安全功能
- **用户认证**: 安全的用户登录系统
- **密码哈希**: 使用bcrypt哈希存储用户密码
- **会话管理**: 安全的会话管理机制
- **数据加密**: 账号密码采用AES-256加密

### 📱 2FA支持
- **TOTP生成**: 生成符合RFC 6238标准的TOTP密钥
- **二维码支持**: 自动生成二维码供验证器应用扫描
- **备用验证码**: 生成10组备用验证码
- **实时验证码**: 显示当前6位验证码，每30秒自动更新
- **兼容性**: 支持Google Authenticator、Microsoft Authenticator等

### 🎨 界面特性
- **响应式设计**: 适配各种屏幕尺寸
- **标签页界面**: 清晰的功能分类
- **美观表格**: 优化的数据展示，长内容自动省略
- **交互友好**: 悬停提示、复制反馈等人性化设计

## 🚀 快速开始

### 环境要求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Web服务器 (Apache/Nginx)

### 安装步骤

1. **克隆项目**
   ```bash
   git clone https://github.com/YOUR_USERNAME/password-manager.git
   cd password-manager
   ```

2. **配置数据库**
   - 复制配置文件: `cp config.example.php config.php`
   - 编辑 `config.php` 文件，修改数据库连接信息:
     ```php
     define('DB_SERVER', 'localhost');
     define('DB_USERNAME', 'your_username');
     define('DB_PASSWORD', 'your_password');
     define('DB_NAME', 'password_manager');
     ```

3. **部署到Web服务器**
   - 将项目文件上传到Web服务器目录
   - 确保Web服务器对项目目录有读写权限

4. **初始化数据库**
   - 首次访问项目URL时会自动创建数据库和数据表
   - 默认管理员账号: `admin` / `admin123`
   - **⚠️ 请立即登录并修改默认密码！**

## 📖 使用指南

### 首次登录
1. 访问项目URL
2. 使用默认账号登录: `admin` / `admin123`
3. 立即修改默认密码

### 管理账号密码
1. **添加账号**: 点击"添加账号"标签页
2. **查看列表**: 在"账号列表"中查看所有保存的账号
3. **搜索**: 使用搜索框快速找到特定平台的账号
4. **复制**: 点击用户名或密码即可复制到剪贴板
5. **编辑**: 点击"编辑"按钮修改账号信息

### 2FA管理
1. **生成密钥**: 在"2FA生成器"中生成TOTP密钥
2. **扫描二维码**: 使用验证器应用扫描二维码
3. **保存信息**: 将密钥和备用验证码保存到账号中
4. **查看验证码**: 在账号列表中查看实时6位验证码

### 密码生成
- 使用"密码生成器"生成安全的随机密码
- 可自定义密码长度 (6-32位)

## 🔧 技术架构

### 后端技术
- **PHP**: 核心业务逻辑
- **MySQL**: 数据存储
- **AES加密**: 敏感数据加密
- **TOTP算法**: RFC 6238标准实现

### 前端技术
- **HTML5**: 页面结构
- **CSS3**: 样式设计
- **JavaScript**: 交互逻辑
- **AJAX**: 异步数据更新

### 安全措施
- 所有敏感数据加密存储
- SQL注入防护
- XSS攻击防护
- CSRF保护
- 安全的会话管理

## 📁 项目结构

```
password-manager/
├── index.php              # 主页面
├── login.php               # 登录页面
├── edit.php                # 编辑账号页面
├── password_manager.php    # 密码管理器核心类
├── config.php              # 数据库配置文件
├── config.example.php      # 配置文件示例
├── security.php            # 安全加密类
├── get_totp.php           # TOTP验证码API
├── change_password.php     # 修改密码页面
├── admin_settings.php      # 管理员设置
├── logout.php              # 退出登录
└── README.md               # 项目说明
```

## 🛡️ 安全建议

1. **修改默认密码**: 立即修改默认管理员密码
2. **HTTPS部署**: 建议在HTTPS环境下部署
3. **定期备份**: 定期备份数据库数据
4. **权限控制**: 设置适当的文件和目录权限
5. **更新维护**: 定期更新系统和依赖

## 🤝 贡献指南

欢迎提交Issue和Pull Request来改进这个项目！

1. Fork 项目
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 打开一个 Pull Request

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情

## 🙏 致谢

感谢所有为这个项目做出贡献的开发者！

---

⭐ 如果这个项目对您有帮助，请给个Star支持一下！