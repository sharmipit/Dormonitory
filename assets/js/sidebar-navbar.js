const pageTitles = {
  "home.html": "Resident Web Portal",
  "digital-key.html": "Digital Key",
  "invite-visitor.html": "Visitor Management",
  "dashboard.html": "Admin Web Portal",
  "dashboard.php": "Admin Web Portal",
  "resident-management.html": "Resident Management",
  "resident-management.php": "Resident Management",
  "room-management.html": "Room Management",
  "room-management.php": "Room Management",
  "visitor-management.html": "Visitor Management",
  "visitor-management.php": "Visitor Management",
  "announcements.html": "Announcements",
  "announcements.php": "Announcements",
  "security-center.html": "Security Center",
  "security-center.php": "Security Center",
};

const isAdmin = window.location.pathname.includes("/admin/");

const sidebarFile = isAdmin
  ? "../includes/admin-bar-layout.php"
  : "../includes/resident-bar-layout.html";

fetch(sidebarFile)
  .then((res) => res.text())
  .then((html) => {
    document.getElementById("sidebar-navbar").innerHTML = html;

    const btn = document.getElementById("hamburger");
    const sidebar = document.getElementById("sidebar");

    btn.onclick = () => {
      sidebar.classList.toggle("closed");
      document.body.classList.toggle("sidebar-closed");
    };

    const currentPage = window.location.pathname.split("/").pop();
    const pageTitle = pageTitles[currentPage] ?? "Hey G@ys^^";
    document.querySelector(".navbar-title").textContent = pageTitle;

    // Auto highlight active sidebar link
    const navLinks = document.querySelectorAll("nav a");
    navLinks.forEach((link) => {
      const linkPage = link.getAttribute("href").split("/").pop();
      if (linkPage === currentPage) {
        link.parentElement.classList.add("active");
      }
    });
  });
