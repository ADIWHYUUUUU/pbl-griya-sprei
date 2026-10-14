/* =========================================
   DETAIL.JS - detail produk & form pesanan
   (data `products` diambil dari data.js)
   ========================================= */

/* 1. AMBIL PRODUK DARI URL  (detail-produk.html?id=0) */
const params = new URLSearchParams(window.location.search);
const productId = Number(params.get("id")) || 0;
const product = products[productId] || products[0];

/* Ukuran standar katalog: "160 × 200 × 30" = lebar × panjang × tinggi */
<<<<<<< HEAD
const [stdWidth, stdLength, stdHeight] = product.size.split("×").map((n) => parseInt(n, 10));
=======
const [stdWidth, stdLength, stdHeight] = product.size
  .split("×")
  .map((n) => parseInt(n, 10));
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6

/* 2. ELEMEN DOM */
const $ = (id) => document.getElementById(id);
const form = $("orderForm");
const modeButtons = document.querySelectorAll(".segmented__btn");
const dims = {
<<<<<<< HEAD
  length: { input: $("length"), hint: $("lengthHint"), label: "Panjang", min: 100, max: 300, std: stdLength },
  width:  { input: $("width"),  hint: $("widthHint"),  label: "Lebar",   min: 80,  max: 260, std: stdWidth  },
  height: { input: $("height"), hint: $("heightHint"), label: "Tinggi",  min: 10,  max: 50,  std: stdHeight },
=======
  length: {
    input: $("length"),
    hint: $("lengthHint"),
    label: "Panjang",
    min: 100,
    max: 300,
    std: stdLength,
  },
  width: {
    input: $("width"),
    hint: $("widthHint"),
    label: "Lebar",
    min: 80,
    max: 260,
    std: stdWidth,
  },
  height: {
    input: $("height"),
    hint: $("heightHint"),
    label: "Tinggi",
    min: 10,
    max: 50,
    std: stdHeight,
  },
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6
};
let mode = "standard";

/* 3. ISI DATA PRODUK KE HALAMAN */
document.title = product.name + " - Griya Sprei";
$("crumbName").textContent = product.name;
$("productTitle").textContent = product.name;
$("productDesc").textContent = product.detail || product.desc;
<<<<<<< HEAD
$("standardSizeText").textContent = `${stdLength} × ${stdWidth} × ${stdHeight} cm`;
=======
$("standardSizeText").textContent =
  `${stdLength} × ${stdWidth} × ${stdHeight} cm`;
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6
$("productImage").style.setProperty("--ph", product.color);
$("productImage").innerHTML = productImage(product);

/* 4. TAB STANDAR / CUSTOM */
function setMode(newMode) {
  mode = newMode;
  form.classList.toggle("is-standard", mode === "standard");
<<<<<<< HEAD
  modeButtons.forEach((b) => b.classList.toggle("segmented__btn--active", b.dataset.mode === mode));
=======
  modeButtons.forEach((b) =>
    b.classList.toggle("segmented__btn--active", b.dataset.mode === mode),
  );
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6

  Object.values(dims).forEach((d) => {
    d.input.readOnly = mode === "standard";
    d.input.parentElement.parentElement.classList.remove("dim--error");
<<<<<<< HEAD
    if (mode === "standard") d.input.value = d.std;          // kembalikan ke ukuran katalog
    d.hint.textContent = mode === "standard" ? "Standar katalog" : `Tentukan ${d.label.toLowerCase()}`;
  });
  hideMessage();
}
modeButtons.forEach((btn) => btn.addEventListener("click", () => setMode(btn.dataset.mode)));
=======
    if (mode === "standard") d.input.value = d.std; // kembalikan ke ukuran katalog
    d.hint.textContent =
      mode === "standard"
        ? "Standar katalog"
        : `Tentukan ${d.label.toLowerCase()}`;
  });
  hideMessage();
}
modeButtons.forEach((btn) =>
  btn.addEventListener("click", () => setMode(btn.dataset.mode)),
);
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6

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
<<<<<<< HEAD
    update(Math.min(max, Math.max(min, Number(output.textContent) + Number(btn.dataset.step))));
=======
    update(
      Math.min(
        max,
        Math.max(min, Number(output.textContent) + Number(btn.dataset.step)),
      ),
    );
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6
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
<<<<<<< HEAD
    d.hint.textContent = ok ? `Tentukan ${d.label.toLowerCase()}` : `${d.label} harus ${d.min}–${d.max} cm`;
=======
    d.hint.textContent = ok
      ? `Tentukan ${d.label.toLowerCase()}`
      : `${d.label} harus ${d.min}–${d.max} cm`;
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6
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
<<<<<<< HEAD
function hideMessage() { $("formMessage").hidden = true; }
=======
function hideMessage() {
  $("formMessage").hidden = true;
}
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6

/* 8. TAMBAHKAN KE KERANJANG
   TODO: ganti dengan request ke backend. Sementara disimpan di localStorage. */
form.addEventListener("submit", (e) => {
  e.preventDefault();

  if (!validateDimensions()) {
    showMessage("Periksa kembali ukuran kasur Anda.", "error");
    return;
  }

  const item = {
    productId,
    name: product.name,
<<<<<<< HEAD
    type: mode,                                   // "standard" atau "custom"
=======
    type: mode, // "standard" atau "custom"
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6
    length: Number(dims.length.input.value),
    width: Number(dims.width.input.value),
    height: Number(dims.height.input.value),
    qty: Number($("qtySprei").textContent),
    extraPillow: Number($("qtyBantal").textContent),
    extraBolster: Number($("qtyGuling").textContent),
    note: $("note").value.trim(),
  };

  const cart = JSON.parse(localStorage.getItem("cart") || "[]");
  cart.push(item);
  localStorage.setItem("cart", JSON.stringify(cart));

  updateCartBadge();
<<<<<<< HEAD
  showMessage("Berhasil ditambahkan ke keranjang. Harga final akan dikonfirmasi Admin.", "ok");
=======
  showMessage(
    "Berhasil ditambahkan ke keranjang. Harga final akan dikonfirmasi Admin.",
    "ok",
  );
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6
});

/* 9. HEADER: BADGE KERANJANG & NAMA USER */
function updateCartBadge() {
  const count = JSON.parse(localStorage.getItem("cart") || "[]").length;
  $("cartBadge").textContent = count;
  $("cartBadge").hidden = count === 0;
}

function showLoggedInUser() {
<<<<<<< HEAD
  const email = localStorage.getItem("userEmail") || sessionStorage.getItem("userEmail");
  if (!email) return;                                    // belum login: tetap "Masuk / Daftar"
  const users = JSON.parse(localStorage.getItem("users") || "[]");
  const user = users.find((u) => u.email === email);
  $("userName").textContent = user ? user.name.split(" ")[0] : email.split("@")[0];
  $("userButton").removeAttribute("href");               // sudah login: tidak menuju halaman login
=======
  const email =
    localStorage.getItem("userEmail") || sessionStorage.getItem("userEmail");
  if (!email) return; // belum login: tetap "Masuk / Daftar"
  const users = JSON.parse(localStorage.getItem("users") || "[]");
  const user = users.find((u) => u.email === email);
  $("userName").textContent = user
    ? user.name.split(" ")[0]
    : email.split("@")[0];
  $("userButton").removeAttribute("href"); // sudah login: tidak menuju halaman login
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6
}

/* 10. JALANKAN */
setMode("standard");
updateCartBadge();
<<<<<<< HEAD
showLoggedInUser();
=======
showLoggedInUser();
>>>>>>> af99841c4edb3a4cc91886b8c6e66220c4a0baf6
