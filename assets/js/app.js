document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".needs-validation").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add("was-validated");
        });
    });

    const badge = document.getElementById("notificationBadge");
    if (!badge) {
        return;
    }

    const endpoint = window.location.pathname.includes("/user/")
        ? "notification_count.php"
        : "user/notification_count.php";

    async function refreshNotifications() {
        try {
            const response = await fetch(endpoint, { headers: { "X-Requested-With": "XMLHttpRequest" } });
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            const total = Number(data.total || 0);
            badge.textContent = total;
            badge.classList.toggle("d-none", total === 0);
        } catch (error) {
            console.warn("Gagal memperbarui notifikasi", error);
        }
    }

    refreshNotifications();
    window.setInterval(refreshNotifications, 15000);
});
