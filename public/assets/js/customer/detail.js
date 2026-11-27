const orderForm = document.getElementById("orderForm");

if (orderForm) {
  const modeInput = document.getElementById("modeInput");
  const standardOnly = orderForm.dataset.standardOnly === "true";
  const modeButtons = [...orderForm.querySelectorAll("[data-mode]")];
  const dimensionInputs = [...orderForm.querySelectorAll(".dim__input input")];
  const message = document.getElementById("formMessage");
  const dimensionHints = {
    length: "Tentukan panjang",
    width: "Tentukan lebar",
    height: "Tentukan tebal kasur",
  };
  const standardSizeText = document.getElementById("standardSizeText");
  const selectedSizeLabel = document.getElementById("selectedSizeLabel");
  const standardStock = document.getElementById("standardStock");
  const quantityStepper = document.getElementById("qtySprei")?.closest("[data-stepper]");
  const submitButton = orderForm.querySelector(".btn-cart");
  const stock = Number(orderForm.dataset.stock);
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

  function updateSizeText() {
    if (standardSizeText) {
      standardSizeText.textContent = `${dimensionInputs.map((input) => input.value).join(" × ")} cm`;
    }
    if (selectedSizeLabel) {
      selectedSizeLabel.textContent = modeInput.value === "custom"
        ? "Ukuran Custom:"
        : standardOnly
          ? "Ukuran Produk:"
          : "Ukuran Standar Kasur:";
    }
  }

  function setMode(mode) {
    if (standardOnly) mode = "standard";
    const isStandard = mode === "standard";
    modeInput.value = mode;
    orderForm.classList.toggle("is-standard", isStandard);
    if (standardStock) standardStock.hidden = !isStandard;
    if (quantityStepper) {
      const maximum = isStandard ? Math.max(1, Math.min(50, stock)) : 50;
      quantityStepper.dataset.max = String(maximum);
      const output = quantityStepper.querySelector("output");
      if (stock > 0 && Number(output.textContent.trim()) > maximum) {
        output.textContent = String(maximum);
      }
      updateStepper(quantityStepper);
    }
    if (submitButton) {
      submitButton.disabled = isStandard && stock <= 0;
    }

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
      dimensionInputs.forEach((input) => {
        input.value = input.dataset.standard;
      });
    }
    updateSizeText();
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
    });
    updateStepper(stepper);
  });

  modeButtons.forEach((button) => {
    button.addEventListener("click", () => setMode(button.dataset.mode));
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
    if (
      !Number.isInteger(quantity) ||
      quantity < 1 ||
      quantity > 50 ||
      (modeInput.value === "standard" && quantity > stock)
    ) {
      showMessage(modeInput.value === "custom"
        ? "Jumlah pesanan maksimal 50."
        : "Jumlah sprei melebihi stok yang tersedia.");
      event.preventDefault();
    }
  });

  dimensionInputs.forEach((input) => {
    input.addEventListener("input", () => {
      input.closest(".dim").classList.remove("dim--error");
      updateSizeText();
    });
  });

  setMode(modeInput.value);
}
