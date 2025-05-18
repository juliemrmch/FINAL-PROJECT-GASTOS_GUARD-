let budgetIdToDelete = null;
// Global variable to store all loaded budgets for search/filter
let allBudgets = [];

document.addEventListener("DOMContentLoaded", function () {
  const currentPage = window.location.pathname.split("/").pop();
  if (currentPage === "budgets.php") {
    loadBudgets();
  }

  const startDateInput = document.getElementById("budget-start-date");
  const endDateInput = document.getElementById("budget-end-date");
  if (startDateInput && endDateInput) {
    startDateInput.value = "";
    endDateInput.value = "";
  }

  const addBudgetForm = document.getElementById("add-budget-form");
  if (addBudgetForm) {
    addBudgetForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);

      fetch("../api/add_budget.php", {
        method: "POST",
        body: formData,
      })
        .then((response) =>
          response
            .json()
            .then((data) => ({ status: response.status, body: data }))
        )
        .then((result) => {
          if (result.status === 200 && result.body.success) {
            closeModal("add-budget-modal");
            loadBudgets();
            document.getElementById("add-budget-form").reset();
            showSuccessModal("Budget added successfully!");
          } else {
            showErrorModal(
              "Error: " + (result.body.message || "Failed to add budget")
            );
          }
        })
        .catch((error) => {
          console.error("Error adding budget:", error);
          showErrorModal("Failed to add budget: Network error");
        });
    });
  }

  const editBudgetForm = document.getElementById("edit-budget-form");
  if (editBudgetForm) {
    editBudgetForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const endDateValue = document.getElementById(
        "edit-budget-end-date"
      ).value;
      if (formData.has("end_date")) {
        formData.set("end_date", endDateValue);
      } else {
        formData.append("end_date", endDateValue);
      }

      fetch("../api/update_budget.php", {
        method: "POST",
        body: formData,
      })
        .then((response) =>
          response.text().then((text) => {
            try {
              const data = JSON.parse(text);
              return { status: response.status, body: data };
            } catch (e) {
              console.error("Invalid JSON response:", text);
              return {
                status: response.status,
                body: {
                  success: false,
                  message: "Invalid server response: " + text.substring(0, 100),
                },
              };
            }
          })
        )
        .then((result) => {
          if (result.status === 200 && result.body.success) {
            closeModal("edit-budget-modal");
            loadBudgets();
            showSuccessModal("Budget updated successfully!");
          } else {
            showErrorModal(
              "Error: " + (result.body.message || "Failed to update budget")
            );
          }
        })
        .catch((error) => {
          console.error("Error updating budget:", error);
          showErrorModal("Failed to update budget: Network error");
        });
    });
  }

  const confirmDeleteButton = document.getElementById("confirm-delete-budget");
  if (confirmDeleteButton) {
    confirmDeleteButton.addEventListener("click", function () {
      if (budgetIdToDelete) {
        const formData = new FormData();
        formData.append("budget_id", budgetIdToDelete);

        fetch("../api/delete_budget.php", {
          method: "POST",
          body: formData,
        })
          .then((response) =>
            response
              .json()
              .then((data) => ({ status: response.status, body: data }))
          )
          .then((result) => {
            if (result.status === 200 && result.body.success) {
              closeModal("delete-budget-modal");
              loadBudgets();
              showSuccessModal("Budget deleted successfully!");
            } else {
              showErrorModal(
                "Error: " + (result.body.message || "Failed to delete budget")
              );
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            showErrorModal("Failed to delete budget: Network error");
          });
        budgetIdToDelete = null;
      }
    });
  }

  document.addEventListener("click", (e) => {
    if (
      e.target.classList.contains("modal-overlay") ||
      e.target.classList.contains("close-modal")
    ) {
      const modal = e.target.closest(".modal");
      if (modal) {
        closeModal(modal.id);
      }
    }
  });

  const budgetFilterForm = document.getElementById("budget-filter-form");
  if (budgetFilterForm) {
    document
      .querySelector("#budget-filter-form .primary")
      .addEventListener("click", applyFilters);
    document
      .querySelector("#budget-filter-form .secondary")
      .addEventListener("click", resetFilters);
  }

  // Search bar functionality
  const searchBox = document.querySelector(".search-box input[type='text']");
  if (searchBox) {
    searchBox.addEventListener("input", function () {
      const query = this.value.trim().toLowerCase();
      console.log("search input:", query, allBudgets);
      if (!query) {
        renderBudgetsTable(allBudgets);
        return;
      }
      const filtered = allBudgets.filter((budget) => {
        return (
          (budget.title && budget.title.toLowerCase().includes(query)) ||
          (budget.category && budget.category.toLowerCase().includes(query)) ||
          (budget.total &&
            budget.total.toString().toLowerCase().includes(query)) ||
          (budget.current &&
            budget.current.toString().toLowerCase().includes(query)) ||
          (budget.start_date &&
            formatDate(budget.start_date).toLowerCase().includes(query)) ||
          (budget.end_date &&
            formatDate(budget.end_date).toLowerCase().includes(query))
        );
      });
      console.log("filtered:", filtered);
      renderBudgetsTable(filtered);
    });
  } else {
    console.error("Search box not found!");
  }
});

function applyFilters() {
  const startDate = document.getElementById("budget-start-date").value || "";
  const endDate = document.getElementById("budget-end-date").value || "";
  const categoryId = document.getElementById("category").value || "";

  if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
    showErrorModal("End date must be after start date");
    return;
  }

  loadBudgets(startDate, endDate, categoryId);
}

function resetFilters() {
  document.getElementById("budget-start-date").value = "";
  document.getElementById("budget-end-date").value = "";
  document.getElementById("category").value = "";
  loadBudgets();
}

function loadBudgets(startDate = "", endDate = "", categoryId = "") {
  const filters = {
    start_date: startDate,
    end_date: endDate,
    category_id: categoryId,
  };

  console.log("Loading budgets with filters:", filters);

  fetch("../api/get_budgets.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(filters),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Parsed API response:", data);
      const tbody = document.querySelector("#budget-table tbody");
      const noBudgetsMessage = document.getElementById("no-budgets-message");

      if (!tbody || !noBudgetsMessage) {
        console.error("Table body or no budgets message element not found");
        return;
      }

      tbody.innerHTML = "";

      if (!data.success || !data.budgets || data.budgets.length === 0) {
        console.log("No budgets to display:", data);
        noBudgetsMessage.style.display = "block";
        allBudgets = [];
        return;
      }

      console.log("Budgets to render:", data.budgets);
      noBudgetsMessage.style.display = "none";
      allBudgets = data.budgets;
      renderBudgetsTable(allBudgets);
    })
    .catch((error) => {
      console.error("Error loading budgets:", error);
      showErrorModal(`Failed to load budgets: ${error.message}`);
    });
}

function renderBudgetsTable(budgets) {
  const tbody = document.querySelector("#budget-table tbody");
  const noBudgetsMessage = document.getElementById("no-budgets-message");
  if (!tbody || !noBudgetsMessage) return;
  tbody.innerHTML = "";
  if (!budgets || budgets.length === 0) {
    noBudgetsMessage.style.display = "block";
    return;
  }
  noBudgetsMessage.style.display = "none";
  budgets.forEach((budget) => {
    const status = getStatusClass(
      budget.progress_percentage || 0,
      budget.status || "normal"
    );
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${escapeHtml(budget.title)}</td>
      <td>${escapeHtml(budget.category)}</td>
      <td>₱${parseFloat(budget.total || 0).toFixed(2)}</td>
      <td>₱${parseFloat(budget.current || 0).toFixed(2)}</td>
      <td>${formatDate(budget.start_date)} to ${formatDate(
      budget.end_date
    )}</td>
      <td class="${status.class}">
        <div class="progress-bar">
          <div class="progress budget-progress-${
            status.class
          }" style="width: ${Math.min(
      100,
      budget.progress_percentage || 0
    )}%"></div>
        </div>
        <span>${status.text} (${(budget.progress_percentage || 0).toFixed(
      0
    )}%)</span>
      </td>
      <td>
        <button class="btn secondary action-btn edit-btn" onclick="openEditModal(${
          budget.budget_id
        })">
          <span class="fas fa-edit"></span>
        </button>
        <button class="btn secondary action-btn delete-btn" onclick="openDeleteModal(${
          budget.budget_id
        })">
          <span class="fas fa-trash-alt"></span>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function getStatusClass(progress, status) {
  if (progress >= 100) {
    return { class: "exceeded", text: "Exceeded" };
  } else if (progress >= 80) {
    return { class: "warning", text: "Warning" };
  } else {
    return { class: "normal", text: "Normal" };
  }
}

function formatDate(dateStr) {
  if (!dateStr) return "";
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;

  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function escapeHtml(str) {
  if (!str) return "";
  return String(str).replace(
    /[&<>"']/g,
    (match) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[match])
  );
}

function openEditModal(budgetId) {
  const filters = {
    budget_id: budgetId,
  };

  fetch("../api/get_budgets.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(filters),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success && data.budgets && data.budgets.length > 0) {
        const budget = data.budgets[0];
        document.getElementById("edit-budget-id").value = budget.budget_id;
        document.getElementById("edit-budget-title").value = budget.title;
        document.getElementById("edit-budget-category").value =
          budget.category_id;
        document.getElementById("edit-budget-amount").value = budget.total;
        document.getElementById("edit-budget-start-date").value =
          budget.start_date;
        document.getElementById("edit-budget-end-date").value = budget.end_date;

        openModal("edit-budget-modal");
      } else {
        showErrorModal(data.message || "Error loading budget");
      }
    })
    .catch((error) => {
      console.error("Error fetching budget details:", error);
      showErrorModal("Error loading budget: Network error");
    });
}

function openDeleteModal(budgetId) {
  budgetIdToDelete = budgetId;
  openModal("delete-budget-modal");
}

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "flex";
    document.body.classList.add("modal-open");
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "none";
    document.body.classList.remove("modal-open");
    const form = modal.querySelector("form");
    if (form) {
      form.reset();
    }
    const messageDiv = modal.querySelector(".message");
    if (messageDiv) {
      messageDiv.textContent = "";
      messageDiv.style.display = "none";
    }
    if (
      !modal.hasAttribute("data-persisted") &&
      (modalId === "success-modal" || modalId === "error-modal")
    ) {
      modal.remove();
    }
  }
}

function showSuccessModal(message) {
  const modal = document.createElement("div");
  modal.className = "modal success-modal-wrapper";
  modal.id = "success-modal";
  modal.style.display = "flex";
  modal.innerHTML = `
        <div class="modal-overlay"></div>
        <div class="modal-content success-modal">
            <div class="modal-header">
                <h2>
                    <span class="fas fa-check-circle success-icon"></span>
                    Success
                </h2>
                <span class="close-modal fas fa-times"></span>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
        </div>
    `;
  document.body.appendChild(modal);
  document.body.classList.add("modal-open");

  setTimeout(() => {
    closeModal("success-modal");
  }, 3000);
}

function showErrorModal(message) {
  const modal = document.createElement("div");
  modal.className = "modal system-modal-wrapper";
  modal.id = "error-modal";
  modal.style.display = "flex";
  modal.innerHTML = `
        <div class="modal-overlay"></div>
        <div class="modal-content system-modal">
            <div class="modal-header">
                <h2>Error</h2>
                <span class="close-modal fas fa-times"></span>
            </div>
            <div class="modal-body">
                <span class="fas fa-exclamation-circle error-icon"></span>
                <p>${message}</p>
            </div>
        </div>
    `;
  document.body.appendChild(modal);
  document.body.classList.add("modal-open");
}
