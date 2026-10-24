const productGrid = document.getElementById("productGrid");
const pagination = document.getElementById("pagination");
const searchInput = document.getElementById("searchInput");
const sizeFilter = document.getElementById("sizeFilter");
const categoryButtons = [...document.querySelectorAll("[data-category]")];
const products = Array.isArray(window.catalogProducts) ? window.catalogProducts : [];
const pageSize = 8;
const currency = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});

const escapeHtml = (value) =>
  String(value ?? "").replace(/[&<>"']/g, (character) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  })[character]);

let activeCategory = ["all", "sprei", "bedcover"].includes(window.catalogCategory)
  ? window.catalogCategory
  : "all";
let currentPage = 1;

function setActiveCategory(category, updateHistory = false) {
  activeCategory = ["all", "sprei", "bedcover"].includes(category)
    ? category
    : "all";
  currentPage = 1;

  categoryButtons.forEach((button) => {
    const selected = button.dataset.category === activeCategory;
    button.classList.toggle("chip--active", selected);
    button.setAttribute("aria-pressed", String(selected));
  });

  if (updateHistory) {
    const url = new URL(window.location.href);
    if (activeCategory === "all") {
      url.searchParams.delete("category");
    } else {
      url.searchParams.set("category", activeCategory);
    }
    window.history.pushState({ category: activeCategory }, "", url);
  }

  renderProducts();
}

function getFilteredProducts() {
  const query = searchInput.value.trim().toLocaleLowerCase("id-ID");
  const selectedSize = sizeFilter.value;

  return products.filter((product) => {
    const searchableText = [product.name, product.type, product.desc, product.size]
      .join(" ")
      .toLocaleLowerCase("id-ID");
    return (
      (activeCategory === "all" || product.category === activeCategory) &&
      (selectedSize === "all" || product.size === selectedSize) &&
      searchableText.includes(query)
    );
  });
}

function renderProducts() {
  const filteredProducts = getFilteredProducts();
  const pageCount = Math.max(1, Math.ceil(filteredProducts.length / pageSize));
  currentPage = Math.min(currentPage, pageCount);
  const pageProducts = filteredProducts.slice(
    (currentPage - 1) * pageSize,
    currentPage * pageSize,
  );

  if (filteredProducts.length === 0) {
    productGrid.innerHTML = '<p class="empty">Produk tidak ditemukan.</p>';
  } else {
    productGrid.innerHTML = pageProducts
      .map((product) => {
        const imageUrl = product.image
          ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}" loading="lazy">`
          : "";
        return `
          <article class="card" id="product-${Number(product.id) || 0}">
            <div class="card__image" style="--img-color:${escapeHtml(product.color || "#cccccc")}">
              ${imageUrl}
            </div>
            <div class="card__body">
              <div class="card__meta">
                <span class="card__type">${escapeHtml(product.type)}</span>
                <span class="card__size">${escapeHtml(product.size || "Ukuran custom")}</span>
              </div>
              <h2 class="card__title">${escapeHtml(product.name)}</h2>
              <p class="card__desc">${escapeHtml(product.desc)}</p>
              <div class="card__footer">
                <div class="card__price-group">
                  <span class="card__label">Harga Mulai</span>
                  <span class="card__price">${currency.format(Number(product.price) || 0)}</span>
                </div>
                <details class="card__details">
                  <summary class="card__detail-button">Lihat Detail</summary>
                  <div class="card__detail-content">
                    <p>${escapeHtml(product.desc || "Belum ada deskripsi produk.")}</p>
                    <span>Stok tersedia: ${Number(product.stock) || 0}</span>
                  </div>
                </details>
              </div>
            </div>
          </article>`;
      })
      .join("");

    productGrid.querySelectorAll(".card__image img").forEach((image) => {
      const imageContainer = image.parentElement;
      image.addEventListener(
        "load",
        () => imageContainer.classList.add("card__image--loaded"),
        { once: true },
      );
      image.addEventListener("error", () => image.remove(), { once: true });

      if (image.complete) {
        if (image.naturalWidth > 0) {
          imageContainer.classList.add("card__image--loaded");
        } else {
          image.remove();
        }
      }
    });
  }

  pagination.innerHTML = Array.from({ length: pageCount }, (_, index) => {
    const page = index + 1;
    const activeClass = page === currentPage ? " page-btn--active" : "";
    const current = page === currentPage ? ' aria-current="page"' : "";
    return `<button type="button" class="page-btn${activeClass}" data-page="${page}"${current}>${page}</button>`;
  }).join("");
}

categoryButtons.forEach((button) => {
  button.addEventListener("click", () => {
    setActiveCategory(button.dataset.category, true);
  });
});

window.addEventListener("popstate", () => {
  const category = new URL(window.location.href).searchParams.get("category");
  setActiveCategory(category || "all");
});

searchInput.addEventListener("input", () => {
  currentPage = 1;
  renderProducts();
});

sizeFilter.addEventListener("change", () => {
  currentPage = 1;
  renderProducts();
});

pagination.addEventListener("click", (event) => {
  const button = event.target.closest("[data-page]");
  if (!button) return;
  currentPage = Number(button.dataset.page) || 1;
  renderProducts();
});

setActiveCategory(activeCategory);
