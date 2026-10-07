/* =========================================
   REGISTER.JS - validasi form daftar akun
   ========================================= */
const form = document.getElementById("registerForm");
const $ = (id) => document.getElementById(id);

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/* Ubah "0812-3456-7890" / "+62 812..." menjadi "81234567890" */
function normalizePhone(value) {
  return value.replace(/\D/g, "").replace(/^(62|0)/, "");
}

/* 1. ATURAN VALIDASI
   Setiap aturan mengembalikan true jika valid, atau teks error jika tidak. */
const rules = {
  name:     (v) => v.trim().length >= 3 || "Nama lengkap minimal 3 karakter",
  email:    (v) => EMAIL_PATTERN.test(v.trim()) || "Masukkan alamat email yang valid",
  phone:    (v) => /^8\d{8,11}$/.test(normalizePhone(v)) || "Nomor WhatsApp tidak valid, contoh: 812-3456-7890",
  password: (v) => v.length >= 8 || "Kata sandi minimal 8 karakter",
  confirm:  (v) => v === $("password").value || "Konfirmasi kata sandi tidak sama",
  terms:    ()  => $("terms").checked || "Anda harus menyetujui ketentuan terlebih dahulu",
};

/* 2. TAMPILKAN / SEMBUNYIKAN ERROR */
function validateField(id) {
  const input = $(id);
  const result = rules[id](input.value);
  const field = input.closest(".field");
  const errorEl = field.querySelector(".field__error");

  field.classList.toggle("field--error", result !== true);
  errorEl.textContent = result === true ? "" : result;
  errorEl.hidden = result === true;
  return result === true;
}

/* Validasi ulang saat user mengetik (hanya jika sudah error) */
Object.keys(rules).forEach((id) => {
  $(id).addEventListener(id === "terms" ? "change" : "input", () => {
    if ($(id).closest(".field").classList.contains("field--error")) validateField(id);
  });
  if (id !== "terms") $(id).addEventListener("blur", () => { if ($(id).value) validateField(id); });
});

/* 3. TOMBOL MATA (lihat / sembunyikan sandi) */
document.querySelectorAll(".input__toggle").forEach((btn) => {
  btn.addEventListener("click", () => {
    const input = $(btn.dataset.target);
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    btn.style.color = show ? "var(--green-dark)" : "";
  });
});

/* 4. KIRIM FORM */
form.addEventListener("submit", (e) => {
  e.preventDefault();

  // Cek semua field (tanpa berhenti di error pertama)
  const allValid = Object.keys(rules).map(validateField).every(Boolean);
  if (!allValid) return;

  register({
    name: $("name").value.trim(),
    email: $("email").value.trim().toLowerCase(),
    phone: "+62" + normalizePhone($("phone").value),
    password: $("password").value,
  });
});

/* 5. PROSES DAFTAR
   TODO: ganti dengan request ke backend (contoh: fetch("/api/register", ...)).
   Sementara data disimpan di localStorage hanya untuk uji coba. */
function register(user) {
  const users = JSON.parse(localStorage.getItem("users") || "[]");

  if (users.some((u) => u.email === user.email)) {
    const field = $("email").closest(".field");
    field.classList.add("field--error");
    field.querySelector(".field__error").textContent = "Email sudah terdaftar, silakan masuk";
    field.querySelector(".field__error").hidden = false;
    return;
  }

  users.push(user);
  localStorage.setItem("users", JSON.stringify(users));
  alert("Pendaftaran berhasil! Silakan masuk.");
  window.location.href = "login.html";
}