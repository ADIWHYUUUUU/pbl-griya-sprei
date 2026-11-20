document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".payment-form");
  const uploadControl = document.querySelector(".upload-control");
  const proofInput = document.querySelector("#payment-proof");
  const chooseFileButton = document.querySelector(".upload-browse");
  const removeFileButton = document.querySelector(".upload-remove");
  const proofName = document.querySelector(".upload-file-name");
  const proofSize = document.querySelector(".upload-file-size");
  const proofIcon = document.querySelector(".upload-file-icon");
  const uploadEmpty = document.querySelector(".upload-empty");
  const uploadActions = document.querySelector(".upload-actions");
  const uploadHelp = document.querySelector(".upload-help");
  const submitButton = form?.querySelector(".payment-submit");
  const submitHint = form?.querySelector(".payment-submit-hint");
  const maxProofSize = 8 * 1024 * 1024;
  const accountsAvailable = submitButton ? !submitButton.disabled : false;

  if (form) {
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

  function getProofError(file) {
    if (!file) {
      return null;
    }

    if (file.size === 0) {
      return { state: "format-error", message: "File kosong atau tidak valid." };
    }
    if (file.size > maxProofSize) {
      return {
        state: "size-error",
        message: `File ${formatFileSize(file.size)}. Maksimal 8 MB.`,
      };
    }

    const hasSupportedType = file.type
      ? ["image/jpeg", "image/png"].includes(file.type)
      : /\.(jpe?g|png)$/i.test(file.name);
    if (!hasSupportedType) {
      return {
        state: "format-error",
        message: "Format tidak didukung. Gunakan JPG atau PNG.",
      };
    }

    return null;
  }

  function formatFileSize(size) {
    return `${(size / 1024 / 1024).toLocaleString("id-ID", {
      maximumFractionDigits: 1,
    })} MB`;
  }

  function updateProofFile(isSending = false) {
    const file = proofInput.files[0];
    uploadControl.classList.remove("upload-control--dragging");

    if (!file) {
      uploadControl.dataset.state = "empty";
      uploadEmpty.hidden = false;
      proofIcon.hidden = true;
      proofName.closest(".upload-file-info").hidden = true;
      uploadActions.hidden = true;
      uploadHelp.textContent = "JPG atau PNG, maksimal 8 MB. Pastikan nominal dan tanggal terlihat jelas.";
      uploadHelp.classList.remove("upload-help--error");
      submitHint.hidden = false;
      submitButton.disabled = !accountsAvailable;
      submitButton.textContent = "Kirim Bukti Pembayaran";
      return;
    }

    const error = getProofError(file);
    uploadControl.dataset.state = isSending ? "sending" : error?.state ?? "ready";
    uploadEmpty.hidden = true;
    proofIcon.hidden = false;
    proofName.closest(".upload-file-info").hidden = false;
    uploadActions.hidden = false;
    proofName.textContent = file.name;
    proofSize.textContent = error?.message ?? `${formatFileSize(file.size)} · siap dikirim`;
    proofIcon.textContent = file.name.split(".").pop().toUpperCase().slice(0, 4) || "FILE";
    uploadHelp.textContent = error?.state === "size-error"
      ? "Coba kompres foto atau ambil tangkapan layar bukti transfer."
      : error?.message ?? "Pastikan nominal dan tanggal transfer terlihat jelas.";
    uploadHelp.classList.toggle("upload-help--error", Boolean(error));
    submitHint.hidden = Boolean(error) || isSending;
    submitHint.textContent = "Pilih file bukti transfer untuk melanjutkan.";
    submitButton.disabled = !accountsAvailable || Boolean(error);
    submitButton.textContent = "Kirim Bukti Pembayaran";
    chooseFileButton.textContent = "Pilih file lain";
    chooseFileButton.disabled = false;
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

    form.addEventListener("submit", function (event) {
      if (form.dataset.submitting === "true") {
        event.preventDefault();
        return;
      }

      const file = proofInput.files[0];
      if (!file || getProofError(file)) {
        event.preventDefault();
        updateProofFile();
        uploadControl.focus();
        return;
      }

      form.dataset.submitting = "true";
      updateProofFile(true);
    });

    updateProofFile();
  }

  if (uploadControl && proofInput) {
    uploadControl.addEventListener("click", function (event) {
      if (!event.target.closest("button")) {
        uploadControl.focus();
        proofInput.click();
      }
    });

    uploadControl.addEventListener("keydown", function (event) {
      if (event.target === uploadControl && ["Enter", " "].includes(event.key)) {
        event.preventDefault();
        proofInput.click();
      }
    });

    ["dragenter", "dragover"].forEach(function (eventName) {
      uploadControl.addEventListener(eventName, function (event) {
        event.preventDefault();
        uploadControl.classList.add("upload-control--dragging");
      });
    });

    uploadControl.addEventListener("dragleave", function (event) {
      if (!(event.relatedTarget instanceof Node) || !uploadControl.contains(event.relatedTarget)) {
        uploadControl.classList.remove("upload-control--dragging");
      }
    });

    uploadControl.addEventListener("dragend", function () {
      uploadControl.classList.remove("upload-control--dragging");
    });

    uploadControl.addEventListener("drop", function (event) {
      event.preventDefault();
      uploadControl.classList.remove("upload-control--dragging");

      const file = event.dataTransfer?.files[0];
      if (!file) {
        return;
      }

      const transfer = new DataTransfer();
      transfer.items.add(file);
      proofInput.files = transfer.files;
      updateProofFile();
    });
  }
});
