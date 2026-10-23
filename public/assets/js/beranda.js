/* =========================================
   BERANDA.JS - menampilkan produk pilihan
   (data diambil dari data.js)
   ========================================= */
const featuredGrid = document.getElementById("featuredGrid");

const featuredList = window.homepageContent?.featured || [];
const escapeHtml = (value) =>
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

featuredGrid.innerHTML = featuredList
  .map((product) => {
    const color = /^#[0-9a-f]{6}$/i.test(product.color || "")
      ? product.color
      : "#cccccc";
    const image = product.image
      ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}">`
      : "";
    return `
    <article class="featured__card">
      <div class="featured__image media" style="--ph:${color}">${image}</div>
      <span class="featured__tag">${escapeHtml(product.tag)}</span>
      <h3 class="featured__title">${escapeHtml(product.name)}</h3>
      <p class="featured__desc">${escapeHtml(product.description)}</p>
      <p class="featured__price">Mulai <strong>${formatRupiah(Number(product.price) || 0)}</strong></p>
      <a href="/frontend/pages/user/katalog.php" class="btn btn--block">Lihat Katalog</a>
    </article>`;
  })
  .join("");
