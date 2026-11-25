(function () {
  var dataElement = document.getElementById("dataPesanan");
  var modal = document.getElementById("modalDetail");
  if (!dataElement || !modal) return;

  var orders = JSON.parse(dataElement.textContent || "{}");
  var pagination = document.getElementById("paginationPesanan");
  var orderCards = Array.from(document.querySelectorAll("[data-pesanan-card]"));
  var pageSize = 5;
  var currentPage = 1;

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

  if (!pagination) return;

  function renderPagination() {
    var pageCount = Math.max(1, Math.ceil(orderCards.length / pageSize));
    orderCards.forEach(function (card, index) {
      card.hidden = index < (currentPage - 1) * pageSize || index >= currentPage * pageSize;
    });

    var previousDisabled = currentPage === 1 ? " disabled" : "";
    var nextDisabled = currentPage === pageCount ? " disabled" : "";
    var pageButtons = Array.from({ length: pageCount }, function (_, index) {
      var page = index + 1;
      var activeClass = page === currentPage ? " page-btn--active" : "";
      var current = page === currentPage ? ' aria-current="page"' : "";
      return '<button type="button" class="page-btn' + activeClass + '" data-page="' + page + '"' + current + '>' + page + "</button>";
    }).join("");

    pagination.innerHTML =
      '<button type="button" class="page-btn" data-page="' + (currentPage - 1) + '" aria-label="Halaman sebelumnya"' + previousDisabled + '>' +
      '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>' +
      "</button>" + pageButtons +
      '<button type="button" class="page-btn" data-page="' + (currentPage + 1) + '" aria-label="Halaman berikutnya"' + nextDisabled + '>' +
      '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>' +
      "</button>";
  }

  pagination.addEventListener("click", function (event) {
    var button = event.target.closest("[data-page]");
    if (!button || button.disabled) return;
    currentPage = Number(button.dataset.page) || 1;
    renderPagination();
  });

  renderPagination();
})();
