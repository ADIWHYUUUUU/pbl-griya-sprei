document.addEventListener("DOMContentLoaded", function () {
  const catalog = window.homepageProducts || {};
  const modal = document.getElementById("uploadModal");
  const picker = document.getElementById("uploadPicker");
  const dropZone = document.getElementById("dropZone");
  const choosePhoto = document.getElementById("choosePhoto");
  const usePhoto = document.getElementById("usePhoto");
  const uploadPreview = document.getElementById("uploadPreview");
  const uploadFileName = document.getElementById("uploadFileName");
  let activeInput = null;
  let selectedFile = null;

  function renderProductPreview(slot) {
    const select = slot.querySelector("[data-product-select]");
    const preview = slot.querySelector("[data-product-preview]");
    const product = catalog[select.value];
    if (!product) {
      preview.innerHTML =
        '<span class="product-preview__empty">Pilih produk untuk melihat pratinjau.</span>';
      return;
    }
    const image = product.image
      ? '<img src="' + escapeHtml(product.image) + '" alt="">'
      : '<span class="product-preview__placeholder"></span>';
    preview.innerHTML =
      image +
      "<div><strong>" +
      escapeHtml(product.name) +
      "</strong><small>Rp " +
      Number(product.price || 0).toLocaleString("id-ID") +
      " • " +
      escapeHtml(product.category) +
      "</small></div>";
  }

  function escapeHtml(value) {
    return String(value || "").replace(/[&<>"']/g, function (character) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[character];
    });
  }

  document.querySelectorAll("[data-slot]").forEach(function (slot) {
    renderProductPreview(slot);
    slot
      .querySelector("[data-product-select]")
      .addEventListener("change", function () {
        renderProductPreview(slot);
      });
  });

  function openUpload(input) {
    activeInput = input;
    selectedFile = null;
    picker.value = "";
    uploadPreview.hidden = true;
    usePhoto.disabled = true;
    uploadFileName.textContent = "";
    modal.hidden = false;
  }

  function closeUpload() {
    modal.hidden = true;
    activeInput = null;
    selectedFile = null;
  }

  function selectFile(file) {
    const allowed = ["image/jpeg", "image/png", "image/webp"];
    if (!file || !allowed.includes(file.type) || file.size > 5 * 1024 * 1024) {
      window.alert(
        "Pilih gambar JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.",
      );
      return;
    }
    selectedFile = file;
    uploadFileName.textContent = file.name;
    uploadPreview.src = URL.createObjectURL(file);
    uploadPreview.hidden = false;
    usePhoto.disabled = false;
  }

  document.querySelectorAll("[data-open-upload]").forEach(function (button) {
    button.addEventListener("click", function () {
      openUpload(
        document.querySelector(
          'input[name="' + button.dataset.openUpload + '"]',
        ),
      );
    });
  });
  document.querySelectorAll("[data-close-upload]").forEach(function (element) {
    element.addEventListener("click", closeUpload);
  });
  choosePhoto.addEventListener("click", function () {
    picker.click();
  });
  picker.addEventListener("change", function () {
    selectFile(picker.files[0]);
  });
  ["dragenter", "dragover"].forEach(function (eventName) {
    dropZone.addEventListener(eventName, function (event) {
      event.preventDefault();
      dropZone.classList.add("is-dragging");
    });
  });
  ["dragleave", "drop"].forEach(function (eventName) {
    dropZone.addEventListener(eventName, function (event) {
      event.preventDefault();
      dropZone.classList.remove("is-dragging");
    });
  });
  dropZone.addEventListener("drop", function (event) {
    selectFile(event.dataTransfer.files[0]);
  });
  dropZone.addEventListener("click", function (event) {
    if (event.target !== choosePhoto) picker.click();
  });
  usePhoto.addEventListener("click", function () {
    if (!activeInput || !selectedFile) return;
    const transfer = new DataTransfer();
    transfer.items.add(selectedFile);
    activeInput.files = transfer.files;
    const image = activeInput
      .closest(".image-field, .category-image")
      .querySelector(".image-preview");
    if (image) {
      image.src = URL.createObjectURL(selectedFile);
      image.dataset.empty = "false";
    }
    closeUpload();
  });
});
