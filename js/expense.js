/**
 * Expense management JavaScript for Gastos Guard
 * Updated to centralize modal handling and fix logout modal issue
 */

// Global variable to store all loaded expenses for search/filter
let allExpenses = [];

document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM loaded - initializing expense management");

  loadExpenses();

  const addExpenseBtn = document.getElementById("add-expense-btn");
  if (addExpenseBtn) {
    console.log("Add Expense button found, attaching event listener");
    addExpenseBtn.removeEventListener("click", openAddExpenseModal);
    addExpenseBtn.addEventListener("click", openAddExpenseModal);
  } else {
    console.error("Add Expense button not found - check HTML ID");
  }

  const addExpenseForm = document.getElementById("add-expense-form");
  if (addExpenseForm) {
    addExpenseForm.removeEventListener("submit", handleExpenseFormSubmit);
    addExpenseForm.addEventListener("submit", handleExpenseFormSubmit);
  } else {
    console.error("Add Expense form not found");
  }

  const editExpenseForm = document.getElementById("edit-expense-form");
  if (editExpenseForm) {
    editExpenseForm.removeEventListener("submit", handleEditExpenseFormSubmit);
    editExpenseForm.addEventListener("submit", handleEditExpenseFormSubmit);
  }

  // Event delegation for closing modals, excluding logout-modal
  document.addEventListener("click", (e) => {
    const modal = e.target.closest(".modal");
    if (!modal || modal.id === "logout-modal") return; // Skip logout-modal

    if (e.target.classList.contains("modal-overlay") || e.target.classList.contains("close-modal")) {
      console.log(`Closing modal via ${e.target.classList.contains("modal-overlay") ? "overlay" : "close button"}: ${modal.id}`);
      closeModal(modal.id);
    }
  });

  // Specific handler for logout-modal close button
  const logoutModal = document.getElementById("logout-modal");
  if (logoutModal) {
    logoutModal.querySelectorAll(".close-modal, .modal-overlay").forEach(el => {
      el.removeEventListener("click", handleLogoutModalClose); // Prevent duplicates
      el.addEventListener("click", handleLogoutModalClose);
    });
  }

  // Search bar functionality
  const searchBox = document.querySelector(".search-box input[type='text']");
  if (searchBox) {
    searchBox.addEventListener("input", function () {
      const query = this.value.trim().toLowerCase();
      if (!query) {
        displayExpenses(allExpenses);
        return;
      }
      const filtered = allExpenses.filter((expense) => {
        return (
          (expense.description &&
            expense.description.toLowerCase().includes(query)) ||
          (expense.category &&
            expense.category.toLowerCase().includes(query)) ||
          (expense.amount &&
            expense.amount.toString().toLowerCase().includes(query)) ||
          (expense.date_spent &&
            formatDate(expense.date_spent).toLowerCase().includes(query))
        );
      });
      displayExpenses(filtered);
    });
  }
});

// Specific handler for logout-modal closing
function handleLogoutModalClose(e) {
  console.log("Closing logout-modal");
  closeModal("logout-modal");
}

function loadExpenses() {
  console.log("Loading expenses...");
  const filters = getFilters();
  fetch("../api/get_expenses.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(filters),
  })
    .then((response) =>
      response.json().then((data) => ({ status: response.status, body: data }))
    )
    .then((result) => {
      if (result.status === 200 && result.body.success) {
        allExpenses = result.body.expenses || [];
        displayExpenses(allExpenses);
        if (window.location.pathname.includes("dashboard.php")) {
          refreshDashboard();
        }
      } else {
        console.error("Error loading expenses:", result.body.message);
        displaySystemMessage(
          "Error loading expenses: " + result.body.message,
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Error fetching expenses:", error);
      displaySystemMessage("Failed to load expenses: Network error", "error");
    });
}

function refreshDashboard() {
  console.log("Refreshing dashboard data...");
  fetch("../api/get_expenses.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({}),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateSpendingOverview(data.expenses);
      } else {
        console.error("Error refreshing dashboard:", data.message);
      }
    })
    .catch((error) => {
      console.error("Error refreshing dashboard:", error);
    });
}

function updateSpendingOverview(expenses) {
  console.log("Updating spending overview...");
  const totalSpentElement = document.querySelector(
    ".total-spent-amount .amount"
  );
  const spendingCategories = document.querySelector(".spending-categories");

  if (!totalSpentElement || !spendingCategories) return;

  const totalSpent = expenses.reduce(
    (sum, expense) => sum + parseFloat(expense.amount),
    0
  );
  totalSpentElement.textContent = `₱${totalSpent.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;

  const categories = {};
  expenses.forEach((expense) => {
    if (!categories[expense.category]) {
      categories[expense.category] = {
        amount: 0,
        category_id: expense.category_id,
        icon: expense.icon,
        color: expense.color,
      };
    }
    categories[expense.category].amount += parseFloat(expense.amount);
  });

  spendingCategories.innerHTML = "";
  if (Object.keys(categories).length === 0) {
    spendingCategories.innerHTML = `
            <div class="no-data">
                <p>No expenses recorded for this period.</p>
                <a href="expenses.php" class="btn secondary">Add Expense</a>
            </div>
        `;
  } else {
    Object.entries(categories).forEach(([name, data]) => {
      let progress = 0;
      let progressColor = data.color ? escapeHtml(data.color) : "#10b981";
      let budget = null;
      if (
        typeof categoryBudgets !== "undefined" &&
        data.category_id &&
        categoryBudgets[data.category_id]
      ) {
        budget = parseFloat(categoryBudgets[data.category_id]);
        if (budget > 0) {
          progress = Math.min(100, (data.amount / budget) * 100);
          if (data.amount >= budget) {
            progressColor = "#f44336";
          } else if (data.amount >= 0.8 * budget) {
            progressColor = "#FF9800";
          } else {
            progressColor = data.color ? escapeHtml(data.color) : "#10b981";
          }
        } else {
          progress = totalSpent > 0 ? (data.amount / totalSpent) * 100 : 0;
        }
      } else {
        progress = totalSpent > 0 ? (data.amount / totalSpent) * 100 : 0;
      }
      const categoryCard = document.createElement("div");
      categoryCard.className = "category-card";
      categoryCard.innerHTML = `
                <h3>${escapeHtml(name)}</h3>
                <div class="category-amount">
                    <span class="fas ${
                      data.icon ? escapeHtml(data.icon) : "fa-ellipsis-h"
                    }"></span>
                    <h4>₱${data.amount.toLocaleString("en-US", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                    })}</h4>
                    ${
                      budget && budget > 0
                        ? `<span style=\"font-size: 0.95em; color: #888; margin-left: 8px;\">/ ₱${budget.toLocaleString(
                            "en-US",
                            {
                              minimumFractionDigits: 2,
                              maximumFractionDigits: 2,
                            }
                          )}</span>`
                        : ""
                    }
                </div>
                <div class="progress-bar">
                    <div class="progress" style="width: ${progress}%; background-color: ${progressColor};"></div>
                </div>
            `;
      spendingCategories.appendChild(categoryCard);
    });
  }
}

function displayExpenses(expenses) {
  const tableBody = document.getElementById("expense-table-body");
  const noExpensesMessage = document.getElementById("no-expenses-message");

  if (!tableBody) {
    console.error("Expense table body not found");
    return;
  }

  tableBody.innerHTML = "";

  if (!expenses || expenses.length === 0) {
    if (noExpensesMessage) {
      noExpensesMessage.style.display = "block";
    }
    return;
  }

  if (noExpensesMessage) {
    noExpensesMessage.style.display = "none";
  }

  expenses.forEach((expense) => {
    const row = document.createElement("tr");
    row.innerHTML = `
            <td>${formatDate(expense.date_spent)}</td>
            <td>${escapeHtml(expense.description) || "-"}</td>
            <td>${escapeHtml(expense.category)}</td>
            <td>₱${parseFloat(expense.amount).toFixed(2)}</td>
            <td>
                <button class="btn secondary action-btn edit-btn" onclick="openEditExpenseModal(${
                  expense.expense_id
                }, '${expense.date_spent}', '${escapeHtml(
      expense.description
    )}', ${expense.category_id}, ${expense.amount})">
                    <span class="fas fa-edit"></span>
                </button>
                <button class="btn secondary action-btn delete-btn" onclick="showDeleteConfirmation(${
                  expense.expense_id
                })">
                    <span class="fas fa-trash-alt"></span>
                </button>
            </td>
        `;
    tableBody.appendChild(row);
  });
}

function formatDate(dateStr) {
  const date = new Date(dateStr);
  return date.toLocaleDateString("en-US", {
    month: "long",
    day: "numeric",
    year: "numeric",
  });
}

function escapeHtml(str) {
  return str
    ? str.replace(
        /[&<>"']/g,
        (match) =>
          ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;",
          }[match])
      )
    : "";
}

function openModal(modalId) {
  console.log(`Opening modal: ${modalId}`);
  const modal = document.getElementById(modalId);
  if (modal) {
    document.body.classList.add("modal-open");
    modal.style.display = "flex";
    modal.offsetHeight; // Force reflow for CSS transitions
    if (modalId === "add-expense-modal") {
      const today = new Date().toISOString().split("T")[0];
      document.getElementById("expense-date").value = today;
      const form = document.getElementById("add-expense-form");
      if (form) {
        form.reset();
        document.getElementById("expense-date").value = today;
      }
      const messageDiv = document.getElementById("add-expense-message");
      if (messageDiv) {
        messageDiv.textContent = "";
        messageDiv.style.display = "none";
        messageDiv.className = "message";
      }
    } else if (modalId === "edit-expense-modal") {
      const messageDiv = document.getElementById("edit-expense-message");
      if (messageDiv) {
        messageDiv.textContent = "";
        messageDiv.style.display = "none";
        messageDiv.className = "message";
      }
    }
  } else {
    console.error(`Modal with ID ${modalId} not found`);
    displaySystemMessage(`Failed to open modal ${modalId}.`, "error");
  }
}

function openAddExpenseModal() {
  openModal("add-expense-modal");
}

function closeModal(modalId) {
  console.log(`Closing modal with ID: ${modalId}`);
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
      modalId !== "add-expense-modal" &&
      modalId !== "edit-expense-modal" &&
      modalId !== "logout-modal" &&
      !modal.hasAttribute("data-persisted")
    ) {
      modal.remove();
    }
  }
}

function openEditExpenseModal(
  expenseId,
  date,
  description,
  categoryId,
  amount
) {
  console.log("Opening Edit Expense modal");
  const modal = document.getElementById("edit-expense-modal");
  if (modal) {
    document.body.classList.add("modal-open");
    modal.style.display = "flex";
    modal.offsetHeight; // Force reflow

    document.getElementById("edit-expense-id").value = expenseId;
    document.getElementById("edit-expense-date").value = date.split("T")[0];
    document.getElementById("edit-expense-description").value = description;
    document.getElementById("edit-expense-category").value = categoryId;
    document.getElementById("edit-expense-amount").value = amount;

    const messageDiv = document.getElementById("edit-expense-message");
    if (messageDiv) {
      messageDiv.textContent = "";
      messageDiv.style.display = "none";
      messageDiv.className = "message";
    }
  } else {
    console.error("Edit Expense modal not found");
    displaySystemMessage(
      "Failed to open edit form. Please try again.",
      "error"
    );
  }
}

function handleExpenseFormSubmit(e) {
  e.preventDefault();
  console.log("Form submitted");

  const formData = new FormData(e.target);
  const expenseData = {
    date_spent: formData.get("date"),
    description: formData.get("description"),
    category_id: formData.get("category"),
    amount: parseFloat(formData.get("amount")),
  };

  if (!expenseData.date_spent) {
    displayMessage("Please select a date", "error");
    return;
  }
  if (!expenseData.description.trim()) {
    displayMessage("Please enter a description", "error");
    return;
  }
  if (!expenseData.category_id) {
    displayMessage("Please select a category", "error");
    return;
  }
  if (isNaN(expenseData.amount) || expenseData.amount <= 0) {
    displayMessage("Please enter a valid amount greater than 0", "error");
    return;
  }

  console.log("Sending expense data:", expenseData);

  fetch("../api/add_expense.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(expenseData),
  })
    .then((response) =>
      response.json().then((data) => ({ status: response.status, body: data }))
    )
    .then((result) => {
      if (result.status === 200 && result.body.success) {
        console.log("Expense added successfully:", result.body);
        closeModal("add-expense-modal");
        setTimeout(() => {
          location.reload();
        }, 300);
      } else {
        console.error("Error adding expense:", result.body.message);
        displayMessage("Error: " + result.body.message, "error");
      }
    })
    .catch((error) => {
      console.error("Error adding expense:", error);
      displayMessage("Failed to add expense: Network error", "error");
    });
}

function handleEditExpenseFormSubmit(e) {
  e.preventDefault();
  console.log("Edit form submitted");

  const formData = new FormData(e.target);
  const expenseData = {
    expense_id: formData.get("expense_id"),
    date_spent: formData.get("date"),
    description: formData.get("description"),
    category_id: formData.get("category"),
    amount: parseFloat(formData.get("amount")),
  };

  if (!expenseData.date_spent) {
    displayEditMessage("Please select a date", "error");
    return;
  }
  if (!expenseData.description.trim()) {
    displayEditMessage("Please enter a description", "error");
    return;
  }
  if (!expenseData.category_id) {
    displayEditMessage("Please select a category", "error");
    return;
  }
  if (isNaN(expenseData.amount) || expenseData.amount <= 0) {
    displayEditMessage("Please enter a valid amount greater than 0", "error");
    return;
  }

  console.log("Sending edit expense data:", expenseData);

  fetch("../api/edit_expense.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(expenseData),
  })
    .then((response) =>
      response.json().then((data) => ({ status: response.status, body: data }))
    )
    .then((result) => {
      if (result.status === 200 && result.body.success) {
        console.log("Expense updated successfully:", result.body);
        closeModal("edit-expense-modal");
        loadExpenses();
        showSuccessModal("Expense updated successfully!");
      } else {
        console.error("Error updating expense:", result.body.message);
        displayEditMessage("Error: " + result.body.message, "error");
      }
    })
    .catch((error) => {
      console.error("Error updating expense:", error);
      displayEditMessage("Failed to update expense: Network error", "error");
    });
}

function showDeleteConfirmation(expenseId) {
  console.log("Showing delete confirmation modal for expense ID:", expenseId);
  const modal = document.createElement("div");
  modal.className = "modal confirmation-modal-wrapper";
  modal.id = "delete-expense-modal";
  modal.style.display = "flex";
  modal.innerHTML = `
        <div class="modal-overlay"></div>
        <div class="modal-content confirmation-modal">
            <div class="modal-header">
                <h2>
                    <span class="fas fa-exclamation-triangle warning-icon"></span>
                    Confirm Deletion
                </h2>
                <span class="close-modal fas fa-times"></span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this expense? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn secondary" onclick="closeModal('delete-expense-modal')">Cancel</button>
                <button class="btn danger" onclick="confirmDelete(${expenseId}, this)">Delete</button>
            </div>
        </div>
    `;
  document.body.appendChild(modal);
  document.body.classList.add("modal-open");
}

function confirmDelete(expenseId, button) {
  console.log("Confirming delete for expense ID:", expenseId);
  fetch("../api/delete_expense.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ expense_id: expenseId }),
  })
    .then((response) =>
      response.json().then((data) => ({ status: response.status, body: data }))
    )
    .then((result) => {
      if (result.status === 200 && result.body.success) {
        console.log("Expense deleted successfully:", result.body);
        loadExpenses();
        showSuccessModal("Expense deleted successfully!");
      } else {
        console.error("Error deleting expense:", result.body.message);
        displaySystemMessage("Error: " + result.body.message, "error");
      }
    })
    .catch((error) => {
      console.error("Error deleting expense:", error);
      displaySystemMessage("Failed to delete expense: Network error", "error");
    })
    .finally(() => {
      closeModal("delete-expense-modal");
    });
}

function showSuccessModal(message) {
  console.log("Showing success modal with message:", message);
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

function getFilters() {
  return {
    date_start: document.getElementById("date_start")
      ? document.getElementById("date_start").value
      : "",
    date_end: document.getElementById("date_end")
      ? document.getElementById("date_end").value
      : "",
    category_id: document.getElementById("category")
      ? document.getElementById("category").value
      : "",
  };
}

function applyFilters() {
  console.log("Applying filters");
  loadExpenses();
}

function resetFilters() {
  console.log("Resetting filters");
  if (document.getElementById("expense-filter-form")) {
    document.getElementById("expense-filter-form").reset();
  }
  loadExpenses();
}

function displayMessage(message, type = "") {
  console.log(`Displaying message: ${message}, type: ${type}`);
  const messageDiv = document.getElementById("add-expense-message");
  if (messageDiv) {
    messageDiv.textContent = message;
    messageDiv.style.display = message ? "block" : "none";
    messageDiv.className =
      "message" +
      (type === "success" ? " success" : type === "error" ? " error" : "");
  }
}

function displayEditMessage(message, type = "") {
  console.log(`Displaying edit message: ${message}, type: ${type}`);
  const messageDiv = document.getElementById("edit-expense-message");
  if (messageDiv) {
    messageDiv.textContent = message;
    messageDiv.style.display = message ? "block" : "none";
    messageDiv.className =
      "message" +
      (type === "success" ? " success" : type === "error" ? " error" : "");
  }
}

function displaySystemMessage(message, type = "") {
  console.log("Showing system message modal with message:", message);
  const modal = document.createElement("div");
  modal.className = "modal system-modal-wrapper";
  modal.id = "system-message-modal";
  modal.style.display = "flex";
  modal.innerHTML = `
        <div class="modal-overlay"></div>
        <div class="modal-content system-modal">
            <div class="modal-header">
                <h2>${type === "success" ? "Success" : "Error"}</h2>
                <span class="close-modal fas fa-times"></span>
            </div>
            <div class="modal-body">
                <span class="fas ${
                  type === "success"
                    ? "fa-check-circle success-icon"
                    : "fa-exclamation-circle error-icon"
                }"></span>
                <p>${message}</p>
            </div>
        </div>
    `;
  document.body.appendChild(modal);
  document.body.classList.add("modal-open");

  if (type === "success") {
    setTimeout(() => {
      closeModal("system-message-modal");
    }, 3000);
  }
}