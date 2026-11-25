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
  const changes = document.getElementById("productChanges");
  const changeCount = document.getElementById("productChangeCount");
  const bedCoverExamples = {
    nama_produk: "Bed Cover Katun Jepang Garis",
    motif: "Garis",
    panjang_standar: "240",
    lebar_standar: "220",
    harga_dasar: "350000",
    stok: "5",
  };
  const defaultPlaceholders = Object.keys(bedCoverExamples).map((id) => [id, field(id).placeholder]);
  let previewUrl = null;

  function field(id) {
    return form.elements.namedItem(id);
  }

  function showText(id, value) {
    document.getElementById(id).textContent = value;
  }

  function productDetails() {
    const category = form.querySelector('input[name="kategori_produk"]:checked');
    const fabric = field("jenis_kain");
    return [
      ["Kategori", category.closest("label").querySelector("strong").textContent],
      ["Nama produk", field("nama_produk").value.trim()],
      ["Jenis kain", fabric.value ? fabric.selectedOptions[0].textContent : ""],
      ["Motif", field("motif").value.trim()],
      ["Panjang", field("panjang_standar").value],
      ["Lebar", field("lebar_standar").value],
      ["Tinggi karet", field("tinggi_standar").disabled ? "" : field("tinggi_standar").value],
      ["Harga dasar", field("harga_dasar").value ? "Rp" + formatNumber.format(field("harga_dasar").value) : ""],
      ["Stok", field("stok").value ? field("stok").value + " set" : ""],
      ["Foto", photoInput.files[0]?.name ?? defaultPhotoNameForSummary],
      ["Deskripsi", field("deskripsi").value.trim()],
    ];
  }

  const defaultPhotoNameForSummary = photoPreview.querySelector("img") ? "Foto saat ini" : "";
  const originalDetails = changes ? productDetails() : [];

  function updateChanges() {
    if (!changes) return;
    const entries = productDetails().filter(([, value], index) => value !== originalDetails[index][1]);
    changeCount.hidden = entries.length === 0;
    changeCount.textContent = entries.length + " perubahan belum disimpan";
    changes.replaceChildren();
    if (!entries.length) {
      changes.textContent = "Belum ada perubahan.";
      return;
    }

    const previousValues = new Map(originalDetails);
    entries.forEach(([label, value]) => {
      const row = document.createElement("div");
      const title = document.createElement("div");
      const previous = document.createElement("s");
      const current = document.createElement("strong");
      title.textContent = label;
      previous.textContent = previousValues.get(label) || "Kosong";
      current.textContent = value || "Kosong";
      row.append(title, previous, " → ", current);
      changes.append(row);
    });
  }

  function updateCatalog() {
    const category = form.querySelector('input[name="kategori_produk"]:checked');
    const fabric = field("jenis_kain");
    const dimensions = ["panjang_standar", "lebar_standar", "tinggi_standar"]
      .map(field)
      .filter((input) => !input.disabled && input.value && input.validity.valid)
      .map((input) => input.value);
    const price = field("harga_dasar");
    const stock = field("stok");
    const displayPrice = price.value && price.validity.valid
      ? "Rp" + formatNumber.format(price.value)
      : "";

    showText("productSizeTitle", {
      SPREI: "Ukuran Kasur",
      BED_COVER: "Ukuran Bed Cover",
      SARUNG_BANTAL_GULING: "Ukuran Sarung Bantal & Guling",
    }[category.value]);
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
    if (photoInput.required) {
      const photoReady = photoInput.files.length > 0 && photoInput.validity.valid;
      showText("productPhotoCheckLabel", photoReady ? "Foto produk" : "Foto produk belum dipilih");
    }
    updateChanges();
  }

  function updateExamples() {
    const selectedCategory = form.querySelector('input[name="kategori_produk"]:checked').value;
    defaultPlaceholders.forEach(([id, placeholder]) => {
      field(id).placeholder = selectedCategory === "BED_COVER" ? bedCoverExamples[id] : placeholder;
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
    if (!file) return photoInput.required ? "Pilih foto produk terlebih dahulu." : "";

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
  form.querySelectorAll('input[name="kategori_produk"]').forEach((input) => {
    input.addEventListener("change", updateExamples);
  });

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
  updateExamples();
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
