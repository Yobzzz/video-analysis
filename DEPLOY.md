# 零成本上线指南

> 以下方案完全免费，无需服务器，无需信用卡。

---

## 方案：Render.com Docker 部署（推荐）

**Render** 提供免费 Web Service 额度（750 小时/月），原生支持 Docker，绑定 GitHub 后自动部署。

### 第 1 步：推送代码到 GitHub

```bash
# 在你的 GitHub 上创建一个新仓库（如 video-analysis）
# 然后把本地代码推上去：

cd video-analysis
git remote add origin https://github.com/你的用户名/video-analysis.git
git add .
git commit -m "production deployment"
git push -u origin main
```

### 第 2 步：注册 Render 并创建服务

1. 打开 [render.com](https://render.com)，点击 **Get Started for Free**，用 GitHub 账号登录
2. 登录后进入 Dashboard，点击 **New +** → **Web Service**
3. 授权 Render 访问你的 GitHub 仓库，选择 `video-analysis`

### 第 3 步：配置服务

| 配置项 | 值 |
|--------|-----|
| Name | `video-analysis`（随意） |
| Region | `Singapore`（亚洲访问最快） |
| Branch | `main` |
| Runtime | **Docker**（自动识别 Dockerfile） |
| Instance Type | **Free** |

### 第 4 步：设置环境变量

在 Environment Variables 中添加：

```
APP_DEBUG=false
APP_ENV=production
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=60
RATE_LIMIT_TIME_WINDOW=60
PARSE_CACHE_TTL=86400
A_BOGUS_PORT=9876
DOUYIN_NODE_BIN=node
```

> 如需解析微博，加 `WEIBO_COOKIE=你的cookie`

### 第 5 步：部署

点击 **Create Web Service**，Render 会自动：
1. 拉取 GitHub 代码
2. 构建 Docker 镜像
3. 启动容器（Nginx + PHP-FPM + a_bogus）
4. 分配免费域名 `xxx.onrender.com`

构建大约 3-5 分钟。完成后访问 `https://你的服务名.onrender.com` 即可使用。

### 第 6 步：绑定自定义域名（可选）

Render Dashboard → Settings → Custom Domains → 添加你的域名，按提示配置 DNS。

### 注意事项

- 免费实例 **15 分钟无请求会自动休眠**，下次请求会有约 30 秒冷启动
- 月流量 100GB，对于个人工具绰绰有余
- 如需 24 小时在线，可搭配 [UptimeRobot](https://uptimerobot.com)（免费）每 5 分钟 ping 一次健康检查接口 `https://xxx.onrender.com/api/v1/health`

---

## 备用方案：Oracle Cloud 永久免费服务器

Oracle 提供 **完全免费的 ARM 服务器**（4 核 24GB），永不回收，需要信用卡验证（不扣费）。

1. 注册 [Oracle Cloud](https://www.oracle.com/cloud/free/)
2. 创建 Always Free 实例（ARM Ampere A1，Ubuntu 22.04）
3. SSH 登录后：

```bash
# 安装 Docker
curl -fsSL https://get.docker.com | bash
sudo usermod -aG docker $USER

# 克隆并启动
git clone https://github.com/你的用户名/video-analysis.git
cd video-analysis
cp .env.example .env
docker compose up -d --build

# 开放防火墙
sudo iptables -I INPUT -p tcp --dport 80 -j ACCEPT
```

4. 在 Oracle Cloud 控制台 → 安全列表 → 添加入站规则（端口 80 TCP）

---

## 部署后验证

```bash
# 健康检查
curl https://你的域名/api/v1/health

# 测试解析
curl -X POST https://你的域名/api/v1/parse \
  -H "Content-Type: application/json" \
  -d '{"url":"抖音/快手/小红书分享链接"}'
```
