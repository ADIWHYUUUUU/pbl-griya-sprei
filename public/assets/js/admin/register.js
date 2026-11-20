const form = document.getElementById("adminRegisterForm");

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (!form.reportValidity()) return;

  try {
    const response = await fetch(form.action, {
      method: "POST",
      body: new FormData(form),
    });
    const result = await response.json();
    if (!response.ok || !result.success) {
      alert(result.message || "Pendaftaran admin gagal. Periksa data dan coba lagi.");
      return;
    }
    alert(result.message);
    window.location.href = result.redirect;
  } catch (error) {
    console.error("ERROR ADMIN REGISTER:", error);
    alert("Pendaftaran admin gagal diproses. Periksa koneksi server.");
  }
});
