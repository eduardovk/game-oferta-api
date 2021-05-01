<?php

namespace App\DAO;

class DealDAO extends Connection
{
    public function __construct()
    {
        parent::__construct();
    }

    //retorna todas as deals do bd em ordem decrescente de acordo com o limite informado
    public function getAllDeals($limit): array
    {
        //verifica se ha  variavel de ambiente para filtrar as lojas
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = "WHERE id_store IN (" . getenv('FILTER_STORES') . ")";
        }
        $query = $this->pdo->prepare('SELECT * FROM deals ' . $where . ' ORDER BY id DESC LIMIT :limit');
        $query->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $run = $query->execute();
        $deals = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $deals;
    }
}
