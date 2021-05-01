<?php

namespace App\DAO;

class GameDAO extends Connection
{
    public function __construct()
    {
        parent::__construct();
    }

    //retorna todos os jogos ativos no bd
    public function getAllGames(): array
    {
        $games = $this->pdo
            ->query('SELECT * FROM games WHERE active = 1 ORDER BY id DESC')
            ->fetchAll(\PDO::FETCH_ASSOC);
        return $games;
    }

    //retorna todos os campos de um jogo a partir de sua plain
    public function getGameByPlain($plain)
    {
        //seleciona jogo cuja plain nao seja duplicada ou nao conferida
        $query = $this->pdo->prepare('SELECT * FROM games WHERE active = 1 AND '
            . '(duplicate_plain IS NULL OR duplicate_checked IS NOT NULL) AND plain  = :plain');
        $query->bindValue(':plain', $plain);
        $run = $query->execute();
        $game = $query->fetchAll(\PDO::FETCH_ASSOC);
        if ($game) return $game[0];
        return null;
    }

    //retorna o jogo informado pela plain e suas deals
    //caso $allDeals seja true, retorna todas deals
    //caso seja false, retorna apenas as deals ativas
    public function getGameDealsByPlain($plain, $allDeals)
    {
        //verifica se ha variavel de ambiente para filtrar as lojas
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = "AND id_store IN (" . getenv('FILTER_STORES') . ")";
        }
        $table = $allDeals ? 'game_all_deals' : 'game_deals';  //seleciona entre uma das views
        $query = $this->pdo->prepare('SELECT * FROM ' . $table . ' WHERE game_plain = :plain ' . $where);
        $query->bindValue(':plain', $plain);
        $run = $query->execute();
        $game = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $game;
    }

    //retorna os jogos e suas deals ativos informados no array de ids
    public function getGamesDealsByIDArray($idArray, $orderBy, $order)
    {
        //verifica se ha variavel de ambiente para filtrar as lojas
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = "AND id_store IN (" . getenv('FILTER_STORES') . ")";
        }
        $ids = ''; //string de ids para utilizar na query
        foreach ($idArray as $id) { //para cada id do array
            $ids .= $id . ','; //adiciona id a string
        }
        $ids = substr($ids, 0, -1); //remove ultima virgula da string
        if ($orderBy != 'id_game') $orderBy = 'id_game'; //TO-DO adicionar outras formas de ordenacao
        $query = $this->pdo->prepare('SELECT * FROM game_deals WHERE id_game IN (' . $ids . ') ' . $where
            . ' ORDER BY ' . $orderBy . ' ' . $order);
        $run = $query->execute();
        $games = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $games;
    }

    //retorna um array com ids de jogos que possuem deals ativas conforme limite e criterio de ordem 
    public function getIDsArray($limit, $orderBy, $order)
    {
        //verifica se ha variavel de ambiente para filtrar as lojas
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = "AND id_store IN (" . getenv('FILTER_STORES') . ")";
        }
        if ($orderBy != 'id_game') $orderBy = 'id_game'; //TO-DO adicionar outras formas de ordenacao
        $query = $this->pdo->prepare('SELECT id_game FROM deals WHERE current_deal = 1'
            . ' GROUP BY id_game ORDER BY ' . $orderBy . ' ' . $order . ' LIMIT :limit');
        $query->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $run = $query->execute();
        $queryResult = $query->fetchAll(\PDO::FETCH_ASSOC);
        $ids = []; //cria array de ids
        foreach ($queryResult as $result) { //para cada resultado da query
            $ids[] = $result['id_game']; //adiciona campo id ao array
        }
        return $ids;
    }
}
