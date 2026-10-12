/* =========================================
   DATA PRODUK - dipakai beranda & katalog
   Untuk foto asli: taruh file di assets/images/ lalu isi
   properti image, contoh:  image: "asterina-sage.png"
   ========================================= */
const products = [
  { name: "Sprei Katun Asterina Sage",         category: "sprei",    size: "160 × 200 × 30", price: 185000, color: "#9bb87a", desc: "Halus dan lembut seperti sutera, lebar kain 280cm & tebal 130gsm berserat alami." },
  { name: "Sprei Katun Asterina Sand",         category: "sprei",    size: "160 × 200 × 30", price: 185000, color: "#c4a67c", desc: "Tekstur sutera MicroSilk adem dengan nuansa warna natural sand hangat." },
  { name: "Sprei Katun Asterina Burgundy",     category: "sprei",    size: "180 × 200 × 35", price: 195000, color: "#7a1f35", desc: "Warna burgundy elegan mewah dengan motif salur sutera halus anti luntur." },
  { name: "Sprei Katun Asterina Mocha",        category: "sprei",    size: "160 × 200 × 30", price: 185000, color: "#a98466", desc: "Warna mocha hangat yang nyaman, kain lebar ekstra 280cm jahit rapi presisi." },
  { name: "Sprei Katun Asterina Classic Blue", category: "sprei",    size: "160 × 200 × 30", price: 185000, color: "#4a74b5", desc: "Warna classic blue tenang dengan tekstur sutera lembut dan berdaya serap tinggi." },
  { name: "Sprei Beverly Alexander Dark Grey", category: "sprei",    size: "180 × 200 × 30", price: 175000, color: "#5d6f86", desc: "Material katun premium lebar kain 250cm, adem, tidak berbulu dan tidak luntur." },
  { name: "Sprei Beverly Hilton Light Grey",   category: "sprei",    size: "160 × 200 × 30", price: 180000, color: "#b9b4ae", desc: "Katun modal kerapatan tinggi dengan teknologi cool touch, eco-friendly dan lembut." },
  { name: "Sprei Beverly Roland Grey & Navy",  category: "sprei",    size: "200 × 200 × 35", price: 195000, color: "#7c7f86", desc: "Motif modern geometric katun modal dingin, lembut halus mewah terbukti awet." },
  { name: "Bedcover Set Verona Cream",         category: "bedcover", size: "180 × 200 × 30", price: 320000, color: "#d9ccb4", desc: "Bedcover set 5 in 1 bahan microfiber lembut, tebal dan hangat." },
  { name: "Bedcover Set Milano Navy",          category: "bedcover", size: "200 × 200 × 35", price: 340000, color: "#2f4469", desc: "Bedcover set warna navy dengan jahitan quilting rapi dan awet." },
];

const formatRupiah = (n) => "Rp " + n.toLocaleString("id-ID");

/* Gambar produk: foto jika ada, kalau tidak ada tampil blok warna */
const productImage = (p) =>
  p.image ? `<img src="../assets/images/${p.image}" alt="${p.name}">` : "";