document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".payment-form");
  const proofInput = document.querySelector("#payment-proof");
  const chooseFileButton = document.querySelector(".upload-browse");
  const removeFileButton = document.querySelector(".upload-remove");
  const proofName = document.querySelector(".upload-file-name");
  const proofSize = document.querySelector(".upload-file-size");
  const proofIcon = document.querySelector(".upload-file-icon");
  const maxProofSize = 8 * 1024 * 1024;

  if (form) {
    form.addEventListener("submit", function (event) {
      const file = proofInput.files[0];
      if (file && file.size > maxProofSize) {
        event.preventDefault();
        window.alert("Ukuran bukti transfer maksimal 8 MB.");
      }
    });

    const bankOptions = form.querySelectorAll(".method-option");
    form.querySelectorAll('input[name="metode_pembayaran"]').forEach(function (radio) {
      radio.addEventListener("change", function () {
        bankOptions.forEach(function (option) {
          const optionRadio = option.querySelector('input[name="metode_pembayaran"]');
          const accountDetails = option.querySelector(".account-details");
          const isSelected = optionRadio.checked;
          option.classList.toggle("method-option--selected", isSelected);
          accountDetails.hidden = !isSelected;
        });
      });
    });
  }

  async function copyText(value, button, defaultText) {
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(value);
      } else {
        const temporaryInput = document.createElement("textarea");
        temporaryInput.value = value;
        temporaryInput.setAttribute("readonly", "");
        temporaryInput.style.position = "fixed";
        temporaryInput.style.opacity = "0";
        document.body.appendChild(temporaryInput);
        temporaryInput.select();
        const copied = document.execCommand("copy");
        temporaryInput.remove();
        if (!copied) throw new Error("Clipboard copy failed");
      }
      button.textContent = "Tersalin";
      window.setTimeout(function () {
        button.textContent = defaultText;
      }, 1600);
    } catch (error) {
      window.alert("Gagal menyalin. Silakan salin secara manual.");
    }
  }

  document.querySelectorAll(".copy-account").forEach(function (button) {
    button.addEventListener("click", function () {
      const accountNumber = button.closest(".account-details").querySelector(".account-number");
      copyText(accountNumber.textContent.trim(), button, "Salin nomor rekening");
    });
  });

  document.querySelectorAll(".copy-amount").forEach(function (button) {
    button.addEventListener("click", function () {
      copyText(button.dataset.copy, button, "Salin nominal");
    });
  });

  function updateProofFile() {
    const file = proofInput.files[0];
    if (!file) {
      proofName.textContent = "Belum ada file dipilih";
      proofSize.textContent = "JPG atau PNG · maksimal 8 MB";
      proofIcon.textContent = "JPG";
      chooseFileButton.textContent = "Pilih file";
      removeFileButton.disabled = true;
      return;
    }

    if (file.size > maxProofSize) {
      window.alert("Ukuran bukti transfer maksimal 8 MB.");
      proofInput.value = "";
      updateProofFile();
      return;
    }

    proofName.textContent = file.name;
    proofSize.textContent = `${(file.size / 1024 / 1024).toLocaleString("id-ID", {
      maximumFractionDigits: 1,
    })} MB · siap dikirim`;
    proofIcon.textContent = file.name.split(".").pop().toUpperCase().slice(0, 4);
    chooseFileButton.textContent = "Ganti file";
    removeFileButton.disabled = false;
  }

  if (proofInput) {
    chooseFileButton.addEventListener("click", function () {
      proofInput.click();
    });
    proofInput.addEventListener("change", updateProofFile);
    removeFileButton.addEventListener("click", function () {
      proofInput.value = "";
      updateProofFile();
    });
  }
});
