<?php

namespace App\DAO;

class StoreDAO extends Connection
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAllStores(): array
    {
        $stores = $this->pdo
            ->query('SELECT id, id_itad, title, color FROM stores ORDER BY title ASC')
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $stores;
    }
}
