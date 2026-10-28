/* =========================================
   DETAIL.JS - detail produk & form pesanan
   (data `products` diambil dari data.js)
   ========================================= */

/* 1. AMBIL PRODUK DARI URL  (detail-produk.html?id=0) */
const params = new URLSearchParams(window.location.search);
const productId = Number(params.get("id")) || 0;
const product = products[productId] || products[0];

/* Ukuran standar katalog: "160 × 200 × 30" = lebar × panjang × tinggi */
const [stdWidth, stdLength, stdHeight] = product.size.split("×").map((n) => parseInt(n, 10));

/* 2. ELEMEN DOM */
const $ = (id) => document.getElementById(id);
const form = $("orderForm");
const modeButtons = document.querySelectorAll(".segmented__btn");
const dims = {
  length: { input: $("length"), hint: $("lengthHint"), label: "Panjang", min: 100, max: 300, std: stdLength },
  width:  { input: $("width"),  hint: $("widthHint"),  label: "Lebar",   min: 80,  max: 260, std: stdWidth  },
  height: { input: $("height"), hint: $("heightHint"), label: "Tinggi",  min: 10,  max: 50,  std: stdHeight },
};
let mode = "standard";

/* 3. ISI DATA PRODUK KE HALAMAN */
document.title = product.name + " - Griya Sprei";
$("crumbName").textContent = product.name;
$("productTitle").textContent = product.name;
$("productDesc").textContent = product.detail || product.desc;
$("standardSizeText").textContent = `${stdLength} × ${stdWidth} × ${stdHeight} cm`;
$("productImage").style.setProperty("--ph", product.color);
$("productImage").innerHTML = productImage(product);

/* 4. TAB STANDAR / CUSTOM */
function setMode(newMode) {
  mode = newMode;
  form.classList.toggle("is-standard", mode === "standard");
  modeButtons.forEach((b) => b.classList.toggle("segmented__btn--active", b.dataset.mode === mode));

  Object.values(dims).forEach((d) => {
    d.input.readOnly = mode === "standard";
    d.input.parentElement.parentElement.classList.remove("dim--error");
    if (mode === "standard") d.input.value = d.std;          // kembalikan ke ukuran katalog
    d.hint.textContent = mode === "standard" ? "Standar katalog" : `Tentukan ${d.label.toLowerCase()}`;
  });
  hideMessage();
}
modeButtons.forEach((btn) => btn.addEventListener("click", () => setMode(btn.dataset.mode)));

/* 5. TOMBOL + / - */
document.querySelectorAll("[data-stepper]").forEach((stepper) => {
  const output = stepper.querySelector("output");
  const min = Number(stepper.dataset.min);
  const max = Number(stepper.dataset.max);
  const [minus, plus] = stepper.querySelectorAll("button");

  const update = (value) => {
    output.textContent = value;
    minus.disabled = value <= min;
    plus.disabled = value >= max;
  };
  stepper.addEventListener("click", (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;
    update(Math.min(max, Math.max(min, Number(output.textContent) + Number(btn.dataset.step))));
  });
  update(Number(output.textContent));
});

/* 6. VALIDASI UKURAN CUSTOM */
function validateDimensions() {
  if (mode === "standard") return true;
  let valid = true;

  Object.values(dims).forEach((d) => {
    const value = Number(d.input.value);
    const ok = value >= d.min && value <= d.max;
    d.input.parentElement.parentElement.classList.toggle("dim--error", !ok);
    d.hint.textContent = ok ? `Tentukan ${d.label.toLowerCase()}` : `${d.label} harus ${d.min}–${d.max} cm`;
    if (!ok) valid = false;
  });
  return valid;
}

/* 7. PESAN STATUS */
function showMessage(text, type) {
  const el = $("formMessage");
  el.textContent = text;
  el.className = "form__message form__message--" + type;
  el.hidden = false;
}
function hideMessage() { $("formMessage").hidden = true; }

/* 8. TAMBAHKAN KE KERANJANG */
form.addEventListener("submit", (e) => {
  e.preventDefault();

  if (!validateDimensions()) {
    showMessage("Periksa kembali ukuran kasur Anda.", "error");
    return;
  }

  const addToCartForm = document.createElement("form");
  addToCartForm.method = "post";
  addToCartForm.action = "keranjang.php";

  const fields = {
    aksi: "tambah_produk",
    nama: product.name,
    jenis: mode,
    panjang: dims.length.input.value,
    lebar: dims.width.input.value,
    tinggi: dims.height.input.value,
    jumlah: $("qtySprei").textContent,
    bantal: $("qtyBantal").textContent,
    guling: $("qtyGuling").textContent,
    catatan: $("note").value.trim(),
    warna: product.color,
  };

  Object.entries(fields).forEach(([name, value]) => {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    addToCartForm.appendChild(input);
  });

  document.body.appendChild(addToCartForm);
  addToCartForm.submit();
});

/* 9. HEADER: BADGE KERANJANG & NAMA USER */
function updateCartBadge() {
  $("cartBadge").hidden = true;
}

function showLoggedInUser() {
  const email = localStorage.getItem("userEmail") || sessionStorage.getItem("userEmail");
  if (!email) return;                                    // belum login: tetap "Masuk / Daftar"
  const users = JSON.parse(localStorage.getItem("users") || "[]");
  const user = users.find((u) => u.email === email);
  $("userName").textContent = user ? user.name.split(" ")[0] : email.split("@")[0];
  $("userButton").removeAttribute("href");               // sudah login: tidak menuju halaman login
}

/* 10. JALANKAN */
setMode("standard");
updateCartBadge();
showLoggedInUser();