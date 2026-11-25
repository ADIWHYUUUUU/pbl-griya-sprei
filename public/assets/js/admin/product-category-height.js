document.addEventListener("DOMContentLoaded", function () {
  const categoryInputs = document.querySelectorAll("input[name='kategori_produk'][type='radio']");
  const heightField = document.getElementById("tinggi_standar_field");
  const heightInput = document.getElementById("tinggi_standar");
  const sizeGrid = document.querySelector(".product-size-grid");

  if (!categoryInputs.length || !heightField || !heightInput) return;

  function updateHeightField() {
    const categoryInput = document.querySelector("input[name='kategori_produk']:checked");
    const hideHeight = categoryInput?.value !== "SPREI";
    heightField.hidden = hideHeight;
    heightInput.required = !hideHeight;
    heightInput.disabled = hideHeight;

    if (sizeGrid) {
      sizeGrid.classList.toggle("without-height", hideHeight);
    }
  }

  categoryInputs.forEach((input) => input.addEventListener("change", updateHeightField));
  updateHeightField();
});
