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

    //retorna o jogo informado pela plain e suas ofertas ativas
    public function getGameByPlain($plain)
    {

        //verifica se ha  variavel de ambiente para filtrar as lojas
        //se houver, quebra a string de ids e cria a clausula where
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = " AND (";
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

        $query = $this->pdo->prepare('SELECT * FROM game_deals WHERE game_plain = :plain' . $where);
        $query->bindValue(':plain', $plain);
        $run = $query->execute();
        $game = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $game;
    }
}
