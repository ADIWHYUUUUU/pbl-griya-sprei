<?php

class CartModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function isStorageReady(): bool
    {
        $statement = $this->pdo->query("SELECT to_regclass('keranjang_pelanggan')");
        $tableName = $statement->fetchColumn();
        return $tableName !== false && $tableName !== null;
    }

    public function getCart(int $userId): array
    {
        $this->assertStorageReady();
        $statement = $this->pdo->prepare(
            'SELECT item_id, item_data
            FROM keranjang_pelanggan
            WHERE id_user = :id_user
            ORDER BY created_at, item_id'
        );
        $statement->execute([':id_user' => $userId]);

        $cart = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $item = json_decode($row['item_data'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($item)) {
                throw new UnexpectedValueException('Data item keranjang tidak valid.');
            }
            $cart[$row['item_id']] = $item;
        }

        return $cart;
    }

    public function saveItem(int $userId, string $itemId, array $item): void
    {
        $this->assertStorageReady();
        $statement = $this->pdo->prepare(
            'INSERT INTO keranjang_pelanggan (id_user, item_id, item_data)
            VALUES (:id_user, :item_id, CAST(:item_data AS JSONB))
            ON CONFLICT (id_user, item_id)
            DO UPDATE SET item_data = EXCLUDED.item_data, updated_at = now()'
        );
        $statement->execute([
            ':id_user' => $userId,
            ':item_id' => $itemId,
            ':item_data' => json_encode($item, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function removeItem(int $userId, string $itemId): void
    {
        $this->assertStorageReady();
        $statement = $this->pdo->prepare(
            'DELETE FROM keranjang_pelanggan
            WHERE id_user = :id_user AND item_id = :item_id'
        );
        $statement->execute([
            ':id_user' => $userId,
            ':item_id' => $itemId,
        ]);
    }

    private function assertStorageReady(): void
    {
        if (!$this->isStorageReady()) {
            throw new RuntimeException(
                'Penyimpanan keranjang belum tersedia. Jalankan migrasi 06_keranjang_pelanggan.sql.'
            );
        }
    }

}
