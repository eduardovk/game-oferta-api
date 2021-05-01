<?php

namespace App\DAO;

class StoreDAO extends Connection
{
    public function __construct()
    {
        parent::__construct();
    }

    //retorna todas as lojas do bd
    public function getAllStores(): array
    {
        //verifica se ha  variavel de ambiente para filtrar as lojas
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = "WHERE  id IN (" . getenv('FILTER_STORES') . ")";
        }
        $stores = $this->pdo
            ->query('SELECT id, id_itad, title, color FROM stores ' . $where . ' ORDER BY title ASC')
            ->fetchAll(\PDO::FETCH_ASSOC);
        return $stores;
    }
}
