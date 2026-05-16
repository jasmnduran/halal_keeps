        </div><!-- /.admin-content -->
    </main><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<!-- Session timeout warning -->
<div id="sessionWarning">
    <h4><i class="fas fa-clock"></i> Session Expiring Soon</h4>
    <p>Your session will expire in <span id="warningCountdown">60</span> seconds due to inactivity.</p>
    <button onclick="extendSession()" class="btn btn-sm btn-primary" style="width:100%">
        <i class="fas fa-refresh"></i> Stay Logged In
    </button>
</div>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
// ── Session timeout countdown ────────────────────────────────────────────────
(function () {
    const TIMEOUT   = <?= SESSION_TIMEOUT ?>;   // seconds
    const WARN_AT   = 60;                        // warn 60s before expiry
    let   remaining = TIMEOUT;
    const timerEl   = document.getElementById('sessionTimer');
    const warnEl    = document.getElementById('sessionWarning');
    const warnCount = document.getElementById('warningCountdown');

    function fmt(s) {
        const m = Math.floor(s / 60), sec = s % 60;
        return m + ':' + String(sec).padStart(2, '0');
    }

    const tick = setInterval(() => {
        remaining--;
        if (timerEl) timerEl.textContent = 'Session: ' + fmt(remaining);

        if (remaining <= WARN_AT) {
            warnEl.style.display = 'block';
            if (warnCount) warnCount.textContent = remaining;
        }
        if (remaining <= 0) {
            clearInterval(tick);
            window.location.href = '<?= BASE_URL ?>auth/logout.php?timeout=1';
        }
    }, 1000);

    // Reset on any user interaction
    ['mousemove', 'keydown', 'click', 'scroll'].forEach(ev => {
        document.addEventListener(ev, () => {
            remaining = TIMEOUT;
            warnEl.style.display = 'none';
            // Ping server to keep PHP session alive
            fetch('<?= BASE_URL ?>admin/api/ping_session.php', { method: 'POST' }).catch(() => {});
        }, { passive: true });
    });
})();

function extendSession() {
    fetch('<?= BASE_URL ?>admin/api/ping_session.php', { method: 'POST' })
        .then(() => { document.getElementById('sessionWarning').style.display = 'none'; })
        .catch(() => {});
}

// ── Screenshot / copy restriction (DLP) ─────────────────────────────────────
document.addEventListener('keydown', function (e) {
    // Block PrintScreen
    if (e.key === 'PrintScreen') {
        navigator.clipboard.writeText('').catch(() => {});
        showDlpWarning('Screenshot blocked. This action has been logged.');
        logDlpEvent('screenshot_attempt');
    }
    // Block Ctrl+P (print)
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        showDlpWarning('Printing is restricted in the admin panel.');
        logDlpEvent('print_attempt');
    }
    // Block Ctrl+S (save page)
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        logDlpEvent('save_attempt');
    }
});

// Warn on copy of sensitive data
document.addEventListener('copy', function () {
    const sel = window.getSelection()?.toString() || '';
    if (sel.length > 50) {
        logDlpEvent('large_copy', sel.length + ' chars copied');
    }
});

function showDlpWarning(msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fbbf24;padding:14px 24px;border-radius:10px;z-index:99999;font-size:.85rem;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,.3)';
    t.innerHTML = '<i class="fas fa-shield-alt" style="margin-right:8px"></i>' + msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}

function logDlpEvent(type, detail) {
    fetch('<?= BASE_URL ?>admin/api/log_dlp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type, detail: detail || '' })
    }).catch(() => {});
}

// ── Disable right-click context menu on sensitive tables ─────────────────────
document.querySelectorAll('.sensitive-table').forEach(el => {
    el.addEventListener('contextmenu', e => {
        e.preventDefault();
        showDlpWarning('Right-click is disabled on sensitive data.');
    });
});
</script>
</body>
</html>
