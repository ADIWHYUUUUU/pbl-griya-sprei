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