/* =========================================
   BERANDA.JS - menampilkan produk pilihan
   (data diambil dari data.js)
   ========================================= */
const featuredGrid = document.getElementById("featuredGrid");

// Pilih 4 produk yang ingin ditampilkan (berdasarkan urutan di data.js)
const featuredList = [
  { index: 0, tag: "Sprei Premium",  short: "Motif emboss salur mewah, sejuk & bebas bulu halus." },
  { index: 8, tag: "Bed Cover Set",  short: "Set bed cover selimut tebal hangat dengan sprei senada." },
  { index: 1, tag: "Sprei Katun",    short: "Serat katun alami 100% adem lembut untuk kulit sensitif." },
  { index: 9, tag: "Bed Cover & Sprei", short: "Warna tenang minimalis, jahitan rapi double stitch." },
];

featuredGrid.innerHTML = featuredList.map(({ index, tag, short }) => {
  const p = products[index];
  const tagClass = p.category === "sprei" ? "featured__tag featured__tag--sprei" : "featured__tag";
  return `
    <article class="featured__card">
      <div class="featured__image media" style="--ph:${p.color}">${productImage(p)}</div>
      <span class="${tagClass}">${tag}</span>
      <h3 class="featured__title">${p.name}</h3>
      <p class="featured__desc">${short}</p>
      <p class="featured__price">Mulai <strong>${formatRupiah(p.price)}</strong></p>
      <a href="detailProduk.html?id=${index}" class="btn btn--block">Lihat Detail</a>
    </article>`;
}).join("");

fetch("../produk/beranda_data.php")
  .then((response) => response.ok ? response.json() : Promise.reject(new Error("Gagal memuat konten beranda")))
  .then((content) => {
    const label = document.querySelector(".hero__pill");
    const title = document.querySelector(".hero__title");
    const description = document.querySelector(".hero__desc");
    if (label) label.textContent = content.hero_label;
    if (title) title.textContent = content.hero_judul;
    if (description) description.textContent = content.hero_deskripsi;
    if (content.hero_foto_url) {
      const heroImage = document.querySelector(".hero__image");
      if (heroImage) heroImage.style.backgroundImage = `url("${content.hero_foto_url}")`;
    }
    document.querySelectorAll(".explore__card").forEach((card, index) => {
      const category = content.kategori?.[index];
      if (!category) return;
      const name = card.querySelector("h3");
      const text = card.querySelector("p");
      const image = card.querySelector(".explore__image");
      if (name) name.textContent = category.nama;
      if (text) text.textContent = category.deskripsi;
      if (image && category.foto_url) image.style.backgroundImage = `url("${category.foto_url}")`;
    });
  })
  .catch(() => {
    // The static defaults remain visible when the optional admin data endpoint is unavailable.
  });