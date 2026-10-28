
(function () {
    'use strict';

    /* ---------------------------------------------------------
       1. KONFIGURASI
       --------------------------------------------------------- */
    var STATUS = {
        baru:                { label: 'Baru',                kelas: 'badge-info' },
        menunggu_verifikasi: { label: 'Menunggu Verifikasi', kelas: 'badge-warn' },
        diproses:            { label: 'Siap Kirim',          kelas: 'badge-ok' },
        dikirim:             { label: 'Dikirim',             kelas: 'badge-blue' },
        selesai:             { label: 'Selesai',             kelas: 'badge-done' },
        dibatalkan:          { label: 'Dibatalkan',          kelas: 'badge-danger' }
    };

    var STATUS_PENJUALAN = ['diproses', 'dikirim', 'selesai'];

    var SUMBER_STOK = {
        produksi: 'Produksi / Jahit sendiri',
        supplier: 'Supplier',
        retur:    'Retur pelanggan',
        koreksi:  'Koreksi stok'
    };

    var NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                      'Agustus', 'September', 'Oktober', 'November', 'Desember'];


    var sekarang = new Date();
    var TAHUN = sekarang.getFullYear();
    var BULAN = sekarang.getMonth(); // 0-11
    var bulanLaporan = BULAN;
    var tahunLaporan = TAHUN;
    var halamanLaporan = 1;
    var PESANAN_PER_HALAMAN = 7;

    function tgl(hari) {
        var maks = new Date(TAHUN, BULAN + 1, 0).getDate();
        var d = Math.min(hari, maks);
        return TAHUN + '-' + String(BULAN + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
    }

    var state = {
        produk: [
            { id: 1, nama: 'Aster Sage Green',  ukuran: 'Queen 160x200',  harga: 230000, stok: 24 },
            { id: 2, nama: 'Hitam Light Grey',  ukuran: 'King 180x200',   harga: 275000, stok: 18 },
            { id: 3, nama: 'Asterina Burgundy', ukuran: 'Queen 160x200',  harga: 230000, stok: 8 },
            { id: 4, nama: 'Bewata Kelabu',     ukuran: 'Single 120x200', harga: 185000, stok: 20 }
        ],
        pesanan: [
            buatPesanan(1,  'ORD-1001', 24, 'Dinas Setiawan', '0812-3456-7890', 'Jl. Kawi No. 12, Malang',
                'menunggu_verifikasi', [[1, 1]], 'Kirim sebelum hari Jumat'),
            buatPesanan(2,  'ORD-1002', 25, 'Rudi Pratama',   '0813-1111-2222', 'Jl. Soekarno Hatta No. 5, Malang',
                'menunggu_verifikasi', [[2, 1]]),
            buatPesanan(3,  'ORD-1003', 26, 'Rina Wahyuni',   '0857-3333-4444', 'Jl. Veteran No. 20, Malang',
                'baru', [[3, 2]]),
            buatPesanan(4,  'ORD-1004', 26, 'Dewi Lestari',   '0821-5555-6666', 'Jl. Ijen No. 8, Malang',
                'baru', [[4, 1]], '', 'COD'),
            buatPesanan(5,  'ORD-1005', 27, 'Andi Kurniawan', '0878-7777-8888', 'Jl. Tidar No. 31, Malang',
                'baru', [[1, 1]]),
            buatPesanan(6,  'ORD-1006', 27, 'Dinas Setiawan', '0812-3456-7890', 'Jl. Kawi No. 12, Malang',
                'baru', [[2, 1]]),
            buatPesanan(7,  'ORD-1007', 3,  'Rudi Pratama',   '0813-1111-2222', 'Jl. Soekarno Hatta No. 5, Malang',
                'selesai', [[3, 1]]),
            buatPesanan(8,  'ORD-1008', 9,  'Rina Wahyuni',   '0857-3333-4444', 'Jl. Veteran No. 20, Malang',
                'selesai', [[4, 2]]),
            buatPesanan(9,  'ORD-1009', 16, 'Dewi Lestari',   '0821-5555-6666', 'Jl. Ijen No. 8, Malang',
                'dikirim', [[2, 2]]),
            buatPesanan(10, 'ORD-1010', 22, 'Andi Kurniawan', '0878-7777-8888', 'Jl. Tidar No. 31, Malang',
                'diproses', [[1, 1]])
        ],
        riwayatStok: []
    };

    function buatPesanan(id, kode, hari, nama, telepon, alamat, status, items, catatanPelanggan, metode) {
        return {
            id: id,
            kode: kode,
            tanggal: tgl(hari),
            pelanggan: { nama: nama, telepon: telepon, alamat: alamat },
            metode: metode || 'Transfer Bank',
            status: status,
            catatanPelanggan: catatanPelanggan || '',
            catatanAdmin: '',
            stokTerpotong: STATUS_PENJUALAN.indexOf(status) !== -1,
            items: items.map(function (i) { return { produkId: i[0], jumlah: i[1] }; })
        };
    }

    /* ---------------------------------------------------------
       3. HELPER FORMAT (ditulis sekali)
       --------------------------------------------------------- */
    function rupiah(angka) {
        return 'Rp ' + Math.round(angka).toLocaleString('id-ID');
    }

    function tanggalIndo(iso) {
        var b = iso.split('-').map(Number);
        return String(b[2]).padStart(2, '0') + ' ' + NAMA_BULAN[b[1] - 1].slice(0, 3) + ' ' + b[0];
    }

    function h(tag, props) {
        var el = document.createElement(tag);
        Object.keys(props || {}).forEach(function (k) {
            var v = props[k];
            if (v === null || v === undefined || v === false) { return; }
            if (k === 'class') { el.className = v; }
            else if (k === 'style') { el.style.cssText = v; }
            else { el.setAttribute(k, v === true ? '' : v); }
        });
        Array.prototype.slice.call(arguments, 2).forEach(function (anak) {
            [].concat(anak).forEach(function (a) {
                if (a !== null && a !== undefined && a !== false) { el.append(a); }
            });
        });
        return el;
    }

    function $(id) { return document.getElementById(id); }

    function kosongkan(el) { el.textContent = ''; }

    function cariProduk(id) {
        return state.produk.filter(function (p) { return p.id === Number(id); })[0];
    }

    function cariPesanan(id) {
        return state.pesanan.filter(function (p) { return p.id === Number(id); })[0];
    }

    function hitungTotal(pesanan) {
        return pesanan.items.reduce(function (sum, i) {
            return sum + i.jumlah * cariProduk(i.produkId).harga;
        }, 0);
    }

    function ringkasProduk(pesanan) {
        var pertama = cariProduk(pesanan.items[0].produkId);
        var teks = pertama.nama + ' (' + pertama.ukuran + ')';
        if (pesanan.items.length > 1) { teks += ' +' + (pesanan.items.length - 1) + ' lainnya'; }
        return teks;
    }

    function urutTerbaru(a, b) {
        return a.tanggal === b.tanggal ? b.id - a.id : (a.tanggal < b.tanggal ? 1 : -1);
    }

    function pesananBulanIni() {
        return pesananPeriode(BULAN, TAHUN);
    }

    function pesananPeriode(bulan, tahun) {
        var awal = tahun + '-' + String(bulan + 1).padStart(2, '0');
        return state.pesanan
            .filter(function (p) { return p.tanggal.indexOf(awal) === 0; })
            .sort(urutTerbaru);
    }

    function hitungStatus(status) {
        return state.pesanan.filter(function (p) { return p.status === status; }).length;
    }

    function ringkasanBulan() {
        return ringkasanPeriode(BULAN, TAHUN);
    }

    function ringkasanPeriode(bulan, tahun) {
        var terbayar = pesananPeriode(bulan, tahun).filter(function (p) {
            return STATUS_PENJUALAN.indexOf(p.status) !== -1;
        });

        var mingguan = [1, 2, 3, 4].map(function () { return { total: 0, transaksi: 0 }; });
        var total = 0;
        var selesai = 0;

        terbayar.forEach(function (p) {
            var nilai = hitungTotal(p);
            var hari = Number(p.tanggal.split('-')[2]);
            var minggu = Math.min(4, Math.ceil(hari / 7)) - 1;
            mingguan[minggu].total += nilai;
            mingguan[minggu].transaksi += 1;
            total += nilai;
            if (p.status === 'selesai') { selesai += 1; }
        });

        return {
            total: total,
            selesai: selesai,
            terbayar: terbayar.length,
            rataRata: terbayar.length ? total / terbayar.length : 0,
            mingguan: mingguan
        };
    }

    function tambahStok(produkId, jumlah, sumber, keterangan) {
        var produk = cariProduk(produkId);
        if (!produk) { return { ok: false, pesan: 'Pilih sprei yang akan ditambah stoknya.' }; }
        if (!Number.isInteger(jumlah) || jumlah < 1 || jumlah > 10000) {
            return { ok: false, pesan: 'Jumlah tambahan stok harus antara 1 dan 10.000.' };
        }
        if (!SUMBER_STOK[sumber]) { return { ok: false, pesan: 'Sumber stok tidak valid.' }; }

        produk.stok += jumlah;
        state.riwayatStok.push({
            produkId: produk.id, jumlah: jumlah, sumber: SUMBER_STOK[sumber],
            keterangan: keterangan, waktu: new Date()
        });
        return { ok: true, pesan: 'Stok ' + produk.nama + ' bertambah ' + jumlah + ' pcs.' };
    }

    /* ---------------------------------------------------------
       5. RENDER (semua tampilan dibuat dari state)
       --------------------------------------------------------- */
    function badgeStatus(status) {
        return h('span', { class: 'badge ' + STATUS[status].kelas }, STATUS[status].label);
    }

    function renderStatistik() {
        var total = state.produk.reduce(function (s, p) { return s + p.stok; }, 0);

        $('statBaru').textContent = hitungStatus('baru');
        $('statVerifikasi').textContent = hitungStatus('menunggu_verifikasi');
        $('statStok').textContent = total;
    }

    function renderGrafik() {
        var r = ringkasanBulan();
        var maksTotal = Math.max.apply(null, r.mingguan.map(function (m) { return m.total; }).concat(1));
        var maksTrx = Math.max.apply(null, r.mingguan.map(function (m) { return m.transaksi; }).concat(1));

        $('mLabelTotal').textContent = 'Total penjualan (' + NAMA_BULAN[BULAN] + ' ' + TAHUN + ')';
        $('mTotal').textContent = rupiah(r.total);
        $('mSelesai').textContent = r.selesai + ' pesanan';

        var chart = $('barChart');
        kosongkan(chart);
        r.mingguan.forEach(function (m, i) {
            chart.append(
                h('div', { class: 'bar-group' },
                    h('div', { class: 'bars' },
                        h('span', { class: 'bar bar-a', title: rupiah(m.total),
                                    style: 'height:' + Math.round(m.total / maksTotal * 100) + '%' }),
                        h('span', { class: 'bar bar-b', title: m.transaksi + ' transaksi',
                                    style: 'height:' + Math.round(m.transaksi / maksTrx * 100) + '%' })
                    ),
                    h('span', { class: 'bar-label' }, 'Minggu ' + (i + 1))
                )
            );
        });
    }

    function renderVerifikasi() {
        var wadah = $('listVerifikasi');
        var antrean = state.pesanan
            .filter(function (p) { return p.status === 'menunggu_verifikasi'; })
            .sort(function (a, b) { return -urutTerbaru(a, b); })
            .slice(0, 5);

        kosongkan(wadah);
        if (!antrean.length) {
            wadah.append(h('p', { class: 'empty-text' }, 'Tidak ada bukti transfer yang menunggu verifikasi.'));
            return;
        }
        antrean.forEach(function (p) {
            wadah.append(
                h('div', { class: 'verif-item' },
                    h('div', { class: 'verif-info' },
                        h('strong', {}, p.kode + ' \u00B7 ' + p.pelanggan.nama),
                        h('span', {}, 'Bukti transfer ' + rupiah(hitungTotal(p)))
                    ),
                    h('button', { type: 'button', class: 'btn-text', disabled: true }, 'Cek pesanan')
                )
            );
        });
    }

    function renderTabelPesanan() {
        var tbody = $('tabelPesanan');
        var daftar = state.pesanan.slice().sort(urutTerbaru).slice(0, 10);

        kosongkan(tbody);
        if (!daftar.length) {
            tbody.append(h('tr', {}, h('td', { colspan: 6, class: 'empty-text' }, 'Belum ada pesanan.')));
            return;
        }
        daftar.forEach(function (p) {
            tbody.append(
                h('tr', {},
                    h('td', {}, h('strong', {}, p.kode)),
                    h('td', {}, p.pelanggan.nama, h('br'), h('small', { class: 'muted' }, ringkasProduk(p))),
                    h('td', {}, tanggalIndo(p.tanggal)),
                    h('td', {}, rupiah(hitungTotal(p))),
                    h('td', {}, badgeStatus(p.status)),
                    h('td', {}, h('button', { type: 'button', class: 'btn-text', 'data-detail': p.id }, 'Lihat detail'))
                )
            );
        });
    }

    function renderDaftarStok() {
        var wadah = $('daftarStok');
        var stokMaksimum = Math.max.apply(null, state.produk.map(function (p) { return p.stok; }).concat(1));
        kosongkan(wadah);
        state.produk.forEach(function (p) {
            var persen = Math.round(p.stok / stokMaksimum * 100);
            wadah.append(
                h('button', { type: 'button', class: 'stock-row', 'data-pilih-produk': p.id },
                    h('span', { class: 'stock-row-heading' },
                        h('span', { class: 'stock-name' }, p.nama + ' (' + p.ukuran + ')'),
                        h('strong', { class: 'stock-qty' }, p.stok + ' set')
                    ),
                    h('span', { class: 'progress' },
                        h('span', { class: 'progress-fill', style: 'width:' + persen + '%' })
                    )
                )
            );
        });
        perbaruiInfoStok();
    }

    function renderLaporan() {
        var r = ringkasanPeriode(bulanLaporan, tahunLaporan);
        var tanggalAkhirBulan = new Date(tahunLaporan, bulanLaporan + 1, 0).getDate();
        var pilihanBulan = $('lapBulan');
        var pilihanTahun = $('lapTahun');

        if (!pilihanBulan.options.length) {
            NAMA_BULAN.forEach(function (nama, i) {
                pilihanBulan.append(h('option', { value: i }, nama));
            });
            for (var tahun = TAHUN - 5; tahun <= TAHUN + 1; tahun += 1) {
                pilihanTahun.append(h('option', { value: tahun }, String(tahun)));
            }
        }
        pilihanBulan.value = String(bulanLaporan);
        pilihanTahun.value = String(tahunLaporan);

        $('lapTotal').textContent = rupiah(r.total);
        $('lapSelesai').textContent = r.selesai + ' pesanan';
        $('lapTerbayar').textContent = r.terbayar + ' total pesanan terkirim';
        $('judulTren').textContent = 'Grafik tren penjualan mingguan (' +
            NAMA_BULAN[bulanLaporan] + ' ' + tahunLaporan + ')';

        var tren = $('lapTren');
        kosongkan(tren);
        var maksTotal = Math.max.apply(null, r.mingguan.map(function (m) { return m.total; }).concat(1));
        r.mingguan.forEach(function (m, i) {
            var hariAwal = i * 7 + 1;
            var hariAkhir = Math.min((i + 1) * 7, tanggalAkhirBulan);
            var tinggi = m.total ? Math.max(8, Math.round(m.total / maksTotal * 100)) : 0;
            tren.append(
                h('div', { class: 'trend-item' },
                    h('strong', {}, rupiah(m.total)),
                    h('div', { class: 'trend-bar-track' },
                        h('span', {
                            class: 'trend-bar',
                            style: 'height:' + tinggi + '%',
                            title: m.transaksi + ' transaksi'
                        })
                    ),
                    h('span', {}, 'Minggu ' + (i + 1)),
                    h('small', {}, '(' + hariAwal + '\u2013' + hariAkhir + ' ' +
                        NAMA_BULAN[bulanLaporan].slice(0, 3) + ')')
                )
            );
        });

        var tbody = $('lapTabel');
        var daftar = pesananPeriode(bulanLaporan, tahunLaporan);
        var jumlahHalaman = Math.max(1, Math.ceil(daftar.length / PESANAN_PER_HALAMAN));
        halamanLaporan = Math.min(halamanLaporan, jumlahHalaman);
        var mulai = (halamanLaporan - 1) * PESANAN_PER_HALAMAN;
        var halaman = daftar.slice(mulai, mulai + PESANAN_PER_HALAMAN);
        kosongkan(tbody);
        if (!daftar.length) {
            tbody.append(h('tr', {}, h('td', { colspan: 5, class: 'empty-text' }, 'Belum ada pesanan pada periode ini.')));
        } else {
            halaman.forEach(function (p) {
                tbody.append(
                    h('tr', {},
                        h('td', {}, h('strong', {}, p.kode)),
                        h('td', {}, tanggalIndo(p.tanggal)),
                        h('td', {}, p.pelanggan.nama, h('br'), h('small', { class: 'muted' }, ringkasProduk(p))),
                        h('td', {}, rupiah(hitungTotal(p))),
                        h('td', {}, badgeStatus(p.status))
                    )
                );
            });
        }

        $('lapNomorHalaman').textContent = halamanLaporan;
        $('lapSebelumnya').disabled = halamanLaporan <= 1;
        $('lapBerikutnya').disabled = halamanLaporan >= jumlahHalaman;
    }

    function renderAll() {
        renderStatistik();
        renderGrafik();
        renderVerifikasi();
        renderTabelPesanan();
        renderDaftarStok();
        renderLaporan();
    }

    function tampilkanPesan(ok, pesan) {
        var area = $('flashArea');
        kosongkan(area);
        var kotak = h('div', { class: 'flash ' + (ok ? 'flash-sukses' : 'flash-error'), role: 'alert' },
            h('span', {}, pesan),
            h('button', { type: 'button', class: 'flash-close', 'aria-label': 'Tutup pesan' }, '\u00D7')
        );
        area.append(kotak);
        setTimeout(function () { if (kotak.parentNode) { kotak.remove(); } }, 7000);
    }

    /* ---------------------------------------------------------
       6. POPUP & SIDEBAR
       --------------------------------------------------------- */
    var elemenPembuka = null;
    var pemicuStok = null;
    var kembaliKeStok = false;
    function bukaModal(id, pembuka) {
        var modal = $(id);
        if (!modal) { return; }

        elemenPembuka = pembuka || document.activeElement;
        if (id === 'modalStok') { pemicuStok = elemenPembuka; }
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        var fokus = modal.id === 'modalDetail'
            ? modal.querySelector('.close-btn')
            : modal.querySelector('select, input, textarea, .btn-primary, .close-btn');
        if (fokus) { fokus.focus(); }
    }

    function tutupModal(modal) {
        if (!modal) { return; }
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');

        if (!document.querySelector('.modal-overlay.active')) {
            document.body.classList.remove('modal-open');
            if (elemenPembuka && elemenPembuka.isConnected) { elemenPembuka.focus(); }
        }
    }

    function bukaFormTambahStok(pembuka) {
        kembaliKeStok = $('modalStok').classList.contains('active');
        if (kembaliKeStok) { tutupModal($('modalStok')); }
        bukaModal('modalTambahStok', pembuka);
    }

    function tutupFormTambahStok() {
        var bukaDaftarStok = kembaliKeStok;
        tutupModal($('modalTambahStok'));
        kembaliKeStok = false;
        if (bukaDaftarStok) { bukaModal('modalStok', pemicuStok); }
    }

    function isiDetail(id) {
        var p = cariPesanan(id);
        if (!p) { return false; }

        var bagianTanggal = p.tanggal.split('-');
        $('dKodeTanggal').textContent = 'Invoice: INV/' + bagianTanggal.join('') +
            '/SP/' + String(p.id).padStart(3, '0');
        $('dNama').textContent = p.pelanggan.nama;
        $('dTelepon').textContent = p.pelanggan.telepon;
        $('dAlamat').textContent = p.pelanggan.alamat;
        $('dTotal').textContent = rupiah(hitungTotal(p));
        $('dStatus').value = STATUS[p.status].label;
        $('dCatatanAdmin').value = p.catatanAdmin || '';
        var statusPembayaran = p.status === 'menunggu_verifikasi'
            ? 'Status: Menunggu Verifikasi Pembayaran'
            : STATUS_PENJUALAN.indexOf(p.status) !== -1
                ? 'Status: Pembayaran terverifikasi'
                : p.status === 'dibatalkan'
                    ? 'Status: Pesanan dibatalkan'
                    : 'Status: Belum dibayar';
        $('dMetode').textContent = statusPembayaran + ' \u00B7 ' + p.metode;
        $('dCatatanPelanggan').textContent = p.catatanPelanggan
            ? 'Catatan: ' + p.catatanPelanggan
            : '';

        var daftar = $('dItems');
        kosongkan(daftar);
        p.items.forEach(function (i) {
            var produk = cariProduk(i.produkId);
            daftar.append(
                h('div', { class: 'item-row' },
                    h('div', { class: 'detail-product-info' },
                        h('strong', {}, produk.nama),
                        h('small', {}, 'Ukuran: ' + produk.ukuran),
                        h('small', {}, 'Kelengkapan: ' + i.jumlah + ' sprei')
                    ),
                    h('strong', { class: 'detail-product-total' }, rupiah(i.jumlah * produk.harga))
                )
            );
        });

        return true;
    }

    function perbaruiInfoStok() {
        var produk = cariProduk($('selectProduk').value);

        $('infoStok').textContent = produk ? 'Tersisa ' + produk.stok + ' set' : '-';
    }

    /* ---------------------------------------------------------
       7. EVENT
       --------------------------------------------------------- */
    document.addEventListener('click', function (event) {
        var t = event.target;
        var el;

        if ((el = t.closest('#terapkanLaporan'))) {
            bulanLaporan = Number($('lapBulan').value);
            tahunLaporan = Number($('lapTahun').value);
            halamanLaporan = 1;
            renderLaporan();
            return;
        }
        if ((el = t.closest('#lapSebelumnya'))) {
            halamanLaporan = Math.max(1, halamanLaporan - 1);
            renderLaporan();
            return;
        }
        if ((el = t.closest('#lapBerikutnya'))) {
            halamanLaporan += 1;
            renderLaporan();
            return;
        }
        if ((el = t.closest('[data-modal]'))) {
            event.preventDefault();
            if (el.dataset.modal === 'modalTambahStok') {
                bukaFormTambahStok(el);
            } else {
                bukaModal(el.dataset.modal, el);
            }
            return;
        }
        if ((el = t.closest('[data-detail]'))) {
            event.preventDefault();
            if (isiDetail(el.dataset.detail)) { bukaModal('modalDetail', el); }
            return;
        }
        if (t.closest('[data-close]')) {
            if (t.closest('#modalTambahStok')) {
                tutupFormTambahStok();
            } else {
                tutupModal(t.closest('.modal-overlay'));
            }
            return;
        }
        if (t.classList.contains('modal-overlay')) {
            if (t.id === 'modalTambahStok') {
                tutupFormTambahStok();
            } else {
                tutupModal(t);
            }
            return;
        }

        if ((el = t.closest('[data-pilih-produk]'))) {
            $('selectProduk').value = el.dataset.pilihProduk;
            $('inputJumlah').value = 1;
            perbaruiInfoStok();
            bukaFormTambahStok(el);
            return;
        }
        if ((el = t.closest('[data-step]'))) {
            var nilai = (parseInt($('inputJumlah').value, 10) || 0) + Number(el.dataset.step);
            $('inputJumlah').value = Math.min(10000, Math.max(1, nilai));
            perbaruiInfoStok();
            return;
        }

        if (t.closest('.flash-close')) { kosongkan($('flashArea')); return; }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(function (modal) {
                if (modal.id === 'modalTambahStok') {
                    tutupFormTambahStok();
                } else {
                    tutupModal(modal);
                }
            });
            return;
        }
        if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[data-modal][role="button"]')) {
            event.preventDefault();
            if (event.target.dataset.modal === 'modalTambahStok') {
                bukaFormTambahStok(event.target);
            } else {
                bukaModal(event.target.dataset.modal, event.target);
            }
        }
    });

    $('formStok').addEventListener('submit', function (event) {
        event.preventDefault();

        var hasil = tambahStok(
            $('selectProduk').value,
            parseInt($('inputJumlah').value, 10),
            $('selectSumber').value,
            $('inputKeterangan').value.trim()
        );

        if (hasil.ok) {
            $('inputJumlah').value = 1;
            $('inputKeterangan').value = '';
            tutupFormTambahStok();
            renderAll();
        }
        tampilkanPesan(hasil.ok, hasil.pesan);
    });

    $('selectProduk').addEventListener('change', function () {
        $('inputJumlah').value = 1;
        perbaruiInfoStok();
    });
    $('inputJumlah').addEventListener('input', perbaruiInfoStok);

    /* ---------------------------------------------------------
       8. INISIALISASI
       --------------------------------------------------------- */
    function isiPilihan(select, pasangan, placeholder) {
        kosongkan(select);
        if (placeholder) { select.append(h('option', { value: '' }, placeholder)); }
        pasangan.forEach(function (p) { select.append(h('option', { value: p[0] }, p[1])); });
    }

    isiPilihan($('selectSumber'),
        Object.keys(SUMBER_STOK).map(function (k) { return [k, SUMBER_STOK[k]]; }));
    isiPilihan($('selectProduk'),
        state.produk.map(function (p) { return [p.id, p.nama + ' (' + p.ukuran + ')']; }));
    $('selectProduk').value = String(state.produk[0].id);
    $('inputJumlah').value = 1;

    renderAll();
})();