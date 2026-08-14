'use strict';
// 本地真实测试网关：HTTP -> FastCGI(php-cgi) -> HTTP
// 用于绕开 Windows 版 php -S 的两个已知 bug（二进制分块上传解析崩溃 / 子进程继承监听套接字），
// 复用现有 index.php / api/v1.php，无需任何 npm 依赖（仅用 Node 内置 net + http）。
// 启动：node local-gateway.js   （需先：php-cgi.exe -b 127.0.0.1:9000）

const net = require('net');
const http = require('http');
const fs = require('fs');
const path = require('path');

const FCGI_HOST = '127.0.0.1';
const FCGI_PORT = 9000;
const DOC_ROOT = path.resolve(__dirname, 'public');
// 使用 router.php 作为入口，恢复 /dl/*.mp4 媒体代理下载与 /api/v1/* 路由
// （index.php 直接入口时 /dl/ 会被当成解析请求而报"不支持的视频平台"）
const SCRIPT_FILENAME = path.join(DOC_ROOT, 'router.php');
const HTTP_PORT = 8080;

function encLen(n) {
  if (n < 128) return Buffer.from([n & 0x7f]);
  return Buffer.from([0x80 | ((n >> 24) & 0x7f), (n >> 16) & 0xff, (n >> 8) & 0xff, n & 0xff]);
}

function nameValuePair(name, value) {
  const n = Buffer.from(String(name));
  const v = Buffer.from(String(value));
  return Buffer.concat([encLen(n.length), encLen(v.length), n, v]);
}

function sendRecord(sock, type, content, requestId) {
  requestId = requestId || 1;
  content = content || Buffer.alloc(0);
  const header = Buffer.alloc(8);
  header[0] = 1; // version
  header[1] = type;
  header[2] = (requestId >> 8) & 0xff;
  header[3] = requestId & 0xff;
  header[4] = (content.length >> 8) & 0xff;
  header[5] = content.length & 0xff;
  header[6] = 0; // padding
  header[7] = 0;
  sock.write(Buffer.concat([header, content]));
}

function fcgiRequest(opts) {
  return new Promise((resolve, reject) => {
    const sock = net.connect(FCGI_PORT, FCGI_HOST);
    let buf = Buffer.alloc(0);
    const stdout = [];
    let done = false;
    let requestId = 1;

    function finish(err, data) {
      if (done) return;
      done = true;
      try { sock.destroy(); } catch (e) {}
      if (err) reject(err); else resolve(data);
    }

    sock.on('connect', () => {
      try {
        // BEGIN_REQUEST (role=RESPONDER=1, flags=0)
        const begin = Buffer.alloc(8);
        begin[0] = 0; begin[1] = 1; begin[2] = 0; begin[3] = 0; begin[4] = 0; begin[5] = 0; begin[6] = 0; begin[7] = 0;
        sendRecord(sock, 1, begin, requestId);

        // PARAMS
        const params = [];
        const add = (k, v) => { if (v !== undefined && v !== null) params.push(nameValuePair(k, v)); };
        add('SCRIPT_FILENAME', opts.scriptFilename);
        add('DOCUMENT_ROOT', opts.documentRoot);
        add('REQUEST_URI', opts.requestUri);
        add('QUERY_STRING', opts.queryString || '');
        add('REQUEST_METHOD', opts.method);
        add('SERVER_PROTOCOL', 'HTTP/1.1');
        add('SERVER_SOFTWARE', 'node-fcgi-gw/1.0');
        add('GATEWAY_INTERFACE', 'CGI/1.1');
        add('SCRIPT_NAME', opts.scriptName || opts.requestUri.split('?')[0]);
        add('PATH_INFO', '');
        add('REDIRECT_STATUS', '200');
        for (const [k, v] of Object.entries(opts.headers || {})) {
          add('HTTP_' + String(k).toUpperCase().replace(/-/g, '_'), v);
        }
        if (opts.headers && opts.headers['content-type']) add('CONTENT_TYPE', opts.headers['content-type']);
        if (opts.headers && opts.headers['content-length']) add('CONTENT_LENGTH', opts.headers['content-length']);

        sendRecord(sock, 4, Buffer.concat(params), requestId);
        sendRecord(sock, 4, Buffer.alloc(0), requestId); // end params

        if (opts.body && opts.body.length) {
          // send stdin in chunks <= 65535
          let off = 0;
          while (off < opts.body.length) {
            const chunk = opts.body.slice(off, off + 65535);
            sendRecord(sock, 5, chunk, requestId);
            off += chunk.length;
          }
        }
        sendRecord(sock, 5, Buffer.alloc(0), requestId); // end stdin
      } catch (e) {
        finish(e);
      }
    });

    sock.on('data', (d) => {
      buf = Buffer.concat([buf, d]);
      while (buf.length >= 8) {
        const version = buf[0];
        const type = buf[1];
        const rid = buf[2] * 256 + buf[3];
        const len = buf[4] * 256 + buf[5];
        const pad = buf[6];
        if (buf.length < 8 + len + pad) break; // wait for more
        const content = buf.slice(8, 8 + len);
        buf = buf.slice(8 + len + pad);
        if (type === 6) {
          stdout.push(content);
        } else if (type === 3) {
          // END_REQUEST
          finish(null, Buffer.concat(stdout));
          return;
        } else if (type === 7) {
          // STDERR - ignore (could log)
        }
      }
    });

    sock.on('error', (e) => finish(e));
    sock.on('close', () => finish(null, Buffer.concat(stdout)));
    setTimeout(() => finish(new Error('FastCGI timeout')), 600000);
  });
}

function parseCgiResponse(buf) {
  const sep = buf.indexOf('\r\n\r\n');
  if (sep < 0) {
    return { status: 200, headers: {}, body: buf };
  }
  const headStr = buf.slice(0, sep).toString('latin1');
  const body = buf.slice(sep + 4);
  let status = 200;
  const headers = {};
  const lines = headStr.split('\r\n');
  for (const line of lines) {
    if (/^status:/i.test(line)) {
      const m = line.match(/status:\s*(\d{3})/i);
      if (m) status = parseInt(m[1], 10);
    } else if (/^HTTP\/\d\.\d\s+\d{3}/i.test(line)) {
      const m = line.match(/(\d{3})/);
      if (m) status = parseInt(m[1], 10);
    } else {
      const m = line.match(/^([^:]+):\s*(.*)$/);
      if (m) {
        const key = m[1].toLowerCase();
        if (key === 'status') {
          const sm = m[2].match(/(\d{3})/);
          if (sm) status = parseInt(sm[1], 10);
        } else {
          headers[key] = m[2];
        }
      }
    }
  }
  return { status, headers, body };
}

const server = http.createServer((req, res) => {
  const chunks = [];
  req.on('data', (c) => chunks.push(c));
  req.on('end', () => {
    const body = Buffer.concat(chunks);
    const urlObj = new URL(req.url, 'http://localhost');
    const requestUri = req.url;
    const queryString = urlObj.search ? urlObj.search.slice(1) : '';
    const headers = {};
    for (const [k, v] of Object.entries(req.headers)) {
      headers[k] = Array.isArray(v) ? v[0] : v;
    }
    fcgiRequest({
      scriptFilename: SCRIPT_FILENAME,
      documentRoot: DOC_ROOT,
      requestUri,
      queryString,
      method: req.method,
      headers,
      body,
    }).then((out) => {
      const { status, headers: h, body: b } = parseCgiResponse(out);
      // remove hop-by-hop / conflicting headers
      delete h['transfer-encoding'];
      delete h['connection'];
      res.writeHead(status, h);
      res.end(b);
    }).catch((e) => {
      res.writeHead(502, { 'Content-Type': 'text/plain; charset=utf-8' });
      res.end('网关错误: ' + e.message);
    });
  });
});

server.listen(HTTP_PORT, '127.0.0.1', () => {
  console.log(`[gateway] HTTP -> FastCGI(php-cgi @ ${FCGI_HOST}:${FCGI_PORT}) 正在监听 http://127.0.0.1:${HTTP_PORT}`);
  console.log(`[gateway] DOC_ROOT=${DOC_ROOT}`);
});
