DO $$
BEGIN
    IF to_regtype('public.jenis_kain_enum') IS NULL THEN
        CREATE TYPE public.jenis_kain_enum AS ENUM (
            'TENCEL',
            'KATUN_JEPANG',
            'KATUN_LOKAL',
            'MICROTENCEL',
            'MICROTEX'
        );
    END IF;

    IF to_regtype('public.tipe_ukuran_enum') IS NULL THEN
        CREATE TYPE public.tipe_ukuran_enum AS ENUM ('STANDAR', 'CUSTOM');
    END IF;
END
$$;
    
DO $$
BEGIN
    IF EXISTS (
        SELECT required.label
        FROM unnest(ARRAY[
            'TENCEL', 'KATUN_JEPANG', 'KATUN_LOKAL', 'MICROTENCEL', 'MICROTEX'
        ]) AS required(label)
        WHERE NOT EXISTS (
            SELECT 1
            FROM pg_enum e
            JOIN pg_type t ON t.oid = e.enumtypid
            JOIN pg_namespace n ON n.oid = t.typnamespace
            WHERE n.nspname = 'public'
              AND t.typname = 'jenis_kain_enum'
              AND e.enumlabel = required.label
        )
    ) THEN
        RAISE EXCEPTION 'jenis_kain_enum harus memiliki label kain yang dipakai aplikasi.';
    END IF;

    IF EXISTS (
        SELECT required.label
        FROM unnest(ARRAY['STANDAR', 'CUSTOM']) AS required(label)
        WHERE NOT EXISTS (
            SELECT 1
            FROM pg_enum e
            JOIN pg_type t ON t.oid = e.enumtypid
            JOIN pg_namespace n ON n.oid = t.typnamespace
            WHERE n.nspname = 'public'
              AND t.typname = 'tipe_ukuran_enum'
              AND e.enumlabel = required.label
        )
    ) THEN
        RAISE EXCEPTION 'tipe_ukuran_enum harus memiliki label STANDAR dan CUSTOM.';
    END IF;
END
$$;

DO $$
DECLARE
    column_type TEXT;
BEGIN
    SELECT udt_name
    INTO column_type
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'produk'
      AND column_name = 'jenis_kain';

    IF column_type IS NULL THEN
        RAISE EXCEPTION 'Kolom public.produk.jenis_kain tidak ditemukan.';
    END IF;

    IF column_type <> 'jenis_kain_enum' THEN
        IF EXISTS (
            SELECT 1
            FROM public.produk
            WHERE NULLIF(upper(btrim(jenis_kain::text)), '') IS NOT NULL
              AND upper(btrim(jenis_kain::text)) NOT IN (
                  'TENCEL', 'KATUN_JEPANG', 'KATUN_LOKAL', 'MICROTENCEL', 'MICROTEX'
              )
        ) THEN
            RAISE EXCEPTION 'Ada nilai jenis_kain yang tidak dikenal; rapikan datanya sebelum migrasi.';
        END IF;

        ALTER TABLE public.produk
            ALTER COLUMN jenis_kain TYPE public.jenis_kain_enum
            USING NULLIF(upper(btrim(jenis_kain::text)), '')::public.jenis_kain_enum;
    END IF;

    SELECT udt_name
    INTO column_type
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'detail_pesanan'
      AND column_name = 'tipe_ukuran';

    IF column_type IS NULL THEN
        RAISE EXCEPTION 'Kolom public.detail_pesanan.tipe_ukuran tidak ditemukan.';
    END IF;

    IF column_type <> 'tipe_ukuran_enum' THEN
        IF EXISTS (
            SELECT 1
            FROM public.detail_pesanan
            WHERE upper(btrim(tipe_ukuran::text)) NOT IN ('STANDAR', 'STANDARD', 'CUSTOM')
        ) THEN
            RAISE EXCEPTION 'Ada nilai tipe_ukuran yang tidak dikenal; rapikan datanya sebelum migrasi.';
        END IF;

        ALTER TABLE public.detail_pesanan
            ALTER COLUMN tipe_ukuran TYPE public.tipe_ukuran_enum
            USING CASE upper(btrim(tipe_ukuran::text))
                WHEN 'STANDARD' THEN 'STANDAR'::public.tipe_ukuran_enum
                ELSE upper(btrim(tipe_ukuran::text))::public.tipe_ukuran_enum
            END;
    END IF;
END
$$;
