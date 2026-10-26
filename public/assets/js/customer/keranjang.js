// =====================================================
// SCRIPT HALAMAN KERANJANG - GRIYA SPREI
// =====================================================

document.querySelectorAll("[data-edit-cart-open]").forEach(function (trigger) {
  var dialog = trigger.nextElementSibling;
  if (!(dialog instanceof HTMLDialogElement)) return;

  trigger.addEventListener("click", function () {
    dialog.showModal();
  });

  dialog.querySelectorAll("[data-edit-cart-close]").forEach(function (button) {
    button.addEventListener("click", function () {
      dialog.close();
    });
  });

  dialog.addEventListener("click", function (event) {
    if (event.target === dialog) dialog.close();
  });

  dialog.querySelectorAll("[data-quantity-step]").forEach(function (button) {
    button.addEventListener("click", function () {
      var input = button.parentElement.querySelector("input");
      var minimum = Number(input.min);
      var maximum = Number(input.max);
      var nextValue = Number(input.value) + Number(button.dataset.quantityStep);
      input.value = String(Math.max(minimum, Math.min(maximum, nextValue)));
    });
  });
});

// Konfirmasi sebelum menghapus produk
var semuaFormHapus = document.querySelectorAll(".form-hapus");

semuaFormHapus.forEach(function (form) {
  form.addEventListener("submit", function (event) {
    var yakin = confirm("Yakin ingin menghapus produk ini dari keranjang?");

    if (!yakin) {
      event.preventDefault(); // batal kirim form
    }
  });
});

// =====================================================
// BAGIAN KERANJANG KOSONG (dulu keranjangKosong.js)
// Hanya jalan kalau kotak keranjang kosong ada di halaman.
// =====================================================

// ---------- A. Animasi muncul ----------
// Setelah halaman selesai dimuat,
// tambahkan class "muncul" supaya kotak tampil pelan-pelan.
window.addEventListener("load", function () {
  var kotak = document.getElementById("kotakKosong");
  if (kotak) {
    kotak.classList.add("muncul");
  }
});

// ---------- B. Ikon keranjang bergoyang kecil saat diarahkan mouse ----------
var ikon = document.querySelector(".ikon-kosong");

if (ikon) {
  ikon.style.transition = "transform 0.3s ease";

  ikon.addEventListener("mouseenter", function () {
    ikon.style.transform = "rotate(-8deg) scale(1.05)";
  });

  ikon.addEventListener("mouseleave", function () {
    ikon.style.transform = "";
  });
}

// ---------- C. Tombol "Jelajahi Katalog" memberi tanda sedang memuat ----------
var tombolKatalog = document.querySelector(".kosong-besar .tombol");
var teksAsli = tombolKatalog ? tombolKatalog.innerHTML : "";

if (tombolKatalog) {
  tombolKatalog.addEventListener("click", function () {
    tombolKatalog.textContent = "Membuka katalog...";
    tombolKatalog.style.opacity = "0.7";
    tombolKatalog.style.pointerEvents = "none"; // cegah klik dua kali
  });
}

// ---------- D. Kembali lewat tombol Back browser -> tombol normal lagi ----------
window.addEventListener("pageshow", function () {
  if (tombolKatalog) {
    tombolKatalog.innerHTML = teksAsli;
    tombolKatalog.style.opacity = "";
    tombolKatalog.style.pointerEvents = "";
  }
});
