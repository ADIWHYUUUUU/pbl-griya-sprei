/* =========================================
   1. DATA PRODUK
   ========================================= */
const products = window.catalogProducts ?? [];

/* =========================================
   2. STATE & ELEMEN DOM
   ========================================= */
const ITEMS_PER_PAGE = 8;
const state = { search: "", category: "all", size: "all", page: 1 };

const grid = document.getElementById("productGrid");
const pagination = document.getElementById("pagination");
const searchInput = document.getElementById("searchInput");
const sizeFilter = document.getElementById("sizeFilter");
const chips = document.querySelectorAll(".chip");

const escapeHTML = (value) =>
  String(value ?? "").replace(
    /[&<>"']/g,
    (character) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      })[character],
  );

/* =========================================
   3. FUNGSI BANTU
   ========================================= */
const formatRupiah = (n) => "Rp " + n.toLocaleString("id-ID");

function getFilteredProducts() {
  const keyword = state.search.toLowerCase();
  return products.filter((p) => {
    const matchText = (p.name + " " + p.desc + " " + p.type)
      .toLowerCase()
      .includes(keyword);
    const matchCategory =
      state.category === "all" || p.category === state.category;
    const matchSize = state.size === "all" || p.size === state.size;
    return matchText && matchCategory && matchSize;
  });
}

/* =========================================
   4. RENDER
   ========================================= */
function createCard(p) {
  const image = p.image
    ? `<img class="card__photo" src="${escapeHTML(p.image)}" alt="${escapeHTML(p.name)}">`
    : "";

  return `
    <article class="card">
      <div class="card__image" style="--img-color: ${escapeHTML(p.color)}">
        ${image}
        <span class="card__badge">${escapeHTML(p.badge)}</span>
      </div>
      <div class="card__body">
        <div class="card__meta">
          <span class="card__type">${escapeHTML(p.type)}</span>
          <span class="card__size">${escapeHTML(p.size)}</span>
        </div>
        <h3 class="card__title">${escapeHTML(p.name)}</h3>
        <p class="card__desc">${escapeHTML(p.desc)}</p>
        <div class="card__footer">
          <div>
            <div class="card__label">Harga mulai</div>
            <div class="card__price">${formatRupiah(p.price)}</div>
          </div>
          <button class="btn" data-name="${escapeHTML(p.name)}">Lihat Detail</button>
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

/* =========================================
   5. EVENT LISTENER
   ========================================= */
searchInput.addEventListener("input", (e) => {
  state.search = e.target.value;
  state.page = 1;
  renderProducts();
});

chips.forEach((chip) => {
  chip.addEventListener("click", () => {
    chips.forEach((c) => c.classList.remove("chip--active"));
    chip.classList.add("chip--active");
    state.category = chip.dataset.category;
    state.page = 1;
    renderProducts();
  });
});

sizeFilter.addEventListener("change", (e) => {
  state.size = e.target.value;
  state.page = 1;
  renderProducts();
});

pagination.addEventListener("click", (e) => {
  const btn = e.target.closest(".page-btn");
  if (!btn || btn.disabled) return;
  state.page = Number(btn.dataset.page);
  renderProducts();
  window.scrollTo({ top: 0, behavior: "smooth" });
});

grid.addEventListener("click", (e) => {
  const btn = e.target.closest(".btn");
  if (btn) alert("Detail produk: " + btn.dataset.name); // ganti dengan link halaman detail
});

/* =========================================
   6. JALANKAN
   ========================================= */
renderProducts();
