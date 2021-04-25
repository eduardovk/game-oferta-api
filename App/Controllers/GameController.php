<?php

namespace App\Controllers;

use App\DAO\GameDAO;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
// use Psr\Http\Message\ResponseInterface as Response;

final class GameController
{

    //retorna todos os jogos ativos no bd
    public function getGames(Request $req, Response $res, array $args): Response
    {
        $gameDAO = new GameDAO();
        $games = $gameDAO->getAllGames();

        $res = $res->withJson($games);
        return $res;
    }

    //retorna o jogo informado pela plain e suas ofertas ativas
    public function getGameByPlain(Request $req, Response $res, array $args): Response
    {
        $gameDAO = new GameDAO();
        $game = $gameDAO->getGameByPlain($args['plain']);

        $res = $res->withJson($game);
        return $res;
    }
}
