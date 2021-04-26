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
        //se houver, quebra a string de ids e cria a clausula where
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = " WHERE (";
            $arr = explode(",", '5,11,14,33,35,37,42,43');
            for ($i = 0; $i < sizeof($arr); $i++) {
                $where .= (" id_store = " . $arr[$i]);
                if ($i < (sizeof($arr) - 1)) {
                    $where .= " OR";
                }
            }
            $where .= ") ";
            echo ($where);
        }

        $deals = $this->pdo
            ->query('SELECT * FROM deals ' . $where . ' ORDER BY id DESC LIMIT 10')
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $deals;
    }
}
