const registerForm = document.getElementById("registerForm");
const namaInput = document.getElementById("nama");
const clearNamaButton = document.getElementById("clearNama");
const emailInput = document.getElementById("email");
const clearEmailButton = document.getElementById("clearEmail");

namaInput.addEventListener("input", () => {
  clearNamaButton.hidden = namaInput.value === "";
});

clearNamaButton.addEventListener("click", () => {
  namaInput.value = "";
  clearNamaButton.hidden = true;
  namaInput.focus();
});

emailInput.addEventListener("input", () => {
  clearEmailButton.hidden = emailInput.value === "";
});

clearEmailButton.addEventListener("click", () => {
  emailInput.value = "";
  clearEmailButton.hidden = true;
  emailInput.focus();
});

document.querySelectorAll(".password-toggle").forEach((button) => {
  button.addEventListener("click", () => {
    const passwordInput = document.getElementById(button.dataset.target);
    const isHidden = passwordInput.type === "password";

    passwordInput.type = isHidden ? "text" : "password";
    button.setAttribute(
      "aria-label",
      isHidden ? "Sembunyikan kata sandi" : "Tampilkan kata sandi",
    );
    button.style.color = isHidden ? "var(--green)" : "";
  });
});

registerForm.addEventListener("submit", async function (e) {
  e.preventDefault();

  const formData = new FormData(registerForm);

  try {
    const response = await fetch("../../backend/actions/register.php", {
      method: "POST",
      body: formData,
    });

    // Ambil respons sebagai text dulu
    const text = await response.text();

    console.log("RESPON BACKEND:");
    console.log(text);

    // Coba ubah menjadi JSON
    const result = JSON.parse(text);

    if (!result.success) {
      alert(result.message);
      return;
    }

    alert(result.message);

    window.location.href = "login.php";
  } catch (error) {
    console.error("ERROR REGISTER:", error);

    alert("Terjadi kesalahan saat melakukan registrasi. Cek Console.");
  }
});
