/**
 * Validation.js - form validation functionality for Gastos Guard
 * Used across the application for validating user input
 */

document.addEventListener("DOMContentLoaded", function () {
  // Apply validation to all forms with data-validate attribute
  const forms = document.querySelectorAll("form[data-validate]");

  forms.forEach((form) => {
    applyFormValidation(form);
  });

  // Always validate these specific forms regardless of attribute
  const specificForms = [
    document.getElementById("login-form"),
    document.getElementById("register-form"),
  ];

  specificForms.forEach((form) => {
    if (form) applyFormValidation(form);
  });

  // Multi-step form validation
  initMultiStepFormValidation();
});

/**
 * Initialize multi-step form validation and navigation
 */
function initMultiStepFormValidation() {
  const nextButtons = document.querySelectorAll(".next-step");

  if (nextButtons.length > 0) {
    nextButtons.forEach((button) => {
      button.addEventListener("click", function (e) {
        // Get the current step
        const currentStep = parseInt(this.getAttribute("data-step"));
        const formStep = document.querySelector(
          ".form-step.step-" + currentStep
        );

        // Validate all fields in the current step
        const fields = formStep.querySelectorAll("input, select, textarea");
        let isStepValid = true;

        // Ensure validation is triggered for each field
        fields.forEach((field) => {
          // Skip hidden fields
          if (field.type === "hidden") return;

          // Trigger validation
          if (field.hasAttribute("required")) {
            validateRequired(field);
          }

          if (field.type === "email") {
            validateEmail(field);
          }

          if (field.id === "full_name") {
            validateFullName(field);
          }

          if (field.id === "username") {
            validateUsername(field);
          }

          if (field.id === "current_balance") {
            validateCurrentBalance(field);
          }

          if (field.id === "password") {
            validatePassword(field);
          }

          if (field.id === "confirm_password") {
            const passwordField = document.getElementById("password");
            validateConfirmPassword(field, passwordField);
          }

          // Check if the field is invalid
          if (field.classList.contains("is-invalid")) {
            isStepValid = false;
          }
        });

        // If step is valid, proceed to next step
        if (isStepValid) {
          const nextStep = currentStep + 1;

          // Hide current step
          document
            .querySelector(".form-step.step-" + currentStep)
            .classList.remove("active");
          // Show next step
          document
            .querySelector(".form-step.step-" + nextStep)
            .classList.add("active");

          // Update step indicators
          document
            .querySelector(".step-indicator.step-" + currentStep)
            .classList.remove("active");
          document
            .querySelector(".step-indicator.step-" + nextStep)
            .classList.add("active");

          // Scroll to top of form
          const authCard = document.querySelector(".auth-card");
          if (authCard) authCard.scrollTop = 0;
        } else {
          // Prevent proceeding
          e.preventDefault();

          // Focus the first invalid field
          const firstInvalid = formStep.querySelector(".is-invalid");
          if (firstInvalid) {
            firstInvalid.focus();
          }

          // Show a message
          showStepErrorMessage(formStep);
        }
      });
    });
  }
}

// Function to display error for password validation
function showPasswordError(message) {
  const errorContainer = document.getElementById("password-error");
  if (errorContainer) {
    errorContainer.textContent = message;
    document.getElementById("password").classList.add("is-invalid");
  }
}

// Function to display error for password confirmation
function showConfirmPasswordError(message) {
  const errorContainer = document.getElementById("confirm-password-error");
  if (errorContainer) {
    errorContainer.textContent = message;
    document.getElementById("confirm_password").classList.add("is-invalid");
  }
}

// Function to clear errors
function clearPasswordErrors() {
  const passwordError = document.getElementById("password-error");
  const confirmPasswordError = document.getElementById(
    "confirm-password-error"
  );
  const passwordField = document.getElementById("password");
  const confirmPasswordField = document.getElementById("confirm_password");

  if (passwordError) passwordError.textContent = "";
  if (confirmPasswordError) confirmPasswordError.textContent = "";
  if (passwordField) passwordField.classList.remove("is-invalid");
  if (confirmPasswordField) confirmPasswordField.classList.remove("is-invalid");
}

/**
 * Apply validation to a form
 * @param {HTMLFormElement} form
 */
function applyFormValidation(form) {
  if (!form) return;

  // Validate specific fields based on their IDs or types
  const fieldsToValidate = {
    email: validateEmail,
    username: validateUsername,
    full_name: validateFullName,
    current_balance: validateCurrentBalance,
    password: validatePassword,
    confirm_password: function (field) {
      const passwordField = document.getElementById("password");
      return validateConfirmPassword(field, passwordField);
    },
  };

  // Attach blur and input event listeners to fields
  for (const fieldId in fieldsToValidate) {
    const field = form.querySelector("#" + fieldId);
    if (field) {
      // Validate on blur
      field.addEventListener("blur", function () {
        fieldsToValidate[fieldId](this);
      });

      // Validate on input for real-time feedback
      field.addEventListener("input", function () {
        fieldsToValidate[fieldId](this);
      });
    }
  }

  // Validate required fields
  const requiredFields = form.querySelectorAll("[required]");
  requiredFields.forEach((field) => {
    field.addEventListener("blur", function () {
      validateRequired(this);
    });

    field.addEventListener("input", function () {
      validateRequired(this);
    });
  });

  // Form submission validation
  form.addEventListener("submit", function (e) {
    let isValid = true;

    // Validate required fields
    requiredFields.forEach((field) => {
      if (!validateRequired(field)) {
        isValid = false;
      }
    });

    // Validate specific fields
    for (const fieldId in fieldsToValidate) {
      const field = form.querySelector("#" + fieldId);
      if (field && !fieldsToValidate[fieldId](field)) {
        isValid = false;
      }
    }

    // Prevent submission if validation fails
    if (!isValid) {
      e.preventDefault();

      // Focus the first invalid field
      const firstInvalid = form.querySelector(".is-invalid");
      if (firstInvalid) {
        firstInvalid.focus();
      }

      // Show form error message
      const errorContainer = form.querySelector(".form-error-message");
      if (!errorContainer) {
        const errorDiv = document.createElement("div");
        errorDiv.className = "alert alert-danger form-error-message";
        errorDiv.textContent =
          "Please correct the errors in the form before submitting.";
        form.prepend(errorDiv);

        // Auto-hide after 5 seconds
        setTimeout(() => {
          errorDiv.style.opacity = "0";
          setTimeout(() => {
            errorDiv.remove();
          }, 500);
        }, 5000);
      }
    }
  });
}

/**
 * Display an error message for a form step
 * @param {HTMLElement} formStep
 */
function showStepErrorMessage(formStep) {
  // Remove any existing error message
  const existingError = formStep.querySelector(".step-error-message");
  if (existingError) {
    existingError.remove();
  }

  // Create and add error message
  const errorDiv = document.createElement("div");
  errorDiv.className = "alert alert-danger step-error-message";
  errorDiv.textContent =
    "Please fill in all required fields correctly before proceeding.";

  // Insert at the top of the form step
  formStep.insertBefore(errorDiv, formStep.firstChild);

  // Auto-hide after 5 seconds
  setTimeout(() => {
    errorDiv.style.opacity = "0";
    setTimeout(() => {
      errorDiv.remove();
    }, 500);
  }, 5000);
}

/**
 * Utility to insert error message after .input-icon-outer
 */
function insertErrorAfterInputIconOuter(field, feedback) {
  const formGroup = field.closest(".form-group");
  if (!formGroup) return;
  const inputIconOuter = field.closest(".input-icon-outer");
  if (inputIconOuter && inputIconOuter.nextSibling) {
    formGroup.insertBefore(feedback, inputIconOuter.nextSibling);
  } else {
    formGroup.appendChild(feedback);
  }
}

/**
 * Validate a required field
 * @param {HTMLInputElement} field
 * @returns {boolean} isValid
 */
function validateRequired(field) {
  if (field.type === "hidden" || field.style.display === "none") {
    return true;
  }
  const value = field.value.trim();
  const isValid = value !== "";
  if (isValid) {
    field.classList.remove("is-invalid");
    field.classList.add("is-valid");
  } else {
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");
    let feedback = field
      .closest(".form-group")
      .querySelector(".invalid-feedback");
    if (!feedback) {
      feedback = document.createElement("div");
      feedback.className = "invalid-feedback";
      insertErrorAfterInputIconOuter(field, feedback);
    }
    const fieldName =
      field.getAttribute("placeholder") ||
      field.getAttribute("name") ||
      "This field";
    feedback.textContent = `${fieldName} is required`;
  }
  return isValid;
}

/**
 * Validate email format
 * @param {HTMLInputElement} field
 * @returns {boolean} isValid
 */
function validateEmail(field) {
  const value = field.value.trim();
  if (!value) {
    return validateRequired(field);
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const isValid = emailRegex.test(value);
  if (isValid) {
    field.classList.remove("is-invalid");
    field.classList.add("is-valid");
  } else {
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");
    let feedback = field
      .closest(".form-group")
      .querySelector(".invalid-feedback");
    if (!feedback) {
      feedback = document.createElement("div");
      feedback.className = "invalid-feedback";
      insertErrorAfterInputIconOuter(field, feedback);
    }
    feedback.textContent = "Please enter a valid email address";
  }
  return isValid;
}

/**
 * Validate full name format
 * @param {HTMLInputElement} field
 * @returns {boolean} isValid
 */
function validateFullName(field) {
  const value = field.value.trim();
  if (!value) {
    return validateRequired(field);
  }
  const fullNameRegex = /^[a-zA-Z\s'-]{2,50}$/;
  const hasSpace = value.includes(" ");
  const matchesPattern = fullNameRegex.test(value);
  const isValid = matchesPattern && hasSpace;
  if (isValid) {
    field.classList.remove("is-invalid");
    field.classList.add("is-valid");
  } else {
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");
    let feedback = field
      .closest(".form-group")
      .querySelector(".invalid-feedback");
    if (!feedback) {
      feedback = document.createElement("div");
      feedback.className = "invalid-feedback";
      insertErrorAfterInputIconOuter(field, feedback);
    }
    if (!hasSpace) {
      feedback.textContent =
        "Please enter your full name (first and last name)";
    } else if (!matchesPattern) {
      feedback.textContent =
        "Full name should contain only letters, spaces, hyphens, and apostrophes";
    }
  }
  return isValid;
}

/**
 * Validate username format
 * @param {HTMLInputElement} field
 * @returns {boolean} isValid
 */
function validateUsername(field) {
  if (!field.value.trim()) {
    return validateRequired(field);
  }
  const usernameRegex = /^[a-zA-Z0-9_-]{3,20}$/;
  const isValid = usernameRegex.test(field.value.trim());
  if (isValid) {
    field.classList.remove("is-invalid");
    field.classList.add("is-valid");
  } else {
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");
    let feedback = field
      .closest(".form-group")
      .querySelector(".invalid-feedback");
    if (!feedback) {
      feedback = document.createElement("div");
      feedback.className = "invalid-feedback";
      insertErrorAfterInputIconOuter(field, feedback);
    }
    feedback.textContent =
      "Username must be 3-20 characters and contain only letters, numbers, underscores, and hyphens";
  }
  return isValid;
}

/**
 * Validate current balance format
 * @param {HTMLInputElement} field
 * @returns {boolean} isValid
 */
function validateCurrentBalance(field) {
  if (!field.value.trim()) {
    return validateRequired(field);
  }
  const value = parseFloat(field.value.trim());
  const isValid = !isNaN(value) && value >= 0 && value <= 1000000;
  if (isValid) {
    field.classList.remove("is-invalid");
    field.classList.add("is-valid");
  } else {
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");
    let feedback = field
      .closest(".form-group")
      .querySelector(".invalid-feedback");
    if (!feedback) {
      feedback = document.createElement("div");
      feedback.className = "invalid-feedback";
      insertErrorAfterInputIconOuter(field, feedback);
    }
    if (isNaN(value)) {
      feedback.textContent = "Please enter a valid number";
    } else if (value < 0) {
      feedback.textContent = "Current balance cannot be negative";
    } else {
      feedback.textContent = "Current balance is too large (maximum 1,000,000)";
    }
  }
  return isValid;
}

/**
 * Validate password strength
 * @param {HTMLInputElement} field
 * @returns {boolean} isValid
 */
function validatePassword(field) {
  clearPasswordErrors();
  if (!field.value) {
    return validateRequired(field);
  }

  const password = field.value;
  let strength = 0;

  // Check password length
  const isLongEnough = password.length >= 8;
  if (isLongEnough) strength += 1;

  // Check for mixed case
  const hasMixedCase = /[a-z]/.test(password) && /[A-Z]/.test(password);
  if (hasMixedCase) strength += 1;

  // Check for numbers
  const hasNumbers = /\d/.test(password);
  if (hasNumbers) strength += 1;

  // Check for special characters
  const hasSpecialChars = /[^a-zA-Z\d]/.test(password);
  if (hasSpecialChars) strength += 1;

  // Password must have at least medium strength (2+)
  const isValidPassword = strength >= 2 && isLongEnough;

  // Update strength meter if it exists
  const strengthMeter = document.querySelector(".strength-meter-fill");
  if (strengthMeter) {
    strengthMeter.setAttribute("data-strength", strength);

    // Update strength text if it exists
    const strengthText = document.querySelector(".strength-text");
    if (strengthText) {
      const strengthLabels = [
        "Too weak",
        "Weak",
        "Medium",
        "Strong",
        "Very strong",
      ];
      strengthText.textContent =
        "Password strength: " + strengthLabels[strength];
    }
  }

  if (!isValidPassword) {
    let errorMessage = "Password must contain ";
    let errorParts = [];

    if (!isLongEnough) errorParts.push("at least 8 characters");
    if (!hasMixedCase) errorParts.push("uppercase and lowercase letters");
    if (!hasNumbers) errorParts.push("at least one number");
    if (!hasSpecialChars) errorParts.push("at least one special character");

    errorMessage += errorParts.join(", ");
    showPasswordError(errorMessage);
    return false;
  }

  field.classList.remove("is-invalid");
  field.classList.add("is-valid");
  return true;
}

/**
 * Validate confirm password
 * @param {HTMLInputElement} field
 * @param {HTMLInputElement} passwordField
 * @returns {boolean} isValid
 */
function validateConfirmPassword(field, passwordField) {
  if (!field.value) {
    return validateRequired(field);
  }

  const password = passwordField ? passwordField.value : "";
  const confirmPassword = field.value;

  if (confirmPassword !== password) {
    showConfirmPasswordError("Passwords do not match");
    return false;
  }

  field.classList.remove("is-invalid");
  field.classList.add("is-valid");
  return true;
}

// Ensure error messages are added properly
function showValidationError(input, message) {
  // Remove any existing error messages in the form-group
  const formGroup = input.closest(".form-group");
  if (!formGroup) return;
  const existingError = formGroup.querySelector(".invalid-feedback");
  if (existingError) {
    existingError.remove();
  }
  input.classList.add("is-invalid");
  // Create error message element
  const errorDiv = document.createElement("div");
  errorDiv.className = "invalid-feedback";
  errorDiv.textContent = message;
  // Add error message after the input-icon-outer div
  const inputIconOuter = input.closest(".input-icon-outer");
  if (inputIconOuter && inputIconOuter.nextSibling) {
    formGroup.insertBefore(errorDiv, inputIconOuter.nextSibling);
  } else {
    formGroup.appendChild(errorDiv);
  }
}
