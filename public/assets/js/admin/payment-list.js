document.addEventListener("DOMContentLoaded", function () {
  const tableBody = document.getElementById("paymentTableBody");
  const search = document.getElementById("paymentSearch");
  const methodFilter = document.getElementById("paymentMethodFilter");
  const dateFilter = document.getElementById("paymentDateFilter");
  const sortSelect = document.getElementById("paymentSort");
  const resultCount = document.getElementById("paymentResultCount");
  const emptyRow = document.getElementById("paymentFilterEmpty");

  if (!tableBody || !search || !methodFilter || !dateFilter || !sortSelect || !resultCount || !emptyRow) {
    return;
  }

  const rows = Array.from(tableBody.querySelectorAll("[data-payment-row]"));
  const tabs = Array.from(document.querySelectorAll("[data-status-filter]"));
  let activeStatus = "";

  const applyFilters = function () {
    const query = search.value.trim().toLocaleLowerCase("id");
    const method = methodFilter.value;
    const selectedDate = dateFilter.value;
    const now = new Date();
    const visibleRows = rows.filter(function (row) {
      const timestamp = Number(row.dataset.date) * 1000;
      const rowDate = new Date(timestamp);
      const matchesStatus = activeStatus === "" || row.dataset.status === activeStatus;
      const matchesQuery = (row.dataset.search || "").toLocaleLowerCase("id").includes(query);
      const matchesMethod = method === "" || row.dataset.method === method;
      let matchesDate = true;

      if (selectedDate === "today") {
        matchesDate = rowDate.toDateString() === now.toDateString();
      } else if (selectedDate !== "") {
        const dayCount = Number(selectedDate);
        matchesDate = timestamp >= now.getTime() - dayCount * 24 * 60 * 60 * 1000;
      }

      return matchesStatus && matchesQuery && matchesMethod && matchesDate;
    });

    const sortMode = sortSelect.value;
    visibleRows.sort(function (first, second) {
      const firstDate = Number(first.dataset.date);
      const secondDate = Number(second.dataset.date);
      const firstAmount = Number(first.dataset.amount);
      const secondAmount = Number(second.dataset.amount);

      if (sortMode === "newest") return secondDate - firstDate;
      if (sortMode === "oldest") return firstDate - secondDate;
      if (sortMode === "amount-high") return secondAmount - firstAmount;
      if (sortMode === "amount-low") return firstAmount - secondAmount;
      if (first.dataset.status === "MENUNGGU_VERIFIKASI" && second.dataset.status !== "MENUNGGU_VERIFIKASI") return -1;
      if (second.dataset.status === "MENUNGGU_VERIFIKASI" && first.dataset.status !== "MENUNGGU_VERIFIKASI") return 1;
      return secondDate - firstDate;
    });

    rows.forEach(function (row) {
      row.hidden = true;
    });
    visibleRows.forEach(function (row) {
      row.hidden = false;
      tableBody.appendChild(row);
    });
    emptyRow.hidden = visibleRows.length !== 0 || rows.length === 0;
    resultCount.textContent = "Menampilkan " + visibleRows.length + " dari " + rows.length + " pembayaran di halaman ini.";
  };

  tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      activeStatus = tab.dataset.statusFilter || "";
      tabs.forEach(function (item) {
        const isActive = item === tab;
        item.classList.toggle("is-active", isActive);
        item.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
      applyFilters();
    });
  });

  [search, methodFilter, dateFilter, sortSelect].forEach(function (control) {
    control.addEventListener(control === search ? "input" : "change", applyFilters);
  });

  applyFilters();
});
