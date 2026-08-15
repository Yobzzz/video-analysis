<!doctype html>
<html lang="zh">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title>Video Analysis · Short Video Tool</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%231b4a49'/%3E%3Cpath d='M9 9h6v6H9zM17 17h6v6h-6zM17 9h6v6h-6zM9 17h6v6H9z' fill='%23f5c85d'/%3E%3C/svg%3E">
<style>
/* ===== Zibll-style Design System for Video Analysis ===== */
:root {
  --theme: #f04494;
  --theme-soft: rgba(240, 68, 148, 0.08);
  --theme-shadow: rgba(253, 83, 161, 0.25);
  --key: #333;
  --ink: #4e5358;
  --muted: #777;
  --muted2: #999;
  --muted3: #b1b1b1;
  --muted4: #d2d2d2;
  --bg: #f5f6f7;
  --paper: #fff;
  --soft: #f6f7f8;
  --line: rgba(50, 50, 50, 0.06);
  --line-strong: rgba(50, 50, 50, 0.12);
  --danger: #e23535;
  --danger-soft: #fff0ee;
  --success: #1e9c63;
  --success-soft: #d8f5e6;
  --shadow: 0 0 10px rgba(116, 116, 116, 0.08);
  --shadow-hover: 0 4px 18px rgba(116, 116, 116, 0.12);
  --r: 8px;
  --rm: 5px;
  --motion: 0.25s ease;
}
:root.dark {
  --key: #f8fafc;
  --ink: #e5eef7;
  --muted: #b4b6bb;
  --muted2: #888a8f;
  --muted3: #636469;
  --muted4: #43454a;
  --bg: #292a2d;
  --paper: #323335;
  --soft: #37383a;
  --line: rgba(114, 114, 114, 0.1);
  --line-strong: rgba(114, 114, 114, 0.2);
  --danger: #ff9a8b;
  --danger-soft: #4d2e2b;
  --success: #4fd093;
  --success-soft: #183b36;
  --theme-soft: rgba(240, 68, 148, 0.14);
  --shadow: 0 0 10px rgba(0, 0, 0, 0.12);
  --shadow-hover: 0 4px 18px rgba(0, 0, 0, 0.2);
}
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
/* Reset & Base */
* { box-sizing: border-box; }
[hidden] { display: none !important; }
html, body { margin: 0; min-height: 100%; }
body {
  background: var(--bg);
  color: var(--ink);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "Helvetica Neue", sans-serif;
  font-size: 15px;
  line-height: 1.55;
  transition: background var(--motion), color var(--motion);
  -webkit-font-smoothing: antialiased;
}
button, input, textarea, a { font: inherit; }
button { cursor: pointer; }
a { color: inherit; text-decoration: none; }
:focus-visible { outline: 3px solid var(--theme-shadow); outline-offset: 2px; }
/* Header */
.topbar {
  background: var(--paper);
  color: var(--ink);
  border-bottom: 1px solid var(--line);
  position: sticky;
  top: 0;
  z-index: 100;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}
:root.dark .topbar { background: rgba(50, 51, 53, 0.85); }
.topbar-inner {
  width: min(1200px, calc(100% - 40px));
  min-height: 64px;
  margin: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}
.brand { display: flex; align-items: center; gap: 10px; min-width: 0; }
.brand-mark {
  width: 36px; height: 36px; flex: 0 0 36px;
  display: grid; place-items: center;
  background: linear-gradient(135deg, var(--theme), #ff8aab);
  color: #fff;
  border-radius: var(--r);
  font-size: 18px; font-weight: 900;
}
.brand-name { margin: 0; font-size: 17px; line-height: 1.1; font-weight: 800; color: var(--key); }
.brand-sub { margin: 2px 0 0; color: var(--muted2); font-size: 12px; }
.topbar-actions { display: flex; align-items: center; gap: 12px; }
.online { display: inline-flex; align-items: center; gap: 7px; color: var(--muted); font-size: 13px; }
.online-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 0 3px var(--success-soft);
}
.theme-toggle {
  width: 34px; height: 34px; padding: 0;
  display: grid; place-items: center;
  border: 1px solid var(--line-strong); border-radius: var(--r);
  background: transparent; color: var(--muted);
  transition: all var(--motion);
}
.theme-toggle:hover { background: var(--soft); color: var(--ink); }
/* Main Layout */
.workspace {
  width: min(1200px, calc(100% - 40px));
  margin: auto;
  padding: 40px 0 60px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 24px;
  align-items: start;
}
.main-column { min-width: 0; }
/* Intro */
.intro { margin: 0 0 28px; }
.eyebrow {
  display: inline-flex; align-items: center; gap: 8px;
  color: var(--theme); font-size: 12px; font-weight: 700;
  letter-spacing: 0.06em;
}
.eyebrow{display:none}
.intro h1 {
  margin: 12px 0 10px;
  font-size: clamp(28px, 3.5vw, 42px);
  line-height: 1.2; font-weight: 900;
  color: var(--key);
  letter-spacing: -0.02em;
}
.intro h1 em { color: var(--theme); font-style: normal; }
.intro p { max-width: 560px; margin: 0; color: var(--muted); font-size: 15px; }
/* Panel / Card */
.panel {
  background: var(--paper);
  border: 1px solid var(--line);
  border-radius: var(--r);
  box-shadow: var(--shadow);
  transition: background var(--motion), border-color var(--motion), box-shadow var(--motion);
}
.ingest-panel { padding: 28px; }
.panel-heading {
  align-items: flex-start; justify-content: space-between;
  gap: 18px; margin-bottom: 24px;
}
.panel-kicker {
  margin: 0 0 4px; color: var(--theme);
  font-size: 11px; font-weight: 800;
  letter-spacing: 0.08em; text-transform: uppercase;
}
.panel-title { margin: 0; font-size: 19px; line-height: 1.2; font-weight: 800; color: var(--key); }
.panel-note { margin: 6px 0 0; color: var(--muted); font-size: 13px; }
/* Mode Switch */
.mode-switch {
  display: inline-flex; gap: 2px; padding: 3px;
  background: var(--soft); border: 1px solid var(--line);
  border-radius: var(--r); flex-shrink: 0;
}
.mode-btn {
  min-height: 32px; padding: 0 14px; border: 0;
  border-radius: var(--rm); background: transparent;
  color: var(--muted); font-size: 13px; font-weight: 700;
  transition: all var(--motion);
}
.mode-btn.active {
  background: var(--theme); color: #fff;
  box-shadow: 0 2px 8px var(--theme-shadow);
}
/* Form Elements */
.field-label { display: block; margin-bottom: 8px; color: var(--ink); font-size: 13px; font-weight: 700; }
.input-row { display: grid; grid-template-columns: minmax(0, 1fr) 44px 116px; gap: 9px; }
.input-wrap { min-width: 0; }
.input-wrap input, .batch-input {
  width: 100%; border: 1px solid var(--line-strong);
  border-radius: var(--r); background: var(--soft);
  color: var(--ink); outline: none; font-size: 14px;
  transition: all var(--motion);
}
.input-wrap input { height: 48px; padding: 0 14px; }
.input-wrap input::placeholder, .batch-input::placeholder { color: var(--muted3); }
.input-wrap input:focus, .batch-input:focus {
  background: var(--paper); border-color: var(--theme);
  box-shadow: 0 0 0 3px var(--theme-soft);
}
.batch-input { min-height: 142px; padding: 13px 14px; resize: vertical; line-height: 1.6; }
/* Buttons */
.icon-btn, .btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  border-radius: var(--r); font-size: 13px; font-weight: 700;
  transition: all var(--motion);
}
.icon-btn {
  width: 44px; height: 48px; padding: 0;
  border: 1px solid var(--line-strong); background: var(--paper); color: var(--theme);
}
.icon-btn:hover { border-color: var(--theme); background: var(--theme-soft); }
.btn {
  min-height: 42px; padding: 0 16px;
  border: 1px solid var(--line-strong); background: var(--paper); color: var(--ink);
}
.btn:hover { border-color: var(--muted3); background: var(--soft); }
.btn:disabled, .icon-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-primary {
  border-color: var(--theme); background: var(--theme); color: #fff;
}
.btn-primary:hover:not(:disabled) {
  background: #e03080; border-color: #e03080;
  transform: translateY(-1px);
  box-shadow: 0 4px 14px var(--theme-shadow);
}
.btn-accent {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff; border: none; font-weight: 600;
}
.btn-accent:hover:not(:disabled) {background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; border-color: transparent !important; color: #fff !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(102,126,234,0.3);
}
.btn-accent:disabled {
  background: var(--line); color: var(--muted); cursor: not-allowed;
}
.process-modal{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;z-index:9999}.process-modal-bg{position:absolute;inset:0;background:rgba(0,0,0,0.35);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px)}.process-modal-box{position:relative;background:#f8f8fa;border:1px solid #e0e0e0;border-radius:16px;padding:22px 26px;width:90%;max-width:380px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,0.1)}.dark .process-modal-box{background:#2a2a30;border-color:#3a3a40}.process-modal-box h3{font-size:15px;margin:0 0 14px;color:var(--key)}.process-bar-wrap{height:6px;background:var(--soft);border-radius:3px;overflow:hidden;margin-bottom:10px}.process-bar{height:100%!important;width:0;display:block!important;background:linear-gradient(90deg,#667eea,#764ba2)!important;border-radius:3px;transition:width 0.5s ease}.process-status{font-size:12px;color:var(--muted);margin:0 0 14px}.process-done p{font-size:13px;color:#10a77a;font-weight:600;margin:0 0 10px}.process-done .btn{display:inline-flex;gap:6px;margin-bottom:6px}.process-md5{font-size:10px;color:var(--muted2);margin:0;word-break:break-all}.process-close-btn{padding:6px 18px;border:1px solid var(--line-strong);border-radius:var(--r);background:transparent;color:var(--muted);font-size:12px;cursor:pointer;transition:all var(--motion);margin-top:6px}.process-close-btn:hover{background:var(--soft);color:var(--ink)}
.btn-teal {
  border-color: var(--theme); background: var(--theme); color: #fff;
}
.btn-teal:hover:not(:disabled) {
  background: #e03080; border-color: #e03080; transform: translateY(-1px);
}
:root.dark .btn-teal, :root.dark .mode-btn.active { color: #fff; }
.btn-quiet { background: transparent; border-color: transparent; color: var(--muted); }
.btn-quiet:hover { color: var(--ink); background: var(--soft); }
.batch-actions { display: flex; gap: 8px; margin-top: 12px; }
.input-hint { margin: 10px 0 0; color: var(--muted2); font-size: 12px; }
/* Platform Strip */
.platform-strip {
  display: flex; flex-wrap: wrap; gap: 8px;
  margin-top: 22px; padding-top: 18px;
  border-top: 1px solid var(--line);
}
.platform-chip {
  display: inline-flex; align-items: center; gap: 6px;
  min-height: 26px; padding: 0 10px;
  border: 1px solid var(--line); border-radius: 100px;
  background: var(--soft); color: var(--muted); font-size: 12px;
}
.platform-chip i { width: 6px; height: 6px; border-radius: 50%; background: var(--chip); }
/* State / Error */
.state-row {
  display: flex; align-items: center; gap: 9px;
  min-height: 38px; margin-top: 14px; padding: 0 2px;
  color: var(--muted); font-size: 13px;
}
.spinner {
  width: 16px; height: 16px; border: 2px solid var(--line-strong);
  border-top-color: var(--theme); border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.error-alert {
  display: flex; align-items: flex-start; gap: 9px;
  margin-top: 14px; padding: 12px 14px;
  border: 1px solid #f2b0a5; border-radius: var(--r);
  background: var(--danger-soft); color: var(--danger); font-size: 13px;
}
.error-alert svg { flex: 0 0 auto; margin-top: 2px; }
/* Result Panel */
.result-panel, .batch-panel { margin-top: 28px; overflow: hidden; padding: 0 28px; }
.result-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(280px, 1fr);
  min-height: 320px;
}
.result-video-wrap {
  min-height: 320px; padding: 22px;
  background: #1a1a2e;
  display: grid; place-items: center;
}
.video-fallback{position:absolute;inset:50% 0 0;text-align:center;font-size:13px;color:var(--muted);padding:12px}
:root.dark .result-video-wrap { background: #1a1a24; }
.result-video-wrap video {
  display: block; width: 100%; max-height: 470px;
  aspect-ratio: 16/10; object-fit: contain;
  border-radius: var(--rm); background: #0f0f1a;
}
:root.dark .result-video-wrap video { background: #0f0f17; }
.result-info { padding: 25px; display: flex; flex-direction: column; min-width: 0; }
.result-tag {
  display: inline-flex; align-self: flex-start;
  padding: 4px 10px; background: var(--theme-soft);
  color: var(--theme); border-radius: var(--rm);
  font-size: 12px; font-weight: 700;
}
.result-title {
  margin: 14px 0 7px; font-size: 20px;
  line-height: 1.35; font-weight: 800; color: var(--key);
  overflow-wrap: anywhere;
}
.result-meta { margin: 0; color: var(--muted); font-size: 13px; }
.creator-row { display: flex; align-items: center; gap: 10px; margin-top: 20px; }
.avatar {
  width: 36px; height: 36px; border-radius: 50%;
  border: 1px solid var(--line); object-fit: cover; background: var(--soft);
}
.creator-label { color: var(--muted2); font-size: 11px; }
.creator-name { margin-top: 1px; font-size: 14px; font-weight: 700; color: var(--ink); }
.result-stats { display: flex; gap: 9px; margin-top: 22px; }
.result-stat {
  min-width: 88px; padding: 10px 12px;
  border-top: 2px solid var(--theme);
  background: var(--theme-soft);
  border-radius: 0 0 var(--rm) var(--rm);
}
.result-stat dt { color: var(--muted2); font-size: 11px; }
.result-stat dd { margin: 2px 0 0; font-size: 15px; font-weight: 800; color: var(--key); }
.result-actions {
  display: flex; flex-wrap: wrap; gap: 8px;
  margin-top: auto; padding-top: 24px;
}
.result-actions .btn { text-decoration: none; }
.result-actions .btn-copy-row { flex-basis: 100%; display: flex; gap: 8px; margin-top: 0; }
/* Batch Panel */
.section-heading {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; padding: 20px 22px 16px;
  border-bottom: 1px solid var(--line);
}
.section-heading h2 { margin: 0; font-size: 17px; color: var(--key); }
.section-heading p { margin: 4px 0 0; color: var(--muted); font-size: 12px; }
.batch-summary { color: var(--muted); font-size: 12px; }
.batch-list { display: grid; }
.batch-empty { padding: 28px 22px; color: var(--muted2); font-size: 13px; }
.batch-item {
  display: grid; grid-template-columns: 108px minmax(0, 1fr) auto;
  gap: 13px; align-items: center;
  padding: 14px 22px; border-bottom: 1px solid var(--line);
}
.batch-item:last-child { border-bottom: 0; }
.batch-thumb {
  width: 108px; height: 64px; object-fit: cover;
  border-radius: var(--rm); background: var(--soft);
}
.batch-content { min-width: 0; }
.batch-title {
  margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  font-size: 14px; font-weight: 700; color: var(--key);
}
.batch-meta {
  margin: 3px 0 0; color: var(--muted); font-size: 12px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.batch-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 6px; margin: 0; }
.batch-actions .btn { min-height: 34px; padding: 0 10px; font-size: 12px; }
.batch-item.is-loading .batch-thumb {
  background: var(--line-strong);
  animation: pulse 1.3s ease-in-out infinite alternate;
}
.batch-item.is-loading .batch-title {
  width: 58%; height: 16px; background: var(--line-strong);
  color: transparent; border-radius: 3px;
}
@keyframes pulse { to { opacity: 0.55; } }
.batch-item.is-error { background: var(--danger-soft); }
.batch-item.is-error .batch-title { color: var(--danger); }
/* Sidebar */
.sidebar { display: grid; gap: 16px; position: sticky; top: 84px; }
.side-panel { padding: 18px; }
.side-heading {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 10px; margin-bottom: 15px;
}
.side-heading h2 { margin: 0; font-size: 16px; color: var(--key); }
.side-heading p { margin: 4px 0 0; color: var(--muted); font-size: 12px; }
.count-badge {
  min-width: 26px; height: 22px; padding: 0 7px;
  display: inline-grid; place-items: center;
  border-radius: var(--rm); background: var(--theme);
  color: #fff; font-size: 12px; font-weight: 800;
}
.history-list { display: grid; gap: 8px; }
.history-empty {
  padding: 28px 10px 22px; border-top: 1px dashed var(--line-strong);
  text-align: center; color: var(--muted);
}
.history-empty-icon {
  width: 40px; height: 40px; margin: 0 auto 10px;
  display: grid; place-items: center;
  border: 1px solid var(--line); border-radius: var(--r);
  color: var(--theme); background: var(--soft);
}
.history-empty strong { display: block; color: var(--key); font-size: 13px; }
.history-empty span { display: block; margin-top: 3px; font-size: 12px; }
.history-entry {
  display: grid; grid-template-columns: 62px minmax(0, 1fr) auto;
  gap: 9px; align-items: center; padding: 8px;
  border: 1px solid var(--line); border-radius: var(--r);
  background: var(--soft);
  transition: box-shadow var(--motion);
}
.history-entry:hover { box-shadow: var(--shadow-hover); }
.history-thumb {
  width: 62px; height: 42px; object-fit: cover;
  border-radius: var(--rm); background: var(--line-strong);
}
.history-thumb-placeholder{display:flex;align-items:center;justify-content:center;background:var(--soft);color:var(--muted2)}
.history-copy { min-width: 0; }
.history-title {
  margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  font-size: 12px; font-weight: 700; color: var(--key);
}
.history-meta {
  margin: 2px 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  color: var(--muted); font-size: 11px;
}
.history-actions { display: flex; gap: 2px; }
.history-actions button {
  width: 28px; height: 28px; padding: 0;
  display: grid; place-items: center; border: 0;
  border-radius: var(--rm); background: transparent; color: var(--muted);
  transition: all var(--motion);
}
.history-actions button:hover { background: var(--theme-soft); color: var(--theme); }
.side-divider { height: 1px; margin: 18px 0; background: var(--line); }
.quick-list { display: grid; gap: 10px; }
.quick-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; color: var(--muted); font-size: 13px; }
.quick-label { display: inline-flex; align-items: center; gap: 8px; }
.quick-label svg { color: var(--theme); }
.quick-value { color: var(--key); font-weight: 800; }
.clear-btn {
  min-height: 28px; padding: 0 8px; border: 0;
  border-radius: var(--rm); background: transparent;
  color: var(--muted); font-size: 12px;
  transition: all var(--motion);
}
.clear-btn:hover { background: var(--danger-soft); color: var(--danger); }
/* Footer */
.main-foot {
  margin-top: auto; padding: 32px 0 20px; text-align: center;
  color: var(--muted2); font-size: 12px;
}
/* Responsive */
@media (max-width: 980px) {
  .workspace { grid-template-columns: 1fr; }
  .sidebar { position: static; grid-template-columns: minmax(0, 1.2fr) minmax(260px, 0.8fr); align-items: start; }
}
@media (max-width: 720px) {
  .topbar-inner, .workspace { width: min(100% - 28px, 1200px); }
  .workspace { padding: 28px 0 40px; gap: 20px; }
  .intro h1 { font-size: 28px; }
  .ingest-panel { padding: 20px; }
  .panel-heading { flex-direction: column; gap: 13px; }
  .result-layout { grid-template-columns: 1fr; }
  .result-video-wrap { min-height: 0; padding: 14px; }
  .result-info { padding: 20px; }
  .sidebar { grid-template-columns: 1fr; }
}
@media (max-width: 520px) {
  .topbar-inner { min-height: 56px; }
  .brand-sub, .online { display: none; }
  .input-row { grid-template-columns: minmax(0, 1fr) 44px; }
  .input-row .btn-primary { grid-column: 1 / -1; }
  .batch-item { grid-template-columns: 76px minmax(0, 1fr); padding: 12px 14px; }
  .batch-thumb { width: 76px; height: 50px; }
  .batch-actions { grid-column: 1 / -1; justify-content: flex-start; }
  .result-actions .btn { flex: 1 1 calc(50% - 8px); }
}
.en{display:none !important}html[lang=en] .zh{display:none !important}html[lang=en] .en{display:inline !important}.lang-btn{width:34px;height:34px;padding:0;display:grid;place-items:center;border:1px solid var(--line-strong);border-radius:var(--r);background:transparent;color:var(--muted);font-size:13px;font-weight:700;transition:all var(--motion);cursor:pointer}.lang-btn:hover{background:var(--soft);color:var(--ink)}
/* ===== AI 视觉模型配置（口播稿分析） ===== */
.api-config{margin-top:14px;border:1px solid var(--line-strong);border-radius:var(--r);background:var(--soft);overflow:hidden}
.api-config>summary{list-style:none;cursor:pointer;padding:12px 16px;display:flex;align-items:center;gap:10px;user-select:none}
.api-config>summary::-webkit-details-marker{display:none}
.api-config>summary .api-config-icon{width:18px;height:18px;color:var(--theme);flex-shrink:0}
.api-config>summary .api-config-title{font-size:14px;font-weight:700;color:var(--ink);flex:1}
.api-config>summary .api-status{font-size:12px;font-weight:600;padding:3px 10px;border-radius:999px;white-space:nowrap}
.api-status.is-on{background:var(--success-soft);color:var(--success)}
.api-status.is-off{background:var(--soft);color:var(--muted);border:1px solid var(--line-strong)}
.api-config[open]>summary{border-bottom:1px solid var(--line)}
.api-config-body{padding:14px 16px;display:flex;flex-direction:column;gap:12px}
.api-config-row{display:flex;flex-direction:column;gap:6px}
.api-config-row label{font-size:12px;font-weight:600;color:var(--muted)}
.api-config-row input{height:42px;padding:0 12px;border:1px solid var(--line-strong);border-radius:var(--rm);background:var(--paper);color:var(--ink);font-size:13px;font-family:inherit}
.api-config-row input:focus{outline:none;border-color:var(--theme);box-shadow:0 0 0 3px var(--theme-soft)}
.api-config-hint{font-size:12px;color:var(--muted);line-height:1.5;margin:0}
.api-config-hint code{background:var(--soft);padding:1px 5px;border-radius:3px;font-size:11px;color:var(--ink)}
.api-config-actions{display:flex;gap:8px;flex-wrap:wrap}
.api-config-actions .btn{height:38px;padding:0 16px;font-size:13px}
.api-paste-area{width:100%;min-height:72px;padding:10px 12px;border:1px dashed var(--line-strong);border-radius:var(--rm);background:var(--paper);color:var(--ink);font-size:12px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;line-height:1.5;resize:vertical}
.api-paste-area:focus{outline:none;border-color:var(--theme);border-style:solid;box-shadow:0 0 0 3px var(--theme-soft)}
.api-paste-area::placeholder{color:var(--muted3)}
.api-config-row .paste-hint{font-size:11px;color:var(--muted2);margin:0}
.api-config-link{font-size:12px;color:var(--theme);text-decoration:none;cursor:pointer;background:none;border:none;padding:0;font-family:inherit}
.api-config-link:hover{text-decoration:underline}
/* ===== 口播稿分析 ===== */
.tabbar{width:min(1200px,calc(100% - 40px));margin:0 auto;padding:14px 0 0;display:flex;gap:8px}
.tab{appearance:none;border:1px solid var(--line-strong);background:var(--paper);color:var(--muted);padding:9px 16px;border-radius:999px;font-size:14px;font-weight:600;transition:all var(--motion);cursor:pointer}
.tab:hover{color:var(--ink);border-color:var(--theme)}
.tab.is-active{background:linear-gradient(135deg,var(--theme),#ff8aab);color:#fff;border-color:transparent;box-shadow:var(--shadow)}
.file-btn{display:inline-flex;align-items:center;gap:6px}
.file-name{font-size:12px;color:var(--muted);align-self:center;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.demo-toggle{display:inline-flex;align-items:center;gap:6px;margin-top:10px;color:var(--muted);font-size:13px;cursor:pointer}
.frames-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;margin:0}
.frame-thumb{position:relative;border-radius:var(--r);overflow:hidden;background:var(--soft);aspect-ratio:16/9;border:1px solid var(--line)}
.frame-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.frame-thumb .t-badge{position:absolute;left:6px;bottom:6px;background:rgba(0,0,0,.62);color:#fff;font-size:11px;padding:2px 6px;border-radius:4px}
.timeline{display:flex;flex-direction:column;gap:10px;margin:14px 0}
.tl-item{display:flex;gap:10px;align-items:flex-start}
.tl-dot{flex:0 0 10px;width:10px;height:10px;border-radius:50%;margin-top:6px;background:var(--muted3)}
.tl-item.operation .tl-dot{background:#3b82f6}
.tl-item.trap .tl-dot{background:#f59e0b}
.tl-item.fail .tl-dot{background:#ef4444}
.tl-item.clear .tl-dot{background:#22c55e}
.tl-body{flex:1;min-width:0}
.tl-head{display:flex;align-items:center;gap:8px}
.tl-label{font-size:12px;font-weight:700;padding:1px 8px;border-radius:999px;background:var(--soft);color:var(--ink)}
.tl-item.operation .tl-label{background:rgba(59,130,246,.14);color:#2563eb}
.tl-item.trap .tl-label{background:rgba(245,158,11,.16);color:#b45309}
.tl-item.fail .tl-label{background:rgba(239,68,68,.16);color:#dc2626}
.tl-item.clear .tl-label{background:rgba(34,197,94,.16);color:#16a34a}
.tl-time{font-size:12px;color:var(--muted2)}
.tl-desc{margin:2px 0 0;color:var(--ink);font-size:14px;line-height:1.5}
.script-block{margin-top:8px}
.script-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.script-head>span:first-child{font-weight:700;color:var(--key)}
.script-box{width:100%;min-height:240px;resize:vertical;padding:14px;border:1px solid var(--line-strong);border-radius:var(--r);background:var(--paper);color:var(--ink);font-size:15px;line-height:1.8;font-family:inherit}
.script-box:focus{outline:none;border-color:var(--theme)}
.script-actions{display:flex;gap:10px;margin-top:10px}</style>
</head>
<body>
<header class="topbar"><div class="topbar-inner"><div class="brand"><span class="brand-mark" aria-hidden="true">V</span><div><p class="brand-name">Video Analysis</p><p class="brand-sub"><span class="zh">短视频无水印工作台</span><span class="en">Short Video Workstation</span></p></div></div><div class="topbar-actions"><span class="online"><i class="online-dot"></i><span class="zh">在线</span><span class="en">Online</span></span><button id="lang-toggle" class="lang-btn" type="button" title="Switch to English">EN</button><button id="theme-toggle" class="theme-toggle" type="button" title="Toggle theme" aria-label="Toggle theme"></button></div></div></header>
<nav class="tabbar" id="tabbar" role="tablist">
  <button class="tab is-active" type="button" data-view="parse" role="tab"><span class="zh">视频解析</span><span class="en">Parse</span></button>
  <button class="tab" type="button" data-view="game" role="tab"><span class="zh">口播稿分析</span><span class="en">Script</span></button>
</nav>
<div id="parse-view">
<main class="workspace">
  <div class="main-column">
    <section class="intro" aria-labelledby="page-title"><span class="eyebrow"></span><h1 id="page-title"><span class="zh">分享链接，<em>直接变成</em>可用视频。</span><span class="en">Share a link, <em>get</em> a usable video.</span></h1><p><span class="zh">粘贴短视频分享链接，快速整理出无水印视频。解析结果、下载和最近记录都在同一个工作区完成。</span><span class="en">Paste short video links to extract watermark-free videos. Results, downloads, and history — all in one workspace.</span></p></section>
    <section class="panel ingest-panel" aria-label="视频解析入口">
      <div class="panel-heading"><div><p class="panel-kicker"><span class="zh">导入链接</span><span class="en">Import link</span></p><h2 class="panel-title"><span class="zh">从一个链接开始</span><span class="en">Start with a Link</span></h2><p class="panel-note"><span class="zh">支持公开分享链接，不需要登录第三方平台。</span><span class="en">No login required. Works with public share links.</span></p></div></div>
      <div id="single-panel"><label id="mode-label" class="field-label" for="video-url"><span class="zh">视频分享链接</span><span class="en">Video Share Link</span></label><div class="input-row"><div class="input-wrap"><input id="video-url" type="url" data-zh="粘贴抖音、快手、B站、小红书、视频号链接" data-en="Paste Douyin/Kuaishou/Bilibili/RED/Channels links..." autocomplete="off" spellcheck="false"></div><button id="paste-btn" class="icon-btn" type="button" title="Paste from clipboard" aria-label="Paste from clipboard"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="3" width="8" height="4" rx="1"></rect><path d="M16 5h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2"></path></svg></button><button id="parse-btn" class="btn btn-primary" type="button"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg><span data-zh="开始解析" data-en="Parse">开始解析</span></button></div>
      </div>
            <p class="input-hint"><span class="zh">小提示：也可以直接粘贴包含文字的整段分享文案，系统会自动提取链接。</span><span class="en">Tip: Paste full share text — links are auto-extracted.</span></p>
      <div class="platform-strip" aria-label="支持平台"><span class="platform-chip" style="--chip:#2d6cdf"><i></i><span class="zh">抖音</span><span class="en">Douyin</span></span><span class="platform-chip" style="--chip:#10a77a"><i></i><span class="zh">快手</span><span class="en">Kuaishou</span></span><span class="platform-chip" style="--chip:#e86d94"><i></i><span class="zh">B站</span><span class="en">Bilibili</span></span><span class="platform-chip" style="--chip:#e64555"><i></i><span class="zh">小红书</span><span class="en">RED</span></span><span class="platform-chip" style="--chip:#f19a42"><i></i><span class="zh">皮皮虾</span><span class="en">Pipixia</span></span><span class="platform-chip" style="--chip:#5e83d7"><i></i><span class="zh">微博</span><span class="en">Weibo</span></span><span class="platform-chip" style="--chip:#4d9d91"><i></i><span class="zh">视频号</span><span class="en">Channels</span></span><span class="platform-chip" style="--chip:#8d6fc1"><i></i><span class="zh">更多平台</span><span class="en">More Platforms</span></span></div>
      <div id="loading" class="state-row" aria-live="polite" hidden><span class="spinner" aria-hidden="true"></span><span id="loading-text"><span class="zh">正在读取视频信息…</span><span class="en">Loading video info...</span></span></div>
      <div id="error" class="error-alert" role="alert" aria-live="assertive" hidden><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 16h.01"></path></svg><span id="error-text"></span></div>
    </section>
    <section id="result" class="panel result-panel" hidden aria-label="解析结果"><div class="result-layout"><div class="result-video-wrap"><video id="result-video" controls playsinline preload="metadata" crossorigin="anonymous"></video></div><div class="result-info"><span id="result-platform" class="result-tag"><span class="zh">解析完成</span><span class="en">Parse Complete</span></span><h2 id="result-title" class="result-title"></h2><p id="result-meta" class="result-meta"></p><div class="creator-row"><img id="result-avatar" class="avatar" alt="" hidden><div><div class="creator-label"><span class="zh">发布作者</span><span class="en">Author</span></div><div id="result-author" class="creator-name"></div></div></div><dl class="result-stats"><div class="result-stat"><dt><span class="zh">点赞</span><span class="en">Likes</span></dt><dd id="result-like">-</dd></div><div class="result-stat"><dt><span class="zh">发布时间</span><span class="en">Posted</span></dt><dd id="result-time">-</dd></div></dl><div class="result-actions"><a id="download-btn" class="btn btn-primary" download target="_blank" rel="noopener"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg><span data-zh="下载视频" data-en="Download">下载视频</span></a><div class="btn-copy-row"><button id="copy-btn" class="btn" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1"></path></svg><span data-zh="复制链接" data-en="Copy Link">复制链接</span></button><button id="copy-text-btn" class="btn" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg><span data-zh="复制文案" data-en="Copy Text">复制文案</span></button></div></div></div></div></section>
  </div>
  <aside class="sidebar"><section id="history" class="panel side-panel" aria-label="解析历史"><div class="side-heading"><div><h2><span class="zh">历史记录</span><span class="en">History</span></h2><p><span class="zh">只保存在当前浏览器</span><span class="en">Stored in Browser</span></p></div><span id="history-count" class="count-badge">0</span></div><div id="history-list" class="history-list"></div><div id="history-empty" class="history-empty"><div class="history-empty-icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v14H4z"></path><path d="M8 9h8"></path><path d="M8 13h5"></path></svg></div><strong><span class="zh">这里还没有解析记录</span><span class="en">No History Yet</span></strong><span><span class="zh">解析成功后会自动保存在这里</span><span class="en">Results appear here</span></span></div><button id="clear-history-btn" class="btn" type="button" style="width:100%;margin-top:12px"><span class="zh">清空历史</span><span class="en">Clear</span></button></section></aside>
</main>
</div>
<div id="game-view" hidden>
<main class="workspace">
  <div class="main-column">
    <section class="intro" aria-labelledby="game-page-title"><span class="eyebrow"></span><h1 id="game-page-title"><span class="zh">上传视频，<em>直接变成</em>口播稿。</span><span class="en">Upload a clip, <em>get</em> a voiceover script.</span></h1><p><span class="zh">粘贴链接或上传本地视频，AI 逐帧读懂画面，自动写出贴合内容的播音口播稿。</span><span class="en">Paste a link or upload a clip — AI reads every frame and writes a voiceover script.</span></p></section>
    <section class="panel ingest-panel" aria-label="口播稿分析入口">
      <div class="panel-heading"><div><p class="panel-kicker"><span class="zh">导入视频</span><span class="en">Import clip</span></p><h2 class="panel-title"><span class="zh">从视频开始</span><span class="en">Start with a Clip</span></h2><p class="panel-note"><span class="zh">支持本地视频文件，或粘贴抖音、快手、B站、小红书、视频号等平台链接。</span><span class="en">Works with local files and links from Douyin, Kuaishou, Bilibili, RED, Channels and more.</span></p></div></div>
      <div id="game-panel">
        <label class="field-label" for="game-url"><span class="zh">视频链接或本地文件</span><span class="en">Video link or local file</span></label>
        <div class="input-row">
          <div class="input-wrap"><input id="game-url" type="text" data-zh="粘贴视频链接，或点击右侧上传本地视频" data-en="Paste a video link, or upload a local clip" autocomplete="off" spellcheck="false"></div>
          <label id="game-file-label" class="btn btn-primary" for="game-file" title="Upload video"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 8 5-5 5 5"></path><path d="M5 21h14"></path></svg><span data-zh="上传" data-en="Upload">上传</span></label>
          <input id="game-file" type="file" accept="video/*" hidden>
          <button id="game-analyze-btn" class="btn btn-primary" type="button"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg><span data-zh="开始分析" data-en="Analyze">开始分析</span></button>
        </div>
      </div>
      <details class="api-config" id="game-api-config">
        <summary>
          <svg class="api-config-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a4 4 0 0 0-4 4v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2h-2V6a4 4 0 0 0-4-4z"></path><path d="M9 14l2 2 4-4"></path></svg>
          <span class="api-config-title"><span class="zh">AI 视觉模型配置</span><span class="en">AI Vision API</span></span>
          <span class="api-status is-off" id="api-status-badge"><span class="zh">未配置</span><span class="en">Not Set</span></span>
        </summary>
        <div class="api-config-body">
          <p class="api-config-hint"><span class="zh">配置后，AI 会逐帧读画面、按实际内容生成口播稿（贴合视频事实，非套话）。不配置则使用本地启发式模式（按时间轴叙述，不看画面）。支持 OpenAI 兼容接口，如 </span><span class="en">With a configured API, AI reads every frame and writes a content-aware script. Supports any OpenAI-compatible endpoint, e.g. </span><code>OpenAI</code><span class="zh"> / </span><span class="en"> / </span><code>火山方舟</code><span class="zh"> / </span><span class="en"> / </span><code>DeepSeek</code><span class="zh"> 等视觉模型。</span><span class="en"> vision models.</span></p>
          <div class="api-config-row">
            <label for="api-paste-area"><span class="zh">批量粘贴（自动识别填入下方）</span><span class="en">Bulk Paste (auto-fill below)</span></label>
            <textarea id="api-paste-area" class="api-paste-area" spellcheck="false" placeholder="支持以下格式，粘贴后自动识别填入：&#10;1) 多行 key=value：&#10;api_key=sk-xxx&#10;base_url=https://api.openai.com/v1&#10;model=gpt-4o-mini&#10;&#10;2) JSON：{&quot;api_key&quot;:&quot;sk-xxx&quot;,&quot;base_url&quot;:&quot;...&quot;,&quot;model&quot;:&quot;...&quot;}&#10;&#10;3) 按行顺序（Key / URL / Model 各一行）"></textarea>
            <p class="paste-hint"><span class="zh">粘贴整段配置后自动填入下方三个输入框，也可点「识别填入」手动触发。</span><span class="en">Paste a block to auto-fill the three fields below, or click &quot;Parse&quot; manually.</span></p>
          </div>
          <div class="api-config-row">
            <label for="api-key-input"><span class="zh">API Key</span><span class="en">API Key</span></label>
            <input id="api-key-input" type="password" autocomplete="off" spellcheck="false" placeholder="sk-... / 火山方舟 UUID">
          </div>
          <div class="api-config-row">
            <label for="api-base-input"><span class="zh">Base URL（接口地址）</span><span class="en">Base URL</span></label>
            <input id="api-base-input" type="url" autocomplete="off" spellcheck="false" placeholder="https://api.openai.com/v1">
          </div>
          <div class="api-config-row">
            <label for="api-model-input"><span class="zh">模型名称</span><span class="en">Model</span></label>
            <input id="api-model-input" type="text" autocomplete="off" spellcheck="false" placeholder="gpt-4o-mini / doubao-1.5-vision-pro-32k">
          </div>
          <div class="api-config-actions">
            <button id="api-save-btn" class="btn btn-primary" type="button"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><path d="M17 21v-8H7v8"></path><path d="M7 3v5h8"></path></svg><span data-zh="保存配置" data-en="Save">保存配置</span></button>
            <button id="api-clear-btn" class="btn" type="button"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg><span data-zh="清空" data-en="Clear">清空</span></button>
            <button id="api-test-btn" class="btn" type="button"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg><span data-zh="测试连通" data-en="Test">测试连通</span></button>
            <button id="api-parse-btn" class="btn" type="button"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg><span data-zh="识别填入" data-en="Parse">识别填入</span></button>
          </div>
          <p class="api-config-hint" id="api-test-result" hidden></p>
          <p class="api-config-hint"><span class="zh">配置只保存在当前浏览器（localStorage），不会上传服务器持久化。每次分析时随请求发给后端调用。</span><span class="en">Stored only in your browser (localStorage). Sent to the backend per request, never persisted server-side.</span></p>
        </div>
      </details>
      <div id="game-loading" class="state-row" aria-live="polite" hidden><span class="spinner" aria-hidden="true"></span><span id="game-loading-text"><span class="zh">正在读取画面、理解操作…</span><span class="en">Reading frames…</span></span></div>
      <div id="game-error" class="error-alert" role="alert" aria-live="assertive" hidden><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 16h.01"></path></svg><span id="game-error-text"></span></div>
    </section>
    <section id="game-result" class="panel result-panel" hidden aria-label="分析结果">
      <div class="result-layout">
        <div class="result-video-wrap"><div id="game-frames" class="frames-grid"></div></div>
        <div class="result-info">
          <span class="result-tag"><span class="zh">分析完成</span><span class="en">Analyzed</span></span>
          <h2 id="game-title" class="result-title"></h2>
          <p id="game-summary" class="result-meta"></p>
          <div id="game-timeline" class="timeline"></div>
          <div class="script-block">
            <div class="script-head"><span><span class="zh">口播配音稿</span><span class="en">Voiceover Script</span></span><span id="game-charcount" class="count-badge">0</span></div>
            <textarea id="game-script" class="script-box" spellcheck="false"></textarea>
            <div class="script-actions"><button id="game-copy-btn" class="btn" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1"></path></svg><span data-zh="复制配音稿" data-en="Copy">复制配音稿</span></button></div>
          </div>
        </div>
      </div>
    </section>
  </div>
  <aside class="sidebar"><section id="game-history" class="panel side-panel" aria-label="口播稿历史记录"><div class="side-heading"><div><h2><span class="zh">历史口播稿</span><span class="en">Scripts</span></h2><p><span class="zh">只保存在当前浏览器</span><span class="en">Stored in Browser</span></p></div><span id="game-history-count" class="count-badge">0</span></div><div id="game-history-list" class="history-list"></div><div id="game-history-empty" class="history-empty"><div class="history-empty-icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v14H4z"></path><path d="M8 9h8"></path><path d="M8 13h5"></path></svg></div><strong><span class="zh">这里还没有口播稿</span><span class="en">No Scripts Yet</span></strong><span><span class="zh">分析完成后自动保存在这里</span><span class="en">Results appear here</span></span></div><button id="game-clear-history-btn" class="btn" type="button" style="width:100%;margin-top:12px"><span class="zh">清空历史</span><span class="en">Clear</span></button></section></aside>
</main>
</div>
<script>
const $=s=>document.querySelector(s),input=$('#video-url'),parseBtn=$('#parse-btn'),pasteBtn=$('#paste-btn'),loading=$('#loading'),loadingText=$('#loading-text'),errorBox=$('#error'),errorText=$('#error-text'),result=$('#result'),video=$('#result-video'),avatar=$('#result-avatar'),titleEl=$('#result-title'),metaEl=$('#result-meta'),authorEl=$('#result-author'),likeEl=$('#result-like'),timeEl=$('#result-time'),platformEl=$('#result-platform'),downloadBtn=$('#download-btn'),copyBtn=$('#copy-btn'),copyTextBtn=$('#copy-text-btn'),historyList=$('#history-list'),historyEmpty=$('#history-empty'),historyCount=$('#history-count'),clearHistoryBtn=$('#clear-history-btn'),themeToggle=$('#theme-toggle');
const HISTORY_KEY='watermark-parse-history-v1',HISTORY_LIMIT=20,URL_PATTERN=/https?:\/\/[^\s"'<>，。；：！？【】（）《》]+/i;
const PLAY_ICON='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>',DOWNLOAD_ICON='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>',COPY_ICON='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2 2v1"></path></svg>',TRASH_ICON='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>';
let ignoreMediaError=false,currentResult=null,batchItems=[],batchRunning=false,batchCancelRequested=false;
function extractUrl(text){const m=String(text).match(URL_PATTERN);return m?m[0].replace(/[),.。]+$/,''):String(text).trim()}
function platformName(url){const h=(String(url).match(/^https?:\/\/([^/]+)/i)||[])[1]||'';if(h.includes('douyin.com'))return'抖音';if(h.includes('kuaishou.com')||h.includes('kuaishou.cn'))return'快手';if(h.includes('bilibili.com')||h.includes('b23.tv'))return'B站';if(h.includes('xiaohongshu.com')||h.includes('xhslink.com'))return'小红书';if(h.includes('channels.weixin.qq.com'))return'视频号';return'短视频'}
function formatTime(v){if(!v&&v!==0)return'';if(typeof v==='number'||/^\\d{10}$/.test(String(v))){const s=typeof v==='number'?v:parseInt(v,10);if(s<1000000000)return'';const d=new Date(s*1000);if(Number.isNaN(d.getTime()))return'';return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}return String(v)}
function formatCount(v){if(v===undefined||v===null||v==='')return'-';const n=Number(v);return Number.isFinite(n)?new Intl.NumberFormat('zh-CN',{notation:'compact',maximumFractionDigits:1}).format(n):String(v)}
function esc(v){return String(v??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;')}
function mediaUrl(url){return'index.php?action=media&url='+encodeURIComponent(url)}function buildDownloadUrl(url,title){if(/kwaicdn\.com|oskwai\.com/i.test(url))return"api/v1/download-proxy?url="+encodeURIComponent(url)+"&filename="+encodeURIComponent((title||"video")+".mp4");return'dl/'+encodeURIComponent((title||'video').slice(0,60).replace(/[<>:"/\\|?*]/g,'')||'video')+'.mp4?url='+encodeURIComponent(url)}
function setLoading(on){loading.hidden=!on;parseBtn.disabled=on;loadingText.textContent=on?'正在读取视频信息…':''}function showError(m){errorText.textContent=m||'解析失败，请稍后再试';errorBox.hidden=false}function hideError(){errorBox.hidden=true;errorText.textContent=''}
function hideResult(){result.hidden=true;ignoreMediaError=true;currentResult=null;video.removeAttribute('src');video.removeAttribute('poster');video.load()}
async function requestParse(url){const r=await fetch('api/v1/parse',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({url})}),j=await r.json().catch(()=>null);if(!r.ok||!j||!j.success||!j.data||!j.data.url)throw new Error(j&&j.message||'解析失败，请稍后再试');return{url,data:j.data}}
async function parseUrlIntoResult(url){hideError();hideResult();setLoading(true);try{const item=await requestParse(url);renderResult(item.data,item.url);addHistory(item);renderHistory();result.scrollIntoView({behavior:'smooth',block:'start'})}catch(e){showError(e.message||'解析失败，请稍后再试')}finally{setLoading(false)}}
async function parseVideo(){const raw=input.value.trim();if(!raw){showError('请先粘贴一个视频分享链接');input.focus();return}const url=extractUrl(raw);if(!/^https?:\/\//i.test(url)){showError('请输入有效的视频链接');return}await parseUrlIntoResult(url)}
function renderResult(data,raw){const vu=data.url||'',cover=data.cover||'';ignoreMediaError=false;currentResult={url:raw,data};video.src=mediaUrl(vu);if(cover)video.poster=mediaUrl(cover);else video.removeAttribute('poster');titleEl.textContent=data.title||'未命名视频';authorEl.textContent=data.author||'未知作者';platformEl.textContent=platformName(raw)+' · 解析完成';metaEl.textContent=[platformName(raw),formatTime(data.time)].filter(Boolean).join(' · ');likeEl.textContent=formatCount(data.like);timeEl.textContent=formatTime(data.time)||'-';if(data.avatar){avatar.src=mediaUrl(data.avatar);avatar.hidden=false}else{avatar.removeAttribute('src');avatar.hidden=true}downloadBtn.href=buildDownloadUrl(vu,data.title);downloadBtn.setAttribute('download',(data.title||'video')+'.mp4');downloadBtn.dataset.dlUrl=mediaUrl(vu);downloadBtn.dataset.dlFilename=(data.title||'video')+'.mp4';result.hidden=false}
function loadHistory(){try{const x=JSON.parse(localStorage.getItem(HISTORY_KEY)||'[]');return Array.isArray(x)?x:[]}catch(e){return[]}}function saveHistory(x){localStorage.setItem(HISTORY_KEY,JSON.stringify(x))}function addHistory(item){const list=loadHistory().filter(x=>x.rawUrl!==item.url);list.unshift({rawUrl:item.url,title:item.data.title||'',author:item.data.author||'',like:item.data.like??0,time:item.data.time??0,cover:item.data.cover||'',videoUrl:item.data.url||'',savedAt:Date.now()});saveHistory(list.slice(0,HISTORY_LIMIT))}function removeHistory(i){const list=loadHistory();list.splice(i,1);saveHistory(list);renderHistory()}
function historyItemHtml(item,i){return'<article class="history-entry" data-index="'+i+'"><div class="history-thumb history-thumb-placeholder"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></div><div class="history-copy"><h3 class="history-title">'+esc(item.title||'未命名视频')+'</h3><p class="history-meta">'+esc([platformName(item.rawUrl),formatTime(item.time)].filter(Boolean).join(' · '))+'</p></div><div class="history-actions"><button type="button" data-action="reparse" title="重新解析" aria-label="重新解析">↻</button><button type="button" data-action="delete" title="删除" aria-label="删除">✕</button></div></article>'}function renderHistory(){const list=loadHistory();historyCount.textContent=String(list.length);historyEmpty.hidden=list.length>0;historyList.innerHTML=list.map(historyItemHtml).join('')}async function copyText(text){try{await navigator.clipboard.writeText(text)}catch(e){const t=document.createElement('textarea');t.value=text;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();document.execCommand('copy');t.remove()}}function flashButton(b,text){const s=b.querySelector('span');if(!s)return;const old=s.textContent;s.textContent=text;setTimeout(()=>s.textContent=old,1200)}function buildShareText(x){return[x.data.title||'未命名视频',platformName(x.url),'无水印链接：'+buildDownloadUrl(x.data.url,x.data.title)].join('\\n')}
historyList.addEventListener('click',async e=>{const card=e.target.closest('.history-entry'),a=e.target.closest('[data-action]');if(!card||!a)return;const item=loadHistory()[Number(card.dataset.index)];if(!item)return;const action=a.dataset.action;if(action==='reparse'){input.value=item.rawUrl;await parseUrlIntoResult(item.rawUrl)}if(action==='copy'){await copyText(item.rawUrl);a.innerHTML='✓';setTimeout(()=>a.innerHTML=COPY_ICON,1000)}if(action==='delete')removeHistory(Number(card.dataset.index))});clearHistoryBtn.addEventListener('click',()=>{saveHistory([]);renderHistory()});
parseBtn.addEventListener('click',parseVideo);input.addEventListener('keydown',e=>{if(e.key==='Enter')parseVideo()});pasteBtn.addEventListener('click',async()=>{try{const text=await navigator.clipboard.readText();input.value=extractUrl(text);input.focus()}catch(e){showError('无法读取剪贴板，请手动粘贴')}});copyBtn.addEventListener('click',async()=>{if(currentResult&&downloadBtn.href){await copyText(downloadBtn.href);flashButton(copyBtn,'已复制')}});copyTextBtn.addEventListener('click',async()=>{if(currentResult){await copyText(buildShareText(currentResult));flashButton(copyTextBtn,'已复制')}});video.addEventListener('error',()=>{if(!ignoreMediaError){video.style.opacity='0.3';const p=document.createElement('p');p.className='video-fallback';p.innerHTML='<span class=zh>预览不可用，请点下载按钮</span><span class=en>Preview unavailable, use download</span>';video.parentElement.appendChild(p)}});
// 下载按钮：直接使用服务端流式下载端点（抖音 /dl/*.mp4，快手 download-proxy）。
// 服务端会返回 Content-Disposition: attachment; filename="*.mp4"，由浏览器原生下载。
// 不再使用 fetch+blob —— 移动端（iOS Safari / 微信内置浏览器）不会触发 blob 下载，
// 且此前会导致文件名回退为 URL 末段（index.php）出现 .php 后缀。
// 这里不调用 preventDefault，放行原生下载即可。
function applyTheme(theme){document.documentElement.classList.toggle('dark',theme==='dark');themeToggle.innerHTML=theme==='dark'?'<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path></svg>':'<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 14.7A8.5 8.5 0 0 1 9.3 3.5 8.5 8.5 0 1 0 20.5 14.7z"></path></svg>';themeToggle.title=theme==='dark'?'切换浅色主题':'切换深色主题';themeToggle.setAttribute('aria-label',themeToggle.title);localStorage.setItem('watermark-parse-theme-v2',theme)}function preferredTheme(){const saved=localStorage.getItem('watermark-parse-theme-v2');return saved==='dark'||saved==='light'?saved:'light'}
function applyLang(l){var r=document.documentElement;r.setAttribute("lang",l);var b=document.getElementById("lang-toggle");if(b){b.textContent=l==="zh"?"EN":"\u4e2d";b.title=l==="zh"?"Switch to English":"\u5207\u6362\u5230\u4e2d\u6587"}var els=document.querySelectorAll("[data-en]");for(var i=0;i<els.length;i++){var el=els[i];if(el.tagName==="SPAN"||el.tagName==="BUTTON")el.textContent=l==="zh"?el.getAttribute("data-zh")||el.textContent:el.getAttribute("data-en")}localStorage.setItem("va-lang",l);var inp=document.getElementById("video-url");if(inp)inp.placeholder=l==="zh"?inp.getAttribute("data-zh"):inp.getAttribute("data-en")}
function preferredLang(){var s=localStorage.getItem("va-lang");return s==="zh"||s==="en"?s:"zh"}
applyLang(preferredLang());
var lt=document.getElementById("lang-toggle");if(lt)lt.addEventListener("click",function(){applyLang(document.documentElement.getAttribute("lang")==="zh"?"en":"zh")});themeToggle.addEventListener('click',()=>applyTheme(document.documentElement.classList.contains('dark')?'light':'dark'));applyTheme(preferredTheme());renderHistory();

/* ===== 口播稿分析 ===== */
const tabbar=$('#tabbar'),parseView=$('#parse-view'),gameView=$('#game-view');
const gameUrl=$('#game-url'),gameFile=$('#game-file'),gameFileLabel=$('#game-file-label'),gameDemo=$('#game-demo'),gameAnalyzeBtn=$('#game-analyze-btn');
const gameLoading=$('#game-loading'),gameLoadingText=$('#game-loading-text'),gameErrorBox=$('#game-error'),gameErrorText=$('#game-error-text');
const gameResult=$('#game-result'),gameFrames=$('#game-frames'),gameTitleEl=$('#game-title'),gameSummaryEl=$('#game-summary'),gameTimeline=$('#game-timeline'),gameScript=$('#game-script'),gameCharcount=$('#game-charcount'),gameCopyBtn=$('#game-copy-btn');
/* ===== AI 视觉模型配置（前端自定义 API） ===== */
const GAME_API_KEY='game-api-config-v1';
const apiKeyInput=$('#api-key-input'),apiBaseInput=$('#api-base-input'),apiModelInput=$('#api-model-input'),apiSaveBtn=$('#api-save-btn'),apiClearBtn=$('#api-clear-btn'),apiTestBtn=$('#api-test-btn'),apiParseBtn=$('#api-parse-btn'),apiStatusBadge=$('#api-status-badge'),apiTestResult=$('#api-test-result'),apiPasteArea=$('#api-paste-area'),gameApiConfig=$('#game-api-config');
function loadApiConfig(){try{const x=JSON.parse(localStorage.getItem(GAME_API_KEY)||'null');return x&&typeof x==='object'?x:null}catch(e){return null}}
function saveApiConfig(c){localStorage.setItem(GAME_API_KEY,JSON.stringify(c))}
function clearApiConfig(){localStorage.removeItem(GAME_API_KEY)}
function getApiConfig(){const c=loadApiConfig();if(!c||!c.api_key)return null;return{api_key:c.api_key,base_url:c.base_url||'',model:c.model||''}}
function updateApiStatus(){const c=getApiConfig();if(c){apiStatusBadge.className='api-status is-on';apiStatusBadge.innerHTML='<span class="zh">已配置</span><span class="en">Ready</span>'}else{apiStatusBadge.className='api-status is-off';apiStatusBadge.innerHTML='<span class="zh">未配置</span><span class="en">Not Set</span>'}}
/* 批量粘贴自识别：支持 JSON / key=value 多行 / 按行顺序 三种格式 */
function parseApiConfigText(text){
  text=String(text||'').trim();
  if(!text)return null;
  // 1) JSON 格式
  if(text.charAt(0)==='{'){try{const j=JSON.parse(text);if(j&&typeof j==='object'){return{api_key:String(j.api_key||j.key||j.apikey||'').trim(),base_url:String(j.base_url||j.baseurl||j.url||'').trim(),model:String(j.model||'').trim()}}}catch(e){}}
  const lines=text.split(/\r?\n/).map(l=>l.trim()).filter(Boolean);
  // 2) key=value / key: value 格式
  const kv={};let kvHit=false;
  lines.forEach(l=>{const m=l.match(/^([a-zA-Z_][a-zA-Z0-9_]*)\s*[:=]\s*(.+)$/);if(m){kv[m[1].toLowerCase()]=m[2].trim().replace(/^["']|["']$/g,'');kvHit=true}});
  if(kvHit){return{api_key:String(kv.api_key||kv.key||kv.apikey||'').trim(),base_url:String(kv.base_url||kv.baseurl||kv.url||'').trim(),model:String(kv.model||'').trim()}}
  // 3) 单行 = 纯 API Key
  if(lines.length===1){return{api_key:lines[0],base_url:'',model:''}}
  // 4) 按行顺序：Key / Base URL / Model
  return{api_key:lines[0]||'',base_url:lines[1]||'',model:lines[2]||''}
}
function applyParsedConfig(c){if(!c)return;if(apiKeyInput)apiKeyInput.value=c.api_key||'';if(apiBaseInput)apiBaseInput.value=c.base_url||'';if(apiModelInput)apiModelInput.value=c.model||''}
if(apiPasteArea){apiPasteArea.addEventListener('paste',()=>{setTimeout(()=>{const c=parseApiConfigText(apiPasteArea.value);if(c){applyParsedConfig(c);apiPasteArea.value='';if(apiTestResult){apiTestResult.hidden=true}}},0)})}
if(apiParseBtn)apiParseBtn.addEventListener('click',()=>{const c=parseApiConfigText(apiPasteArea?apiPasteArea.value:'');if(!c||!c.api_key){showGameError('粘贴内容未能识别出 API Key，请检查格式');return}applyParsedConfig(c);apiPasteArea.value='';if(apiTestResult)apiTestResult.hidden=true;flashButton(apiParseBtn,'已填入')})
function fillApiForm(){const c=loadApiConfig()||{};if(apiKeyInput)apiKeyInput.value=c.api_key||'';if(apiBaseInput)apiBaseInput.value=c.base_url||'';if(apiModelInput)apiModelInput.value=c.model||''}
if(apiSaveBtn)apiSaveBtn.addEventListener('click',()=>{const c={api_key:(apiKeyInput.value||'').trim(),base_url:(apiBaseInput.value||'').trim(),model:(apiModelInput.value||'').trim()};if(!c.api_key){showGameError('请填写 API Key');apiKeyInput.focus();return}saveApiConfig(c);updateApiStatus();flashButton(apiSaveBtn,'已保存');if(apiTestResult)apiTestResult.hidden=true});
if(apiClearBtn)apiClearBtn.addEventListener('click',()=>{clearApiConfig();fillApiForm();updateApiStatus();if(apiTestResult)apiTestResult.hidden=true});
if(apiTestBtn)apiTestBtn.addEventListener('click',async()=>{const c=getApiConfig();if(!c){showGameError('请先填写并保存 API Key');return}apiTestBtn.disabled=true;apiTestResult.hidden=false;apiTestResult.textContent='正在测试连通…';apiTestResult.style.color='var(--muted)';try{const r=await fetch('api/v1/game-test',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({api_config:c})});const j=await r.json().catch(()=>null);if(r.ok&&j&&j.success){apiTestResult.textContent='✓ '+(j.message||'连接正常，视觉模型可用');apiTestResult.style.color='var(--success)';setTimeout(()=>{if(gameApiConfig)gameApiConfig.open=false},1500)}else{apiTestResult.textContent='✗ '+(j&&j.message||'测试失败（HTTP '+r.status+'）');apiTestResult.style.color='var(--danger)'}}catch(e){apiTestResult.textContent='✗ '+(e.message||'请求异常');apiTestResult.style.color='var(--danger)'}finally{apiTestBtn.disabled=false}});
fillApiForm();updateApiStatus();
if(tabbar){tabbar.addEventListener('click',e=>{const b=e.target.closest('.tab');if(!b)return;tabbar.querySelectorAll('.tab').forEach(t=>t.classList.toggle('is-active',t===b));const v=b.dataset.view;parseView.hidden=v!=='parse';gameView.hidden=v!=='game';window.scrollTo({top:0,behavior:'smooth'})})}
function showGameError(m){gameErrorText.textContent=m;gameErrorBox.hidden=false}
function gameHideError(){gameErrorBox.hidden=true}
function gameSetLoading(on,t){gameLoading.hidden=!on;if(t)gameLoadingText.innerHTML=t}
async function analyzeGame(){gameHideError();gameResult.hidden=true;const useDemo=gameDemo&&gameDemo.checked;const urlVal=(gameUrl.value||'').trim();const url=extractUrl(urlVal);const isUrl=/^https?:\/\//i.test(url);const hasFile=gameFile.files&&gameFile.files.length;if(!useDemo&&!isUrl&&!hasFile){showGameError('请粘贴视频链接、上传本地视频，或勾选演示数据');return}const apiConfig=getApiConfig();gameAnalyzeBtn.disabled=true;gameSetLoading(true);try{let resp,source='';if(useDemo){resp=await fetch('api/v1/game-analysis',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(apiConfig?{demo:true,api_config:apiConfig}:{demo:true})});source='演示数据'}else if(isUrl){resp=await fetch('api/v1/game-analysis',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(apiConfig?{url,api_config:apiConfig}:{url})});source=url}else{const fd=new FormData();fd.append('video',gameFile.files[0]);if(apiConfig)fd.append('api_config',JSON.stringify(apiConfig));resp=await fetch('api/v1/game-analysis',{method:'POST',body:fd});source=gameFile.files[0].name}const j=await resp.json().catch(()=>null);if(!resp.ok||!j||!j.success||!j.data){throw new Error((j&&j.message)||'分析失败')}addGameHistory(j.data,source);renderGame(j.data)}catch(e){showGameError(e.message||'分析失败')}finally{gameSetLoading(false);gameAnalyzeBtn.disabled=false}}
function renderGame(d){gameTitleEl.textContent=d.title||'未命名';gameSummaryEl.textContent=d.summary||'';gameFrames.innerHTML='';if(Array.isArray(d.frames)&&d.frames.length){d.frames.forEach(f=>{const cell=document.createElement('div');cell.className='frame-thumb';const img=document.createElement('img');img.src=f.dataUrl;img.alt='帧 @'+f.t+'s';cell.appendChild(img);const badge=document.createElement('span');badge.className='t-badge';badge.textContent=f.t+'s';cell.appendChild(badge);gameFrames.appendChild(cell)})}else if(d.frames){gameFrames.innerHTML='<p class="result-meta">演示模式不抽取画面，可上传本地视频查看抽帧缩略图。</p>'}else{gameFrames.innerHTML='<p class="result-meta">历史记录不包含抽帧画面。</p>'}gameTimeline.innerHTML='';if(Array.isArray(d.segments)){d.segments.forEach(s=>{const item=document.createElement('div');item.className='tl-item '+(s.type||'operation');const dot=document.createElement('span');dot.className='tl-dot';const body=document.createElement('div');body.className='tl-body';const head=document.createElement('div');head.className='tl-head';const label=document.createElement('span');label.className='tl-label';label.textContent=s.label||s.type;const time=document.createElement('span');time.className='tl-time';time.textContent=(s.t_start||0)+'–'+(s.t_end||0)+'s';head.appendChild(label);head.appendChild(time);const desc=document.createElement('p');desc.className='tl-desc';desc.textContent=s.desc||'';body.appendChild(head);body.appendChild(desc);item.appendChild(dot);item.appendChild(body);gameTimeline.appendChild(item)})}gameScript.value=d.script||'';updateGameCount();gameResult.hidden=false;gameResult.scrollIntoView({behavior:'smooth',block:'start'})}
function updateGameCount(){gameCharcount.textContent=String((gameScript.value||'').length)}
if(gameScript)gameScript.addEventListener('input',updateGameCount);
gameAnalyzeBtn.addEventListener('click',analyzeGame);
// 上传视频：显式触发文件选择框（避免 hidden input + label 在部分浏览器失效），并回显文件名
if(gameFileLabel){gameFileLabel.addEventListener('click',e=>{e.preventDefault();gameFile.click()})}
if(gameFile){gameFile.addEventListener('change',()=>{const f=gameFile.files&&gameFile.files[0];if(f){const sz=(f.size/1048576).toFixed(1);gameUrl.value=f.name;if(gameDemo)gameDemo.checked=false}else{gameUrl.value=''}})}
// 输入框手动改动：粘贴链接时清空文件残留，避免残留文件覆盖链接导致"一直分析第一个视频"
if(gameUrl){gameUrl.addEventListener('input',()=>{const v=gameUrl.value.trim();if(/^https?:\/\//i.test(v)||v===''){if(gameFile)gameFile.value=''}});gameUrl.addEventListener('paste',()=>{setTimeout(()=>{if(gameFile)gameFile.value=''},0)})}
if(gameCopyBtn)gameCopyBtn.addEventListener('click',async()=>{await copyText(gameScript.value);flashButton(gameCopyBtn,'已复制')});

/* ===== 口播稿历史记录 ===== */
const GAME_HISTORY_KEY='game-analysis-history-v1',GAME_HISTORY_LIMIT=20;
const gameHistoryList=$('#game-history-list'),gameHistoryEmpty=$('#game-history-empty'),gameHistoryCount=$('#game-history-count'),gameClearHistoryBtn=$('#game-clear-history-btn');
function loadGameHistory(){try{const x=JSON.parse(localStorage.getItem(GAME_HISTORY_KEY)||'[]');return Array.isArray(x)?x:[]}catch(e){return[]}}
function saveGameHistory(x){localStorage.setItem(GAME_HISTORY_KEY,JSON.stringify(x))}
function addGameHistory(d,source){if(!d||!d.script)return;const list=loadGameHistory();list.unshift({title:d.title||'未命名',summary:d.summary||'',script:d.script,segments:d.segments||[],source:source||'',savedAt:Date.now()});saveGameHistory(list.slice(0,GAME_HISTORY_LIMIT));renderGameHistory()}
function removeGameHistory(i){const list=loadGameHistory();list.splice(i,1);saveGameHistory(list);renderGameHistory()}
function gameHistoryTime(ts){const d=new Date(ts),p=n=>String(n).padStart(2,'0');return (d.getMonth()+1)+'-'+p(d.getDate())+' '+p(d.getHours())+':'+p(d.getMinutes())}
function gameHistoryItemHtml(item,i){return '<article class="history-entry" data-index="'+i+'"><div class="history-thumb history-thumb-placeholder"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 5h16v14H4z"></path><path d="M8 9h8"></path><path d="M8 13h5"></path></svg></div><div class="history-copy"><h3 class="history-title">'+esc(item.title||'未命名')+'</h3><p class="history-meta">'+esc((item.source||'')+' · '+gameHistoryTime(item.savedAt))+'</p></div><div class="history-actions"><button type="button" data-action="restore" title="查看结果" aria-label="查看结果">↻</button><button type="button" data-action="delete" title="删除" aria-label="删除">✕</button></div></article>'}
function renderGameHistory(){const list=loadGameHistory();if(gameHistoryCount)gameHistoryCount.textContent=String(list.length);if(gameHistoryEmpty)gameHistoryEmpty.hidden=list.length>0;if(gameHistoryList)gameHistoryList.innerHTML=list.map(gameHistoryItemHtml).join('')}
if(gameHistoryList)gameHistoryList.addEventListener('click',e=>{const card=e.target.closest('.history-entry'),a=e.target.closest('[data-action]');if(!card||!a)return;const item=loadGameHistory()[Number(card.dataset.index)];if(!item)return;const action=a.dataset.action;if(action==='restore'){renderGame({title:item.title,summary:item.summary,script:item.script,segments:item.segments});if(gameUrl)gameUrl.value=item.source||''}if(action==='delete')removeGameHistory(Number(card.dataset.index))});
if(gameClearHistoryBtn)gameClearHistoryBtn.addEventListener('click',()=>{saveGameHistory([]);renderGameHistory()});
renderGameHistory();

</script>
</body>
</html>