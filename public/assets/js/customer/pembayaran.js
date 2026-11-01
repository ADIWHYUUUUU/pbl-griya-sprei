document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".payment-form");
  if (!form) return;

  form.addEventListener("submit", function (event) {
    const file = form.querySelector('input[type="file"]').files[0];
    if (file && file.size > 2 * 1024 * 1024) {
      event.preventDefault();
      window.alert("Ukuran bukti transfer maksimal 2 MB.");
    }
  });
});
