document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".product-form-card");
  const photoInput = document.getElementById("gambar_produk");
  const photoButton = document.getElementById("productPhotoButton");
  const photoName = document.getElementById("productPhotoName");
  const photoPreview = document.getElementById("productPhotoPreview");
  const photoError = document.getElementById("productPhotoError");

  if (!form || !photoInput || !photoButton || !photoName || !photoPreview || !photoError) {
    return;
  }

  const maxPhotoSize = 2 * 1024 * 1024;
  const defaultPhotoName = photoName.textContent;
  const defaultPhotoPreview = photoPreview.innerHTML;
  const catalogPhoto = document.getElementById("catalogPhoto");
  const defaultCatalogPhoto = catalogPhoto.innerHTML;
  const picker = photoInput.closest(".product-photo-picker");
  const formatNumber = new Intl.NumberFormat("id-ID");
  let previewUrl = null;

  function field(id) {
    return form.elements.namedItem(id);
  }

  function showText(id, value) {
    document.getElementById(id).textContent = value;
  }

  function updateCatalog() {
    const category = form.querySelector('input[name="kategori_produk"]:checked');
    const fabric = field("jenis_kain");
    const dimensions = ["panjang_standar", "lebar_standar", "tinggi_standar"]
      .map(field)
      .filter((input) => input.required && input.value && input.validity.valid)
      .map((input) => input.value);
    const price = field("harga_dasar");
    const stock = field("stok");
    const displayPrice = price.value && price.validity.valid
      ? "Rp" + formatNumber.format(price.value)
      : "";

    showText("catalogCategory", [
      category.closest("label").querySelector("strong").textContent,
      fabric.value ? fabric.selectedOptions[0].textContent : "",
    ].filter(Boolean).join(" · "));
    showText("catalogName", field("nama_produk").value.trim() || "Nama produk");
    showText("catalogSize", dimensions.length ? dimensions.join(" × ") + " cm" : "Ukuran belum diisi");
    showText("catalogPrice", displayPrice || "Harga belum diisi");
    showText("productPriceHelp", displayPrice ? "Tampil di katalog: " + displayPrice : "Harga yang tampil di katalog.");
    showText("catalogStock", stock.value && stock.validity.valid ? "Stok: " + stock.value + " set" : "Stok belum diisi");

    form.querySelectorAll("[data-check]").forEach((item) => {
      const complete = item.dataset.check.split(",").every((id) => {
        const input = field(id);
        return !input.required || (input.value.trim() && input.validity.valid);
      });
      item.classList.toggle("is-complete", complete);
    });
  }
  let submitting = false;

  function clearPreviewUrl() {
    if (previewUrl) {
      URL.revokeObjectURL(previewUrl);
      previewUrl = null;
    }
  }

  function resetPhotoPreview() {
    clearPreviewUrl();
    photoName.textContent = defaultPhotoName;
    photoName.classList.remove("product-photo-message");
    photoPreview.innerHTML = defaultPhotoPreview;
    catalogPhoto.innerHTML = defaultCatalogPhoto;
  }

  function showPhotoError(message) {
    clearPreviewUrl();
    photoName.textContent = "Foto belum valid";
    photoName.classList.add("product-photo-message");
    photoPreview.innerHTML = defaultPhotoPreview;
    catalogPhoto.innerHTML = defaultCatalogPhoto;
    photoError.textContent = message;
    photoError.hidden = false;
    photoInput.setCustomValidity(message);
  }

  function validatePhoto(file) {
    if (!file) return "Pilih foto produk terlebih dahulu.";

    const extension = file.name.split(".").pop().toLowerCase();
    const supportedExtension = ["jpg", "jpeg", "png"].includes(extension);
    const supportedMime = !file.type || ["image/jpeg", "image/png"].includes(file.type);

    if (!supportedExtension || !supportedMime) {
      return "Format foto tidak didukung. Pilih file JPG atau PNG.";
    }
    if (file.size === 0) {
      return "File foto kosong atau tidak valid.";
    }
    if (file.size > maxPhotoSize) {
      return "Ukuran foto maksimal 2 MB.";
    }

    return "";
  }

  function updatePhoto() {
    const file = photoInput.files && photoInput.files[0];
    if (!file) {
      resetPhotoPreview();
      photoError.textContent = "";
      photoError.hidden = true;
      photoInput.setCustomValidity("");
      return;
    }

    const error = validatePhoto(file);
    if (error) {
      showPhotoError(error);
      return;
    }

    clearPreviewUrl();
    photoError.textContent = "";
    photoError.hidden = true;
    photoInput.setCustomValidity("");
    photoName.textContent = file.name;
    photoName.classList.remove("product-photo-message");
    photoPreview.replaceChildren();

    previewUrl = URL.createObjectURL(file);
    const image = document.createElement("img");
    image.src = previewUrl;
    image.alt = "Pratinjau foto produk";
    photoPreview.appendChild(image);
    catalogPhoto.replaceChildren(image.cloneNode());
  }

  photoButton.addEventListener("click", function () {
    photoInput.click();
  });
  photoInput.addEventListener("change", updatePhoto);
  form.addEventListener("input", updateCatalog);
  form.addEventListener("change", updateCatalog);

  picker.addEventListener("dragover", function (event) {
    event.preventDefault();
    picker.classList.add("is-dragging");
  });
  picker.addEventListener("dragleave", function (event) {
    if (!picker.contains(event.relatedTarget)) {
      picker.classList.remove("is-dragging");
    }
  });
  picker.addEventListener("drop", function (event) {
    event.preventDefault();
    picker.classList.remove("is-dragging");
    const file = event.dataTransfer.files[0];
    if (!file) return;

    const selection = new DataTransfer();
    selection.items.add(file);
    photoInput.files = selection.files;
    updatePhoto();
    updateCatalog();
  });

  updatePhoto();
  updateCatalog();
  photoInput.addEventListener("invalid", function () {
    if (!photoInput.files || photoInput.files.length === 0) {
      showPhotoError("Pilih foto produk terlebih dahulu.");
    }
  });

  form.addEventListener("submit", function (event) {
    if (submitting) {
      event.preventDefault();
      return;
    }

    const error = validatePhoto(photoInput.files && photoInput.files[0]);
    if (error) {
      event.preventDefault();
      showPhotoError(error);
      photoInput.reportValidity();
      photoButton.focus();
      return;
    }

    submitting = true;
  });

  window.addEventListener("pagehide", clearPreviewUrl, { once: true });
});
