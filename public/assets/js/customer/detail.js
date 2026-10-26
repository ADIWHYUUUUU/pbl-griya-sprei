const orderForm = document.getElementById("orderForm");

if (orderForm) {
  const modeInput = document.getElementById("modeInput");
  const modeButtons = [...orderForm.querySelectorAll("[data-mode]")];
  const dimensionInputs = [...orderForm.querySelectorAll(".dim__input input")];
  const message = document.getElementById("formMessage");
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

  function setMode(mode) {
    const isStandard = mode === "standard";
    modeInput.value = mode;
    orderForm.classList.toggle("is-standard", isStandard);

    modeButtons.forEach((button) => {
      const selected = button.dataset.mode === mode;
      button.classList.toggle("segmented__btn--active", selected);
      button.setAttribute("aria-selected", String(selected));
    });

    dimensionInputs.forEach((input) => {
      input.readOnly = isStandard;
      if (isStandard) input.value = input.dataset.standard;
      input.closest(".dim").classList.remove("dim--error");
      const hint = document.getElementById(`${input.id}Hint`);
      hint.textContent = isStandard
        ? "Standar katalog"
         : `Tentukan ${input.id.toLowerCase()}`;
    });
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
      const invalidDimension = dimensionInputs.find((input) => !input.checkValidity());
      if (invalidDimension) {
        invalidDimension.closest(".dim").classList.add("dim--error");
        showMessage(`Masukkan ${invalidDimension.labels[0].textContent.trim().toLowerCase()} minimal ${invalidDimension.min} cm.`);
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
    });
  });

  setMode(modeInput.value);
}
