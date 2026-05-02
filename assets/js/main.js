/**
 * ============================================================================
 * PAGE: RESIDENT HOME (STATIC)
 * ============================================================================
 * NOTE: This section remains static as requested.
 * UI/UX: Handles announcements and activity detail modals.
 * ============================================================================
 */
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("universalModal");
  const container = document.getElementById("modalContainer");

  const announcementBtns = document.querySelectorAll(".announcement-btn");
  announcementBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const template = document.getElementById("announcementTemplate");
      container.innerHTML = "";
      container.appendChild(template.content.cloneNode(true));

      document.getElementById("modalTitle").innerText = btn.dataset.title;
      document.getElementById("modalDescription").innerText =
        btn.dataset.content;
      document.getElementById("modalImg").src = btn.dataset.img;
      document.getElementById("modalCategory").innerText = btn.dataset.category;
      document.getElementById("modalDate").innerText = btn.dataset.date;

      modal.style.display = "flex";
      document.body.style.overflow = "hidden";
    });
  });

  function openViewAll() {
    const template = document.getElementById("viewAllTemplate");
    container.innerHTML = "";
    container.appendChild(template.content.cloneNode(true));

    const listBody = container.querySelector("#fullActivityList");
    document
      .querySelectorAll(".activities-card .activity-item")
      .forEach((item) => {
        const clone = item.cloneNode(true);

        clone.querySelector(".read-more").onclick = () =>
          openActivityDetail(item);
        listBody.appendChild(clone);
      });

    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  function openActivityDetail(originalItem) {
    const template = document.getElementById("activityDetailTemplate");
    container.innerHTML = "";
    container.appendChild(template.content.cloneNode(true));

    const title = originalItem.querySelector("h3").innerText;

    const fullContentElement = originalItem.querySelector(".full-text");
    const contentToShow = fullContentElement
      ? fullContentElement.innerText
      : originalItem.querySelector("p").innerText;

    const isGuestPass = title.toLowerCase().includes("guest");
    const labels = container.querySelectorAll(".info-row span");
    if (isGuestPass && labels.length >= 3) {
      labels[0].innerText = "Guest Name";
      labels[1].innerText = "Validity";
      labels[2].innerText = "Access Level";
    } else if (labels.length >= 3) {
      labels[0].innerText = "Device";
      labels[1].innerText = "Method";
      labels[2].innerText = "Status";
    }

    document.getElementById("detailTitle").innerText = title;
    document.getElementById("detailFullDescription").innerText = contentToShow;
    document.getElementById("detailTime").innerText =
      originalItem.querySelector(".time").innerText;
    document.getElementById("detailIcon").innerHTML =
      originalItem.querySelector(".icon-box").innerHTML;

    document.getElementById("detailLocation").innerText =
      originalItem.dataset.location || "Unknown";
    document.getElementById("detailDevice").innerText =
      originalItem.dataset.device || "N/A";
    document.getElementById("detailMethod").innerText =
      originalItem.dataset.method || "N/A";
    document.getElementById("detailStatus").innerText =
      originalItem.dataset.status || "Authorized";

    const backBtn = document.getElementById("backToActivities");
    if (backBtn) backBtn.onclick = openViewAll;

    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  const viewAllBtn = document.querySelector(".view-all");
  if (viewAllBtn)
    viewAllBtn.addEventListener("click", (e) => {
      e.preventDefault();
      openViewAll();
    });

  document.querySelectorAll(".activity-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const item = e.target.closest(".activity-item");
      openActivityDetail(item);
    });
  });
});

function closeUniversalModal() {
  document.getElementById("universalModal").style.display = "none";
  document.body.style.overflow = "auto";
}

window.onclick = (e) => {
  const modal = document.getElementById("universalModal");
  if (e.target === modal) closeUniversalModal();
};

/**
 * ============================================================================
 * PAGE: INVITE VISITOR (Visitor Pass Generation)
 * ============================================================================
 * BACKEND TO-DO:
 * 1. Database: Map inputs to 'visitor_logs' table.
 * 2. PHP: Use 'POST' method to send form data to 'save_visitor.php'.
 * 3. Token: Generate a short-lived UUID for the 'qr_token' column.
 * ============================================================================
 */
document.getElementById("visitorForm").addEventListener("submit", function (e) {
  e.preventDefault();

  let isValid = true;
  const inputs = [
    { id: "visitorName", errorId: "nameError" },
    { id: "contactNumber", errorId: "contactError", isPhone: true },
    { id: "accessLevel", errorId: "accessError" },
  ];

  inputs.forEach((inputObj) => {
    const inputElement = document.getElementById(inputObj.id);
    const parent = inputElement.parentElement;
    const value = inputElement.value.trim();

    if (value === "") {
      parent.classList.add("error");
      isValid = false;
    } else if (inputObj.isPhone && !/^\d{11}$/.test(value)) {
      parent.classList.add("error");
      isValid = false;
    } else {
      parent.classList.remove("error");
      parent.classList.add("success");
    }
  });

  if (isValid) {
    alert(
      "Success! Visitor pass for " +
        document.getElementById("visitorName").value +
        " has been generated.",
    );
    this.reset();
  }
});

document.querySelectorAll(".form-group input").forEach((input) => {
  input.addEventListener("input", function () {
    this.parentElement.classList.remove("error");
  });
});

/**
 * ============================================================================
 * PAGE: INVITE VISITOR (QR Generation Logic)
 * ============================================================================
 * BACKEND BLUEPRINT:
 * 1. Integration: Replace 'setTimeout' with fetch('save_visitor.php').
 * 2. Validation: PHP must perform secondary validation (server-side).
 * 3. Security: Encrypt the 'qr_token' before returning it to the frontend.
 * ============================================================================
 */
document.getElementById("visitorForm").addEventListener("submit", function (e) {
  e.preventDefault();

  let isValid = true;
  const nameInput = document.getElementById("visitorName");
  const contactInput = document.getElementById("contactNumber");

  if (nameInput.value.trim() === "") {
    nameInput.parentElement.classList.add("error");
    isValid = false;
  }
  if (!/^\d{11}$/.test(contactInput.value.trim())) {
    contactInput.parentElement.classList.add("error");
    isValid = false;
  }

  if (isValid) {
    const visitorName = nameInput.value;
    const contactNum = contactInput.value;
    const qrToken =
      "QR_" + Math.random().toString(36).substr(2, 9).toUpperCase();

    const expiry = new Date();
    expiry.setHours(expiry.getHours() + 24);

    document.getElementById("expiryTimestamp").innerText =
      expiry.toLocaleString();

    const qrContainer = document.getElementById("qrContainer");
    qrContainer.style.color = "#05cd99";

    qrContainer.querySelector(".qr-instruction").innerText =
      "Pass Active: " + qrToken;

    alert(`Pass Generated!\nVisitor: ${visitorName}\nToken: ${qrToken}`);

    nameInput.parentElement.classList.remove("error");
    contactInput.parentElement.classList.remove("error");
  }
});

document.querySelectorAll(".form-group input").forEach((input) => {
  input.addEventListener("input", function () {
    this.parentElement.classList.remove("error");
  });
});
