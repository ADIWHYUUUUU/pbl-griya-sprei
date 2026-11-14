document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("paymentVerificationForm");
  const rejectButton = document.getElementById("rejectPaymentButton");
  const cancelRejectButton = document.getElementById("cancelRejectButton");
  const confirmRejectButton = document.getElementById("confirmRejectButton");
  const closeRejectButton = document.getElementById("closeRejectButton");
  const dialog = document.getElementById("rejectDialog");
  const dialogPanel = dialog?.querySelector(".reject-dialog");
  const decision = document.getElementById("paymentDecision");
  const note = document.getElementById("adminNote");
  const noteError = document.getElementById("adminNoteError");
  const noteCounter = document.getElementById("adminNoteCounter");
  const reasonChips = dialog?.querySelectorAll(".reject-reason-chip");

  if (dialog && dialog.parentElement !== document.body) {
    document.body.appendChild(dialog);
  }

  if (form && decision) {
    form.addEventListener("submit", function (event) {
      if (decision.value !== "reject") {
        decision.value = "verify";
      }
    });
  }

  if (!form || !rejectButton || !dialog || !dialogPanel || !decision || !note) return;

  const updateNoteState = function () {
    const hasReason = note.value.trim().length > 0;
    if (confirmRejectButton) confirmRejectButton.disabled = !hasReason;
    if (noteError) noteError.hidden = hasReason;
    if (noteCounter) noteCounter.textContent = `${note.value.length}/300`;
    note.setAttribute("aria-invalid", String(!hasReason));
  };

  const closeDialog = function () {
    dialog.hidden = true;
    note.value = "";
    decision.value = "";
    reasonChips?.forEach(function (chip) {
      chip.classList.remove("is-selected");
      chip.setAttribute("aria-pressed", "false");
    });
    updateNoteState();
    rejectButton.focus();
  };

  rejectButton.addEventListener("click", function () {
    decision.value = "";
    dialog.hidden = false;
    updateNoteState();
    note.focus();
  });

  note.addEventListener("input", updateNoteState);

  reasonChips?.forEach(function (chip) {
    chip.setAttribute("aria-pressed", "false");
    chip.addEventListener("click", function () {
      reasonChips.forEach(function (otherChip) {
        const selected = otherChip === chip;
        otherChip.classList.toggle("is-selected", selected);
        otherChip.setAttribute("aria-pressed", String(selected));
      });
      note.value = chip.dataset.reason || "";
      updateNoteState();
      note.focus();
    });
  });

  cancelRejectButton?.addEventListener("click", closeDialog);
  closeRejectButton?.addEventListener("click", closeDialog);

  dialog.addEventListener("click", function (event) {
    if (event.target === dialog) closeDialog();
  });

  dialog.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      event.preventDefault();
      closeDialog();
      return;
    }

    if (event.key !== "Tab") return;
    const focusableElements = dialogPanel.querySelectorAll(
      'button:not([disabled]), textarea, [href], [tabindex]:not([tabindex="-1"])',
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && document.activeElement === firstElement) {
      event.preventDefault();
      lastElement.focus();
    } else if (!event.shiftKey && document.activeElement === lastElement) {
      event.preventDefault();
      firstElement.focus();
    }
  });

  confirmRejectButton?.addEventListener("click", function () {
    if (!note.value.trim()) {
      updateNoteState();
      note.focus();
      return;
    }
    decision.value = "reject";
    form.submit();
  });

  updateNoteState();
});
