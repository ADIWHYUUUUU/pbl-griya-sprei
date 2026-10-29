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

  var editForm = dialog.querySelector(".edit-cart__form");
  var modeInput = editForm.querySelector('input[name="mode"]');
  var modeButtons = editForm.querySelectorAll("[data-edit-mode]");
  var dimensionInputs = editForm.querySelectorAll('.edit-cart__dimensions input[type="number"]');
  var dimensionHint = editForm.querySelector("[data-edit-dimension-hint]");
  var customDimensions = Array.from(dimensionInputs, function (input) {
    return input.value;
  });
  var currentMode = modeInput.value;

  function setEditMode(mode) {
    var isStandard = mode === "standard";

    if (currentMode === "custom" && isStandard) {
      customDimensions = Array.from(dimensionInputs, function (input) {
        return input.value;
      });
    } else if (currentMode === "standard" && !isStandard) {
      dimensionInputs.forEach(function (input, index) {
        input.value = customDimensions[index];
      });
    }

    modeInput.value = mode;
    currentMode = mode;

    modeButtons.forEach(function (modeButton) {
      var isSelected = modeButton.dataset.editMode === mode;
      modeButton.classList.toggle("is-active", isSelected);
      modeButton.setAttribute("aria-pressed", String(isSelected));
    });

    dimensionInputs.forEach(function (input) {
      input.disabled = isStandard;
      if (isStandard) input.value = input.dataset.standard;
    });

    dimensionHint.hidden = !isStandard;
  }

  modeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      setEditMode(button.dataset.editMode);
    });
  });
  setEditMode(modeInput.value);

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

var cartSelectionRoot = document.querySelector("[data-cart-selectable-ids]");

if (cartSelectionRoot) {
  var selectableCartIds = JSON.parse(cartSelectionRoot.dataset.cartSelectableIds || "[]");
  var selectableCartIdSet = new Set(selectableCartIds);
  var selectionStorageKey = "griya-sprei-cart-selection";
  var selectedCartIds = new Set(
    JSON.parse(sessionStorage.getItem(selectionStorageKey) || "[]").filter(function (itemId) {
      return selectableCartIdSet.has(itemId);
    })
  );
  var selectAllCheckbox = document.getElementById("cartSelectAll");
  var selectionCount = document.getElementById("cartSelectionCount");
  var checkoutCount = document.getElementById("checkoutSelectedCount");
  var checkoutButton = document.getElementById("checkoutSelectedButton");
  var selectedItemsContainer = document.getElementById("selectedCartItems");
  var checkoutSelectionForm = document.getElementById("checkoutSelectionForm");
  var itemCheckboxes = document.querySelectorAll(".cart-item-checkbox");

  function updateCartSelection() {
    itemCheckboxes.forEach(function (checkbox) {
      checkbox.checked = selectedCartIds.has(checkbox.value);
    });

    if (selectAllCheckbox) {
      selectAllCheckbox.checked =
        selectableCartIds.length > 0 &&
        selectableCartIds.every(function (itemId) {
          return selectedCartIds.has(itemId);
        });
      selectAllCheckbox.indeterminate =
        selectedCartIds.size > 0 && !selectAllCheckbox.checked;
    }

    if (selectionCount) {
      selectionCount.textContent = selectedCartIds.size + " produk dipilih";
    }
    if (checkoutCount) {
      checkoutCount.textContent = String(selectedCartIds.size);
    }
    if (checkoutButton) {
      checkoutButton.disabled = selectedCartIds.size === 0;
    }
    if (selectedItemsContainer) {
      selectedItemsContainer.replaceChildren();
      selectedCartIds.forEach(function (itemId) {
        var input = document.createElement("input");
        input.type = "hidden";
        input.name = "item_ids[]";
        input.value = itemId;
        selectedItemsContainer.appendChild(input);
      });
    }

    sessionStorage.setItem(selectionStorageKey, JSON.stringify(Array.from(selectedCartIds)));
  }

  itemCheckboxes.forEach(function (checkbox) {
    checkbox.addEventListener("change", function () {
      if (checkbox.checked) {
        selectedCartIds.add(checkbox.value);
      } else {
        selectedCartIds.delete(checkbox.value);
      }
      updateCartSelection();
    });
  });

  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener("change", function () {
      selectableCartIds.forEach(function (itemId) {
        if (selectAllCheckbox.checked) {
          selectedCartIds.add(itemId);
        } else {
          selectedCartIds.delete(itemId);
        }
      });
      updateCartSelection();
    });
  }

  if (checkoutSelectionForm) {
    checkoutSelectionForm.addEventListener("submit", function (event) {
      if (selectedCartIds.size === 0) {
        event.preventDefault();
        return;
      }
      sessionStorage.removeItem(selectionStorageKey);
    });
  }

  updateCartSelection();
}
