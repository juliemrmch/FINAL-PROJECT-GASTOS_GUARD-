$(document).ready(function () {
  // DataTable initialization without server-side sorting
  var table = $("#usersTable").DataTable({
    ajax: {
      url: "../api/get_users.php",
      type: "GET",
      data: function (d) {
        // Pass filter parameters to the server only on apply
        d.name = $("#filter-name").val().trim();
        d.email = $("#filter-email").val().trim();
        d.status = $("#filter-status").val();
        d.join_date = $("#filter-join-date").val();
        d.sort = $("#filter-sort").val();
        d.order = $("#filter-order").val();
      },
      dataSrc: "",
    },
    columns: [
      {
        data: null,
        render: function (data, type, row) {
          // Avatar initials in ALL CAPS
          var initials = (row.name || row.username || "")
            .split(" ")
            .map((w) => w[0])
            .join("")
            .toUpperCase();
          return `<div class="user-cell"><div class="user-avatar">${initials}</div> <span class="user-name">${row.name || row.username}</span></div>`;
        },
      },
      { data: "email" },
      { data: "join_date" },
      {
        data: "status",
        render: {
          _: function (data, type, row) {
            if (type === "display") {
              if (data === "Active")
                return '<span class="badge badge-active">Active</span>';
              if (data === "Inactive")
                return '<span class="badge badge-inactive">Inactive</span>';
              if (data === "Pending")
                return '<span class="badge badge-pending">Pending</span>';
            }
            return data;
          },
        },
      },
      { data: "expenses" },
      {
        data: "total_spent",
        render: function (data) {
          return `₱${parseFloat(data).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })}`;
        },
      },
      {
        data: null,
        orderable: false, // Disable column header sorting
        render: function () {
          return `
                        <button class="action-btn view-btn"><span class="fas fa-eye"></span></button>
                        <button class="action-btn edit-btn"><span class="fas fa-pen"></span></button>
                        <button class="action-btn delete-btn"><span class="fas fa-trash"></span></button>
                    `;
        },
      },
    ],
    paging: true,
    searching: false,
    info: true,
    lengthChange: false,
    pageLength: 5,
    order: [], // Disable initial DataTable sorting
    dom: "lrtip",
    language: {
      emptyTable: "No users found.",
    },
    initComplete: function () {
      // Fetch user stats after table loads
      $.get("../api/get_user_stats.php", function (stats) {
        $("#total-users").text(stats.total_users);
        $("#active-users").text(stats.active_users);
        $("#new-users").text(stats.new_users);
      });
    },
  });

  // Apply filters only on "Apply" button click
  $("#filterForm").on("submit", function (e) {
    e.preventDefault();
    table.ajax.reload(); // Reload table with new filter parameters
  });

  // Reset filter functionality
  $("#resetFilters").on("click", function (e) {
    e.preventDefault();
    $("#filterForm")[0].reset();
    table.ajax.reload(); // Reload table with no filters
  });

  // Handle View, Edit, Delete actions
  $("#usersTable tbody").on("click", ".view-btn", function () {
    var data = table.row($(this).parents("tr")).data();
    viewUser(data);
  });

  $("#usersTable tbody").on("click", ".edit-btn", function () {
    var data = table.row($(this).parents("tr")).data();
    editUser(data);
  });

  $("#usersTable tbody").on("click", ".delete-btn", function () {
    var data = table.row($(this).parents("tr")).data();
    deleteUser(data);
  });

  // View user function
  function viewUser(user) {
    var html = `
      <div style='margin-bottom: 12px;'><strong>Username:</strong> ${user.username || ""}</div>
      <div style='margin-bottom: 12px;'><strong>Email:</strong> ${user.email || ""}</div>
      <div style='margin-bottom: 12px;'><strong>Full Name:</strong> ${user.name || user.full_name || ""}</div>
      <div style='margin-bottom: 12px;'><strong>Status:</strong> ${user.status || ""}</div>
      <div style='margin-bottom: 12px;'><strong>Join Date:</strong> ${user.join_date || ""}</div>
      <div style='margin-bottom: 12px;'><strong>Expenses:</strong> ${user.expenses || 0}</div>
      <div style='margin-bottom: 12px;'><strong>Total Spent:</strong> ₱${parseFloat(user.total_spent || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}</div>
    `;
    $("#viewUserDetails").html(html);
    $("#viewUserModal").addClass("show");
  }

  $("#closeViewUserModal").on("click", function () {
    $("#viewUserModal").removeClass("show");
  });
  $("#viewUserModal").on("click", function (e) {
    if (e.target === this) $(this).removeClass("show");
  });

  // Edit user function
  function editUser(user) {
    $("#edit_username").val(user.username || "");
    $("#edit_email").val(user.email || "");
    $("#edit_full_name").val(user.name || user.full_name || "");
    $("#edit_status").val(user.status || "Active");
    $("#editUserError").hide().text("");
    $("#editUserModal").addClass("show");
    $("#editUserForm").data("userId", user.user_id);
  }

  $("#closeEditUserModal").on("click", function () {
    $("#editUserModal").removeClass("show");
  });
  $("#editUserModal").on("click", function (e) {
    if (e.target === this) $(this).removeClass("show");
  });

  // Edit User Form Submit
  $("#editUserForm").on("submit", function (e) {
    e.preventDefault();
    var userId = $(this).data("userId");
    var formData = {
      id: userId,
      username: $("#edit_username").val().trim(),
      email: $("#edit_email").val().trim(),
      full_name: $("#edit_full_name").val().trim(),
      status: $("#edit_status").val(),
    };
    var $error = $("#editUserError");
    $error.hide().text("");
    $.ajax({
      url: "../api/edit_user.php",
      method: "POST",
      data: formData,
      success: function (res) {
        if (res && res.error) {
          $error.text(res.error).show();
          return;
        }
        $("#editUserModal").removeClass("show");
        showActionConfirmModal("edit");
        table.ajax.reload();
        $.get("../api/get_user_stats.php", function (stats) {
          $("#total-users").text(stats.total_users);
          $("#active-users").text(stats.active_users);
          $("#new-users").text(stats.new_users);
        });
        renderTopSpenders();
        renderRecentActivity();
      },
      error: function (xhr) {
        var msg = "Failed to update user.";
        if (xhr.responseJSON && xhr.responseJSON.error) {
          msg = xhr.responseJSON.error;
        }
        $error.text(msg).show();
      },
    });
  });

  // Delete user function
  var userToDelete = null;
  function deleteUser(user) {
    userToDelete = user;
    $("#deleteUserMessage").html(
      "Are you sure you want to delete user: <strong>" +
        (user.name || user.username) +
        "</strong>?"
    );
    $("#deleteUserModal").addClass("show");
  }

  $("#closeDeleteUserModal, #cancelDeleteUserBtn").on("click", function () {
    $("#deleteUserModal").removeClass("show");
    userToDelete = null;
  });
  $("#deleteUserModal").on("click", function (e) {
    if (e.target === this) $(this).removeClass("show");
  });
  $("#confirmDeleteUserBtn").on("click", function () {
    if (!userToDelete) return;
    var userId = userToDelete.user_id;
    $.ajax({
      url: "../api/delete_user.php",
      method: "POST",
      data: { id: userId },
      success: function (res) {
        $("#deleteUserModal").removeClass("show");
        if (res && res.error) {
          alert(res.error);
          userToDelete = null;
          return;
        }
        showActionConfirmModal("delete");
        table.ajax.reload();
        $.get("../api/get_user_stats.php", function (stats) {
          $("#total-users").text(stats.total_users);
          $("#active-users").text(stats.active_users);
          $("#new-users").text(stats.new_users);
        });
        renderTopSpenders();
        renderRecentActivity();
        userToDelete = null;
      },
      error: function (xhr) {
        $("#deleteUserModal").removeClass("show");
        alert("Failed to delete user.");
        userToDelete = null;
      },
    });
  });

  // Add/Edit/Delete User Confirmation Modal logic
  function showActionConfirmModal(action) {
    if (action === "add") {
      $("#actionConfirmHeader").text("User Added");
      $("#addUserConfirmMessage").text("User has been added successfully!");
    } else if (action === "edit") {
      $("#actionConfirmHeader").text("User Updated");
      $("#addUserConfirmMessage").text("User has been updated successfully!");
    } else if (action === "delete") {
      $("#actionConfirmHeader").text("User Deleted");
      $("#addUserConfirmMessage").text("User has been deleted successfully!");
    }
    $("#addUserConfirmModal").addClass("show");
  }

  $("#closeAddUserConfirmModal, #closeAddUserConfirmBtn").on("click", function () {
    $("#addUserConfirmModal").removeClass("show");
  });
  $("#addUserConfirmModal").on("click", function (e) {
    if (e.target === this) $(this).removeClass("show");
  });

  // Update addNewUser
  function addNewUser() {
    var $form = $("#addUserForm");
    var $error = $("#addUserError");
    $error.hide().text("");
    var username = $("#username").val().trim();
    var email = $("#email").val().trim();
    var password = $("#password").val().trim();
    var full_name = $("#full_name").val().trim();
    if (!username || !email || !password || !full_name) {
      $error.text("All fields are required.").show();
      return;
    }
    var emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
    if (!emailPattern.test(email)) {
      $error.text("Please enter a valid email address.").show();
      return;
    }
    if (password.length < 6) {
      $error.text("Password must be at least 6 characters.").show();
      return;
    }
    var formData = $form.serialize();
    $.ajax({
      url: "../api/add_user.php",
      method: "POST",
      data: formData,
      success: function (res) {
        if (res && res.error) {
          $error.text(res.error).show();
          return;
        }
        $("#addUserModal").removeClass("show");
        $form[0].reset();
        $error.hide().text("");
        table.ajax.reload();
        $.get("../api/get_user_stats.php", function (stats) {
          $("#total-users").text(stats.total_users);
          $("#active-users").text(stats.active_users);
          $("#new-users").text(stats.new_users);
        });
        renderTopSpenders();
        renderRecentActivity();
        showActionConfirmModal("add");
      },
      error: function (xhr) {
        var msg = "Failed to add user.";
        if (xhr.responseJSON && xhr.responseJSON.error) {
          msg = xhr.responseJSON.error;
        }
        $error.text(msg).show();
      },
    });
  }

  // Add User Form Submit
  $("#addUserForm").on("submit", function (e) {
    e.preventDefault();
    addNewUser();
  });

  function renderTopSpenders() {
    $.get("../api/get_top_spenders.php", function (spenders) {
      var html = "";
      spenders.forEach(function (user) {
        var initials = (user.full_name || user.username || "")
          .split(" ")
          .map((w) => w[0])
          .join("")
          .toUpperCase();
        html += `
          <li style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
            <div style="display: flex; align-items: center; gap: 16px;">
              <div class="user-avatar" style="background: #ff7a3d; color: #fff; width: 44px; height: 44px; font-size: 18px;">${initials}</div>
              <div>
                <div style="color: #fff; font-weight: 500; font-size: 16px;">${user.full_name}</div>
                <div style="color: #bfc9da; font-size: 14px;">${user.expenses} expense${user.expenses == 1 ? "" : "s"}</div>
              </div>
            </div>
            <div style="color: #fff; font-size: 16px; font-weight: 600;">₱${parseFloat(user.total_spent).toLocaleString(undefined, {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })}</div>
          </li>
        `;
      });
      $("#topSpendersList").html(html);
    });
  }

  // Call after page load
  renderTopSpenders();

  function renderRecentActivity() {
    $.get("../api/get_recent_activity.php", function (activities) {
      var html = "";
      if (Array.isArray(activities)) {
        activities.forEach(function (act) {
          let icon = "",
            iconBg = "",
            iconColor = "";
          if (act.type === "expense_added") {
            icon = "fas fa-check-circle";
            iconBg = "#1e463a";
            iconColor = "#2ecc71";
          } else if (act.type === "profile_updated") {
            icon = "fas fa-pen-square";
            iconBg = "#4b3a1e";
            iconColor = "#ff7a3d";
          } else if (act.type === "expense_deleted") {
            icon = "fas fa-times-circle";
            iconBg = "#4b1e1e";
            iconColor = "#ea5455";
          }
          // Format date/time
          let dt = new Date(act.datetime);
          let now = new Date();
          let dateStr = "";
          if (dt.toDateString() === now.toDateString()) {
            dateStr =
              "Today, " +
              dt.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
          } else {
            let yesterday = new Date(now);
            yesterday.setDate(now.getDate() - 1);
            if (dt.toDateString() === yesterday.toDateString()) {
              dateStr =
                "Yesterday, " +
                dt.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
            } else {
              dateStr =
                dt.toLocaleDateString() +
                ", " +
                dt.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
            }
          }
          html += `
            <li style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 18px;">
              <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: ${iconBg}; color: ${iconColor}; font-size: 18px;"><span class="${icon}"></span></span>
              <div>
                <div style="color: #fff; font-size: 15px; font-weight: 500;">${act.user} ${act.description}</div>
                <div style="color: #bfc9da; font-size: 13px;">${dateStr}</div>
              </div>
            </li>
          `;
        });
      } else {
        html = '<li style="color:#bfc9da;">No recent activity.</li>';
      }
      $("#recentActivityList").html(html);
    });
  }

  // Call after page load
  renderRecentActivity();
});