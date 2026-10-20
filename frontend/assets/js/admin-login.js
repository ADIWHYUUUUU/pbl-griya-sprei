const form = document.getElementById("adminLoginForm");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const emailField = document.getElementById("emailField");
const passwordField = document.getElementById("passwordField");
const passwordHint = document.getElementById("passwordHint");
const clearEmailButton = document.getElementById("clearEmail");
const toggleButton = document.getElementById("togglePassword");
const rememberCheckbox = document.getElementById("remember");

function validateEmail() {
  const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim());
  emailField.classList.toggle("field--error", !valid);
  return valid;
}

function validatePassword() {
  const valid = passwordInput.value.length > 0;
  passwordField.classList.toggle("field--error", !valid);
  passwordHint.hidden = valid;
  return valid;
}

emailInput.addEventListener("input", () => {
  clearEmailButton.hidden = emailInput.value === "";
  if (emailField.classList.contains("field--error")) validateEmail();
});

clearEmailButton.addEventListener("click", () => {
  emailInput.value = "";
  clearEmailButton.hidden = true;
  emailInput.focus();
});

toggleButton.addEventListener("click", () => {
  const showPassword = passwordInput.type === "password";
  passwordInput.type = showPassword ? "text" : "password";
  toggleButton.setAttribute("aria-label", showPassword ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
  toggleButton.style.color = showPassword ? "var(--green)" : "";
});

passwordInput.addEventListener("input", () => {
  if (passwordField.classList.contains("field--error")) validatePassword();
});

emailInput.addEventListener("blur", () => {
  if (emailInput.value !== "") validateEmail();
});

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (!validateEmail() || !validatePassword()) return;

  try {
    const response = await fetch("/backend/actions/admin/login.php", {
      method: "POST",
      body: new FormData(form),
    });
    const result = await response.json();
    if (!result.success) {
      alert(result.message);
      return;
    }
    if (rememberCheckbox.checked) {
      localStorage.setItem("adminEmail", emailInput.value.trim());
    } else {
      localStorage.removeItem("adminEmail");
    }
    window.location.href = result.redirect;
  } catch (error) {
    console.error("ERROR ADMIN LOGIN:", error);
    alert("Login gagal diproses. Periksa koneksi server.");
  }
});

const savedAdminEmail = localStorage.getItem("adminEmail");
if (savedAdminEmail) {
  emailInput.value = savedAdminEmail;
  clearEmailButton.hidden = false;
  rememberCheckbox.checked = true;
}