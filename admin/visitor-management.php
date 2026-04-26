<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dormitory</title>
  <link rel="stylesheet" href="/Dormonitory/assets/css/sidebar-navbar-styles.css" />
  <link rel="stylesheet" href="/Dormonitory/assets/css/admin-styles.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet" />
</head>

<body>
  <div id="sidebar-navbar"></div>

  <div class="layout">
    <div class="main">
      <!--
        <button class="add-res-btn">
          <i class="bi bi-plus-lg"></i> Add New Resident
        </button>
        -->
      <div class="card">
        <div class="top-bar">
          <div class="search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search visitor, resident, or contact..." />
          </div>
        </div>

        <table class="visitor-table">
          <thead>
            <tr>
              <th>Visitor</th>
              <th>Resident Visiting</th>
              <th>Contact Number</th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            <tr>
              <td>
                <div class="user">
                  <div class="avatar"><i class="bi bi-person"></i></div>
                  <div class="user-info">
                    Joshua Defensor
                    <small>Log ID: L10058</small>
                  </div>
                </div>
              </td>
              <td>James Abobo (RM10001)</td>
              <td>+63 912 345 6789</td>
              <td class="actions">
                <i class="bi bi-pencil-square edit-btn"></i>
                <i class="bi bi-trash delete-btn"></i>
              </td>
            </tr>

            <tr>
              <td>
                <div class="user">
                  <div class="avatar"><i class="bi bi-person"></i></div>
                  <div class="user-info">
                    Joshua Golosino
                    <small>Log ID: L10057</small>
                  </div>
                </div>
              </td>
              <td>Hazel Ann Carillo (RM10002)</td>
              <td>+63 917 888 1234</td>
              <td class="actions">
                <i class="bi bi-pencil-square edit-btn"></i>
                <i class="bi bi-trash delete-btn"></i>
              </td>
            </tr>

            <tr>
              <td>
                <div class="user">
                  <div class="avatar"><i class="bi bi-person"></i></div>
                  <div class="user-info">
                    Shinellah Gamboa
                    <small>Log ID: L10056</small>
                  </div>
                </div>
              </td>
              <td>Sharmagne Gamboa (RM10003)</td>
              <td>+63 905 222 9090</td>
              <td class="actions">
                <i class="bi bi-pencil-square edit-btn"></i>
                <i class="bi bi-trash delete-btn"></i>
              </td>
            </tr>
            <tr>
              <td>
                <div class="user">
                  <div class="avatar"><i class="bi bi-person"></i></div>
                  <div class="user-info">
                    Carole Ann Abobo
                    <small>Log ID: L10055</small>
                  </div>
                </div>
              </td>
              <td>James Abobo (RM10001)</td>
              <td>+63 912 345 6789</td>
              <td class="actions">
                <i class="bi bi-pencil-square edit-btn"></i>
                <i class="bi bi-trash delete-btn"></i>
              </td>
            </tr>
            <tr>
              <td>
                <div class="user">
                  <div class="avatar"><i class="bi bi-person"></i></div>
                  <div class="user-info">
                    Papa James Abobo
                    <small>Log ID: L10054</small>
                  </div>
                </div>
              </td>
              <td>James Abobo (RM10001)</td>
              <td>+63 912 345 6789</td>
              <td class="actions">
                <i class="bi bi-pencil-square edit-btn"></i>
                <i class="bi bi-trash delete-btn"></i>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="footer">
          <div>Showing 5 of 58 visitors</div>
          <div class="pagination">
            <span>Previous</span>
            <div class="page active">1</div>
            <div class="page">2</div>
            <span>Next</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="/Dormonitory/assets/js/sidebar-navbar.js"></script>
</body>

</html>