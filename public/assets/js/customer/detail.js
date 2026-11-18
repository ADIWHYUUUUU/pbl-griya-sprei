const orderForm = document.getElementById("orderForm");

if (orderForm) {
  const modeInput = document.getElementById("modeInput");
  const modeButtons = [...orderForm.querySelectorAll("[data-mode]")];
  const dimensionInputs = [...orderForm.querySelectorAll(".dim__input input")];
  const message = document.getElementById("formMessage");
  const sizeSelection = orderForm.querySelector(".size-selection");
  const dimensionHints = {
    length: "Tentukan panjang",
    width: "Tentukan lebar",
    height: "Tentukan tebal kasur",
  };
  const pricing = orderForm.dataset.pricing
    ? JSON.parse(orderForm.dataset.pricing)
    : {};
  const fabricInputs = [...orderForm.querySelectorAll('input[name="jenis_kain"]')];
  const pricePreview = document.getElementById("pricePreview");
  const customPriceNote = document.getElementById("customPriceNote");
  const standardPriceNote = document.getElementById("standardPriceNote");
  const orderSubtotal = document.getElementById("orderSubtotal");
  const standardSizeText = document.getElementById("standardSizeText");
  const sizeOptions = orderForm.querySelector(".size-options");
  const money = new Intl.NumberFormat("id-ID");
  const hiddenQuantities = {
    qtySprei: document.getElementById("qtySpreiInput"),
    qtyBantal: document.getElementById("qtyBantalInput"),
    qtyGuling: document.getElementById("qtyGulingInput"),
  };

  function showMessage(text) {
    message.textContent = text;
    message.hidden = false;
    message.classList.add("form__message--error");
  }

  function selectedFabric() {
    return orderForm.querySelector('input[name="jenis_kain"]:checked')?.value || "";
  }

  function selectedWidth() {
    return Number(orderForm.querySelector('input[name="ukuran_standar"]:checked')?.value);
  }

  function updateStandardDimensions() {
    const fabric = pricing[selectedFabric()];
    const width = selectedWidth();
    if (!fabric || !Number.isInteger(width)) return;

    document.getElementById("length").value = "200";
    document.getElementById("width").value = String(width);
    document.getElementById("height").value = String(fabric.height);
  }

  function renderSizes() {
    if (!sizeOptions) return;
    const fabric = pricing[selectedFabric()];
    if (!fabric) return;

    const previousWidth = selectedWidth();
    const sizeNames = {
      90: "Single",
      100: "Single",
      120: "Super Single",
      140: "Queen",
      160: "Queen",
      180: "King",
      200: "Super King",
    };
    sizeOptions.innerHTML = Object.entries(fabric.prices)
      .map(([width, price]) => `
        <label class="size-option">
          <input type="radio" name="ukuran_standar" value="${Number(width)}" required ${Number(width) === previousWidth ? "checked" : ""}>
          <span class="size-option__content">
            <strong>${sizeNames[Number(width)]} (${Number(width)})</strong>
            <small>${Number(width)} × 200 × ${Number(fabric.height)} cm</small>
            <b>Rp ${money.format(Number(price))}</b>
          </span>
        </label>
      `)
      .join("");

    if (!sizeOptions.querySelector('input[name="ukuran_standar"]:checked')) {
      sizeOptions.querySelector('input[name="ukuran_standar"]').checked = true;
    }
  }

  function updatePrice() {
    const isCustom = modeInput.value === "custom";
    const fabric = pricing[selectedFabric()];
    const price = Number(fabric?.prices?.[selectedWidth()] ?? orderForm.dataset.price);
    if (pricePreview) {
      pricePreview.textContent = isCustom
        ? "Konfirmasi Admin"
        : `Rp ${money.format(price)}`;
    }
    if (customPriceNote) customPriceNote.hidden = !isCustom;
    if (standardPriceNote) standardPriceNote.hidden = isCustom;
    if (orderSubtotal) {
      const quantity = Number(hiddenQuantities.qtySprei.value);
      orderSubtotal.textContent = isCustom
        ? "Dikonfirmasi Admin"
        : `Rp ${money.format(price * quantity)}`;
    }
    if (standardSizeText) {
      standardSizeText.textContent = `${dimensionInputs[0].value} × ${dimensionInputs[1].value} × ${dimensionInputs[2].value} cm`;
    }
  }

  function setMode(mode) {
    const isStandard = mode === "standard";
    modeInput.value = mode;
    orderForm.classList.toggle("is-standard", isStandard);
    if (sizeSelection) sizeSelection.hidden = !isStandard;

    modeButtons.forEach((button) => {
      const selected = button.dataset.mode === mode;
      button.classList.toggle("segmented__btn--active", selected);
      button.setAttribute("aria-selected", String(selected));
    });

    dimensionInputs.forEach((input) => {
      input.readOnly = isStandard;
      input.closest(".dim").classList.remove("dim--error");
      const hint = document.getElementById(`${input.id}Hint`);
      hint.textContent = isStandard
        ? "Standar katalog"
        : dimensionHints[input.id];
    });

    if (isStandard) {
      if (Object.keys(pricing).length > 0) {
        updateStandardDimensions();
      } else {
        dimensionInputs.forEach((input) => {
          input.value = input.dataset.standard;
        });
      }
    }
    updatePrice();
  }

  function updateStepper(stepper) {
    const output = stepper.querySelector("output");
    const value = Number(output.textContent.trim());
    const minimum = Number(stepper.dataset.min);
    const maximum = Number(stepper.dataset.max);
    const buttons = [...stepper.querySelectorAll("[data-step]")];

    buttons.forEach((button) => {
      const nextValue = value + Number(button.dataset.step);
      button.disabled = nextValue < minimum || nextValue > maximum;
    });

    if (output.id in hiddenQuantities) {
      hiddenQuantities[output.id].value = String(value);
    }
  }

  orderForm.querySelectorAll("[data-stepper]").forEach((stepper) => {
    stepper.addEventListener("click", (event) => {
      const button = event.target.closest("[data-step]");
      if (!button || button.disabled) return;

      const output = stepper.querySelector("output");
      const minimum = Number(stepper.dataset.min);
      const maximum = Number(stepper.dataset.max);
      const nextValue = Number(output.textContent.trim()) + Number(button.dataset.step);
      output.textContent = String(Math.max(minimum, Math.min(maximum, nextValue)));
      updateStepper(stepper);
      updatePrice();
    });
    updateStepper(stepper);
  });

  modeButtons.forEach((button) => {
    button.addEventListener("click", () => setMode(button.dataset.mode));
  });

  fabricInputs.forEach((input) => {
    input.addEventListener("change", () => {
      renderSizes();
      if (modeInput.value === "standard") updateStandardDimensions();
      updatePrice();
    });
  });

  sizeOptions?.addEventListener("change", (event) => {
    if (event.target.matches('input[name="ukuran_standar"]')) {
      if (modeInput.value === "standard") updateStandardDimensions();
      updatePrice();
    }
  });

  orderForm.addEventListener("submit", (event) => {
    message.hidden = true;
    message.textContent = "";
    message.classList.remove("form__message--error");

    if (modeInput.value === "custom") {
      const invalidDimension = dimensionInputs.find(
        (input) => !input.checkValidity() || Number(input.value) < 1,
      );
      if (invalidDimension) {
        invalidDimension.closest(".dim").classList.add("dim--error");
        showMessage(
          `Masukkan ${invalidDimension.labels[0].textContent.trim().toLowerCase()} dalam bilangan bulat lebih besar dari 0.`,
        );
        invalidDimension.focus();
        event.preventDefault();
        return;
      }
    }

    const quantity = Number(hiddenQuantities.qtySprei.value);
    const stock = Number(orderForm.dataset.stock);
    if (!Number.isInteger(quantity) || quantity < 1 || quantity > stock) {
      showMessage("Jumlah sprei melebihi stok yang tersedia.");
      event.preventDefault();
    }
  });

  dimensionInputs.forEach((input) => {
    input.addEventListener("input", () => {
      input.closest(".dim").classList.remove("dim--error");
      updatePrice();
    });
  });

  setMode(modeInput.value);
}
