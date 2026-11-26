(function () {
  var pagination = document.getElementById("paginationPesanan");
  var orderCards = Array.from(document.querySelectorAll("[data-pesanan-card]"));
  var pageSize = 5;
  var currentPage = 1;

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
