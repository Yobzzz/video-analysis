# Video Analysis

短视频无水印解析 + 视频内容分析（口播稿生成）工具。

一个开箱即用的短视频工作台：粘贴分享链接即可**解析出无水印视频**（支持播放预览与下载）；上传本地视频或粘贴链接即可**让 AI 逐帧看懂画面内容，自动输出口播播音稿**，直接复制用于发布。

---

## 一、平台功能简介

### 1. 视频解析（无水印下载）

输入短视频分享链接（支持粘贴带文字的整段分享文案，自动提取链接），即可获取视频信息与**无水印播放地址**，支持在线预览、下载（服务端代理转发，自动处理 CDN 防盗链 Referer，支持 Range 断点续传）。

**支持平台：**

| 平台 | 分享链接示例 |
| --- | --- |
| 抖音 douyin | `https://v.douyin.com/xxxx/` |
| 快手 kuaishou | `https://v.kuaishou.com/xxxx` |
| B站 bilibili | `https://www.bilibili.com/video/BVxxxx` / `https://b23.tv/xxxx` |
| 小红书 xiaohongshu | `https://www.xiaohongshu.com/...` / `https://xhslink.com/...` |
| 微信视频号 shipinhao | `https://channels.weixin.qq.com/...` |
| 西瓜视频 xigua | `https://www.ixigua.com/...` |
| 皮皮虾 pipixia | `https://h5.pipix.com/...` |
| 微博 weibo | `https://weibo.com/...` |
| 微视 weishi | `https://h5.weishi.qq.com/...` |
| 最右 izuiyou | `https://izuiyou.com/...` |
| 皮皮搞笑 pipigx | `https://share.ippzone.com/...` |

### 2. 视频分析（口播稿生成）

对短视频做**画面内容理解**，输出可直接用于配音/发布的口播稿（script）、标题与内容总结，并附带画面分段时间线。

- **三种输入方式**：上传本地视频（mp4/mov 等）、粘贴平台分享链接（自动解析后下载分析）、`{"demo": true}` 离线演示（无需视频，本地联调）；
- **分析流程**：ffmpeg 抽帧 → 逐帧画面理解 → 梳理视频流程 → 输出播音稿；
- **视觉模型**：默认对接火山方舟（OpenAI 兼容格式，`doubao-seed-2-0-mini-260428`），无 Key 时自动降级为离线启发式分析（`provider: heuristic`）；
- **输出内容**：标题、总结、口播稿全文（可一键复制）、关键帧缩略图、画面变化分段。

### 3. 界面功能（Web 工作台）

- 双 Tab 页面：**解析** / **口播稿分析**，同一工作区完成全部操作；
- 右侧**历史记录**面板（解析历史 + 口播稿历史，仅保存在当前浏览器 localStorage，可清空）；
- 上传本地文件后，文件名自动填入输入框（覆盖原链接）；
- 深色/浅色主题切换、中英双语界面；
- API 速率限制、可选 API Key 鉴权、解析缓存。

---

## 二、部署方式

### 环境要求

| 依赖 | 版本 | 用途 |
| --- | --- | --- |
| PHP | 8.1+（扩展：curl、json、mbstring、fileinfo） | 后端服务 |
| Composer | 2.x | PHP 依赖 |
| Node.js | 18+ | 抖音 a_bogus 签名 |
| ffmpeg | 任意较新版本 | 口播稿分析抽帧（可用 `FFMPEG_BIN` 指定路径） |

### 方式一：Docker 部署（推荐，服务器）

```bash
cp .env.example .env        # 按需编辑配置
docker compose up -d --build
```

启动后访问 `http://localhost`（或 `http://<服务器IP>`），健康检查：`http://localhost/api/v1/health`。

一键部署脚本（可选）：

```bash
bash deploy.sh                          # 仅本地构建启动
bash deploy.sh myrepo/app:v1            # 构建并推送镜像后启动
```

### 方式二：Windows 本地运行（含便携 PHP 环境）

> 说明：`php -S` 内置服务器存在上传文件 bug，本地推荐用 **php-cgi + Node 网关** 方式。

**A. 网关方式（推荐，解决上传问题）：**

```bat
:: 1) 启动 php-cgi（FastCGI 后端）
php-cgi.exe -b 127.0.0.1:9000 -c <php.ini 路径>

:: 2) 启动本地网关（HTTP → FastCGI，监听 8080）
node local-gateway.js
```

打开 `http://127.0.0.1:8080/`。一键脚本：`start_local.bat`（需按本机路径调整）。

**B. 内置服务器方式（不支持上传）：**

```bat
start_local.bat    # 或 start.bat：启动抖音签名服务 + php -S localhost:8000
```

打开 `http://localhost:8000`。

**C. Composer 方式（Linux/macOS）：**

```bash
composer install
composer serve:full      # 启动抖音签名服务 + PHP 开发服务器
```

打开 `http://localhost:8000`。

### 环境变量说明

复制 `.env.example` 为 `.env` 后按需修改：

| 变量 | 默认 | 说明 |
| --- | --- | --- |
| `APP_NAME` / `APP_DEBUG` / `APP_ENV` | — | 应用基础配置 |
| `RATE_LIMIT_ENABLED` | `true` | 速率限制开关（默认 60 次/分钟/IP） |
| `API_KEY_ENABLED` / `API_KEY` | `false` | 可选 API Key 鉴权（开启后除 health 外均需 `X-API-Key` 或 `Authorization: Bearer`） |
| `DOUYIN_NODE_BIN` / `A_BOGUS_PORT` | `node` / `9876` | 抖音 a_bogus 签名（Node.js） |
| `DOUYIN_COOKIE` | 空 | 抖音浏览器 Cookie（可显著提高解析成功率，F12 复制） |
| `WEIBO_COOKIE` | 空 | 微博解析所需 Cookie |
| `PARSE_CACHE_ENABLED` / `PARSE_CACHE_TTL` | `true` / `86400` | 解析缓存（file 驱动，可切 redis） |
| `MEDIA_PROXY_ENABLED` / `MEDIA_PROXY_MAX_SIZE` | `true` / `524288000` | 媒体代理下载开关与大小上限 |
| `GAME_ANALYSIS_ENABLED` | `true` | 口播稿分析功能开关 |
| `OPENAI_API_KEY` | 空 | **火山方舟 API Key**（开启视觉模型理解，必填） |
| `OPENAI_BASE_URL` | `https://ark.cn-beijing.volces.com/api/v3` | OpenAI 兼容端点 |
| `GAME_OPENAI_MODEL` | `doubao-seed-2-0-mini-260428` | 视觉模型（可换 `doubao-1.5-vision-pro-32k` 或推理端点 `ep-xxx`） |
| `FFMPEG_BIN` | `ffmpeg` | ffmpeg 可执行文件路径 |

> 火山方舟配置：控制台 → 方舟 → 开通管理，开通视觉模型；API Key 管理创建 UUID 格式的 Key 填入 `OPENAI_API_KEY`。

---

## 三、使用方式

### 1. 网页使用（推荐）

**视频解析：**
1. 打开首页，切换到「解析」Tab；
2. 在输入框粘贴分享链接（或整段分享文案），点击「开始解析」；
3. 解析完成后可在线预览、复制链接、下载无水印视频；
4. 结果自动进入右侧「历史记录」，可随时重新解析/清空。

**口播稿分析：**
1. 切换到「口播稿分析」Tab；
2. 三选一：点击「上传」选择本地视频文件（文件名自动填入输入框）/ 粘贴视频链接 / 直接开始；
3. 点击「开始分析」，等待 AI 逐帧分析（约 1–2 分钟，取决于视频长度与模型）；
4. 完成后查看关键帧、画面分段与口播稿，一键复制用于配音发布；
5. 结果自动保存到右侧「历史口播稿」。

### 2. API 使用

| 接口 | 方法 | 说明 |
| --- | --- | --- |
| `/api/v1/health` | GET | 健康检查（始终公开） |
| `/api/v1/platforms` | GET | 支持平台列表 |
| `/api/v1/parse` | GET/POST | 解析视频链接（query / JSON / 表单） |
| `/api/v1/game-analysis` | POST | 口播稿分析（multipart 上传 `video` 字段 / JSON `{"url":...}` / JSON `{"demo":true}`） |
| `/api/v1/download-proxy` | GET | 直通下载（快手等 CDN 直链） |
| `/dl/<名称>.mp4?url=...` | GET | 媒体代理下载（支持 Range 断点续传） |

**解析示例：**

```bash
curl "http://localhost:8000/api/v1/parse?url=https://v.douyin.com/xxxx/"
```

**口播稿分析示例（本地上传）：**

```bash
curl -F "video=@local.mp4" http://localhost:8000/api/v1/game-analysis
```

**口播稿分析示例（链接 / 演示）：**

```bash
curl -X POST -H "Content-Type: application/json" -d '{"url":"https://v.kuaishou.com/xxxx"}' \
     http://localhost:8000/api/v1/game-analysis

curl -X POST -H "Content-Type: application/json" -d '{"demo":true}' \
     http://localhost:8000/api/v1/game-analysis
```

完整接口文档见 [public/openapi.yaml](public/openapi.yaml)。

---

## 四、项目结构

```
├── app/                  # 应用代码（Parsers 解析器 / Services 服务 / Utils 工具）
├── bin/                  # 命令行工具（lint / smoke）
├── config/               # 配置（parser 平台配置、缓存、Cookie 等）
├── docker/               # Docker 配置（nginx / supervisord）
├── public/               # Web 入口（app.php 前端 / index.php 路由 / api/v1.php）
├── scripts/              # 抖音 a_bogus 签名脚本
├── storage/              # 运行时数据（日志、缓存、上传临时文件）
├── composer.json         # PHP 依赖与脚本（serve / lint / test 等）
├── local-gateway.js      # Windows 本地 HTTP→FastCGI 网关
├── start.bat / start_local.bat / start.sh / deploy.sh
└── Dockerfile / docker-compose.yml
```

---

## 五、免责声明

本项目仅供个人学习和研究使用，请勿用于商业用途或侵犯第三方权益。解析结果版权归原作者所有。
