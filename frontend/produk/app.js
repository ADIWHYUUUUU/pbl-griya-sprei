/* Griya Sprei — interaksi halaman admin */
(function () {
    'use strict';
    var $ = function (s, c) { return (c || document).querySelector(s); };
    var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

    /* ---- Menu mobile ---- */
    var sidebar = $('#sidebar'), scrim = $('#scrim'), burger = $('#burger');
    function toggleMenu(open) {
        sidebar.classList.toggle('is-open', open);
        scrim.classList.toggle('is-open', open);
    }
    if (burger) burger.addEventListener('click', function () { toggleMenu(!sidebar.classList.contains('is-open')); });
    if (scrim) scrim.addEventListener('click', function () { toggleMenu(false); });

    /* ---- Format harga: 185000 -> 185.000 ---- */
    $$('[data-rupiah]').forEach(function (el) {
        el.addEventListener('input', function () {
            var digits = el.value.replace(/\D/g, '');
            el.value = digits ? Number(digits).toLocaleString('id-ID') : '';
        });
    });

    /* ---- Chip ukuran cepat (Single / Queen / King / Super King) ---- */
    var lebar = $('#lebar'), panjang = $('#panjang'), chips = $$('.chip');
    function syncChips() {
        chips.forEach(function (c) {
            var on = lebar.value === c.dataset.lebar && panjang.value === c.dataset.panjang;
            c.classList.toggle('is-active', on);
            c.setAttribute('aria-pressed', on);
        });
    }
    if (chips.length && lebar && panjang) {
        chips.forEach(function (c) {
            c.addEventListener('click', function () {
                lebar.value = c.dataset.lebar;
                panjang.value = c.dataset.panjang;
                syncChips();
            });
        });
        lebar.addEventListener('input', syncChips);
        panjang.addEventListener('input', syncChips);
        syncChips();
    }

    /* ---- Penghitung karakter deskripsi ---- */
    $$('[data-counter-for]').forEach(function (out) {
        var ta = document.getElementById(out.dataset.counterFor);
        if (!ta) return;
        var max = ta.maxLength > 0 ? ta.maxLength : 500;
        function update() {
            out.textContent = ta.value.length + ' / ' + max + ' Karakter';
            out.classList.toggle('is-full', ta.value.length >= max);
        }
        ta.addEventListener('input', update);
        update();
    });

    /* ---- Pilih & pratinjau foto ---- */
    $$('[data-photo]').forEach(function (box) {
        var input = $('input[type=file]', box);
        var btn = $('[data-photo-pick]', box);
        var thumb = $('[data-photo-thumb]', box);
        var name = $('[data-photo-name]', box);
        var meta = $('[data-photo-meta]', box);
        var ok = $('[data-photo-ok]', box);
        var badge = $('[data-photo-badge]', box);

        btn.addEventListener('click', function () { input.click(); });
        input.addEventListener('change', function () {
            var f = input.files[0];
            if (!f) return;
            var allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (allowed.indexOf(f.type) === -1) { alert('Format foto harus JPG, PNG, atau WebP.'); input.value = ''; return; }
            if (f.size > 5 * 1024 * 1024) { alert('Ukuran foto maksimal 5 MB.'); input.value = ''; return; }

            var url = URL.createObjectURL(f);
            thumb.innerHTML = '';
            var img = new Image();
            img.alt = '';
            img.onload = function () {
                var mb = (f.size / 1048576).toFixed(1);
                if (meta) meta.textContent = 'Format: JPG, PNG, atau WebP • ' + img.naturalWidth + ' x ' + img.naturalHeight + ' px (' + mb + ' MB)';
                if (ok) ok.hidden = Math.min(img.naturalWidth, img.naturalHeight) < 800;
            };
            img.src = url;
            thumb.appendChild(img);
            name.textContent = f.name;
            if (badge) badge.textContent = 'Foto Baru';
        });

        /* ---- Pratinjau produk pilihan beranda ---- */
        $$('.home-product-slot select').forEach(function (select) {
            select.addEventListener('change', function () {
                var option = select.options[select.selectedIndex];
                var slot = select.closest('.home-product-slot');
                var name = $('[data-product-preview-name]', slot);
                var price = $('[data-product-preview-price]', slot);
                var thumb = $('.home-product-thumb', slot);
                if (name) name.textContent = option.dataset.productName || '';
                if (price) price.textContent = option.dataset.productPrice || '';
                if (thumb) {
                    thumb.innerHTML = option.dataset.productPhoto
                        ? '<img src="' + option.dataset.productPhoto.replace(/"/g, '&quot;') + '" alt="">'
                        : '<span aria-hidden="true">⌑</span>';
                }
            });
        });
    });

    /* ---- Dialog konfirmasi hapus ---- */
    var dlg = document.createElement('dialog');
    dlg.className = 'confirm';
    dlg.innerHTML = '<form method="dialog"><h3>Hapus produk?</h3><p></p>' +
        '<div class="row"><button value="no" class="btn btn-light">Batal</button>' +
        '<button value="yes" class="btn btn-danger-solid">Ya, hapus</button></div></form>';
    document.body.appendChild(dlg);

    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form.dataset || !form.dataset.confirm || form.dataset.confirmed) return;
        ev.preventDefault();
        $('p', dlg).textContent = form.dataset.confirm;
        dlg.returnValue = '';
        dlg.showModal();
        dlg.onclose = function () {
            if (dlg.returnValue === 'yes') { form.dataset.confirmed = '1'; form.submit(); }
        };
    });

    /* ---- Alert sukses hilang otomatis ---- */
    $$('.alert--success').forEach(function (a) {
        setTimeout(function () { a.style.display = 'none'; }, 5000);
    });
})();