<?php
/**
 * qr_scanner_bg.php  —  Always-On Background QR Attendance Scanner
 * Place at: PosandGymSystem/qr_scanner_bg.php
 *
 * Handles a special ?action=settings AJAX call so JS can hot-reload gym name/logo
 * without a full page refresh whenever the admin updates settings.
 *
 * Handles ?action=todaylog AJAX call to fetch today's attendance log from the
 * database, so the log is shared across every device (not just localStorage
 * on whichever device happened to scan the member).
 */
include "config/db.php";

// ── AJAX: return live settings so JS can update header without page reload ──
if (isset($_GET['action']) && $_GET['action'] === 'settings') {
    header('Content-Type: application/json');
    $s = $conn->query("SELECT gym_name, logo_path FROM gym_settings WHERE id = 1")->fetch_assoc();
    echo json_encode($s ?: ['gym_name' => 'Gym', 'logo_path' => '']);
    exit;
}

// ── AJAX: return today's attendance log from the database (shared across devices) ──
if (isset($_GET['action']) && $_GET['action'] === 'todaylog') {
    header('Content-Type: application/json');
    $rows = [];
    $res = $conn->query(
        "SELECT a.id, a.checkin_time, a.checkout_time, m.fullname,
                TIMESTAMPDIFF(SECOND, a.checkin_time, IFNULL(a.checkout_time, NOW())) AS elapsed_secs
         FROM attendance a
         JOIN members m ON m.id = a.member_id
         WHERE DATE(a.checkin_time) = CURDATE()
         ORDER BY a.checkin_time DESC
         LIMIT 60"
    );
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id'   => (int)$r['id'],
                'name' => $r['fullname'],
                'type' => $r['checkout_time'] !== null ? 'checkout' : 'checkin',
                'time' => $r['checkout_time'] !== null ? $r['checkout_time'] : $r['checkin_time'],
            ];
        }
    }
    echo json_encode($rows);
    exit;
}

// ── Normal page load ────────────────────────────────────────────────────────
$settings = $conn->query("SELECT * FROM gym_settings WHERE id = 1")->fetch_assoc();
if (!$settings) {
    $settings = ['gym_name' => 'Olympic Fitness Gym', 'logo_path' => 'gym logo.jpg'];
}
$gym_name = htmlspecialchars($settings['gym_name']);
$logo_src = htmlspecialchars($settings['logo_path']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Scanner — <?php echo $gym_name; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0a0a0f;color:#fff;font-family:'Segoe UI',sans-serif;height:100vh;overflow:hidden;display:flex;flex-direction:column}

/* Header */
.gym-header{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0}
.gym-header .gym-info{display:flex;align-items:center;gap:12px}
#gym-logo{width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.15)}
.gym-header h1{font-size:1.1rem;font-weight:700;margin:0}
.status-dot{width:9px;height:9px;border-radius:50%;background:#22c55e;box-shadow:0 0 8px #22c55e;animation:pulse-dot 2s infinite;display:inline-block;margin-right:6px}
.status-text{font-size:.78rem;color:#22c55e}
#clock{font-size:1rem;color:rgba(255,255,255,.45);font-variant-numeric:tabular-nums}

/* Layout */
.scanner-layout{flex:1;display:grid;grid-template-columns:1fr 380px;overflow:hidden}

/* Camera */
.camera-panel{position:relative;background:#000;display:flex;align-items:center;justify-content:center;overflow:hidden}
#qr-reader{width:100%;height:100%}
#qr-reader video{object-fit:cover!important;width:100%!important;height:100%!important}
#qr-reader__scan_region{width:100%!important;height:100%!important}
#qr-reader__dashboard{display:none!important}
#qr-reader img{display:none!important}

/* Responsive: stack layout on narrow / portrait screens so the camera
   panel gets real height instead of being squeezed by the fixed
   380px side column (this was the main cause of the black-screen-
   until-rotated-to-landscape issue on phones/tablets). */
@media (max-width:900px){
  body{height:100vh;height:100dvh;overflow:auto}
  .scanner-layout{grid-template-columns:1fr;grid-template-rows:55vh auto;overflow:auto}
  .camera-panel{min-height:55vh}
}

/* Scan overlay */
.scan-overlay{position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;justify-content:center}
.scan-frame{width:220px;height:220px;position:relative}
.scan-frame::before,.scan-frame::after,.corner-br,.corner-bl{content:'';position:absolute;width:30px;height:30px;border-color:#22c55e;border-style:solid}
.scan-frame::before{top:0;left:0;border-width:3px 0 0 3px}
.scan-frame::after{top:0;right:0;border-width:3px 3px 0 0}
.corner-br{bottom:0;right:0;border-width:0 3px 3px 0}
.corner-bl{position:absolute;bottom:0;left:0;width:30px;height:30px;border:3px solid #22c55e;border-width:0 0 3px 3px}
.scan-line{position:absolute;left:5%;right:5%;top:0;height:2px;background:linear-gradient(90deg,transparent,#22c55e,transparent);animation:scan-anim 2s linear infinite}
@keyframes scan-anim{0%{top:0}50%{top:100%}100%{top:0}}

.scan-hint{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.6);color:rgba(255,255,255,.7);padding:6px 18px;border-radius:20px;font-size:.8rem;white-space:nowrap}
.scanning-badge{position:absolute;top:14px;left:14px;background:rgba(0,0,0,.6);color:#22c55e;font-size:.72rem;padding:4px 12px;border-radius:20px;display:flex;align-items:center;gap:6px}
.spin{animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

#camera-error{display:none;text-align:center;color:rgba(255,255,255,.5);padding:40px;position:absolute;inset:0;display:none;flex-direction:column;align-items:center;justify-content:center;background:#000}
#camera-error i{font-size:3rem;margin-bottom:16px;color:#ef4444}

/* Result panel */
.result-panel{background:#111118;border-left:1px solid rgba(255,255,255,.07);display:flex;flex-direction:column;overflow:hidden;position:relative}
.panel-title{padding:13px 18px;font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.3);border-bottom:1px solid rgba(255,255,255,.06);flex-shrink:0}

#current-result{flex-shrink:0;padding:16px;border-bottom:1px solid rgba(255,255,255,.06);min-height:150px;display:flex;align-items:center;justify-content:center}
.result-idle{text-align:center;color:rgba(255,255,255,.22)}
.result-idle i{font-size:2.2rem;margin-bottom:8px;display:block}
.result-idle p{font-size:.85rem}

.result-card{width:100%;border-radius:12px;padding:16px;text-align:center;animation:card-in .3s ease}
@keyframes card-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.result-card.checkin {background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3)}
.result-card.checkout{background:rgba(251,191,36,.10);border:1px solid rgba(251,191,36,.3)}
.result-card.error   {background:rgba(239,68,68,.10);border:1px solid rgba(239,68,68,.3)}
.result-card.warning {background:rgba(251,191,36,.10);border:1px solid rgba(251,191,36,.3)}
.rc-icon{font-size:2rem;margin-bottom:8px}
.result-card.checkin  .rc-icon{color:#22c55e}
.result-card.checkout .rc-icon{color:#fbbf24}
.result-card.error    .rc-icon{color:#ef4444}
.result-card.warning  .rc-icon{color:#fbbf24}
.rc-label{font-size:.62rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;opacity:.55;margin-bottom:4px}
.rc-name{font-size:1.15rem;font-weight:700;margin-bottom:4px}
.rc-msg{font-size:.78rem;opacity:.72}
.rc-info{font-size:.7rem;opacity:.45;margin-top:6px}

/* Voice indicator */
#voice-indicator{display:none;align-items:center;gap:8px;padding:8px 18px;background:rgba(99,102,241,.12);border-bottom:1px solid rgba(99,102,241,.2);font-size:.75rem;color:#a5b4fc;flex-shrink:0}
#voice-indicator.active{display:flex}
.voice-bars{display:flex;align-items:flex-end;gap:2px;height:16px}
.voice-bars span{width:3px;background:#818cf8;border-radius:2px;animation:voice-bar .6s ease-in-out infinite alternate}
.voice-bars span:nth-child(1){height:4px;animation-delay:0s}
.voice-bars span:nth-child(2){height:10px;animation-delay:.1s}
.voice-bars span:nth-child(3){height:16px;animation-delay:.2s}
.voice-bars span:nth-child(4){height:10px;animation-delay:.3s}
.voice-bars span:nth-child(5){height:4px;animation-delay:.4s}
@keyframes voice-bar{to{height:16px;opacity:.4}}

/* Log */
.log-title{padding:10px 18px 6px 18px;font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.28);flex-shrink:0;display:flex;align-items:center;justify-content:space-between}
.log-title span{display:flex;align-items:center;gap:6px}
#btn-clear-log{background:none;border:1px solid rgba(239,68,68,.35);color:rgba(239,68,68,.7);font-size:.65rem;padding:2px 8px;border-radius:10px;cursor:pointer;letter-spacing:.04em;font-weight:600;transition:all .2s;text-transform:uppercase}
#btn-clear-log:hover{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.7);color:#ef4444}

/* Confirm clear overlay */
#clear-confirm{display:none;position:absolute;bottom:0;left:0;right:0;background:#1a1a24;border-top:1px solid rgba(239,68,68,.3);padding:14px 16px;z-index:10;flex-direction:column;gap:10px}
#clear-confirm.show{display:flex}
#clear-confirm p{font-size:.8rem;color:rgba(255,255,255,.75);margin:0;text-align:center}
#clear-confirm p strong{color:#ef4444}
.confirm-btns{display:flex;gap:8px}
.confirm-btns button{flex:1;padding:6px 0;border-radius:8px;border:none;font-size:.78rem;font-weight:600;cursor:pointer;transition:background .2s}
#btn-confirm-yes{background:#ef4444;color:#fff}
#btn-confirm-yes:hover{background:#dc2626}
#btn-confirm-no{background:rgba(255,255,255,.08);color:rgba(255,255,255,.7)}
#btn-confirm-no:hover{background:rgba(255,255,255,.14)}
#scan-log{flex:1;overflow-y:auto;padding:0 10px 10px}
#scan-log::-webkit-scrollbar{width:4px}
#scan-log::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:2px}
.log-item{display:flex;align-items:flex-start;gap:10px;padding:8px;border-radius:8px;margin-bottom:4px;font-size:.76rem;background:rgba(255,255,255,.03)}
.log-dot{width:8px;height:8px;border-radius:50%;margin-top:4px;flex-shrink:0}
.li-checkin  .log-dot{background:#22c55e}
.li-checkout .log-dot{background:#fbbf24}
.li-error    .log-dot{background:#ef4444}
.li-warning  .log-dot{background:#f97316}
.log-body{flex:1}
.log-name{font-weight:600;color:#fff}
.log-sub{color:rgba(255,255,255,.4);font-size:.7rem}
.log-time{color:rgba(255,255,255,.28);font-size:.66rem;flex-shrink:0;margin-top:2px}

/* Flash overlay */
#flash-overlay{position:fixed;inset:0;pointer-events:none;z-index:9999;opacity:0;transition:opacity .1s}

@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.35}}
</style>
</head>
<body>

<div id="flash-overlay"></div>

<!-- Header — gym name & logo update live via JS polling -->
<div class="gym-header">
  <div class="gym-info">
    <img id="gym-logo" src="<?php echo $logo_src; ?>" alt="Logo" onerror="this.style.display='none'">
    <div>
      <h1 id="gym-name"><?php echo $gym_name; ?></h1>
      <div><span class="status-dot"></span><span class="status-text">Scanner Active — Always On</span></div>
    </div>
  </div>
  <div id="clock">--:--:--</div>
</div>

<div class="scanner-layout">

  <!-- Camera panel -->
  <div class="camera-panel">
    <div id="qr-reader"></div>

    <div class="scan-overlay">
      <div class="scan-frame">
        <div class="scan-line"></div>
        <div class="corner-br"></div>
        <div class="corner-bl"></div>
      </div>
    </div>

    <div class="scanning-badge">
      <i class="fas fa-circle-notch spin"></i> Scanning…
    </div>

    <div class="scan-hint">
      <i class="fas fa-qrcode me-1"></i> Present QR code to camera
    </div>

    <div id="camera-error">
      <i class="fas fa-video-slash"></i>
      <p style="font-size:1rem;margin-bottom:8px">Camera not detected or permission denied.</p>
      <p style="font-size:.8rem;margin-bottom:16px">Allow camera access in your browser, then retry.</p>
      <button class="btn btn-sm btn-outline-light" onclick="initScanner()">
        <i class="fas fa-redo me-1"></i> Retry Camera
      </button>
    </div>
  </div>

  <!-- Result panel -->
  <div class="result-panel">
    <div class="panel-title"><i class="fas fa-bolt me-1"></i> Last Scan</div>

    <div id="current-result">
      <div class="result-idle">
        <i class="fas fa-qrcode"></i>
        <p>Waiting for QR code…</p>
      </div>
    </div>

    <!-- Voice speaking indicator -->
    <div id="voice-indicator">
      <div class="voice-bars">
        <span></span><span></span><span></span><span></span><span></span>
      </div>
      <span id="voice-text">Speaking…</span>
    </div>

    <div class="log-title">
      <span><i class="fas fa-history"></i> Today's Log</span>
      <button id="btn-clear-log" onclick="askClearLog()" title="Clear today's log">
        <i class="fas fa-trash-alt me-1"></i>Clear
      </button>
    </div>
    <div id="scan-log"></div>

    <!-- Confirm clear dialog (slides up from bottom of panel) -->
    <div id="clear-confirm">
      <p><strong>Clear all logs?</strong><br>This removes today's scan history from this screen.</p>
      <div class="confirm-btns">
        <button id="btn-confirm-no"  onclick="cancelClearLog()"><i class="fas fa-times me-1"></i>Cancel</button>
        <button id="btn-confirm-yes" onclick="confirmClearLog()"><i class="fas fa-trash me-1"></i>Yes, Clear</button>
      </div>
    </div>
  </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════════════════
//  CLOCK
// ═══════════════════════════════════════════════════════════════════════════
function updateClock() {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('en-PH', {hour12: true});
}
setInterval(updateClock, 1000);
updateClock();

// ═══════════════════════════════════════════════════════════════════════════
//  LIVE GYM SETTINGS POLL (every 30s — picks up logo/name changes instantly)
// ═══════════════════════════════════════════════════════════════════════════
function pollSettings() {
  fetch('qr_scanner_bg.php?action=settings')
    .then(r => r.json())
    .then(data => {
      const nameEl = document.getElementById('gym-name');
      const logoEl = document.getElementById('gym-logo');
      if (data.gym_name && nameEl.textContent !== data.gym_name) {
        nameEl.textContent = data.gym_name;
        document.title = 'QR Scanner — ' + data.gym_name;
      }
      if (data.logo_path && logoEl.getAttribute('src') !== data.logo_path) {
        logoEl.src = data.logo_path;
        logoEl.style.display = '';
      }
    })
    .catch(() => {}); // silent fail
}
setInterval(pollSettings, 30000);

// ═══════════════════════════════════════════════════════════════════════════
//  AUDIO ENGINE — Web Audio API beeps (no external files)
// ═══════════════════════════════════════════════════════════════════════════
const _AC = window.AudioContext || window.webkitAudioContext;
let _actx = null;
function getACtx() { if (!_actx) _actx = new _AC(); return _actx; }

function tone(freq, dur, type = 'sine', vol = 0.45, delay = 0) {
  const ctx = getACtx(), osc = ctx.createOscillator(), g = ctx.createGain();
  osc.connect(g); g.connect(ctx.destination);
  osc.type = type;
  osc.frequency.setValueAtTime(freq, ctx.currentTime + delay);
  g.gain.setValueAtTime(0, ctx.currentTime + delay);
  g.gain.linearRampToValueAtTime(vol, ctx.currentTime + delay + 0.01);
  g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + dur);
  osc.start(ctx.currentTime + delay);
  osc.stop(ctx.currentTime + delay + dur + 0.05);
}

const beeps = {
  checkin()  { tone(660,.12,'sine',.45,0); tone(880,.22,'sine',.45,.16); },
  checkout() { tone(880,.12,'sine',.4,0);  tone(660,.22,'sine',.4,.16); },
  error()    { tone(200,.4,'sawtooth',.4,0); tone(160,.4,'sawtooth',.4,.48); },
  warning()  { tone(440,.14,'square',.35,0); tone(440,.14,'square',.35,.22); tone(440,.14,'square',.35,.44); }
};

// Unlock audio on first click (browser policy)
document.addEventListener('click', () => { getACtx().resume(); }, { once: true });

// ═══════════════════════════════════════════════════════════════════════════
//  SPEECH ENGINE — Web Speech API voice announcements
// ═══════════════════════════════════════════════════════════════════════════
const synth = window.speechSynthesis;

// Pick a clear English voice — prefer a female en-PH or en-US voice
let _voice = null;
function pickVoice() {
  const voices = synth.getVoices();
  // Priority: en-PH → en-US female → any en voice
  const preferred = ['en-PH', 'en-US', 'en-GB', 'en-AU'];
  for (const lang of preferred) {
    const v = voices.find(v =>
      v.lang.startsWith(lang) && v.name.toLowerCase().includes('female')
    ) || voices.find(v => v.lang.startsWith(lang));
    if (v) { _voice = v; return; }
  }
  if (voices.length) _voice = voices[0];
}
synth.addEventListener('voiceschanged', pickVoice);
pickVoice();

function speak(text, onEnd) {
  if (!synth) { onEnd && onEnd(); return; }
  synth.cancel(); // stop any current speech

  const utter = new SpeechSynthesisUtterance(text);
  utter.voice  = _voice;
  utter.lang   = 'en-PH';
  utter.rate   = 0.92;   // slightly slower = clearer
  utter.pitch  = 1.05;
  utter.volume = 1.0;

  // Show / hide voice indicator
  const indicator = document.getElementById('voice-indicator');
  const voiceText = document.getElementById('voice-text');
  indicator.classList.add('active');
  voiceText.textContent = text;

  utter.onend = utter.onerror = () => {
    indicator.classList.remove('active');
    onEnd && onEnd();
  };

  synth.speak(utter);
}

// ── Voice lines per event ──────────────────────────────────────────────────
// name = member's full name extracted from API response
const voices = {
  checkin(name) {
    speak(`Attendance recorded. Welcome, ${name}!`);
  },
  checkout(name, duration) {
    speak(`Check-out successful. Goodbye, ${name}. You stayed for ${duration}.`);
  },
  expired(name) {
    speak(`Sorry, ${name ? name + ', your' : 'this'} membership has expired. Please renew at the front desk.`);
  },
  unknown() {
    speak('Access denied. QR code not recognized. Please see the staff.');
  },
  tooSoon() {
    speak('Too soon to check out. Please wait a few more minutes.');
  },
  error() {
    speak('System error. Please try again or see the staff.');
  },
  alreadyDone() {
    speak('You have already completed your visit for today.');
  }
};

// ═══════════════════════════════════════════════════════════════════════════
//  FLASH OVERLAY
// ═══════════════════════════════════════════════════════════════════════════
function flash(color) {
  const el = document.getElementById('flash-overlay');
  el.style.background = color;
  el.style.opacity = '0.2';
  setTimeout(() => { el.style.opacity = '0'; }, 220);
}

// ═══════════════════════════════════════════════════════════════════════════
//  LOG — persisted in localStorage, auto-clears at midnight
// ═══════════════════════════════════════════════════════════════════════════
const LOG_KEY = 'gym_scanner_log';
const LOG_HIDDEN_KEY = 'gym_scanner_log_hidden_until_new_scan';

// Load saved log entries for today only
function loadLog() {
  try {
    const saved = JSON.parse(localStorage.getItem(LOG_KEY) || '[]');
    const today = new Date().toDateString();
    // Keep only entries from today
    return saved.filter(e => e.date === today);
  } catch(e) { return []; }
}

// Save log entries to localStorage
function saveLog(entries) {
  try {
    localStorage.setItem(LOG_KEY, JSON.stringify(entries.slice(0, 60)));
  } catch(e) {}
}

// Render a single log entry object into the DOM
function renderLogItem(entry, prepend = true) {
  const log  = document.getElementById('scan-log');
  const item = document.createElement('div');
  item.className = `log-item li-${entry.type}`;
  item.innerHTML = `
    <div class="log-dot"></div>
    <div class="log-body">
      <div class="log-name">${escHtml(entry.name)}</div>
      <div class="log-sub">${escHtml(entry.msg)}</div>
    </div>
    <div class="log-time">${escHtml(entry.time)}</div>`;
  if (prepend) log.prepend(item); else log.appendChild(item);
}

// Restore today's log from the SERVER (database) so every device sees the
// same shared log — localStorage alone only reflected scans made on that
// specific device/browser, which is why the log wasn't visible on other
// phones/tablets.
let lastKnownEntryKey = localStorage.getItem('gym_scanner_last_entry') || '';

function restoreLog() {
  fetch('qr_scanner_bg.php?action=todaylog')
    .then(r => r.json())
    .then(rows => {
      const entries = (rows || []).map(r => ({
        type: r.type === 'checkout' ? 'checkout' : 'checkin',
        name: r.name,
        msg: r.type === 'checkout' ? 'Checked out' : 'Checked in successfully',
        time: new Date(r.time.replace(' ', 'T')).toLocaleTimeString('en-PH', {hour12:true, hour:'2-digit', minute:'2-digit'}),
        date: new Date().toDateString(),
        _key: r.id + '-' + r.type
      }));

      const hiddenDate = localStorage.getItem(LOG_HIDDEN_KEY);
      const newestKey = entries.length ? entries[0]._key : '';

      // If this device hid the log, and no genuinely NEW scan has arrived
      // since hiding, keep showing the "cleared" state instead of refilling
      // it from the poll.
      if (hiddenDate === new Date().toDateString() && newestKey === lastKnownEntryKey) {
        return;
      }

      // A new scan arrived (or log was never hidden) — show it and clear
      // the hidden flag so the log behaves normally again.
      if (hiddenDate) localStorage.removeItem(LOG_HIDDEN_KEY);
      lastKnownEntryKey = newestKey;
      localStorage.setItem('gym_scanner_last_entry', newestKey);

      saveLog(entries); // keep a local cache too
      renderLogEntries(entries);
    })
    .catch(() => {
      // Server unreachable — fall back to whatever this device has locally
      renderLogEntries(loadLog());
    });
}

// Render a full list of entries into the DOM (replacing current contents)
function renderLogEntries(entries) {
  const log = document.getElementById('scan-log');
  log.innerHTML = '';

  if (!entries || entries.length === 0) {
    log.innerHTML = '<div style="text-align:center;color:rgba(255,255,255,.2);font-size:.75rem;padding:20px 0">No scans yet today</div>';
    return;
  }
  entries.forEach(e => renderLogItem(e, false));
}

// Poll the server periodically so this device's log stays in sync with
// scans happening on other devices/scanners.
setInterval(restoreLog, 15000);

// Add a new log entry — saves to localStorage AND renders to DOM
function addLog(type, name, msg) {
  const now   = new Date().toLocaleTimeString('en-PH', {hour12:true, hour:'2-digit', minute:'2-digit'});
  const today = new Date().toDateString();
  const entry = { type, name, msg, time: now, date: today };

  // Load existing, prepend new entry, save back
  const existing = loadLog();
  existing.unshift(entry); // newest first
  saveLog(existing);

  // Update DOM — clear "no scans" placeholder if present
  const log = document.getElementById('scan-log');
  const placeholder = log.querySelector('div[style]');
  if (placeholder) log.innerHTML = '';

  renderLogItem(entry, true);
  while (log.children.length > 60) log.removeChild(log.lastChild);
}

// Auto-clear log at midnight (check every minute)
function scheduleMidnightClear() {
  const now     = new Date();
  const midnight = new Date(now);
  midnight.setHours(24, 0, 0, 0);
  const msToMidnight = midnight - now;

  setTimeout(() => {
    localStorage.removeItem(LOG_KEY);
    document.getElementById('scan-log').innerHTML =
      '<div style="text-align:center;color:rgba(255,255,255,.2);font-size:.75rem;padding:20px 0">New day — log cleared</div>';
    scheduleMidnightClear(); // schedule next midnight
  }, msToMidnight);
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ═══════════════════════════════════════════════════════════════════════════
//  RESULT CARD
// ═══════════════════════════════════════════════════════════════════════════
const iconMap  = { checkin:'fa-sign-in-alt', checkout:'fa-sign-out-alt', error:'fa-ban', warning:'fa-exclamation-triangle' };
const labelMap = { checkin:'Checked In',     checkout:'Checked Out',     error:'Access Denied', warning:'Warning' };

function showResult(type, name, message, info) {
  document.getElementById('current-result').innerHTML = `
    <div class="result-card ${type}">
      <div class="rc-icon"><i class="fas ${iconMap[type] || 'fa-info-circle'}"></i></div>
      <div class="rc-label">${labelMap[type] || type}</div>
      <div class="rc-name">${escHtml(name)}</div>
      <div class="rc-msg">${escHtml(message)}</div>
      ${info ? `<div class="rc-info"><i class="fas fa-info-circle me-1"></i>${escHtml(info)}</div>` : ''}
    </div>`;
}

function showIdle() {
  document.getElementById('current-result').innerHTML = `
    <div class="result-idle">
      <i class="fas fa-qrcode"></i>
      <p>Waiting for QR code…</p>
    </div>`;
}

// ═══════════════════════════════════════════════════════════════════════════
//  EXTRACT NAME from API success string
// ═══════════════════════════════════════════════════════════════════════════
function extractName(str, keyword) {
  const idx = str.indexOf(keyword);
  if (idx === -1) return str;
  return str.substring(idx + keyword.length)
            .replace(/[.,!].*$/s, '')
            .trim();
}

function extractDuration(str) {
  // "Duration: 2h 15m" or "Duration: 5 minutes"
  const m = str.match(/Duration:\s*(.+?)\./i);
  return m ? m[1] : '';
}

// ═══════════════════════════════════════════════════════════════════════════
//  CLEAR LOG
// ═══════════════════════════════════════════════════════════════════════════
function askClearLog() {
  document.getElementById('clear-confirm').classList.add('show');
}

function cancelClearLog() {
  document.getElementById('clear-confirm').classList.remove('show');
}

function confirmClearLog() {
  // This only hides the log on THIS device's view. Attendance records stay
  // in the database for reports — other devices, and this device's reports,
  // are unaffected.
  localStorage.removeItem(LOG_KEY);
  localStorage.setItem(LOG_HIDDEN_KEY, new Date().toDateString());

  // Clear the DOM
  const log = document.getElementById('scan-log');
  log.innerHTML = '<div style="text-align:center;color:rgba(255,255,255,.2);font-size:.75rem;padding:20px 0"><i class="fas fa-check-circle me-1" style="color:#22c55e"></i>Log cleared on this device</div>';

  // Hide confirm dialog
  document.getElementById('clear-confirm').classList.remove('show');
}

// ═══════════════════════════════════════════════════════════════════════════
//  QR PROCESSING
// ═══════════════════════════════════════════════════════════════════════════
let isProcessing = false;

function processQR(token) {
  if (isProcessing) return;
  isProcessing = true;
  getACtx().resume();

  fetch('qr_attendance.php?token=' + encodeURIComponent(token), {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(data => {

    if (data.success) {
      // ── CHECK IN ──────────────────────────────────────────────────────
      if (data.type === 'checkin') {
        const name = extractName(data.success, 'Welcome, ');
        beeps.checkin();
        flash('#22c55e');
        showResult('checkin', name, 'Welcome! Attendance recorded.', data.info || '');
        addLog('checkin', name, 'Checked in successfully');
        voices.checkin(name);

      // ── CHECK OUT ─────────────────────────────────────────────────────
      } else if (data.type === 'checkout') {
        const name     = extractName(data.success, 'Goodbye, ');
        const duration = extractDuration(data.success);
        beeps.checkout();
        flash('#fbbf24');
        showResult('checkout', name, data.success, '');
        addLog('checkout', name, 'Checked out' + (duration ? ' — ' + duration : ''));
        voices.checkout(name, duration);
      }

    } else if (data.type === 'too_soon') {
      // ── TOO SOON ──────────────────────────────────────────────────────
      beeps.warning();
      flash('#f97316');
      showResult('warning', 'Too Soon', data.error, '');
      addLog('warning', 'Too Soon', data.error);
      voices.tooSoon();

    } else {
      // ── ERROR — figure out which kind ─────────────────────────────────
      const msg = data.error || 'Unknown error';
      const lmsg = msg.toLowerCase();

      if (lmsg.includes('expired')) {
        // Membership expired — extract name if possible
        const name = extractName(msg, 'for ') || '';
        beeps.error();
        flash('#ef4444');
        showResult('error', name || 'Expired Member', 'Membership has expired.', 'Please visit the front desk to renew.');
        addLog('error', name || 'Expired Member', msg);
        voices.expired(name);

      } else if (lmsg.includes('not found') || lmsg.includes('invalid')) {
        beeps.error();
        flash('#ef4444');
        showResult('error', 'Unknown QR Code', msg, 'This QR code is not registered in the system.');
        addLog('error', 'Unknown', msg);
        voices.unknown();

      } else if (lmsg.includes('already checked in') || lmsg.includes('checked out today')) {
        beeps.warning();
        flash('#f97316');
        showResult('warning', 'Already Recorded', msg, '');
        addLog('warning', 'Already Done', msg);
        voices.alreadyDone();

      } else {
        beeps.error();
        flash('#ef4444');
        showResult('error', 'Error', msg, '');
        addLog('error', 'System Error', msg);
        voices.error();
      }
    }

    // Show result for 5 seconds then go back to idle
    setTimeout(() => { showIdle(); isProcessing = false; }, 5000);
  })
  .catch(() => {
    beeps.error();
    showResult('error', 'Connection Error', 'Could not reach server.', 'Check network connection.');
    addLog('error', 'System', 'Server unreachable');
    voices.error();
    setTimeout(() => { showIdle(); isProcessing = false; }, 4000);
  });
}

// ═══════════════════════════════════════════════════════════════════════════
//  QR CAMERA
// ═══════════════════════════════════════════════════════════════════════════
let html5Qrcode = null;

function initScanner() {
  const errEl = document.getElementById('camera-error');
  errEl.style.display = 'none';

  const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

  const doStart = () => {
    html5Qrcode = new Html5Qrcode('qr-reader');

    const config = {
      fps: 15,
      qrbox: (vw, vh) => {
        const size = Math.floor(Math.min(vw, vh) * 0.7);
        return { width: size, height: size };
      },
      formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
      // NOTE: no forced aspectRatio here — on phones, forcing a
      // widescreen (1.7) ratio onto getUserMedia made the browser
      // fail to start the stream in portrait orientation, which is
      // why the camera only worked after rotating to landscape.
    };

    Html5Qrcode.getCameras()
      .then(cameras => {
        if (!cameras || cameras.length === 0) throw new Error('No cameras');

        if (isMobile) {
          // On phones/tablets, let the browser pick the rear camera
          // via facingMode instead of guessing from (often empty)
          // camera labels — this is far more reliable on mobile.
          return html5Qrcode.start(
            { facingMode: 'environment' },
            config,
            (decoded) => processQR(decoded),
            () => {}
          );
        }

        // Desktop: prefer USB/external camera by label as before.
        let camId = cameras[cameras.length - 1].id;
        for (const cam of cameras) {
          const lbl = (cam.label || '').toLowerCase();
          if (lbl.includes('usb') || lbl.includes('external') || lbl.includes('back')) {
            camId = cam.id; break;
          }
        }

        return html5Qrcode.start(
          camId,
          config,
          (decoded) => processQR(decoded),
          () => {}
        );
      })
      .catch(err => {
        console.error('Camera error:', err);
        // If facingMode failed for some reason on a mobile device,
        // fall back to picking a camera by id so we still get a feed.
        if (isMobile) {
          Html5Qrcode.getCameras()
            .then(cameras => {
              if (!cameras || cameras.length === 0) throw new Error('No cameras');
              let camId = cameras[cameras.length - 1].id;
              for (const cam of cameras) {
                const lbl = (cam.label || '').toLowerCase();
                if (lbl.includes('back') || lbl.includes('rear')) { camId = cam.id; break; }
              }
              return html5Qrcode.start(camId, config, (decoded) => processQR(decoded), () => {});
            })
            .catch(err2 => {
              console.error('Camera fallback error:', err2);
              errEl.style.display = 'flex';
            });
        } else {
          errEl.style.display = 'flex';
        }
      });
  };

  if (html5Qrcode) {
    html5Qrcode.stop().catch(() => {}).finally(doStart);
  } else {
    doStart();
  }
}

// Auto-recover if scanner stops unexpectedly
setInterval(() => {
  if (html5Qrcode && typeof html5Qrcode.getState === 'function') {
    try {
      if (html5Qrcode.getState() === Html5QrcodeScannerState.NOT_STARTED) initScanner();
    } catch(e) {}
  }
}, 12000);

// Restart camera on orientation change so a rotation never leaves the
// video element stuck on a stale/black frame.
let orientationTimer = null;
window.addEventListener('orientationchange', () => {
  clearTimeout(orientationTimer);
  orientationTimer = setTimeout(initScanner, 400);
});

// ═══════════════════════════════════════════════════════════════════════════
//  BOOT
// ═══════════════════════════════════════════════════════════════════════════
window.addEventListener('load', () => {
  restoreLog();           // reload today's log from localStorage
  scheduleMidnightClear(); // auto-clear at midnight
  setTimeout(initScanner, 600);
});
</script>
</body>
</html>
