<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isCustomerLoggedIn = ($_SESSION['login'] ?? false) === true
    && ($_SESSION['role'] ?? '') === 'CUSTOMER';
?>
<footer class="footer">
    <div class="footer__inner">

        <!-- Kolom 1: tentang & alamat -->
        <div class="footer__column">
            <h3 class="footer__title">Griya Sprei Malang</h3>
            <p class="footer__text">
                Melayani sprei, bed cover, sarung bantal,
                dan sarung guling untuk rumah tangga,
                kos, homestay, hingga rumah sakit sejak 2010.
            </p>
            <p class="footer__text">
                <a class="footer__link" target="_blank" rel="noopener"
                    href="https://www.google.com/maps/search/?api=1&amp;query=Jl.+Kanjuruhan+Asri+No.+8,+Tlogomas,+Lowokwaru,+Kota+Malang">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z" />
                        <circle cx="12" cy="10" r="2.5" />
                    </svg>
                    Jl. Kanjuruhan Asri No. 8, Tlogomas, Lowokwaru, Kota Malang
                </a>
            </p>
        </div>

        <!-- Kolom 2: kontak -->
        <div class="footer__column">
            <h3 class="footer__title">Kontak Kami</h3>
            <ul class="footer__list">
                <li>
                    <a class="footer__link" href="https://wa.me/+6285814626335" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" />
                        </svg>
                        +62 858-1462-6335
                    </a>
                </li>
                <li>
                    <a class="footer__link" href="https://instagram.com/produksispreikatun" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="5" />
                            <circle cx="12" cy="12" r="4" />
                            <circle cx="17.5" cy="6.5" r="1" fill="currentColor" />
                        </svg>
                        @produksispreikatun
                    </a>
                </li>
                <li>
                    <a class="footer__link" href="mailto:griyasprei8@gmail.com">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="m3 7 9 6 9-6" />
                        </svg>
                        griyasprei8@gmail.com
                    </a>
                </li>
            </ul>
        </div>

        <div class="footer__column">
            <h3 class="footer__title">Tautan</h3>
            <ul class="footer__list">
                <li><a href="/?page=customer-catalog">Katalog</a></li>
                <?php if ($isCustomerLoggedIn): ?>
                    <li><span aria-disabled="true">Pesanan Saya</span></li>
                <?php endif; ?>
            </ul>
        </div>

    </div>
</footer>