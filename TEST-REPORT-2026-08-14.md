# 多平台视频解析功能实测报告

日期：2026-08-14
测试环境：本地网关（`node local-gateway.js` → `php-cgi` 127.0.0.1:9000），PHP 8.2
测试对象：`/api/v1/parse`（解析）→ `index.php?action=media`（播放代理）→ `/dl/*.mp4`（下载，Range 断点续传）

## 结论速览

| 平台 | 解析 | 播放代理 | 下载 | 结论 |
|---|---|---|---|---|
| 快手 kuaishou.com | ✅ 通过 | ✅ 206 video/mp4 | ✅ 206 attachment | 全链路正常 |
| bilibili（含 b23.tv 短链） | ✅ 通过（已修复 bug） | ✅ 206 video/mp4 | ✅ 206 attachment | 全链路正常 |
| 小红书 xiaohongshu.com | ✅ 通过（需新鲜 xsec_token） | ✅ 206 video/mp4 | ✅ 206 attachment | 全链路正常 |
| 抖音 douyin.com | ❌ 解析失败（平台风控） | — | — | 代码已补齐，需浏览器 Cookie/环境 |
| 视频号 channels.weixin.qq.com | ❌ 解析失败（API 端点失效） | — | — | 需新的公开解析方案 |

前端页面正常（HTTP 200，"视频解析 / 小游戏分析"两个 Tab 均渲染）。

---

## 一、通过的平台（端到端实测数据）

### 1. 快手
- 测试链接：`https://www.kuaishou.com/short-video/3x3pns6bs5y529s`（热门视频）
- 解析：HTTP 200，1.9s → 标题《伊朗称击落3架美军战机？》，作者"新华社"，直链 `v23-3.kwaicdn.com/...mp4`
- 播放代理：HTTP 206 Partial Content，`content-type: video/mp4`，`content-range: bytes 0-.../138056`，支持 Range ✅
- 下载：HTTP 206 + `content-disposition: attachment` ✅
- 注：`www.kuaishou.com/f/短链` 有时效性，过期后返回"无法解析快手视频 ID"（属链接失效，非代码缺陷）

### 2. bilibili
- 测试链接：`https://www.bilibili.com/video/BV1mJuB6jEDj`（完整 URL）与 `b23.tv` 短链
- 解析：HTTP 200，0.02s → 标题《船新版本新宝岛！这个联动怎么说？》，作者"不齐舞团"，直链 `upos-sz-mirrorhw.bilivideo.com/...`（13.7MB）
- 播放代理：HTTP 206，`video/mp4`，`content-range: bytes 0-1048575/13745814` ✅
- 下载：HTTP 206 + `content-disposition: attachment` ✅
- 防盗链：bilivideo.com 需 `Referer: bilibili.com`，MediaProxy 已正确处理（此前修复）

### 3. 小红书
- 测试链接：`https://www.xiaohongshu.com/explore/69bbb74c00000000220253a5?xsec_token=...`（带新鲜 token）
- 解析：HTTP 200，0.7s → 标题"aitoearn：全平台内容营销工具"，作者"珉绮"，直链 `sns-video-qc.xhscdn.com/...mp4?sign=...`（1.46MB）
- 播放代理：HTTP 206，`video/mp4` ✅；下载：HTTP 206 + attachment ✅
- 防盗链：xhscdn.com 需 `Referer: xiaohongshu.com`，MediaProxy 已处理（此前修复）
- **重要限制**：xsec_token 时效性极强（数小时~数天），过期后解析返回"解析失败，未找到有效内容"。用户需使用 App 内"复制链接"得到的**新鲜**分享链接。已用过期 token（2024-11）与新鲜 token 分别验证。

---

## 二、未通过的平台（原因与已做的代码修复）

### 4. 抖音 douyin.com
现象：`/shipin/7608765510318065727` 解析失败（HTTP 500 业务错误"解析视频信息失败，视频可能已删除或接口暂时失效"）。

根因（三层）：
1. **主路径失效**：抖音分享页（www.iesdouyin.com/share/video）已改版，`window._ROUTER_DATA` 不再含 `videoInfoRes` 视频数据（桌面/移动端视频页均为 72914B 反爬 JS 壳，无 SSR 数据）。
2. **代码缺陷（已修复）**：`DouyinParser` 的 `parse()` 调用了不存在的 `fetchAwemeDetail() / fetchLegacy()` 方法 → 必然 PHP `Call to undefined method` 500。这是本次修复的重点。
3. **平台风控**：`www.douyin.com/aweme/v1/web/aweme/detail/` 无签名返回 200 空 body；加 a_bogus 签名后返回 `filter_reason: "core_dep"`（环境校验不通过，缺 webid/verifyFp 等浏览器环境参数）。上游参考项目 qianxunbainian/jiexi 已放弃纯 PHP 方案、改用 Cloudflare Worker 解析抖音。

本次修复（uncommitted）：
- `app/Parsers/DouyinParser.php`
  - 补齐 `fetchAwemeDetail()`：ttwid Cookie + a_bogus 签名（本地 node 服务 `scripts/a_bogus_server.js`，端口 9876）+ aweme/detail API
  - 补齐 `fetchLegacy()`：iesdouyin iteminfo 旧接口兜底
  - `fetchTtwid()`：从 `live.douyin.com` 响应 Set-Cookie 提取 ttwid（原 `ttwid.bytedance.com/union/register` 注册接口已失效：404 / "parse params fail"）
  - 支持 `DOUYIN_COOKIE` 环境变量注入浏览器 Cookie（可显著提升通过风控概率）
- `config/parser.php`：douyin 段新增 `cookie` 配置项
- `.env.example`：追加 `DOUYIN_COOKIE` 说明与 a_bogus 签名服务启动方法（`cd scripts && node a_bogus_server.js`）

验证：修复后解析不再崩溃，三级策略（分享页 → 签名 API → legacy）依次执行后返回明确业务错误；签名服务已实测可生成 188 字符 a_bogus。

### 5. 微信视频号 channels.weixin.qq.com
现象：`?vid=...` 分享链接解析失败（HTTP 500"视频数据解析失败"）。

根因：
- 解析器调用的 API `https://channels.weixin.qq.com/cgi-bin/mmfinder-bin/feeds/video`（POST `{"vid":...}`）已失效：实测 POST 返回 `Cannot POST /cgi-bin/mmfinder-bin/feeds/video`，GET 返回"视频号助手"SPA 前端壳。
- 分享页 `readtemplate?t=weixin_video&vid=...` 对无效/过期 vid 也回退到 SPA 壳，无 SSR 数据。
- 公开网络无 2026 年可用的视频号解析方案可参考（接口均需登录态/内部参数）。

结论：视频号解析器代码结构完整（ID 提取、错误处理均正确），但数据源端点已失效，**需获取真实视频号分享链接 + 找到新的可用接口后才能修复**。当前保留代码不动，如实上报。

---

## 三、本次代码改动清单（均未提交，等待 push 指令）

| 文件 | 改动 |
|---|---|
| `app/Parsers/BilibiliParser.php` | extractBvid：正则 `BV\w+` 无捕获组却读 `$m[1]` → 改为 `(BV\w+)`，修复完整 URL 解析 400 |
| `app/Parsers/DouyinParser.php` | 补齐 fetchAwemeDetail / fetchLegacy / fetchTtwid / signQuery（消除必然 500） |
| `config/parser.php` | douyin 段新增 `cookie`（读 `DOUYIN_COOKIE`） |
| `.env.example` | 追加 DOUYIN_COOKIE 与 a_bogus 服务说明（保持原 GBK 编码） |

## 四、复测方法

```bash
# 1. 启动本地环境（两窗口）
node local-gateway.js          # 窗口 A：HTTP→FastCGI 网关（127.0.0.1:8080）
php-cgi -b 127.0.0.1:9000      # 窗口 B：PHP FastCGI（php 便携版在 E:\internet\workbuddy\jiexi\php\）
# 2. 抖音签名服务（可选，仅抖音需要）
cd scripts && node a_bogus_server.js
# 3. 测试
curl "http://127.0.0.1:8080/api/v1/parse?url=<urlencode 平台分享链接>"
```

## 五、遗留事项

1. 抖音：如需在本项目内恢复解析，两条路线——(a) 部署 Cloudflare Worker 做签名+代理（上游方案）；(b) 用户配置 `DOUYIN_COOKIE` 后重测（有登录态时接口接受度更高）。
2. 小红书：测试/使用时必须用 App 分享的新鲜链接（xsec_token 过期即失败）。
3. 视频号：等待真实分享链接样本或新接口方案后再修复。
4. 游戏分析（讲稿输出）：demo 模式可用；真实视频抽帧分析需部署到 Nginx/Apache + PHP-FPM 环境（Windows `php -S`/FastCGI 下 ffmpeg 抽帧会中断服务，为已知限制）。

---

# 二轮修复记录（2026-08-14，用户实测反馈 3 项）

## 1. 小红书短链 xhslink.cn 报"不支持的视频平台" → 已修复并验证

根因：`VideoParser::PLATFORMS` 仅收录 `xhslink.com`，缺 `xhslink.cn`；且 `HttpClient::getLocation` 只用 HEAD 请求，而 xhslink.cn 对 HEAD 返回 404、仅 GET 才 302，导致短链重定向拿不到。

修复：
- `app/Services/VideoParser.php`：小红书 domains 增加 `xhslink.cn`。
- `app/Utils/HttpClient.php`：`getLocation()` 先 HEAD、失败（非 3xx）自动回退 GET（只收响应头、丢弃 body）。此修复同样惠及快手 `www.kuaishou.com/f/` 短链。

实测（`https://xhslink.cn/o/3hYQJfxe9m4`）：
- 解析 HTTP 200 → 标题《谁懂啊！粉白配色真的好甜！超级喜欢》，作者"刘圆圆888"，视频直链 sns-video-v2.xhscdn.com。
- 播放代理 HTTP 206，content-type video/mp4，content-range bytes 0-262143/2125495 ✅

## 2. 小游戏分析：抖音链接"无效的视频链接" / "下载失败 HTTP 403" → 已修复并验证

根因（两层）：
- 前端 `analyzeGame()` 直接用整段分享文本做 URL → 校验失败报"无效的视频链接"。
- 下载环节 `HttpClient::request($cdnUrl, null, [], 3)` 空 headers 直连抖音 CDN → 无 Referer 被 403。

修复：
- `public/app.php`：`analyzeGame` 改用 `extractUrl()` 从分享文本自动提取 URL。
- `app/Services/GameAnalyzer.php`：下载时带浏览器 UA + 平台防盗链 Referer（复用 `MediaProxy::refererForHost()`）；下载后校验文件头魔数（ftyp/FLV/EBML/TS）确认真的是视频；平台解析失败时给出针对性提示（抖音提示配置 DOUYIN_COOKIE）；后端同样支持从整段分享文本兜底提取 URL。
- `app/Services/MediaProxy.php`：把平台 CDN Referer 映射提取为公共方法 `refererForHost()`（播放代理与游戏分析下载共用同一套映射）。

实测（`https://v.douyin.com/Qeizqz8ltNk/`，"过五关斩六将小游戏"视频）：
- 整段分享文本粘贴（"1.58 复制打开抖音…https://v.douyin.com/Qeizqz8ltNk/ :3pm…"）→ HTTP 200。
- 纯 URL → HTTP 200。时长 24.7s，抽帧 12 张，标题《下一步到底咋操作 #过五关斩六将小游戏…》，讲稿沿时间轴生成。

## 3. 快手分析：只抽 4 帧、讲稿与视频内容无关 → 已增强并验证

根因：启发式 Provider 只看场景变化阈值，desc 全是与内容无关的固定模板（"开局先摸两下"），从不引用标题/时间轴；且抽帧间隔 3s，短视频只出 3~4 帧。

修复（三层）：
- **讲稿按内容流程梳理**：`app/Services/Vision/LocalHeuristicProvider.php` 重写——开场直接引用视频标题（平台解析出的文案）→ 主体沿时间轴逐段叙述（"开场第 0 秒…大约 2 秒处…临近结尾…"）→ 收尾总结画面阶段数 + 互动提问。每句都锚定画面时间段，与内容强相关。
- **OpenAI 视觉模式**（配置 Key 后启用）：`OpenAIVisionProvider.php` prompt 重写——要求 AI 先判断视频主题/类型 → 梳理内容流程（玩法步骤/情节推进/高光翻车/收尾）→ 基于画面真实内容写播音稿，禁止"操作丝滑"类套话，画面文字/关卡名尽量写入；标题/作者注入请求。
- **抽帧加密**：`config/app.php` 默认 frame_interval 3→2s、max_frames 10→12；`GameAnalyzer` 短视频自适应采样（约每 10 秒 10 帧封顶，6 秒视频也能抽 6 帧）。

实测（快手《伊朗称击落3架美军战机?》6.2s 视频）：
- 抽帧 3 → 6 张；讲稿开头"今天看的这段视频，标题叫「伊朗称击落3架美军战机?」"，主体按 0s/2s/3s 时间轴推进，结尾总结"画面变化了 4 个阶段"。

## 本轮改动文件（均未提交，等待 push 指令）

| 文件 | 改动 |
|---|---|
| app/Services/VideoParser.php | 小红书 domains + xhslink.cn |
| app/Utils/HttpClient.php | getLocation HEAD→GET 回退 |
| app/Services/MediaProxy.php | 提取 refererForHost() 公共映射 |
| app/Services/GameAnalyzer.php | 下载带 UA/Referer、视频内容校验、元信息注入、自适应抽帧、分享文本兜底 |
| app/Services/Vision/LocalHeuristicProvider.php | 重写为按标题+时间轴梳理流程的讲稿 |
| app/Services/Vision/OpenAIVisionProvider.php | prompt 增强：主题判断→流程梳理→基于画面的播音稿 |
| config/app.php | 抽帧默认值加密 |
| public/app.php | analyzeGame 用 extractUrl 提取分享文本 URL |

## 说明

- 讲稿与内容"真正相关"的最强解法是 OpenAI 视觉模式：在 `.env` 配置 `GAME_ANALYSIS_PROVIDER=openai` + `OPENAI_API_KEY` 后，AI 会逐帧读画面、按实际内容梳理流程写稿（本地启发式模式受限于不看画面，已做到"引用标题 + 按时间轴叙述"的上限）。
- 抖音此次实测解析成功（该短链当前可用）；若遇风控时段解析失败，会带 Referer/UA 直连下载并给出明确提示，不再是无意义的 403。
