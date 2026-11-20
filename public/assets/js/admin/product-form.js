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
  let previewUrl = null;
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
  }

  function showPhotoError(message) {
    clearPreviewUrl();
    photoName.textContent = "Foto belum valid";
    photoName.classList.add("product-photo-message");
    photoPreview.innerHTML = defaultPhotoPreview;
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
  }

  photoButton.addEventListener("click", function () {
    photoInput.click();
  });
  photoInput.addEventListener("change", updatePhoto);
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
