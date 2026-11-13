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
         * Admin dibuat melalui alur terpisah.
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


    public function createAdmin($data)
    {
        $sql = '
            INSERT INTO "user" (nama, email, password, no_hp, role, created_at)
            VALUES (:nama, :email, :password, :no_hp, \'ADMIN\', CURRENT_TIMESTAMP)
            RETURNING id_user
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':nama' => $data['nama'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':no_hp' => $data['no_hp']
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

    public function createPasswordResetToken($idUser, $tokenHash, $cooldownSeconds)
    {
        $this->pdo->beginTransaction();
        try {
            $lockUser = $this->pdo->prepare(
                "SELECT role,
                        CASE
                            WHEN password_reset_requested_at IS NULL
                              OR password_reset_requested_at <= CURRENT_TIMESTAMP
                                 - (CAST(:cooldown_seconds AS INTEGER) * INTERVAL '1 second')
                            THEN 1
                            ELSE 0
                        END AS can_request
                 FROM \"user\"
                 WHERE id_user = :id_user
                 FOR UPDATE"
            );
            $lockUser->execute([
                ':id_user' => $idUser,
                ':cooldown_seconds' => $cooldownSeconds,
            ]);
            $user = $lockUser->fetch(PDO::FETCH_ASSOC);
            if (!$user || $user['role'] !== 'CUSTOMER' || (int) $user['can_request'] !== 1) {
                $this->pdo->rollBack();
                return false;
            }

            $update = $this->pdo->prepare(
                'UPDATE "user"
                 SET password_reset_token_hash = :token_hash,
                     password_reset_expires_at = CURRENT_TIMESTAMP + INTERVAL \'10 minutes\',
                     password_reset_used_at = NULL,
                     password_reset_requested_at = CURRENT_TIMESTAMP
                 WHERE id_user = :id_user AND role = \'CUSTOMER\''
            );
            $update->execute([
                ':id_user' => $idUser,
                ':token_hash' => $tokenHash,
            ]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function resetCustomerPassword($tokenHash, $passwordHash)
    {
        $this->pdo->beginTransaction();
        try {
            $update = $this->pdo->prepare(
                'UPDATE "user"
                 SET password = :password,
                     password_reset_used_at = CURRENT_TIMESTAMP
                 WHERE password_reset_token_hash = :token_hash
                   AND password_reset_used_at IS NULL
                   AND password_reset_expires_at > CURRENT_TIMESTAMP
                   AND role = \'CUSTOMER\'
                 RETURNING id_user'
            );
            $update->execute([
                ':password' => $passwordHash,
                ':token_hash' => $tokenHash,
            ]);
            if ($update->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}