CREATE TABLE keranjang_pelanggan (
    id_user     INTEGER     NOT NULL REFERENCES "user"(id_user) ON DELETE CASCADE,
    item_id     VARCHAR(32) NOT NULL,
    item_data   JSONB       NOT NULL,
    created_at  TIMESTAMP   NOT NULL DEFAULT now(),
    updated_at  TIMESTAMP   NOT NULL DEFAULT now(),
    PRIMARY KEY (id_user, item_id),
    CHECK (item_id ~ '^[a-f0-9]{32}$')
);
