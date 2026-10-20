const activeScript = document.currentScript;

if (activeScript) {
  const detailScript = document.createElement("script");
  detailScript.src = new URL("detail_produk.js", activeScript.src).href;
  activeScript.after(detailScript);
}
