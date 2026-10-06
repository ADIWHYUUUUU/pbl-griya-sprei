/* =========================================
   1. ELEMEN DOM
   ========================================= */
const form          = document.getElementById("loginForm");
const emailInput    = document.getElementById("email");
const passwordInput = document.getElementById("password");
const emailField    = document.getElementById("emailField");
const passwordField = document.getElementById("passwordField");
const emailHint     = document.getElementById("emailHint");
const passwordHint  = document.getElementById("passwordHint");
const clearEmailBtn = document.getElementById("clearEmail");
const toggleBtn     = document.getElementById("togglePassword");
const rememberBox   = document.getElementById("remember");

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
   3. TOMBOL BANTU DI DALAM INPUT
   ========================================= */
// Tombol hapus email: hanya tampil kalau input ada isinya
emailInput.addEventListener("input", () => {
  clearEmailBtn.hidden = emailInput.value === "";
  if (emailField.classList.contains("field--error")) validateEmail();
});

clearEmailBtn.addEventListener("click", () => {
  emailInput.value = "";
  clearEmailBtn.hidden = true;
  emailInput.focus();
});

// Tombol lihat / sembunyikan kata sandi
toggleBtn.addEventListener("click", () => {
  const isHidden = passwordInput.type === "password";
  passwordInput.type = isHidden ? "text" : "password";
  toggleBtn.setAttribute("aria-label", isHidden ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
  toggleBtn.style.color = isHidden ? "var(--green)" : "";
});

passwordInput.addEventListener("input", () => {
  if (passwordField.classList.contains("field--error")) validatePassword();
});

emailInput.addEventListener("blur", () => { if (emailInput.value) validateEmail(); });

/* =========================================
   4. KIRIM FORM
   ========================================= */
form.addEventListener("submit", (e) => {
  e.preventDefault();

  const emailOk    = validateEmail();
  const passwordOk = validatePassword();
  if (!emailOk || !passwordOk) return;

  const data = {
    email: emailInput.value.trim(),
    password: passwordInput.value,
    remember: rememberBox.checked,
  };

  login(data);
});

/* =========================================
   5. PROSES LOGIN
   ========================================= */
function login(data) {
  // TODO: ganti dengan request ke backend, misalnya:
  // fetch("/api/login", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data) })

  const storage = data.remember ? localStorage : sessionStorage;
  storage.setItem("userEmail", data.email);

  window.location.href = "../katalog.html";   // arahkan ke katalog produk
}

/* Isi otomatis email jika sebelumnya dipilih "Ingat saya" */
const savedEmail = localStorage.getItem("userEmail");
if (savedEmail) {
  emailInput.value = savedEmail;
  clearEmailBtn.hidden = false;
  rememberBox.checked = true;
}