(() => {
  const toast = document.getElementById("productToast");
  if (!toast) return;

  const closeButton = toast.querySelector(".product-toast__close");
  const url = new URL(window.location.href);
  url.searchParams.delete("success");
  window.history.replaceState(window.history.state, "", url);

  let dismissTimer;
  const dismiss = () => {
    window.clearTimeout(dismissTimer);
    toast.classList.add("is-hidden");
    window.setTimeout(() => toast.remove(), 220);
  };

  closeButton.addEventListener("click", dismiss);
  dismissTimer = window.setTimeout(dismiss, 4500);
})();
