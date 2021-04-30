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

    //retorna informacoes sobre o jogo atraves da plain informada
    public function getGameByPlain(Request $req, Response $res, array $args): Response
    {
        $gameDAO = new GameDAO();
        $plain = $args['plain']; //recebe plain como parametro get da url
        $games = $gameDAO->getGameByPlain($plain);
        $res = $res->withJson($games);
        return $res;
    }

    //retorna o jogo informado pela plain e suas ofertas ativas
    public function getGameDealsByPlain(Request $req, Response $res, array $args): Response
    {
        $plain = $args['plain']; //recebe plain como parametro get da url
        //array com informacoes do jogo
        $gameDeals =  array(
            'plain' => $plain,
            'name' => '',
            'id' => '',
            'cover' => '',
            'deals' => array() //array de ofertas do jogo
        );
        $gameDAO = new GameDAO();
        //caso haja parametro all_deals e seja true, retorna todas as deals
        $params = $req->getQueryParams();
        $allDeals = false;
        if (isset($params['all_deals']) && $params['all_deals'] == 'true') $allDeals = true;
        $gameInfo = $gameDAO->getGameByPlain($plain); //pega informacoes do jogo no bd
        if ($gameInfo) { //se encontrar o jogo no bd
            //preenche array com informacoes do jogo
            $gameDeals['name'] = $gameInfo['name'];
            $gameDeals['id'] = $gameInfo['id'];
            $gameDeals['cover'] = 'https:' . $gameInfo['igdb_cover'];
            $deals = $gameDAO->getGameDealsByPlain($plain, $allDeals); //busca as deals do jogo
            foreach ($deals as $deal) { //para cada deal encontrada
                //seleciona informacoes relevantes da deal e insere no array
                $dealInfo = array(
                    'id_deal' => $deal['id'],
                    'id_store' => $deal['id_store'],
                    'store_name' => $deal['store_name'],
                    'store_plain' => $deal['id_itad'],
                    'price_old' => $deal['price_old'],
                    'price_new' => $deal['price_new'],
                    'price_cut' => $deal['price_cut']
                );
                //insere array da deal no array principal
                array_push($gameDeals['deals'], $dealInfo);
            }
        }
        $res = $res->withJson($gameDeals);
        return $res;
    }
}
