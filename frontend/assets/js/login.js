/* =========================================
   1. ELEMEN DOM
   ========================================= */

const form = document.getElementById("loginForm");

const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");

const emailField = document.getElementById("emailField");
const passwordField = document.getElementById("passwordField");

const passwordHint = document.getElementById("passwordHint");

const clearEmailBtn = document.getElementById("clearEmail");
const toggleBtn = document.getElementById("togglePassword");

const rememberBox = document.getElementById("remember");

const MIN_PASSWORD = 6;
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/* =========================================
   2. VALIDASI
   ========================================= */

function validateEmail() {
  const valid = EMAIL_PATTERN.test(emailInput.value.trim());

  emailField.classList.toggle("field--error", !valid);

  return valid;
}

function validatePassword() {
  const valid = passwordInput.value.length >= MIN_PASSWORD;

  passwordField.classList.toggle("field--error", !valid);
  passwordHint.hidden = valid;

  return valid;
}

/* =========================================
   3. FITUR INPUT
   ========================================= */

// Tombol hapus email
emailInput.addEventListener("input", () => {
  clearEmailBtn.hidden = emailInput.value === "";

  if (emailField.classList.contains("field--error")) {
    validateEmail();
  }
});

clearEmailBtn.addEventListener("click", () => {
  emailInput.value = "";
  clearEmailBtn.hidden = true;

  emailInput.focus();
});

// Tombol tampilkan / sembunyikan password
toggleBtn.addEventListener("click", () => {
  const isHidden = passwordInput.type === "password";

  passwordInput.type = isHidden ? "text" : "password";

  toggleBtn.setAttribute(
    "aria-label",
    isHidden ? "Sembunyikan kata sandi" : "Tampilkan kata sandi",
  );

  toggleBtn.style.color = isHidden ? "var(--green)" : "";
});

passwordInput.addEventListener("input", () => {
  if (passwordField.classList.contains("field--error")) {
    validatePassword();
  }
});

emailInput.addEventListener("blur", () => {
  if (emailInput.value !== "") {
    validateEmail();
  }
});

/* =========================================
   4. SUBMIT LOGIN
   ========================================= */

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const emailOk = validateEmail();
  const passwordOk = validatePassword();

  if (!emailOk || !passwordOk) {
    return;
  }

  const email = emailInput.value.trim();
  const password = passwordInput.value;

  const formData = new FormData();

  formData.append("email", email);
  formData.append("password", password);

  try {
    const response = await fetch("../../backend/actions/login.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    // Login gagal
    if (!result.success) {
      alert(result.message);
      return;
    }

    // Fitur ingat email
    if (rememberBox.checked) {
      localStorage.setItem("userEmail", email);
    } else {
      localStorage.removeItem("userEmail");
    }

    // Redirect mengikuti AuthController
    window.location.href = result.redirect;
  } catch (error) {
    console.error("ERROR LOGIN:", error);

    alert("Terjadi kesalahan saat login. Cek Console F12.");
  }
});

/* =========================================
   5. FITUR INGAT SAYA
   ========================================= */

const savedEmail = localStorage.getItem("userEmail");

if (savedEmail) {
  emailInput.value = savedEmail;

  clearEmailBtn.hidden = false;

  rememberBox.checked = true;
}
