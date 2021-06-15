<?php

namespace App\DAO;

use PDO;

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
    //caso $allOffers seja true, retorna todas deals de oferta
    //caso seja false, retorna apenas as deals ativas
    public function getGameDealsByPlain($plain, $allDeals, $allOffers, $storeFilter)
    {
        $where = "";
        $storeFilterArr = false;
        //configura clausula where de filtro de lojas
        if ($storeFilter) {
            $storeFilterArr = explode(',', $storeFilter);
            $where = $this->createParamsString($storeFilterArr);
        } else if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "")
            $where = " AND id_store IN (" . getenv('FILTER_STORES') . ") ";
        if ($allOffers)  //se allOffers = true, retorna apenas deals com desconto
            $where .= ' AND price_cut > 0 ';
        $table = ($allDeals || $allOffers) ? 'game_all_deals' : 'game_deals';  //seleciona entre uma das views
        $query = $this->pdo->prepare('SELECT * FROM ' . $table . ' WHERE game_plain = :plain ' . $where . ' 
        ORDER BY inserted_at DESC');
        $query->bindValue(':plain', $plain);
        if ($storeFilterArr) {
            for ($i = 0; $i < sizeof($storeFilterArr); $i++)
                $query->bindValue(':s' . $i, $storeFilterArr[$i]);
        }
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
        $allowed = array('id_game', 'rating_count'); //valores de orderby permitidos
        //como pdo filtra somente values, verifica manualmente se nao ha tentativa de sql injection
        if (!in_array($orderBy, $allowed)) $orderBy = 'rating_count';
        $query = $this->pdo->prepare('SELECT d.*, g.rating_count FROM game_deals as d '
            . 'INNER JOIN games AS g ON (d.id_game = g.id) WHERE d.id_game IN (' . $ids . ') '
            . $where . ' ORDER BY ' . $orderBy . ' ' . $order);
        $run = $query->execute();
        $games = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $games;
    }

    //retorna um array com ids de jogos que possuem deals ativas conforme limite e criterio de ordem 
    public function getIDsArray($limit, $orderBy, $order, $storeFilter, $minDiscount)
    {
        $allowed = array('id_game', 'rating_count'); //valores de orderby permitidos
        //como pdo filtra somente values, verifica manualmente se nao ha tentativa de sql injection
        if (!in_array($orderBy, $allowed)) $orderBy = 'rating_count';
        $query = null;
        $storeFilterArr = false;
        $where = "";
        //configura clausula where de filtro de lojas
        if ($storeFilter) {
            $storeFilterArr = explode(',', $storeFilter);
            $where = $this->createParamsString($storeFilterArr);
        }
        $query = $this->pdo->prepare('SELECT d.id_game  FROM deals AS d INNER JOIN games AS g ON (g.id = d.id_game) 
        WHERE d.current_deal = 1 ' . $where . ' AND d.price_cut >= :minDiscount GROUP BY d.id_game 
        ORDER BY ' . $orderBy . ' ' . $order . ' LIMIT :limit');
        $query->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $query->bindValue(':minDiscount', $minDiscount, \PDO::PARAM_INT);
        if ($storeFilterArr) {
            for ($i = 0; $i < sizeof($storeFilterArr); $i++)
                $query->bindValue(':s' . $i, $storeFilterArr[$i]);
        }
        $run = $query->execute();
        $queryResult = $query->fetchAll(\PDO::FETCH_ASSOC);
        $ids = []; //cria array de ids
        foreach ($queryResult as $result) { //para cada resultado da query
            if (isset($result['id_game'])) $ids[] = $result['id_game']; //adiciona campo id ao array
        }
        return $ids;
    }

    //retorna um array com sugestoes de auto-complete de nomes + plains
    public function getNameSuggestions($search)
    {
        //caso o termo de pesquisa seja menor q 4 caracteres, utiliza query LIKE termo%
        //caso tenha 4 caracteres ou mais, utilzia query LIKE %termo%
        $search = strlen($search) < 4 ? $search . '%' : '%' . $search . '%';
        $query = $this->pdo->prepare('SELECT name as label, name as value, plain, id FROM games WHERE (name '
            . 'LIKE :search OR alt_name_1 LIKE :search OR alt_name_2 LIKE :search) '
            . ' AND plain IS NOT NULL AND active = 1 ORDER BY rating_count DESC LIMIT 10');
        $query->bindValue(':search', $search);
        $run = $query->execute();
        $queryResult = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $queryResult;
    }

    //retorna array de ids do resultado da busca de jogo por nome
    public function searchGame($name, $limit, $storeFilter, $minDiscount)
    {
        //caso o termo de pesquisa seja menor q 4 caracteres, utiliza query LIKE termo%
        //caso tenha 4 caracteres ou mais, utilzia query LIKE %termo%
        $search = strlen($name) < 4 ? $name . '%' : '%' . $name . '%';
        $query = null;
        $where = "";
        $storeFilterArr = false;
        //configura clausula where de filtro de lojas
        if ($storeFilter) {
            $storeFilterArr = explode(',', $storeFilter);
            $where = $this->createParamsString($storeFilterArr);
        }
        $query = $this->pdo->prepare('SELECT g.id FROM games AS g INNER JOIN deals AS d ON (g.id = d.id_game)
            WHERE d.current_deal = 1 ' . $where . ' AND (g.name LIKE :search OR g.alt_name_1 LIKE :search 
            OR g.alt_name_2 LIKE :search) AND g.plain IS NOT NULL AND g.active = 1 AND d.price_cut >= :minDiscount 
            GROUP BY g.name ORDER BY g.rating_count DESC LIMIT :limit');
        $query->bindValue(':search', $search);
        $query->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $query->bindValue(':minDiscount', $minDiscount, \PDO::PARAM_INT);
        if ($storeFilterArr) {
            for ($i = 0; $i < sizeof($storeFilterArr); $i++)
                $query->bindValue(':s' . $i, $storeFilterArr[$i]);
        }
        $run = $query->execute();
        $queryResult = $query->fetchAll(\PDO::FETCH_ASSOC);
        $ids = array(); //cria array de ids
        foreach ($queryResult as $id) { //para cada resultado
            if (isset($id['id'])) $ids[] = $id['id']; //insere id no array
        }
        return $ids;
    }

    //cria string de parametros para clausula IN do MySQL
    public function createParamsString($paramsArr)
    {
        $params = "";
        for ($i = 0; $i < sizeof($paramsArr) - 1; $i++)
            $params .= ":s" . $i . ",";
        $params .= ":s" . (sizeof($paramsArr) - 1);
        $where = " AND id_store IN ($params) ";
        return $where;
    }
}
