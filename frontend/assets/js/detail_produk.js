const $ = (id) => document.getElementById(id);
const form = $("orderForm");
const productId = Number(form.elements.namedItem("id_produk").value);
const productName = $("productTitle").textContent.trim();
const stock = Number(form.dataset.stock);
const price = Number(form.dataset.price);
const category = form.dataset.category;
const stockStatus = $("stockStatus");
const modeButtons = document.querySelectorAll(".segmented__btn");
const submitButton = form.querySelector('[type="submit"]');
const dimensions = {
  length: { input: $("length"), hint: $("lengthHint"), label: "Panjang" },
  width: { input: $("width"), hint: $("widthHint"), label: "Lebar" },
  height: { input: $("height"), hint: $("heightHint"), label: "Tinggi" },
};

Object.values(dimensions).forEach((dimension) => {
  dimension.standard = Number(dimension.input.value);
  dimension.min = Number(dimension.input.min);
  dimension.max = Number(dimension.input.max);
});

let mode = "standard";

function showMessage(text, type) {
  const message = $("formMessage");
  message.textContent = text;
  message.className = `form__message form__message--${type}`;
  message.hidden = false;
}

function hideMessage() {
  $("formMessage").hidden = true;
}

function setMode(newMode) {
  mode = newMode;
  form.classList.toggle("is-standard", mode === "standard");
  stockStatus.hidden = mode !== "standard";

  modeButtons.forEach((button) => {
    const isActive = button.dataset.mode === mode;
    button.classList.toggle("segmented__btn--active", isActive);
    button.setAttribute("aria-selected", String(isActive));
  });

  Object.values(dimensions).forEach((dimension) => {
    dimension.input.readOnly = mode === "standard";
    dimension.input.parentElement.parentElement.classList.remove("dim--error");
    if (mode === "standard") dimension.input.value = dimension.standard;
    dimension.hint.textContent = mode === "standard"
      ? "Standar katalog"
      : `Tentukan ${dimension.label.toLowerCase()}`;
  });

  hideMessage();
}

modeButtons.forEach((button) => {
  button.addEventListener("click", () => setMode(button.dataset.mode));
});

document.querySelectorAll("[data-stepper]").forEach((stepper) => {
  const output = stepper.querySelector("output");
  const minimum = Number(stepper.dataset.min);
  const maximum = Number(stepper.dataset.max);
  const [minusButton, plusButton] = stepper.querySelectorAll("button");

  const update = (value) => {
    output.textContent = String(value);
    minusButton.disabled = value <= minimum;
    plusButton.disabled = value >= maximum;
  };

  stepper.addEventListener("click", (event) => {
    const button = event.target.closest("button");
    if (!button) return;

    const nextValue = Number(output.textContent) + Number(button.dataset.step);
    update(Math.min(maximum, Math.max(minimum, nextValue)));
  });

  update(Number(output.textContent));
});

function validateDimensions() {
  if (mode === "standard") return true;
  let valid = true;

  Object.values(dimensions).forEach((dimension) => {
    const value = Number(dimension.input.value);
    const isValid = Number.isFinite(value)
      && value >= dimension.min
      && value <= dimension.max;
    dimension.input.parentElement.parentElement.classList.toggle("dim--error", !isValid);
    dimension.hint.textContent = isValid
      ? `Tentukan ${dimension.label.toLowerCase()}`
      : `${dimension.label} harus ${dimension.min}–${dimension.max} cm`;
    if (!isValid) valid = false;
  });

  return valid;
}

function readCart() {
  try {
    const cart = JSON.parse(localStorage.getItem("cart") || "[]");
    return Array.isArray(cart) ? cart : [];
  } catch {
    return [];
  }
}

function updateCartBadge() {
  const count = readCart().reduce((total, item) => {
    const quantity = Number(item.qty);
    return total + (Number.isFinite(quantity) && quantity > 0 ? quantity : 1);
  }, 0);
  $("cartBadge").textContent = String(count);
  $("cartBadge").hidden = count === 0;
}

form.addEventListener("submit", (event) => {
  event.preventDefault();

  if (!Number.isSafeInteger(productId) || productId < 1) {
    showMessage("ID produk tidak valid. Silakan buka produk dari katalog.", "error");
    return;
  }
  if (!Number.isFinite(stock) || stock < 1) {
    showMessage("Stok produk sedang habis.", "error");
    return;
  }
  if (!validateDimensions()) {
    showMessage("Periksa kembali ukuran kasur Anda.", "error");
    return;
  }

  const quantity = Number($("qtySprei").textContent);
  if (!Number.isInteger(quantity) || quantity < 1 || quantity > stock) {
    showMessage(`Jumlah pesanan melebihi stok yang tersedia (${stock} set).`, "error");
    return;
  }

  const item = {
    productId,
    name: productName,
    category,
    price,
    type: mode,
    length: Number(dimensions.length.input.value),
    width: Number(dimensions.width.input.value),
    height: Number(dimensions.height.input.value),
    qty: quantity,
    extraPillow: Number($("qtyBantal").textContent),
    extraBolster: Number($("qtyGuling").textContent),
    note: $("note").value.trim(),
  };

  try {
    const cart = readCart();
    cart.push(item);
    localStorage.setItem("cart", JSON.stringify(cart));
  } catch {
    showMessage("Keranjang tidak dapat disimpan di browser ini.", "error");
    return;
  }

  updateCartBadge();
  showMessage("Berhasil ditambahkan ke keranjang. Harga final akan dikonfirmasi Admin.", "ok");
});

if (stock < 1) {
  submitButton.disabled = true;
}

setMode("standard");
updateCartBadge();