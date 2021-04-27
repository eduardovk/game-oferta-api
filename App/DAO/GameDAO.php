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
        $where = "";
        if (getenv('FILTER_STORES') && getenv('FILTER_STORES') != "") {
            $where = "AND id_store IN (" . getenv('FILTER_STORES') . ")";
        }

        $query = $this->pdo->prepare('SELECT * FROM game_deals WHERE game_plain = :plain ' . $where);
        $query->bindValue(':plain', $plain);
        $run = $query->execute();
        $game = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $game;
    }
}
