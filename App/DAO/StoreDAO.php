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
        //verifica se ha  variavel de ambiente para filtrar as lojas
        //se houver, quebra a string de ids e cria a clausula where
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = "WHERE (";
            $arr = explode(",", '5,11,14,33,35,37,42,43');
            for ($i = 0; $i < sizeof($arr); $i++) {
                $where .= (" id = " . $arr[$i]);
                if ($i < (sizeof($arr) - 1)) {
                    $where .= " OR";
                }
            }
            $where .= ") ";
            echo ($where);
        }

        $stores = $this->pdo
            ->query('SELECT id, id_itad, title, color FROM stores ' . $where . ' ORDER BY title ASC')
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $stores;
    }
}
