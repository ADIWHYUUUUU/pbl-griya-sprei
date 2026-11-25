document.addEventListener("DOMContentLoaded", function () {
  const categoryInputs = document.querySelectorAll("#kategori_produk, input[name='kategori_produk'][type='radio']");
  const heightField = document.getElementById("tinggi_standar_field");
  const heightInput = document.getElementById("tinggi_standar");
  const sizeGrid = document.querySelector(".product-size-grid, .edit-grid-3");

  if (!categoryInputs.length || !heightField || !heightInput) return;

  function updateHeightField() {
    const categoryInput = document.querySelector("#kategori_produk, input[name='kategori_produk']:checked");
    const hideHeight = categoryInput?.value === "SARUNG_BANTAL_GULING";
    heightField.hidden = hideHeight;
    heightInput.required = !hideHeight;

    if (hideHeight) {
      heightInput.value = "";
    }

    if (sizeGrid) {
      sizeGrid.classList.toggle("without-height", hideHeight);
    }
  }

  categoryInputs.forEach((input) => input.addEventListener("change", updateHeightField));
  updateHeightField();
});
