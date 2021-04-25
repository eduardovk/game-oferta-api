<?php

namespace App\DAO;

class DealDAO extends Connection
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAllDeals(): array
    {
        $deals = $this->pdo
            ->query('SELECT * FROM deals ORDER BY id DESC LIMIT 10')
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $deals;
    }
}
