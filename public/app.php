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
.en{display:none !important}html[lang=en] .zh{display:none !important}html[lang=en] .en{display:inline !important}.lang-btn{width:34px;height:34px;padding:0;display:grid;place-items:center;border:1px solid var(--line-strong);border-radius:var(--r);background:transparent;color:var(--muted);font-size:13px;font-weight:700;transition:all var(--motion);cursor:pointer}.lang-btn:hover{background:var(--soft);color:var(--ink)}</style>
</head>
<body>
<header class="topbar"><div class="topbar-inner"><div class="brand"><span class="brand-mark" aria-hidden="true">V</span><div><p class="brand-name">Video Analysis</p><p class="brand-sub"><span class="zh">短视频无水印工作台</span><span class="en">Short Video Workstation</span></p></div></div><div class="topbar-actions"><span class="online"><i class="online-dot"></i><span class="zh">在线</span><span class="en">Online</span></span><button id="lang-toggle" class="lang-btn" type="button" title="Switch to English">EN</button><button id="theme-toggle" class="theme-toggle" type="button" title="Toggle theme" aria-label="Toggle theme"></button></div></div></header>
<main class="workspace">
  <div class="main-column">
    <section class="intro" aria-labelledby="page-title"><span class="eyebrow"></span><h1 id="page-title"><span class="zh">分享链接，<em>直接变成</em>可用视频。</span><span class="en">Share a link, <em>get</em> a usable video.</span></h1><p><span class="zh">粘贴短视频分享链接，快速整理出无水印视频。解析结果、下载和最近记录都在同一个工作区完成。</span><span class="en">Paste short video links to extract watermark-free videos. Results, downloads, and history — all in one workspace.</span></p></section>
    <section class="panel ingest-panel" aria-label="视频解析入口">
      <div class="panel-heading"><div><p class="panel-kicker"><span class="zh">导入链接</span><span class="en">Import link</span></p><h2 class="panel-title"><span class="zh">从一个链接开始</span><span class="en">Start with a Link</span></h2><p class="panel-note"><span class="zh">支持公开分享链接，不需要登录第三方平台。</span><span class="en">No login required. Works with public share links.</span></p></di</div>
      <div id="single-panel"><label id="mode-label" class="field-label" for="video-url"><span class="zh">视频分享链接</span><span class="en">Video Share Link</span></label><div class="input-row"><div class="input-wrap"><input id="video-url" type="url" data-zh="粘贴抖音、快手、B站、小红书、视频号链接" data-en="Paste Douyin/Kuaishou/Bilibili/RED/Channels links..." autocomplete="off" spellcheck="false"></div><button id="paste-btn" class="icon-btn" type="button" title="Paste from clipboard" aria-label="Paste from clipboard"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="3" width="8" height="4" rx="1"></rect><path d="M16 5h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2"></path></svg></button><button id="parse-btn" class="btn btn-primary" type="button"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg><span data-zh="开始解析" data-en="Parse">开始解析</span></button></div><p class="input-hint"><span class="zh">小提示：也可以直接粘贴包含文字的整段分享文案，系统会自动提取链接。</span><span class="en">Tip: Paste full share text — links are auto-extracted.</span></p></div>
      <div class="platform-strip" aria-label="支持平台"><span class="platform-chip" style="--chip:#2d6cdf"><i></i><span class="zh">抖音</span><span class="en">Douyin</span></span><span class="platform-chip" style="--chip:#10a77a"><i></i><span class="zh">快手</span><span class="en">Kuaishou</span></span><span class="platform-chip" style="--chip:#e86d94"><i></i><span class="zh">B站</span><span class="en">Bilibili</span></span><span class="platform-chip" style="--chip:#e64555"><i></i><span class="zh">小红书</span><span class="en">RED</span></span><span class="platform-chip" style="--chip:#4d9d91"><i></i><span class="zh">视频号</span><span class="en">Channels</span></span></div>
      <div id="loading" class="state-row" aria-live="polite" hidden><span class="spinner" aria-hidden="true"></span><span id="loading-text"><span class="zh">正在读取视频信息…</span><span class="en">Loading video info...</span></span></div>
      <div id="error" class="error-alert" role="alert" aria-live="assertive" hidden><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v5"></path><path d="M12 16h.01"></path></svg><span id="error-text"></span></div>
    </section>
    <section id="result" class="panel result-panel" hidden aria-label="解析结果"><div class="result-layout"><div class="result-video-wrap"><video id="result-video" controls playsinline preload="metadata" crossorigin="anonymous"></video></div><div class="result-info"><span id="result-platform" class="result-tag"><span class="zh">解析完成</span><span class="en">Parse Complete</span></span><h2 id="result-title" class="result-title"></h2><p id="result-meta" class="result-meta"></p><div class="creator-row"><img id="result-avatar" class="avatar" alt="" hidden><div><div class="creator-label"><span class="zh">发布作者</span><span class="en">Author</span></div><div id="result-author" class="creator-name"></div></div></div><dl class="result-stats"><div class="result-stat"><dt><span class="zh">点赞</span><span class="en">Likes</span></dt><dd id="result-like">-</dd></div><div class="result-stat"><dt><span class="zh">发布时间</span><span class="en">Posted</span></dt><dd id="result-time">-</dd></div></dl><div class="result-actions"><a id="download-btn" class="btn btn-primary" download target="_blank" rel="noopener"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg><span data-zh="下载视频" data-en="Download">下载视频</span></a><div class="btn-copy-row"><button id="copy-btn" class="btn" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1"></path></svg><span data-zh="复制链接" data-en="Copy Link">复制链接</span></button><button id="copy-text-btn" class="btn" type="button"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg><span data-zh="复制文案" data-en="Copy Text">复制文案</span></button></div></div></div></div></section>
  </div>
  <aside class="sidebar"><section id="history" class="panel side-panel" aria-label="最近解析"><div class="side-heading"><div><h2><span class="zh">最近处理</span><span class="en">Recent</span></h2><p><span class="zh">只保存在当前浏览器</span><span class="en">Stored in Browser</span></p></div><span id="history-count" class="count-badge">0</span></div><div id="history-list" class="history-list"></div><div id="history-empty" class="history-empty"><div class="history-empty-icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v14H4z"></path><path d="M8 9h8"></path><path d="M8 13h5"></path></svg></div><strong><span class="zh">这里还没有记录</span><span class="en">No History Yet</span></strong><span><span class="zh">解析完成后会自动出现在这里</span><span class="en">Results appear here automatically.</span></span></div><div class="side-divider"></div><button id="clear-history-btn" class="clear-btn" type="button"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2-2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> <span data-zh="清空记录" data-en="Clear">清空记录</span></button></section><section class="panel side-panel" aria-label="工作台信息"><div class="side-heading"><div><h2><span class="zh">工作台</span><span class="en">Workspace</span></h2><p><span class="zh">让每次整理更顺手</span><span class="en">Tools for your workflow.</span></p></div></div><div class="quick-list"><div class="quick-row"><span class="quick-label"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg><span class="zh">单个解析</span><span class="en">Single</span></span><strong class="quick-value"><span class="zh">即时</span><span class="en">Instant</span></strong></div><div class="quick-row"><span class="quick-label"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 6h13"></path><path d="M8 12h13"></path><path d="M8 18h13"></path><path d="M3 6h.01"></path><path d="M3 12h.01"></path><path d="M3 18h.01"></path></svg><span class="zh">批量处理</span><span class="en">Batch</span></span><strong class="quick-value"><span class="zh">最多 20 条，点一下即可复用。</span><span class="en">Up to 20 items. Click to reuse.</span></strong></div><div class="quick-row"><span class="quick-label"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18"></path><path d="M3 12h18"></path></svg><span class="zh">记录方式</span><span class="en">Storage</span></span><strong class="quick-value"><span class="zh">本地保存</span><span class="en">Local</span></strong></div></div></section></aside>
<div class="main-foot"><span class="zh">Video Analysis</span><span class="en">Video Analysis</span></div>
</main>
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
function mediaUrl(url){if(/kwaicdn\.com|oskwai\.com|yximgs\.com/i.test(url))return url;return'index.php?action=media&url='+encodeURIComponent(url)}function buildDownloadUrl(url,title){if(/kwaicdn\.com|oskwai\.com/i.test(url))return"api/v1/download-proxy?url="+encodeURIComponent(url)+"&filename="+encodeURIComponent((title||"video")+".mp4");return'dl/'+encodeURIComponent((title||'video').slice(0,60).replace(/[<>:"/\\|?*]/g,'')||'video')+'.mp4?url='+encodeURIComponent(url)}
function setLoading(on){loading.hidden=!on;parseBtn.disabled=on;loadingText.textContent=on?'正在读取视频信息…':''}function showError(m){errorText.textContent=m||'解析失败，请稍后再试';errorBox.hidden=false}function hideError(){errorBox.hidden=true;errorText.textContent=''}
function hideResult(){result.hidden=true;ignoreMediaError=true;currentResult=null;video.removeAttribute('src');video.removeAttribute('poster');video.load()}
async function requestParse(url){const r=await fetch('api/v1/parse',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({url})}),j=await r.json().catch(()=>null);if(!r.ok||!j||!j.success||!j.data||!j.data.url)throw new Error(j&&j.message||'解析失败，请稍后再试');return{url,data:j.data}}
async function parseUrlIntoResult(url){hideError();hideResult();setLoading(true);try{const item=await requestParse(url);renderResult(item.data,item.url);addHistory(item);renderHistory();result.scrollIntoView({behavior:'smooth',block:'start'})}catch(e){showError(e.message||'解析失败，请稍后再试')}finally{setLoading(false)}}
async function parseVideo(){const raw=input.value.trim();if(!raw){showError('请先粘贴一个视频分享链接');input.focus();return}const url=extractUrl(raw);if(!/^https?:\/\//i.test(url)){showError('请输入有效的视频链接');return}await parseUrlIntoResult(url)}
function renderResult(data,raw){const vu=data.url||'',cover=data.cover||'';ignoreMediaError=false;currentResult={url:raw,data};video.src=mediaUrl(vu);if(cover)video.poster=cover;else video.removeAttribute('poster');titleEl.textContent=data.title||'未命名视频';authorEl.textContent=data.author||'未知作者';platformEl.textContent=platformName(raw)+' · 解析完成';metaEl.textContent=[platformName(raw),formatTime(data.time)].filter(Boolean).join(' · ');likeEl.textContent=formatCount(data.like);timeEl.textContent=formatTime(data.time)||'-';if(data.avatar){avatar.src=data.avatar;avatar.hidden=false}else{avatar.removeAttribute('src');avatar.hidden=true}downloadBtn.href=buildDownloadUrl(vu,data.title);downloadBtn.setAttribute('download',(data.title||'video')+'.mp4');downloadBtn.dataset.dlUrl=mediaUrl(vu);downloadBtn.dataset.dlFilename=(data.title||'video')+'.mp4';result.hidden=false}
function loadHistory(){try{const x=JSON.parse(localStorage.getItem(HISTORY_KEY)||'[]');return Array.isArray(x)?x:[]}catch(e){return[]}}function saveHistory(x){localStorage.setItem(HISTORY_KEY,JSON.stringify(x))}function addHistory(item){const list=loadHistory().filter(x=>x.rawUrl!==item.url);list.unshift({rawUrl:item.url,title:item.data.title||'',author:item.data.author||'',like:item.data.like??0,time:item.data.time??0,cover:item.data.cover||'',videoUrl:item.data.url||'',savedAt:Date.now()});saveHistory(list.slice(0,HISTORY_LIMIT))}function removeHistory(i){const list=loadHistory();list.splice(i,1);saveHistory(list);renderHistory()}
function historyItemHtml(item,i){return'<article class="history-entry" data-index="'+i+'"><div class="history-thumb history-thumb-placeholder"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></div><div class="history-copy"><h3 class="history-title">'+esc(item.title||'未命名视频')+'</h3><p class="history-meta">'+esc([platformName(item.rawUrl),formatTime(item.time)].filter(Boolean).join(' · '))+'</p></div><div class="history-actions"><button type="button" data-action="reparse" title="重新解析" aria-label="重新解析">↻</button><button type="button" data-action="delete" title="删除" aria-label="删除">✕</button></div></article>'}function renderHistory(){const list=loadHistory();historyCount.textContent=String(list.length);historyEmpty.hidden=list.length>0;historyList.innerHTML=list.map(historyItemHtml).join('')}async function copyText(text){try{await navigator.clipboard.writeText(text)}catch(e){const t=document.createElement('textarea');t.value=text;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();document.execCommand('copy');t.remove()}}function flashButton(b,text){const s=b.querySelector('span');if(!s)return;const old=s.textContent;s.textContent=text;setTimeout(()=>s.textContent=old,1200)}function buildShareText(x){return[x.data.title||'未命名视频',platformName(x.url),'无水印链接：'+buildDownloadUrl(x.data.url,x.data.title)].join('\\n')}
historyList.addEventListener('click',async e=>{const card=e.target.closest('.history-entry'),a=e.target.closest('[data-action]');if(!card||!a)return;const item=loadHistory()[Number(card.dataset.index)];if(!item)return;const action=a.dataset.action;if(action==='reparse'){input.value=item.rawUrl;await parseUrlIntoResult(item.rawUrl)}if(action==='copy'){await copyText(item.rawUrl);a.innerHTML='✓';setTimeout(()=>a.innerHTML=COPY_ICON,1000)}if(action==='delete')removeHistory(Number(card.dataset.index))});clearHistoryBtn.addEventListener('click',()=>{saveHistory([]);renderHistory()});
parseBtn.addEventListener('click',parseVideo);input.addEventListener('keydown',e=>{if(e.key==='Enter')parseVideo()});pasteBtn.addEventListener('click',async()=>{try{const text=await navigator.clipboard.readText();input.value=extractUrl(text);input.focus()}catch(e){showError('无法读取剪贴板，请手动粘贴')}});copyBtn.addEventListener('click',async()=>{if(currentResult&&downloadBtn.href){await copyText(downloadBtn.href);flashButton(copyBtn,'已复制')}});copyTextBtn.addEventListener('click',async()=>{if(currentResult){await copyText(buildShareText(currentResult));flashButton(copyTextBtn,'已复制')}});video.addEventListener('error',()=>{if(!ignoreMediaError){video.style.opacity='0.3';const p=document.createElement('p');p.className='video-fallback';p.innerHTML='<span class=zh>预览不可用，请点下载按钮</span><span class=en>Preview unavailable, use download</span>';video.parentElement.appendChild(p)}});
downloadBtn.addEventListener('click',async e=>{const url=downloadBtn.dataset.dlUrl;if(!url)return;if(/kwaicdn\.com|oskwai\.com|download-proxy/i.test(downloadBtn.href))return;e.preventDefault();const f=downloadBtn.dataset.dlFilename||'video.mp4',orig=downloadBtn.textContent;downloadBtn.textContent='下载中...';downloadBtn.disabled=true;try{const r=await fetch(url),blob=await r.blob();const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=f;a.click();URL.revokeObjectURL(a.href)}catch(er){console.error(er)}downloadBtn.textContent=orig;downloadBtn.disabled=false});
function applyTheme(theme){document.documentElement.classList.toggle('dark',theme==='dark');themeToggle.innerHTML=theme==='dark'?'<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path></svg>':'<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 14.7A8.5 8.5 0 0 1 9.3 3.5 8.5 8.5 0 1 0 20.5 14.7z"></path></svg>';themeToggle.title=theme==='dark'?'切换浅色主题':'切换深色主题';themeToggle.setAttribute('aria-label',themeToggle.title);localStorage.setItem('watermark-parse-theme-v2',theme)}function preferredTheme(){const saved=localStorage.getItem('watermark-parse-theme-v2');return saved==='dark'||saved==='light'?saved:'light'}
function applyLang(l){var r=document.documentElement;r.setAttribute("lang",l);var b=document.getElementById("lang-toggle");if(b){b.textContent=l==="zh"?"EN":"\u4e2d";b.title=l==="zh"?"Switch to English":"\u5207\u6362\u5230\u4e2d\u6587"}var els=document.querySelectorAll("[data-en]");for(var i=0;i<els.length;i++){var el=els[i];if(el.tagName==="SPAN"||el.tagName==="BUTTON")el.textContent=l==="zh"?el.getAttribute("data-zh")||el.textContent:el.getAttribute("data-en")}localStorage.setItem("va-lang",l);var inp=document.getElementById("video-url");if(inp)inp.placeholder=l==="zh"?inp.getAttribute("data-zh"):inp.getAttribute("data-en")}
function preferredLang(){var s=localStorage.getItem("va-lang");return s==="zh"||s==="en"?s:"zh"}
applyLang(preferredLang());
var lt=document.getElementById("lang-toggle");if(lt)lt.addEventListener("click",function(){applyLang(document.documentElement.getAttribute("lang")==="zh"?"en":"zh")});themeToggle.addEventListener('click',()=>applyTheme(document.documentElement.classList.contains('dark')?'light':'dark'));applyTheme(preferredTheme());renderHistory();
</script>
</body>
</html>