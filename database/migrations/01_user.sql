CREATE TABLE "user" (
    id_user    SERIAL PRIMARY KEY,
    nama       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    no_hp      VARCHAR(20)  NOT NULL,
    role       role_user    NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT now()
);

CREATE TYPE role_user AS ENUM ('CUSTOMER', 'ADMIN');