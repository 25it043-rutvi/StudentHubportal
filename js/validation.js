/**
 * StudentHub Portal - Registration Form Frontend Validation
 * Practical 5: Comprehensive Client-Side Validation & UX Feedback
 * Features: Regular expressions, live password strength meter,
 * real-time keyup/change validation, accessible inline error messages.
 */

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('registration-form');
  if (!form) return;

  const fullNameInput = document.getElementById('fullname');
  const emailInput = document.getElementById('email');
  const phoneInput = document.getElementById('phone');
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('confirm_password');
  const courseInput = document.getElementById('course');
  const termsCheckbox = document.getElementById('terms');
  
  // Real-time event listeners
  if (fullNameInput) {
    fullNameInput.addEventListener('input', () => validateFullName(fullNameInput));
    fullNameInput.addEventListener('blur', () => validateFullName(fullNameInput));
  }

  if (emailInput) {
    emailInput.addEventListener('input', () => validateEmail(emailInput));
    emailInput.addEventListener('blur', () => validateEmail(emailInput));
  }

  if (phoneInput) {
    phoneInput.addEventListener('input', () => validatePhone(phoneInput));
    phoneInput.addEventListener('blur', () => validatePhone(phoneInput));
  }

  if (passwordInput) {
    passwordInput.addEventListener('input', () => {
      validatePassword(passwordInput);
      updatePasswordStrength(passwordInput.value);
      if (confirmPasswordInput && confirmPasswordInput.value) {
        validateConfirmPassword(passwordInput, confirmPasswordInput);
      }
    });
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', () => validateConfirmPassword(passwordInput, confirmPasswordInput));
    confirmPasswordInput.addEventListener('blur', () => validateConfirmPassword(passwordInput, confirmPasswordInput));
  }

  if (courseInput) {
    courseInput.addEventListener('change', () => validateCourse(courseInput));
  }

  if (termsCheckbox) {
    termsCheckbox.addEventListener('change', () => validateTerms(termsCheckbox));
  }

  // Handle Form Submission
  form.addEventListener('submit', (e) => {
    let isValid = true;

    if (fullNameInput && !validateFullName(fullNameInput)) isValid = false;
    if (emailInput && !validateEmail(emailInput)) isValid = false;
    if (phoneInput && !validatePhone(phoneInput)) isValid = false;
    if (passwordInput && !validatePassword(passwordInput)) isValid = false;
    if (confirmPasswordInput && !validateConfirmPassword(passwordInput, confirmPasswordInput)) isValid = false;
    if (courseInput && !validateCourse(courseInput)) isValid = false;
    if (!validateGender()) isValid = false;
    if (termsCheckbox && !validateTerms(termsCheckbox)) isValid = false;

    if (!isValid) {
      e.preventDefault();
      if (window.showNotification) {
        window.showNotification('Validation Error', 'Please correct the highlighted fields before submitting.', 'danger');
      }
    } else {
      // Allow submission to continue (e.g. to PHP backend)
      // If submitting via file/demo mode, can show success
    }
  });
});

/* --- Individual Validation Rules --- */

function setError(inputElement, errorElementId, message) {
  const formGroup = inputElement.closest('.form-group');
  const errorEl = document.getElementById(errorElementId);
  
  inputElement.classList.remove('is-valid');
  inputElement.classList.add('is-invalid');
  
  if (formGroup) formGroup.classList.add('has-error');
  if (errorEl) {
    errorEl.textContent = message;
    errorEl.style.display = 'block';
  }
  return false;
}

function setSuccess(inputElement, errorElementId) {
  const formGroup = inputElement.closest('.form-group');
  const errorEl = document.getElementById(errorElementId);
  
  inputElement.classList.remove('is-invalid');
  inputElement.classList.add('is-valid');
  
  if (formGroup) formGroup.classList.remove('has-error');
  if (errorEl) {
    errorEl.textContent = '';
    errorEl.style.display = 'none';
  }
  return true;
}

function validateFullName(input) {
  const val = input.value.trim();
  // Regex: Only alphabets and single spaces, 3 to 50 characters
  const nameRegex = /^[A-Za-z\s]{3,50}$/;

  if (!val) {
    return setError(input, 'name-error', 'Full name is required.');
  }
  if (!nameRegex.test(val)) {
    return setError(input, 'name-error', 'Name must contain only letters and be at least 3 characters long.');
  }
  return setSuccess(input, 'name-error');
}

function validateEmail(input) {
  const val = input.value.trim();
  // Standard RFC 5322 simplified email regex
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

  if (!val) {
    return setError(input, 'email-error', 'Email address is required.');
  }
  if (!emailRegex.test(val)) {
    return setError(input, 'email-error', 'Please enter a valid email address (e.g., student@charusat.edu).');
  }
  return setSuccess(input, 'email-error');
}

function validatePhone(input) {
  const val = input.value.trim();
  // 10 digit Indian mobile format starting with 6, 7, 8, or 9
  const phoneRegex = /^[6-9]\d{9}$/;

  if (!val) {
    return setError(input, 'phone-error', 'Mobile number is required.');
  }
  if (!phoneRegex.test(val)) {
    return setError(input, 'phone-error', 'Enter a valid 10-digit mobile number starting with 6-9.');
  }
  return setSuccess(input, 'phone-error');
}

function validatePassword(input) {
  const val = input.value;
  // Min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character
  const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/;

  if (!val) {
    return setError(input, 'password-error', 'Password is required.');
  }
  if (val.length < 8) {
    return setError(input, 'password-error', 'Password must be at least 8 characters long.');
  }
  if (!passwordRegex.test(val)) {
    return setError(input, 'password-error', 'Password must include uppercase, lowercase, number, and special character.');
  }
  return setSuccess(input, 'password-error');
}

function validateConfirmPassword(passwordInput, confirmInput) {
  const pwd = passwordInput.value;
  const confirmPwd = confirmInput.value;

  if (!confirmPwd) {
    return setError(confirmInput, 'confirm-password-error', 'Please confirm your password.');
  }
  if (pwd !== confirmPwd) {
    return setError(confirmInput, 'confirm-password-error', 'Passwords do not match.');
  }
  return setSuccess(confirmInput, 'confirm-password-error');
}

function validateCourse(input) {
  const val = input.value;
  if (!val || val === '') {
    return setError(input, 'course-error', 'Please select your department/course.');
  }
  return setSuccess(input, 'course-error');
}

function validateGender() {
  const genderRadios = document.querySelectorAll('input[name="gender"]');
  const errorEl = document.getElementById('gender-error');
  let selected = false;

  genderRadios.forEach(radio => {
    if (radio.checked) selected = true;
  });

  if (!selected) {
    if (errorEl) {
      errorEl.textContent = 'Please select your gender.';
      errorEl.style.display = 'block';
    }
    return false;
  } else {
    if (errorEl) {
      errorEl.textContent = '';
      errorEl.style.display = 'none';
    }
    return true;
  }
}

function validateTerms(input) {
  const errorEl = document.getElementById('terms-error');
  if (!input.checked) {
    if (errorEl) {
      errorEl.textContent = 'You must accept the terms and conditions.';
      errorEl.style.display = 'block';
    }
    return false;
  } else {
    if (errorEl) {
      errorEl.textContent = '';
      errorEl.style.display = 'none';
    }
    return true;
  }
}

/* --- Password Strength Meter --- */
function updatePasswordStrength(password) {
  const fillBar = document.getElementById('strength-fill');
  const textLabel = document.getElementById('strength-text');
  if (!fillBar || !textLabel) return;

  if (!password) {
    fillBar.style.width = '0%';
    fillBar.style.backgroundColor = 'transparent';
    textLabel.textContent = 'Strength: Not entered';
    textLabel.style.color = 'var(--text-muted)';
    return;
  }

  let score = 0;
  if (password.length >= 8) score++;
  if (/[A-Z]/.test(password)) score++;
  if (/[0-9]/.test(password)) score++;
  if (/[@$!%*?&#]/.test(password)) score++;

  if (score === 1) {
    fillBar.style.width = '25%';
    fillBar.style.backgroundColor = '#ef4444';
    textLabel.textContent = 'Strength: Weak';
    textLabel.style.color = '#ef4444';
  } else if (score === 2) {
    fillBar.style.width = '50%';
    fillBar.style.backgroundColor = '#f59e0b';
    textLabel.textContent = 'Strength: Moderate';
    textLabel.style.color = '#f59e0b';
  } else if (score === 3) {
    fillBar.style.width = '75%';
    fillBar.style.backgroundColor = '#0ea5e9';
    textLabel.textContent = 'Strength: Good';
    textLabel.style.color = '#0ea5e9';
  } else if (score === 4) {
    fillBar.style.width = '100%';
    fillBar.style.backgroundColor = '#10b981';
    textLabel.textContent = 'Strength: Strong';
    textLabel.style.color = '#10b981';
  }
}
