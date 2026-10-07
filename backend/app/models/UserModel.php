<?php

class UserModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }


    // =========================
    // CARI USER BERDASARKAN EMAIL
    // =========================
    public function findByEmail($email)
    {
        $sql = '
            SELECT *
            FROM "user"
            WHERE email = :email
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // =========================
    // TAMBAH USER
    // =========================
    public function createUser($data)
    {
        /*
         * Akun yang dibuat melalui register
         * SELALU memiliki role = user.
         *
         * Admin tidak dibuat melalui register,
         * tetapi menggunakan config/admin.php.
         */

        $sql = "
            INSERT INTO \"user\"
            (
                nama,
                email,
                password,
                no_hp,
                role,
                created_at
            )
            VALUES
            (
                :nama,
                :email,
                :password,
                :no_hp,
                'CUSTOMER',
                CURRENT_TIMESTAMP
            )
            RETURNING id_user
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':nama'     => $data['nama'],
            ':email'    => $data['email'],
            ':password' => $data['password'],
            ':no_hp'    => $data['no_hp']
        ]);

        return $stmt->fetchColumn();
    }


    // =========================
    // CEK EMAIL SUDAH TERDAFTAR
    // =========================
    public function emailExists($email)
    {
        $sql = '
            SELECT COUNT(*)
            FROM "user"
            WHERE email = :email
        ';

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetchColumn() > 0;
    }
}