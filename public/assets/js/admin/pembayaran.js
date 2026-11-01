document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("paymentVerificationForm");
  const rejectButton = document.getElementById("rejectPaymentButton");
  const cancelRejectButton = document.getElementById("cancelRejectButton");
  const confirmRejectButton = document.getElementById("confirmRejectButton");
  const dialog = document.getElementById("rejectDialog");
  const decision = document.getElementById("paymentDecision");
  const note = document.getElementById("adminNote");

  if (form && decision) {
    form.addEventListener("submit", function (event) {
      if (decision.value !== "reject") {
        decision.value = "verify";
      }
    });
  }

  if (!rejectButton || !dialog || !decision || !note) return;

  rejectButton.addEventListener("click", function () {
    dialog.hidden = false;
    note.focus();
  });

  if (cancelRejectButton) {
    cancelRejectButton.addEventListener("click", function () {
      dialog.hidden = true;
      note.value = "";
    });
  }

  if (confirmRejectButton) {
    confirmRejectButton.addEventListener("click", function () {
      if (!note.value.trim()) {
        note.reportValidity();
        return;
      }
      decision.value = "reject";
      form.submit();
    });
  }
});
