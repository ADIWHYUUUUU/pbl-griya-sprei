
// ==========================================
// DETAIL PRODUK - GRIYA SPREI
// Terintegrasi dengan CartController PHP
// ==========================================

// Helper mengambil elemen berdasarkan ID
const $ = (id) => document.getElementById(id);

// ==========================================
// ELEMEN UTAMA
// ==========================================
const form = $("orderForm");

const productId = Number(
  form.elements.namedItem("id_produk").value
);

const productName = $("productTitle").textContent.trim();

const stock = Number(form.dataset.stock);
const price = Number(form.dataset.price);
const category = form.dataset.category;

const stockStatus = $("stockStatus");

const modeButtons = document.querySelectorAll(
  ".segmented__btn"
);

const submitButton = form.querySelector('[type="submit"]');

// ==========================================
// INPUT HIDDEN UNTUK BACKEND
// ==========================================
const modeInput = $("modeInput");

const qtySpreiInput = $("qtySpreiInput");
const qtyBantalInput = $("qtyBantalInput");
const qtyGulingInput = $("qtyGulingInput");

// ==========================================
// DIMENSI KASUR
// ==========================================
const dimensions = {
  length: {
    input: $("length"),
    hint: $("lengthHint"),
    label: "Panjang"
  },

  width: {
    input: $("width"),
    hint: $("widthHint"),
    label: "Lebar"
  },

  height: {
    input: $("height"),
    hint: $("heightHint"),
    label: "Tinggi"
  }
};

// Simpan ukuran standar dari database.
Object.values(dimensions).forEach((dimension) => {
  dimension.standard = Number(dimension.input.value);
  dimension.min = Number(dimension.input.min);
  dimension.max = Number(dimension.input.max);
});

// Mode awal
let mode = "standard";

// Menyimpan ukuran CUSTOM ketika berpindah mode.
const customSizes = {};

// ==========================================
// TAMPILKAN PESAN
// ==========================================
function showMessage(text, type) {
  const message = $("formMessage");

  message.textContent = text;

  message.className = `form__message form__message--${type}`;

  message.hidden = false;
}

// ==========================================
// SEMBUNYIKAN PESAN
// ==========================================
function hideMessage() {
  const message = $("formMessage");

  message.textContent = "";
  message.hidden = true;
}

// ==========================================
// PILIH STANDARD / CUSTOM
// ==========================================
function setMode(newMode) {
  if (!["standard", "custom"].includes(newMode)) {
    return;
  }

  // Simpan ukuran custom sebelum berpindah
  // ke pilihan STANDARD.
  if (mode === "custom" && newMode === "standard") {
    Object.entries(dimensions).forEach(([key, dimension]) => {
      customSizes[key] = dimension.input.value;
    });
  }

  mode = newMode;

  // Sinkronkan pilihan mode ke input hidden.
  modeInput.value = mode;

  form.classList.toggle(
    "is-standard",
    mode === "standard"
  );

  stockStatus.hidden = mode !== "standard";

  // Mengubah tampilan tombol aktif.
  modeButtons.forEach((button) => {
    const isActive = button.dataset.mode === mode;

    button.classList.toggle(
      "segmented__btn--active",
      isActive
    );

    button.setAttribute(
      "aria-selected",
      String(isActive)
    );
  });

  // Mengatur input panjang, lebar, tinggi.
  Object.entries(dimensions).forEach(([key, dimension]) => {
    const input = dimension.input;

    input.readOnly = mode === "standard";

    input.parentElement.parentElement.classList.remove(
      "dim--error"
    );

    if (mode === "standard") {
      // Mengambil kembali ukuran standar produk.
      input.value = dimension.standard;

      dimension.hint.textContent = "Standar katalog";

    } else {
      // Mengaktifkan input CUSTOM.
      input.value =
        customSizes[key] ?? dimension.standard;

      dimension.hint.textContent =
        `Tentukan ${dimension.label.toLowerCase()}`;
    }
  });

  hideMessage();
}

// ==========================================
// EVENT TOMBOL STANDARD / CUSTOM
// ==========================================
modeButtons.forEach((button) => {
  button.addEventListener("click", () => {
    setMode(button.dataset.mode);
  });
});

// ==========================================
// STEPPER JUMLAH
// ==========================================
document.querySelectorAll("[data-stepper]").forEach(
  (stepper) => {
    const output = stepper.querySelector("output");

    const minimum = Number(stepper.dataset.min);
    const maximum = Number(stepper.dataset.max);

    const [minusButton, plusButton] =
      stepper.querySelectorAll("button");

    if (!output || !minusButton || !plusButton) {
      return;
    }

    // Update tampilan angka dan status tombol.
    const update = (value) => {
      const finalValue = Math.min(
        maximum,
        Math.max(minimum, value)
      );

      output.textContent = String(finalValue);

      minusButton.disabled = finalValue <= minimum;
      plusButton.disabled = finalValue >= maximum;
    };

    // Klik tombol + dan -
    stepper.addEventListener("click", (event) => {
      const button = event.target.closest("button");

      if (!button) return;

      const currentValue = Number(
        output.textContent.trim()
      );

      const step = Number(button.dataset.step);

      const nextValue = currentValue + step;

      update(nextValue);
    });

    // Kondisi awal.
    update(Number(output.textContent.trim()));
  }
);

// ==========================================
// VALIDASI DIMENSI CUSTOM
// ==========================================
function validateDimensions() {
  // Ukuran STANDARD sudah ditentukan database.
  if (mode === "standard") {
    return true;
  }

  let valid = true;

  Object.values(dimensions).forEach((dimension) => {
    const rawValue = dimension.input.value.trim();
    const value = Number(rawValue);

    const isValid =
      rawValue !== "" &&
      Number.isFinite(value) &&
      value >= dimension.min &&
      value <= dimension.max &&
      dimension.input.checkValidity();

    dimension.input.parentElement.parentElement.classList.toggle(
      "dim--error",
      !isValid
    );

    dimension.hint.textContent = isValid
      ? `Tentukan ${dimension.label.toLowerCase()}`
      : `${dimension.label} harus ${dimension.min}–${dimension.max} cm`;

    if (!isValid) {
      valid = false;
    }
  });

  return valid;
}

// ==========================================
// AMBIL JUMLAH DARI OUTPUT STEPPER
// ==========================================
function getQuantity(id) {
  const output = $(id);

  if (!output) {
    return NaN;
  }

  return Number(output.textContent.trim());
}

// ==========================================
// SUBMIT FORM KE BACKEND PHP
// ==========================================
form.addEventListener("submit", (event) => {
  hideMessage();

  // ========================================
  // VALIDASI ID PRODUK
  // ========================================
  if (
    !Number.isSafeInteger(productId) ||
    productId < 1
  ) {
    event.preventDefault();

    showMessage(
      "ID produk tidak valid. Silakan buka produk dari katalog.",
      "error"
    );

    return;
  }

  // ========================================
  // VALIDASI STOK
  // ========================================
  if (
    !Number.isFinite(stock) ||
    stock < 1
  ) {
    event.preventDefault();

    showMessage(
      "Stok produk sedang habis.",
      "error"
    );

    return;
  }

  // ========================================
  // VALIDASI UKURAN
  // ========================================
  if (!validateDimensions()) {
    event.preventDefault();

    showMessage(
      "Periksa kembali ukuran kasur Anda.",
      "error"
    );

    return;
  }

  // ========================================
  // AMBIL JUMLAH PRODUK
  // ========================================
  const quantity = getQuantity("qtySprei");

  const extraPillow = getQuantity("qtyBantal");

  const extraBolster = getQuantity("qtyGuling");

  // ========================================
  // VALIDASI JUMLAH SPREI
  // ========================================
  if (
    !Number.isInteger(quantity) ||
    quantity < 1 ||
    quantity > Math.min(50, stock)
  ) {
    event.preventDefault();

    showMessage(
      `Jumlah pesanan melebihi batas yang tersedia (${Math.min(50, stock)} set).`,
      "error"
    );

    return;
  }

  // ========================================
  // VALIDASI TAMBAHAN SARUNG
  // ========================================
  if (
    !Number.isInteger(extraPillow) ||
    !Number.isInteger(extraBolster) ||
    extraPillow < 0 ||
    extraPillow > 20 ||
    extraBolster < 0 ||
    extraBolster > 20
  ) {
    event.preventDefault();

    showMessage(
      "Jumlah tambahan sarung bantal atau guling tidak valid.",
      "error"
    );

    return;
  }

  // ========================================
  // SINKRONISASI DATA KE INPUT HIDDEN
  // ========================================

  // STANDARD / CUSTOM
  modeInput.value = mode;

  // Jumlah sprei
  qtySpreiInput.value = String(quantity);

  // Tambahan sarung bantal
  qtyBantalInput.value = String(extraPillow);

  // Tambahan sarung guling
  qtyGulingInput.value = String(extraBolster);

  // ========================================
  // KIRIM DATA KE ACTION PHP
  // ========================================

  // Tidak menggunakan localStorage lagi.
  // Tidak memanggil event.preventDefault()
  // apabila seluruh validasi sudah berhasil.
  //
  // Browser akan mengirim POST menuju:
  //
  // /backend/actions/user/tambah-keranjang.php
  //
  // Action kemudian memanggil:
  //
  // CartController->add($_POST)

  submitButton.disabled = true;
});

// ==========================================
// KONDISI AWAL
// ==========================================
if (stock < 1) {
  submitButton.disabled = true;
}

setMode("standard");
