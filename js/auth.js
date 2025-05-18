/**
 * Authentication page scripts
 * Manages UI interactions for login and registration pages
 */

document.addEventListener("DOMContentLoaded", function () {
  // Password visibility toggle
  initPasswordToggles();

  // Password strength meter on registration page
  initPasswordStrengthMeter();

  // Form submission loading state
  initFormLoadingState();

  // Slideshow functionality
  initSlideshow();

  // Alert auto-close
  initAlertAutoClose();

  // Multi-step form navigation
  initMultiStepForm();
});

/**
 * Initialize password visibility toggle buttons
 */
function initPasswordToggles() {
  const toggleButtons = document.querySelectorAll(".toggle-password");
  toggleButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const input = this.parentNode.querySelector("input");
      const type =
        input.getAttribute("type") === "password" ? "text" : "password";
      input.setAttribute("type", type);

      // Toggle icon
      const icon = this.querySelector("i");
      icon.classList.toggle("fa-eye");
      icon.classList.toggle("fa-eye-slash");
    });
  });
}

/**
 * Initialize password strength meter
 */
function initPasswordStrengthMeter() {
  const passwordInput = document.getElementById("password");
  if (passwordInput) {
    const strengthMeter = document.querySelector(".strength-meter-fill");
    const strengthText = document.querySelector(".strength-text");

    if (strengthMeter && strengthText) {
      passwordInput.addEventListener("input", function () {
        const password = this.value;
        let strength = 0;

        // Check password length
        if (password.length >= 8) strength += 1;

        // Check for mixed case
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;

        // Check for numbers
        if (password.match(/\d/)) strength += 1;

        // Check for special characters
        if (password.match(/[^a-zA-Z\d]/)) strength += 1;

        // Update strength meter
        strengthMeter.setAttribute("data-strength", strength);

        // Update strength text
        const strengthLabels = [
          "Too weak",
          "Weak",
          "Medium",
          "Strong",
          "Very strong",
        ];
        strengthText.textContent =
          "Password strength: " + strengthLabels[strength];
      });
    }
  }
}

/**
 * Initialize form loading state
 */
function initFormLoadingState() {
  const authForms = document.querySelectorAll(".auth-form");
  authForms.forEach((form) => {
    form.addEventListener("submit", function () {
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.classList.add("btn-loading");
        // submitButton.textContent = ''; // Do not clear the button text
      }
    });
  });
}

/**
 * Initialize slideshow background
 */
function initSlideshow() {
  const slides = document.querySelectorAll(".slideshow-background .slide");

  if (slides.length > 0) {
    let currentSlide = 0;

    function nextSlide() {
      // Hide current slide
      slides[currentSlide].classList.remove("active");

      // Move to next slide
      currentSlide = (currentSlide + 1) % slides.length;

      // Show new slide
      slides[currentSlide].classList.add("active");
    }

    // Change slide every 6 seconds
    setInterval(nextSlide, 6000);
  }
}

/**
 * Initialize alert auto-close
 */
function initAlertAutoClose() {
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0";
      setTimeout(() => {
        alert.style.display = "none";
      }, 500);
    }, 5000);
  });
}

/**
 * Initialize multi-step form navigation
 */
function initMultiStepForm() {
  const prevButtons = document.querySelectorAll(".prev-step");
  const formSteps = document.querySelectorAll(".form-step");
  const stepIndicators = document.querySelectorAll(".step-indicator");

  prevButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const currentStep = parseInt(this.getAttribute("data-step"));
      const prevStep = currentStep - 1;

      // Hide current step
      document
        .querySelector(".form-step.step-" + currentStep)
        .classList.remove("active");
      // Show previous step
      document
        .querySelector(".form-step.step-" + prevStep)
        .classList.add("active");

      // Update step indicators
      document
        .querySelector(".step-indicator.step-" + currentStep)
        .classList.remove("active");
      document
        .querySelector(".step-indicator.step-" + prevStep)
        .classList.add("active");

      // Scroll to top of form
      const authCard = document.querySelector(".auth-card");
      if (authCard) authCard.scrollTop = 0;
    });
  });
}
