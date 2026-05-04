const pageTitles = {
  "home.php": "Resident Web Portal",
  "digital-key.php": "Digital Key",
  "invite-visitor.php": "Invite Visitor",
  "resident-profile.php": "Resident Profile",
  "dashboard.php": "Admin Web Portal",
  "resident-management.php": "Resident Management",
  "room-management.php": "Room Management",
  "visitor-management.php": "Visitor Log",
  "announcements.php": "Announcements",
  "security-center.php": "Security Center",
  "admin-profile.php": "Admin Profile",
};

const isAdmin = window.location.pathname.includes("/admin/");
const sidebarFile = isAdmin
  ? "/Dormonitory/includes/admin-bar-layout.php"
  : "/Dormonitory/includes/resident-bar-layout.php";

fetch(sidebarFile)
  .then((res) => res.text())
  .then((html) => {
    document.getElementById("sidebar-navbar").innerHTML = html;

    // --- 1. SIDEBAR HAMBURGER LOGIC ---
    const btn = document.getElementById("hamburger");
    const sidebar = document.getElementById("sidebar");

    if (btn && sidebar) {
      btn.onclick = () => {
        sidebar.classList.toggle("closed");
        document.body.classList.toggle("sidebar-closed");
      };
    }

    // --- 2. PROFILE DROPDOWN LOGIC ---
    const profileTrigger = document.getElementById("profile-trigger");
    const profileModal = document.getElementById("profile-modal");

    if (profileTrigger && profileModal) {
      profileTrigger.onclick = (e) => {
        e.stopPropagation();
        profileModal.classList.toggle("active");
      };
    }

    // --- 3. CUSTOM SIGN OUT MODAL LOGIC ---
    const signoutModal = document.getElementById("signout-modal");
    const cancelBtn = document.getElementById("cancel-signout");
    const signoutButtons = document.querySelectorAll(
      ".signout-btn, .btn-signout",
    );

    signoutButtons.forEach((btn) => {
      btn.onclick = (e) => {
        e.preventDefault();
        if (profileModal) profileModal.classList.remove("active");
        if (signoutModal) signoutModal.style.display = "flex";
      };
    });

    if (cancelBtn) {
      cancelBtn.onclick = () => {
        signoutModal.style.display = "none";
      };
    }

    // --- 4. CLICK OUTSIDE TO CLOSE LOGIC ---
    document.addEventListener("click", (e) => {
      if (
        profileModal &&
        !profileModal.contains(e.target) &&
        e.target !== profileTrigger
      ) {
        profileModal.classList.remove("active");
      }
      if (signoutModal && e.target === signoutModal) {
        signoutModal.style.display = "none";
      }
    });

    // --- 5. PAGE TITLE & ACTIVE LINK LOGIC ---

    const pathParts = window.location.pathname.split("/");
    const currentPage = pathParts[pathParts.length - 1] || "dashboard.php";

    const pageTitle = pageTitles[currentPage] ?? "Dormonitory";

    const titleElement = document.querySelector(".navbar-title");
    if (titleElement) {
      titleElement.textContent = pageTitle;
    }

    const navLinks = document.querySelectorAll("nav a");
    navLinks.forEach((link) => {
      const href = link.getAttribute("href");
      if (href) {
        const linkPage = href.split("/").pop();
        if (linkPage === currentPage) {
          link.parentElement.classList.add("active");
        }
      }
    });
  })
  .catch((err) => console.error("Error loading sidebar:", err));
