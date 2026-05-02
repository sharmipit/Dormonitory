<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Security Center | Dormonitory</title>

  <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css" />
  <link rel="stylesheet" href="/Dormonitory/assets/css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

  <style>
    .ann-badge.critical {
      background: #fee2e2;
      color: #dc2626;
    }

    .ann-badge.warning {
      background: #fef9c3;
      color: #d97706;
    }

    .ann-badge.info {
      background: #ede9fe;
      color: #3030b6;
    }

    .alert-inline-icon {
      width: 38px;
      height: 38px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }

    .alert-inline-icon.critical {
      background: #fee2e2;
      color: #dc2626;
    }

    .alert-inline-icon.warning {
      background: #fef9c3;
      color: #d97706;
    }

    .alert-inline-icon.info {
      background: #ede9fe;
      color: #3030b6;
    }

.alert-inline-icon, .ann-thumb {
    width: 45px !important;
    height: 45px !important;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}

.ann-badge {
    display: inline-flex;
    padding: 4px 12px !important;
    font-size: 0.75rem !important;
    text-transform: capitalize;
    font-weight: 600;
}

@media (max-width: 768px) {
    .ann-table-head {
        display: none !important;
    }

    .ann-row {
        display: flex !important;
        flex-direction: column !important;
        padding: 20px !important;
        gap: 12px !important;
        border-bottom: 1px solid #eee !important;
        align-items: flex-start !important;
    }

    .ann-item {
        width: 100% !important;
        margin-bottom: 5px;
    }

    .ann-title {
        font-size: 1.1rem !important;
        margin-bottom: 4px;
        white-space: normal !important;
    }

    .ann-desc {
        font-size: 0.9rem !important;
        color: #666;
        line-height: 1.4;
    }

    .ann-row > div:nth-child(2) { 
        order: -1; 
    }

    .ann-date {
        font-size: 0.85rem !important;
        color: #9e9a9a;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .ann-topbar {
        flex-direction: column !important;
        gap: 12px !important;
    }

    .search, .ann-filter {
        width: 100% !important;
    }

    .add-res-btn, .alert-top-btn {
        width: 100% !important;
        justify-content: center !important;
    }
}
  </style>
</head>

<body>

  <div id="sidebar-navbar"></div>

  <div class="layout">
    <div class="main">

      <div class="alerts-top-bar">
        <button class="alert-top-btn"><i class="bi bi-shield-check"></i>Emergency Protocol</button>
        <button class="alert-top-btn"><i class="bi bi-file-earmark-text"></i>Generate Report</button>
      </div>

      <div class="ann-wrap">

        <div class="ann-topbar">

          <div class="search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search alerts..." id="alert-search">
          </div>

          <div class="ann-filter" id="alert-filter-btn">
            <span id="alert-filter-label">All Severity</span>

            <svg width="14" height="14" viewBox="0 0 20 20" fill="none">
              <path d="M5 8l5 5 5-5" stroke="#9E9A9A" stroke-width="1.8" stroke-linecap="round"
                stroke-linejoin="round" />
            </svg>

            <div class="ann-dropdown" id="alert-dropdown">
              <div class="ann-dropdown-item" data-val="all">All Severity</div>
              <div class="ann-dropdown-item" data-val="critical">Critical</div>
              <div class="ann-dropdown-item" data-val="warning">Warning</div>
              <div class="ann-dropdown-item" data-val="info">Information</div>
            </div>
          </div>

        </div>

        <div class="ann-table-head">
          <span>Alert</span>
          <span>Severity</span>
          <span>Date</span>
          <span>Actions</span>
        </div>

        <div id="alert-body"></div>

        <div class="ann-footer">
          <span class="ann-footer-info" id="alert-count"></span>

          <div class="ann-pagination">
            <button class="page-nav" id="prev-btn">Previous</button>
            <button class="page-btn active" data-page="1">1</button>
            <button class="page-btn" data-page="2">2</button>
            <button class="page-btn" data-page="3">3</button>
            <button class="page-nav" id="next-btn">Next</button>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    const ALERTS = [
      { title: 'Unauthorized QR Code Scanned', desc: 'Unrecognized QR code at South Gate.', severity: 'critical', date: '5 mins ago' },
      { title: 'Expired QR Code Used', desc: 'Attempted entry using expired QR.', severity: 'warning', date: '17 mins ago' },
      { title: 'Repeated Invalid QR Attempts', desc: 'Multiple failed scans at Room 102.', severity: 'critical', date: '2 hours ago' },
      { title: 'Visitor QR Code Expired', desc: 'Visitor QR needs re-issuance.', severity: 'info', date: '3 hours ago' },
      { title: 'Late Entry Flag', desc: 'Entry after curfew detected.', severity: 'warning', date: '14 hours ago' },
      { title: 'Suspicious Door Access Attempt', desc: 'Forced entry detected at Room 205.', severity: 'critical', date: 'Yesterday' },
      { title: 'Multiple Failed Login Attempts', desc: '5 failed admin logins from unknown IP.', severity: 'warning', date: 'Yesterday' },
      { title: 'Camera Offline Detected', desc: 'CCTV camera near North Hall is offline.', severity: 'info', date: '2 days ago' },
      { title: 'Unauthorized Visitor Detected', desc: 'Unknown person entered lobby.', severity: 'critical', date: '2 days ago' },
      { title: 'Emergency Exit Opened', desc: 'Emergency exit opened without alarm.', severity: 'warning', date: '3 days ago' }
    ];

    const PER_PAGE = 5;
    let page = 1;
    let filter = 'all';
    let search = '';

    function getData() {
      return ALERTS.filter(a => {
        const matchFilter = filter === 'all' || a.severity === filter;
        const matchSearch =
          a.title.toLowerCase().includes(search) ||
          a.desc.toLowerCase().includes(search);

        return matchFilter && matchSearch;
      });
    }

    function render() {
      const data = getData();
      const total = data.length;
      const totalPages = Math.ceil(total / PER_PAGE) || 1;

      if (page > totalPages) page = 1;

      const start = (page - 1) * PER_PAGE;
      const paginated = data.slice(start, start + PER_PAGE);

      document.getElementById('alert-body').innerHTML = paginated.map(a => `
    <div class="ann-row">

      <div class="ann-item">
        <div class="alert-inline-icon ${a.severity}">
          <i class="bi bi-exclamation-circle"></i>
        </div>
        <div>
          <div class="ann-title">${a.title}</div>
          <div class="ann-desc">${a.desc}</div>
        </div>
      </div>

      <div>
        <span class="ann-badge ${a.severity}">${a.severity}</span>
      </div>

      <div class="ann-date">
        <i class="bi bi-clock"></i> ${a.date}
      </div>

      <div class="ann-actions">
        <button class="ann-menu-btn">
          <i class="bi bi-three-dots"></i>
        </button>
      </div>

    </div>
  `).join('');

      document.getElementById('alert-count').textContent =
        `Showing ${paginated.length} of ${total} alerts`;

      document.querySelectorAll('.page-btn').forEach(btn => {
        const btnPage = Number(btn.dataset.page);
        btn.style.display = btnPage <= totalPages ? '' : 'none';
        btn.classList.toggle('active', btnPage === page);
      });
    }

    document.getElementById('alert-search').addEventListener('input', e => {
      search = e.target.value.toLowerCase().trim();
      page = 1;
      render();
    });

    document.getElementById('alert-filter-btn').addEventListener('click', e => {
      e.stopPropagation();
      document.getElementById('alert-dropdown').classList.toggle('open');
    });

    document.addEventListener('click', () => {
      document.getElementById('alert-dropdown').classList.remove('open');
    });

    document.querySelectorAll('.ann-dropdown-item').forEach(item => {
      item.addEventListener('click', (e) => {
        e.stopPropagation();

        filter = item.dataset.val;

        document.getElementById('alert-filter-label').textContent = item.textContent;

        page = 1;
        document.getElementById('alert-dropdown').classList.remove('open');

        render();
      });
    });

    document.querySelectorAll('.page-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        page = Number(btn.dataset.page);
        render();
      });
    });

    document.getElementById('prev-btn').addEventListener('click', () => {
      if (page > 1) {
        page--;
        render();
      }
    });

    document.getElementById('next-btn').addEventListener('click', () => {
      const totalPages = Math.ceil(getData().length / PER_PAGE);

      if (page < totalPages) {
        page++;
        render();
      }
    });

    render();
  </script>
  <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>

</body>

</html>