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
            ->query('SELECT * FROM games ORDER BY id DESC')
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $games;
    }

    //retorna o jogo informado pela plain e suas ofertas ativas
    public function getGameByPlain($plain)
    {
        $query = $this->pdo->prepare('SELECT * FROM game_deals WHERE game_plain = :plain');
        $query->bindValue(':plain', $plain);
        $run = $query->execute();
        $game = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $game;
    }
}
