# Video Analysis

短视频无水印解析工具，输入分享链接即可获取视频信息和无水印地址。

### 环境要求

- PHP 8.1+
- PHP 扩展：`curl`、`json`
- Composer
- Node.js 18+（抖音解析需要）

### 快速开始

```bash
composer install
composer serve:full
```

打开 `http://localhost:8000`。

#### 环境变量

```env
APP_NAME="Video Analysis"
APP_DEBUG=false
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=60
DOUYIN_NODE_BIN=node
WEIBO_COOKIE=''
```

### Docker 部署

```bash
cp .env.example .env
docker compose up -d --build
```

零成本上线指南见 [DEPLOY.md](DEPLOY.md)。

### API 接口

| 接口 | 方法 | 说明 |
| --- | --- | --- |
| `/api/v1/health` | GET | 健康检查 |
| `/api/v1/platforms` | GET | 支持平台列表 |
| `/api/v1/parse` | GET/POST | 解析视频链接 |

### 免责声明

仅供个人学习和研究使用。
