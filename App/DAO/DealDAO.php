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
        //verifica se ha  variavel de ambiente para filtrar as lojas
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = "WHERE id_store IN (" + getenv('FILTER_STORES') + ")";
        }

        $deals = $this->pdo
            ->query('SELECT * FROM deals ' . $where . ' ORDER BY id DESC LIMIT 10')
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $deals;
    }
}
