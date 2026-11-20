const resetMessage = document.getElementById("resetMessage");

function showResetMessage(message, success = false) {
  resetMessage.textContent = message;
  resetMessage.classList.toggle("flash-message--success", success);
  resetMessage.setAttribute("role", success ? "status" : "alert");
  resetMessage.hidden = false;
}

async function submitResetForm(form) {
  const button = form.querySelector('button[type="submit"]');
  if (button) button.disabled = true;
  try {
    const response = await fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      headers: { Accept: "application/json" },
    });
    const result = await response.json();
    if (result.redirect) {
      window.location.href = result.redirect;
      return result.success === true;
    }
    showResetMessage(result.message || "Permintaan gagal diproses.", result.success === true);
    return result.success === true;
  } catch {
    showResetMessage("Terjadi kesalahan. Silakan coba lagi.");
    return false;
  } finally {
    if (button) button.disabled = false;
  }
}

document.querySelectorAll("[data-reset-form]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    submitResetForm(form);
  });
});

const resendForm = document.getElementById("resendCodeForm");
const resendButton = document.getElementById("resendCodeButton");
if (resendForm && resendButton) {
  let timer;
  const startCountdown = () => {
    let remaining = 60;
    resendButton.disabled = true;
    resendButton.textContent = `Kirim ulang kode (${remaining})`;
    clearInterval(timer);
    timer = setInterval(() => {
      remaining -= 1;
      if (remaining <= 0) {
        clearInterval(timer);
        resendButton.textContent = "Kirim ulang kode";
        resendButton.disabled = false;
      } else {
        resendButton.textContent = `Kirim ulang kode (${remaining})`;
      }
    }, 1000);
  };
  startCountdown();
  resendForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!resendButton.disabled && await submitResetForm(resendForm)) startCountdown();
  });
}
