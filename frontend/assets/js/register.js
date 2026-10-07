const registerForm = document.getElementById('registerForm');

registerForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(registerForm);

    try {
        const response = await fetch('../../backend/actions/register.php', {
            method: 'POST',
            body: formData
        });

        // Ambil respons sebagai text dulu
        const text = await response.text();

        console.log('RESPON BACKEND:');
        console.log(text);

        // Coba ubah menjadi JSON
        const result = JSON.parse(text);

        if (!result.success) {
            alert(result.message);
            return;
        }

        alert(result.message);

        window.location.href = 'login.php';

    } catch (error) {
        console.error('ERROR REGISTER:', error);

        alert('Terjadi kesalahan saat melakukan registrasi. Cek Console.');
    }
});