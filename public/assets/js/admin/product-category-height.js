document.addEventListener("DOMContentLoaded", function () {
  const categoryInput = document.getElementById("kategori_produk");
  const heightField = document.getElementById("tinggi_standar_field");
  const heightInput = document.getElementById("tinggi_standar");
  const sizeGrid = document.querySelector(".product-size-grid, .edit-grid-3");

  if (!categoryInput || !heightField || !heightInput) return;

  function updateHeightField() {
    const hideHeight = categoryInput.value === "SARUNG_BANTAL_GULING";
    heightField.hidden = hideHeight;
    heightInput.required = !hideHeight;

    if (hideHeight) {
      heightInput.value = "";
    }

    if (sizeGrid) {
      sizeGrid.classList.toggle("without-height", hideHeight);
    }
  }

  categoryInput.addEventListener("change", updateHeightField);
  updateHeightField();
});
