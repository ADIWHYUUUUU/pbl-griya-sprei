(function () {
  var dataElement = document.getElementById("dataPesanan");
  var modal = document.getElementById("modalDetail");
  if (!dataElement || !modal) return;

  var orders = JSON.parse(dataElement.textContent || "{}");

  function closeModal() {
    modal.classList.remove("tampil");
    document.body.classList.remove("ada-modal");
  }

  function openModal(order) {
    modal.querySelectorAll("[data-isi]").forEach(function (element) {
      var value = order[element.dataset.isi];
      element.textContent = value === "" || value === null || value === undefined
        ? "-"
        : value;
    });
    document.getElementById("detailCatatanAdmin").hidden = !order.catatan_admin;
    modal.classList.add("tampil");
    document.body.classList.add("ada-modal");
  }

  document.querySelectorAll("[data-buka-detail]").forEach(function (button) {
    button.addEventListener("click", function () {
      var order = orders[button.dataset.bukaDetail];
      if (order) openModal(order);
    });
  });

  modal.querySelectorAll("[data-tutup-modal]").forEach(function (button) {
    button.addEventListener("click", closeModal);
  });

  modal.addEventListener("click", function (event) {
    if (event.target === modal) closeModal();
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && modal.classList.contains("tampil")) {
      closeModal();
    }
  });
})();
