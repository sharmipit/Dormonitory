const pageTitles = {
  "home.php": "Resident Web Portal",
  "digital-key.php": "Digital Key",
  "invite-visitor.php": "Invite Visitor",
  "dashboard.php": "Admin Web Portal",
  "resident-management.php": "Resident Management",
  "room-management.php": "Room Management",
  "visitor-management.php": "Visitor Log",
  "announcements.php": "Announcements",
  "security-center.php": "Security Center",
};

const isAdmin = window.location.pathname.includes("/admin/");
const sidebarFile = isAdmin
  ? "/Dormonitory/includes/admin-bar-layout.php"
  : "/Dormonitory/includes/resident-bar-layout.php";

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
    const pageTitle = pageTitles[currentPage] ?? "Dormitory";
    document.querySelector(".navbar-title").textContent = pageTitle;
    const navLinks = document.querySelectorAll("nav a");
    navLinks.forEach((link) => {
      const linkPage = link.getAttribute("href").split("/").pop();
      if (linkPage === currentPage) {
        link.parentElement.classList.add("active");
      }
    });
  });
