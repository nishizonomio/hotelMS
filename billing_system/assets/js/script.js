document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.getElementById("darkModeToggle");
  const icon = document.getElementById("darkIcon");

  // Apply saved mode on page load
  if (localStorage.getItem("darkMode") === "enabled") {
    document.body.classList.add("dark-mode");
    icon.classList.replace("fa-moon", "fa-sun");
  }

  // Toggle and save preference
  toggleBtn.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");
    const isDark = document.body.classList.contains("dark-mode");

    localStorage.setItem("darkMode", isDark ? "enabled" : "disabled");
    icon.classList.replace(
      isDark ? "fa-moon" : "fa-sun",
      isDark ? "fa-sun" : "fa-moon"
    );
  });
});
const toggleBtn = document.getElementById("theme");
toggleBtn.addEventListener("click", () => {
  document.body.classList.toggle("dark-mode");
  toggleBtn.classList.toggle("active");
});

document
  .getElementById("createInvoiceForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const spinner = document.getElementById("createInvoiceSpinner");
    const modalEl = document.getElementById("createInvoiceModal");
    const modal = bootstrap.Modal.getInstance(modalEl);

    const toastSuccess = new bootstrap.Toast(
      document.getElementById("toastSuccess"),
      {
        delay: 3000,
        autohide: true,
      }
    );
    const toastError = new bootstrap.Toast(
      document.getElementById("toastError"),
      {
        delay: 4000,
        autohide: true,
      }
    );

    spinner.style.display = "block";

    fetch("actions/create_invoice.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((result) => {
        spinner.style.display = "none";

        if (result.trim() === "success") {
          toastSuccess.show();
          modal.hide();

          // Refresh dashboard data
          fetch("dashboard_data.php")
            .then((res) => res.json())
            .then((data) => {
              document.getElementById("total-sales").textContent =
                "₱ " + data.totalSales;
              document.getElementById("pending-invoice").textContent =
                data.pendingInvoices;
              document.getElementById("overdue").textContent =
                data.overdueInvoices;
              document.getElementById("refunds").textContent = data.refunds;

              const recentContainer =
                document.getElementById("recent-invoices");
              recentContainer.innerHTML = "";
              data.recentInvoices.forEach((invoice) => {
                const div = document.createElement("div");
                div.textContent = `${invoice.id} ${invoice.guest_name} ₱${invoice.amount} ${invoice.status}`;
                recentContainer.appendChild(div);
              });
            });
        } else {
          toastError.show();
        }
      })
      .catch((error) => {
        spinner.style.display = "none";
        toastError.show();
        console.error("Error:", error);
      });
  });

document.addEventListener("DOMContentLoaded", () => {
  const card = document.getElementById("card");
  const summary = document.getElementById("summary");
  const details = document.getElementById("details");
  const backBtn = document.getElementById("backBtn");

  // When card is clicked → show details
  card.addEventListener("click", function (e) {
    // Prevent card click from triggering when Back button is clicked
    if (e.target.id !== "backBtn") {
      summary.classList.add("hidden");
      details.classList.remove("hidden");
    }
  });

  // When back button is clicked → go back to summary
  backBtn.addEventListener("click", function (e) {
    e.stopPropagation(); // Stop event from bubbling to card click
    summary.classList.remove("hidden");
    details.classList.add("hidden");
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const AUTO_HIDE_MS = 4000; // base delay before hide
  const STAGGER_MS = 250; // stagger between stacked messages

  const container = document.querySelector(".flash-container");
  if (!container) {
    return; // no flash messages, just exit quietly
  }

  const alerts = Array.from(container.querySelectorAll(".flash-message"));

  alerts.forEach((el, i) => {
    // show (CSS-driven)
    requestAnimationFrame(() => el.classList.add("show"));

    // hide after delay (staggered)
    const delay = AUTO_HIDE_MS + i * STAGGER_MS;
    setTimeout(() => {
      if (window.bootstrap && typeof bootstrap.Alert === "function") {
        try {
          const bs = bootstrap.Alert.getOrCreateInstance(el);
          bs.close();
          return;
        } catch (err) {
          console.warn("Bootstrap Alert close failed, using fallback.", err);
        }
      }

      // Fallback: fade out + remove node
      el.classList.remove("show");
      el.style.transition = "opacity 240ms ease, transform 240ms ease";
      el.style.opacity = "0";
      el.style.transform = "translateY(-8px)";
      setTimeout(() => {
        if (el && el.parentNode) el.parentNode.removeChild(el);
        if (
          container &&
          container.childElementCount === 0 &&
          container.parentNode
        ) {
          container.parentNode.removeChild(container);
        }
      }, 300);
    }, delay);
  });

  // Listen for Bootstrap’s closed event only if container exists
  container.addEventListener(
    "closed.bs.alert",
    (e) => {
      const el = e.target;
      if (el && el.parentNode) el.parentNode.removeChild(el);
    },
    true
  );
});
