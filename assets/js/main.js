// --- RESIDENT HOME PAGE ---

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('universalModal');
    const container = document.getElementById('modalContainer');

    // --- ANNOUNCEMENT LOGIC ---
    const announcementBtns = document.querySelectorAll('.announcement-btn');
    announcementBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const template = document.getElementById('announcementTemplate');
            container.innerHTML = '';
            container.appendChild(template.content.cloneNode(true));

            document.getElementById('modalTitle').innerText = btn.dataset.title;
            document.getElementById('modalDescription').innerText = btn.dataset.content;
            document.getElementById('modalImg').src = btn.dataset.img;
            document.getElementById('modalCategory').innerText = btn.dataset.category;
            document.getElementById('modalDate').innerText = btn.dataset.date;

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    });

    // --- ACTIVITY LOGIC ---
    
    function openViewAll() {
        const template = document.getElementById('viewAllTemplate');
        container.innerHTML = '';
        container.appendChild(template.content.cloneNode(true));
        
        const listBody = container.querySelector('#fullActivityList');
        document.querySelectorAll('.activities-card .activity-item').forEach(item => {
            const clone = item.cloneNode(true);
        
            clone.querySelector('.read-more').onclick = () => openActivityDetail(item);
            listBody.appendChild(clone);
        });

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function openActivityDetail(originalItem) {
        const template = document.getElementById('activityDetailTemplate');
        container.innerHTML = '';
        container.appendChild(template.content.cloneNode(true));

        const title = originalItem.querySelector('h3').innerText;

        // 1. Get the Full Content
        const fullContentElement = originalItem.querySelector('.full-text');
        const contentToShow = fullContentElement ? fullContentElement.innerText : originalItem.querySelector('p').innerText;

        // 2. Adjust Labels for Guest Pass vs Security Log
        const isGuestPass = title.toLowerCase().includes('guest');
        const labels = container.querySelectorAll('.info-row span');
        if(isGuestPass && labels.length >= 3) {
            labels[0].innerText = "Guest Name";
            labels[1].innerText = "Validity";
            labels[2].innerText = "Access Level";
        } else if (labels.length >= 3) {
            labels[0].innerText = "Device";
            labels[1].innerText = "Method";
            labels[2].innerText = "Status";
        }

        // 3. Inject the Data from attributes and hidden spans
        document.getElementById('detailTitle').innerText = title;
        document.getElementById('detailFullDescription').innerText = contentToShow;
        document.getElementById('detailTime').innerText = originalItem.querySelector('.time').innerText;
        document.getElementById('detailIcon').innerHTML = originalItem.querySelector('.icon-box').innerHTML;
        
        document.getElementById('detailLocation').innerText = originalItem.dataset.location || "Unknown";
        document.getElementById('detailDevice').innerText = originalItem.dataset.device || "N/A";
        document.getElementById('detailMethod').innerText = originalItem.dataset.method || "N/A";
        document.getElementById('detailStatus').innerText = originalItem.dataset.status || "Authorized";

        const backBtn = document.getElementById('backToActivities');
        if(backBtn) backBtn.onclick = openViewAll;

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    const viewAllBtn = document.querySelector('.view-all');
    if(viewAllBtn) viewAllBtn.addEventListener('click', (e) => {
        e.preventDefault();
        openViewAll();
    });

    document.querySelectorAll('.activity-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const item = e.target.closest('.activity-item');
            openActivityDetail(item);
        });
    });
});

// Global close functions
function closeUniversalModal() {
    document.getElementById('universalModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal if clicking outside the content area
window.onclick = (e) => {
    const modal = document.getElementById('universalModal');
    if (e.target === modal) closeUniversalModal();
};

