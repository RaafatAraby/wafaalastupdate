// Wafaa WhatsApp Bridge — minimal Express + Baileys service.
//
// Endpoints (all require X-API-Key except /health):
//   GET  /health        → liveness probe
//   GET  /status        → { connected, state, phone, lastConnectedAt, qr, qrAge }
//   GET  /qr            → returns latest QR as PNG image
//   POST /send          → { phone, message } → sends a WhatsApp text
//   POST /disconnect    → wipes auth + forces re-pair
//
// Auth is a single shared secret in the X-API-Key header. Set the same
// value in Laravel's .env as WHATSAPP_BRIDGE_KEY.

const express = require('express');
const fs = require('fs');
const path = require('path');
const QRCode = require('qrcode');
const pino = require('pino');
const { Boom } = require('@hapi/boom');
const {
  default: makeWASocket,
  useMultiFileAuthState,
  DisconnectReason,
  fetchLatestBaileysVersion,
} = require('@whiskeysockets/baileys');

require('dotenv').config({ path: path.join(__dirname, '.env') });

const PORT = parseInt(process.env.PORT || '3000', 10);
const API_KEY = (process.env.API_KEY || '').trim();
const AUTH_DIR = path.resolve(process.env.AUTH_DIR || path.join(__dirname, 'auth_info'));
const CORS_ORIGINS = (process.env.CORS_ORIGINS || '*').split(',').map((s) => s.trim());
// Optional URL prefix (e.g. "/whatsapp-bridge") so the same code works
// whether mounted at the host root or under a sub-path in cPanel.
const BASE_PATH = (process.env.BASE_PATH || '').replace(/\/+$/, '');

if (!API_KEY) {
  console.error('FATAL: API_KEY env var is required. Aborting.');
  process.exit(1);
}

if (!fs.existsSync(AUTH_DIR)) {
  fs.mkdirSync(AUTH_DIR, { recursive: true });
}

const logger = pino({ level: process.env.LOG_LEVEL || 'warn' });

// ───── Connection state ──────────────────────────────────────────────
const state = {
  sock: null,
  connection: 'unknown',     // 'open' | 'connecting' | 'qr' | 'close' | 'logged_out' | 'unknown'
  qrRaw: null,
  qrPng: null,               // data: URL
  qrAt: null,
  phone: null,
  lastConnectedAt: null,
  startingUp: false,
};

async function startSocket() {
  if (state.startingUp) return;
  state.startingUp = true;

  try {
    const { state: authState, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    const { version } = await fetchLatestBaileysVersion();

    const sock = makeWASocket({
      version,
      auth: authState,
      printQRInTerminal: false,
      logger,
      browser: ['Wafaa System', 'Chrome', '1.0'],
      syncFullHistory: false,
      markOnlineOnConnect: false,
    });

    state.sock = sock;
    state.connection = 'connecting';

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
      const { connection, lastDisconnect, qr } = update;

      if (qr) {
        state.qrRaw = qr;
        state.qrAt = Date.now();
        try {
          state.qrPng = await QRCode.toDataURL(qr, { margin: 1, scale: 6 });
        } catch (e) {
          state.qrPng = null;
        }
        state.connection = 'qr';
      }

      if (connection === 'open') {
        state.connection = 'open';
        state.qrRaw = null;
        state.qrPng = null;
        state.qrAt = null;
        state.lastConnectedAt = new Date().toISOString();
        const id = sock.user?.id || '';
        state.phone = id ? id.split(':')[0].split('@')[0] : null;
      }

      if (connection === 'close') {
        const code = (lastDisconnect?.error instanceof Boom)
          ? lastDisconnect.error.output?.statusCode
          : null;

        if (code === DisconnectReason.loggedOut) {
          state.connection = 'logged_out';
          // Wipe auth so next start produces a fresh QR.
          try { fs.rmSync(AUTH_DIR, { recursive: true, force: true }); } catch (_) {}
          fs.mkdirSync(AUTH_DIR, { recursive: true });
        } else {
          state.connection = 'close';
        }

        // Auto-reconnect (unless we were explicitly logged out and waiting for QR).
        setTimeout(() => {
          state.startingUp = false;
          startSocket().catch((e) => logger.error({ err: e }, 'reconnect failed'));
        }, 1500);
        return;
      }
    });
  } catch (e) {
    logger.error({ err: e }, 'startSocket error');
    setTimeout(() => {
      state.startingUp = false;
      startSocket().catch(() => {});
    }, 3000);
    return;
  }

  state.startingUp = false;
}

// ───── HTTP API ──────────────────────────────────────────────────────
const app = express();
const router = express.Router();
app.use(express.json({ limit: '128kb' }));

app.use((req, res, next) => {
  const origin = req.headers.origin;
  if (CORS_ORIGINS.includes('*')) {
    res.setHeader('Access-Control-Allow-Origin', '*');
  } else if (origin && CORS_ORIGINS.includes(origin)) {
    res.setHeader('Access-Control-Allow-Origin', origin);
  }
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, X-API-Key');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  if (req.method === 'OPTIONS') return res.status(204).end();
  next();
});

function authGuard(req, res, next) {
  const provided = req.headers['x-api-key'];
  if (provided !== API_KEY) {
    return res.status(401).json({ error: 'unauthorized' });
  }
  next();
}

router.get('/health', (req, res) => {
  res.json({ ok: true, uptime: process.uptime(), connection: state.connection });
});

router.get('/status', authGuard, (req, res) => {
  res.json({
    connected: state.connection === 'open',
    state: state.connection,
    phone: state.phone,
    lastConnectedAt: state.lastConnectedAt,
    qr: state.qrPng,
    qrAge: state.qrAt ? Math.round((Date.now() - state.qrAt) / 1000) : null,
  });
});

router.get('/qr', authGuard, async (req, res) => {
  if (!state.qrRaw) return res.status(404).send('no QR available');
  try {
    const png = await QRCode.toBuffer(state.qrRaw, { margin: 1, scale: 6 });
    res.setHeader('Content-Type', 'image/png');
    res.send(png);
  } catch (e) {
    res.status(500).send('qr render failed');
  }
});

router.post('/send', authGuard, async (req, res) => {
  if (state.connection !== 'open' || !state.sock) {
    return res.status(503).json({ error: 'not_connected', state: state.connection });
  }

  const { phone, message } = req.body || {};
  if (!phone || !message) {
    return res.status(400).json({ error: 'phone and message are required' });
  }

  const normalised = String(phone).replace(/\D+/g, '');
  if (normalised.length < 8) {
    return res.status(400).json({ error: 'invalid phone' });
  }

  const jid = `${normalised}@s.whatsapp.net`;
  try {
    await state.sock.sendMessage(jid, { text: String(message) });
    res.json({ success: true, jid });
  } catch (e) {
    logger.error({ err: e }, 'send failed');
    res.status(500).json({ error: 'send_failed', message: e.message });
  }
});

router.post('/disconnect', authGuard, async (req, res) => {
  try {
    if (state.sock) {
      try { await state.sock.logout(); } catch (_) {}
      try { state.sock.end(undefined); } catch (_) {}
    }
  } finally {
    try { fs.rmSync(AUTH_DIR, { recursive: true, force: true }); } catch (_) {}
    fs.mkdirSync(AUTH_DIR, { recursive: true });
    state.sock = null;
    state.connection = 'connecting';
    state.qrRaw = null;
    state.qrPng = null;
    state.qrAt = null;
    state.phone = null;
  }
  setTimeout(() => startSocket().catch(() => {}), 500);
  res.json({ success: true });
});

// Mount the routes under both the configured BASE_PATH and the host
// root, so the same image works whether the platform strips the
// prefix (Heroku-style) or forwards it (cPanel-style).
if (BASE_PATH) {
  app.use(BASE_PATH, router);
}
app.use('/', router);

app.listen(PORT, () => {
  console.log(`Wafaa WhatsApp bridge listening on :${PORT} (base path: '${BASE_PATH || '/'}')`);
  startSocket().catch((e) => console.error('startSocket failed:', e));
});
