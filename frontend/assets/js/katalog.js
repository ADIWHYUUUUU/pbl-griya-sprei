/* =========================================
   KATALOG.JS - filter, pencarian, pagination
   (data `products` diambil dari data.js)
   ========================================= */
const ITEMS_PER_PAGE = 8;
const state = { search: "", category: "all", size: "all", page: 1 };

const grid        = document.getElementById("productGrid");
const pagination  = document.getElementById("pagination");
const searchInput = document.getElementById("searchInput");
const sizeFilter  = document.getElementById("sizeFilter");
const chips       = document.querySelectorAll(".chip");

/* 1. FILTER */
function getFilteredProducts() {
  const keyword = state.search.toLowerCase();
  return products.filter((p) =>
    (p.name + " " + p.desc).toLowerCase().includes(keyword) &&
    (state.category === "all" || p.category === state.category) &&
    (state.size === "all" || p.size === state.size)
  );
}

/* 2. RENDER */
function createCard(p) {
  const type = p.category === "sprei" ? "Sprei Katun" : "Bedcover Set";
  return `
    <article class="card">
      <div class="card__image media" style="--ph:${p.color}">${productImage(p)}</div>
      <div class="card__body">
        <div class="card__meta">
          <span class="card__type">${type}</span>
          <span class="card__size">${p.size}</span>
        </div>
        <h3 class="card__title">${p.name}</h3>
        <p class="card__desc">${p.desc}</p>
        <div class="card__footer">
          <div>
            <div class="card__label">Harga mulai</div>
            <div class="card__price">${formatRupiah(p.price)}</div>
          </div>
          <a class="btn" href="detailProduk.html?id=${products.indexOf(p)}">Lihat Detail</a>
        </div>
      </div>
    </article>`;
}

function renderProducts() {
  const list = getFilteredProducts();
  const totalPages = Math.max(1, Math.ceil(list.length / ITEMS_PER_PAGE));
  state.page = Math.min(state.page, totalPages);

  const start = (state.page - 1) * ITEMS_PER_PAGE;
  const pageItems = list.slice(start, start + ITEMS_PER_PAGE);

  grid.innerHTML = pageItems.length
    ? pageItems.map(createCard).join("")
    : `<p class="empty">Produk tidak ditemukan. Coba kata kunci atau filter lain.</p>`;
  renderPagination(totalPages);
}

function renderPagination(totalPages) {
  let html = `<button class="page-btn" data-page="${state.page - 1}" ${state.page === 1 ? "disabled" : ""} aria-label="Sebelumnya">‹</button>`;
  for (let i = 1; i <= totalPages; i++) {
    html += `<button class="page-btn ${i === state.page ? "page-btn--active" : ""}" data-page="${i}">${i}</button>`;
  }
  html += `<button class="page-btn" data-page="${state.page + 1}" ${state.page === totalPages ? "disabled" : ""} aria-label="Berikutnya">›</button>`;
  pagination.innerHTML = html;
}

/* 3. EVENT */
searchInput.addEventListener("input", (e) => { state.search = e.target.value; state.page = 1; renderProducts(); });

chips.forEach((chip) => chip.addEventListener("click", () => {
  chips.forEach((c) => c.classList.remove("chip--active"));
  chip.classList.add("chip--active");
  state.category = chip.dataset.category;
  state.page = 1;
  renderProducts();
}));

sizeFilter.addEventListener("change", (e) => { state.size = e.target.value; state.page = 1; renderProducts(); });

pagination.addEventListener("click", (e) => {
  const btn = e.target.closest(".page-btn");
  if (!btn || btn.disabled) return;
  state.page = Number(btn.dataset.page);
  renderProducts();
  window.scrollTo({ top: 0, behavior: "smooth" });
});


renderProducts();