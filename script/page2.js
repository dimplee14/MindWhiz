// Cache DOM elements for form and inputs to avoid repeated querySelector calls
const form = document.querySelector("form");
const submitBtn = document.querySelector(".page-2-start-btn");
const nameInput = document.querySelector('input[name="name"]');
const parentInput = document.querySelector('input[name="parentname"]');
const emailInput = document.querySelector('input[name="email"]');
const phoneInput = document.querySelector('input[name="phone"]');
const standardInput = document.querySelector('input[name="standard"]');
const schoolInput = document.querySelector('input[name="school"]');
const languageSelect = document.querySelector('select[name="language"]');

// ===== Client-side Validation Functions =====
// Each function checks a specific field, displays error messages, and toggles error styling

/**
 * Validates name fields: checks non-empty and letters/spaces only
 */
function validateName(input) {
  const value = input.value.trim();
  const errorSpan = input.nextElementSibling;
  if (value === "") {
    input.classList.add("error");
    errorSpan.innerHTML = "This field is required";
    return false;
  } else if (!/^[a-zA-Z\s]+$/.test(value)) {
    input.classList.add("error");
    errorSpan.innerHTML = "Only letters and spaces allowed";
    return false;
  } else {
    input.classList.remove("error");
    errorSpan.innerHTML = "";
    return true;
  }
}

/**
 * Validates email: checks non-empty and RFC-style format (user@domain.ext)
 */
function validateEmail(input) {
  const value = input.value.trim();
  const errorSpan = input.nextElementSibling;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (value === "") {
    input.classList.add("error");
    errorSpan.innerHTML = "Email is required";
    return false;
  } else if (!emailRegex.test(value)) {
    input.classList.add("error");
    errorSpan.innerHTML = "Please enter a valid email";
    return false;
  } else {
    input.classList.remove("error");
    errorSpan.innerHTML = "";
    return true;
  }
}

/**
 * Validates phone: checks non-empty and exactly 10 digits
 */
function validatePhone(input) {
  const value = input.value.trim();
  const errorSpan = input.nextElementSibling;
  const phoneRegex = /^[0-9]{10}$/;
  if (value === "") {
    input.classList.add("error");
    errorSpan.innerHTML = "Phone number is required";
    return false;
  } else if (!phoneRegex.test(value)) {
    input.classList.add("error");
    errorSpan.innerHTML = "Must be  10 digits eg: 9876543210";
    return false;
  } else {
    input.classList.remove("error");
    errorSpan.innerHTML = "";
    return true;
  }
}

/**
 * Validates standard/grade field: checks non-empty
 */
function validateStandard(input) {
  const value = input.value.trim();
  const errorSpan = input.nextElementSibling;
  if (value === "") {
    input.classList.add("error");
    errorSpan.innerHTML = "This field is required";
    return false;
  } else {
    input.classList.remove("error");
    errorSpan.innerHTML = "";
    return true;
  }
}

function validateSchool(input) {
  const value = input.value.trim();
  const errorSpan = input.nextElementSibling;
  if (value === "") {
    input.classList.add("error");
    errorSpan.innerHTML = "This field is required";
    return false;
  } else {
    input.classList.remove("error");
    errorSpan.innerHTML = "";
    return true;
  }
}

/**
 * Aggregates validation for all fields and enables/disables submit button
 * Called after each field change to update form state
 */
function checkFormValidity() {
  const isNameValid =
    nameInput.value.trim() !== "" &&
    /^[a-zA-Z\s]+$/.test(nameInput.value.trim());
  const isParentValid =
    parentInput.value.trim() !== "" &&
    /^[a-zA-Z\s]+$/.test(parentInput.value.trim());
  const isEmailValid =
    emailInput.value.trim() !== "" &&
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim());
  const isPhoneValid =
    phoneInput.value.trim() !== "" &&
    /^[0-9]{10}$/.test(phoneInput.value.trim());
  const isStandardValid = standardInput.value.trim() !== "";
  const isSchoolValid =
    schoolInput.value.trim() !== "" ;

  // Submit button enabled only when all fields pass validation
  const allValid =
    isNameValid &&
    isParentValid &&
    isEmailValid &&
    isPhoneValid &&
    isStandardValid &&
    isSchoolValid;
  submitBtn.disabled = !allValid;
}

// ===== Event Listeners =====
// Bind validation and form state update to each input's "input" event for real-time feedback

// Name field: validate letters/spaces and update submit state
nameInput.addEventListener("input", () => {
  validateName(nameInput);
  checkFormValidity();
});

// Parent name field: reuse name validator and update submit state
parentInput.addEventListener("input", () => {
  validateName(parentInput);
  checkFormValidity();
});

// Email field: validate format and update submit state
emailInput.addEventListener("input", () => {
  validateEmail(emailInput);
  checkFormValidity();
});

// Phone field: validate 10-digit format and update submit state
phoneInput.addEventListener("input", () => {
  validatePhone(phoneInput);
  checkFormValidity();
});

// Standard field: validate non-empty and update submit state
standardInput.addEventListener("input", () => {
  validateStandard(standardInput);
  checkFormValidity();
});

// School field: reuse name validator and update submit state
schoolInput.addEventListener("input", () => {
  validateSchool(schoolInput);
  checkFormValidity();
});

// ===== Email Change Functionality =====
// Wait for DOM to be fully loaded before attaching event listener
function initChangeEmailButton() {
  const changeEmailBtn = document.getElementById("change-email-btn");
  if (changeEmailBtn) {
    changeEmailBtn.addEventListener("click", function(e) {
      e.preventDefault();
      
      // Confirm before changing email
      if (confirm('Do you want to verify a different email address? You will need to verify the new email.')) {
        // Redirect to email verification with change=true parameter
        // Email verification page will clear the verified_email from session
        window.location.href = "../html/email_verify.php?change=true";
      }
    });
    console.log("Change email button initialized successfully");
  } else {
    console.warn("Change email button not found");
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initChangeEmailButton);
} else {
  initChangeEmailButton();
}
