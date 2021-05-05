<?php

namespace App\Controllers;

use App\DAO\GameDAO;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
// use Psr\Http\Message\ResponseInterface as Response;

final class GameController
{

    //retorna todos os jogos ativos no bd
    //Ex.: game-oferta-api/games
    public function getGames(Request $req, Response $res, array $args): Response
    {
        $gameDAO = new GameDAO();
        $games = $gameDAO->getAllGames();
        $res = $res->withJson($games);
        return $res;
    }

    //retorna informacoes sobre o jogo atraves da plain informada
    //Ex.: game-oferta-api/game/doom
    public function getGameByPlain(Request $req, Response $res, array $args): Response
    {
        $gameDAO = new GameDAO();
        $plain = $args['plain']; //recebe plain como parametro get da url
        $games = $gameDAO->getGameByPlain($plain);
        $res = $res->withJson($games);
        return $res;
    }

    //retorna o jogo informado pela plain e suas ofertas ativas
    //Ex.: game-oferta-api/game_deals/falloutii?all_deals=false
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

    //retorna deals ativas de todos os jogos de acordo com o limite
    //e o criterio de ordem especificados nos parametros get na url
    //Ex.: game-oferta-api/games_deals?limit=5&orderby=id_game&order=DESC
    public function getGamesDeals(Request $req, Response $res, array $args): Response
    {
        $params = $req->getQueryParams(); //recebe parametros get da url
        $limit = 20; //limite de jogos caso parametro nao seja informado
        if (isset($params['limit']) && $params['limit'] > 0 && $params['limit'] <= 20) {
            $limit = $params['limit']; //recebe parametro limit
        }
        //verifica se ha parametros orderby e order, caso contrario utiliza padrao
        $orderBy = isset($params['orderby']) ? $params['orderby'] : 'rating_count';
        $order = (isset($params['order']) && strtoupper($params['order']) == 'ASC') ? 'ASC' : 'DESC';
        $games = []; //cria array de jogos selecionados
        $gameDAO = new GameDAO();
        //recebe do bd um array com ids de jogos que possuem deals ativas conforme limite e criterio de ordem
        $ids = $gameDAO->getIDsArray($limit, $orderBy, $order);
        $results = $gameDAO->getGamesDealsByIDArray($ids, $orderBy, $order);
        foreach ($results as $result) { //para cada resultado da query
            if (!isset($games[$result['game_plain']])) { //caso game ainda nao esteja no array
                $games[$result['game_plain']] = array( //adiciona game no array com suas informacoes
                    'plain' => $result['game_plain'],
                    'name' => $result['game_name'],
                    'id' => $result['id_game'],
                    'cover' => $result['game_cover'],
                    'deals' => array() //array de ofertas do jogo
                );
            }
            //estrutura informacoes da deal
            $dealInfo = array(
                'id_deal' => $result['id'],
                'id_store' => $result['id_store'],
                'store_name' => $result['store_name'],
                'store_plain' => $result['id_itad'],
                'price_old' => $result['price_old'],
                'price_new' => $result['price_new'],
                'price_cut' => $result['price_cut']
            );
            //insere informacoes da deal no array deals do jogo
            array_push($games[$result['game_plain']]['deals'], $dealInfo);
        }
        //organiza em um novo array e devolve como resposta
        $finalArray = array();
        foreach ($games as $game) {
            $finalArray[] = $game;
        }
        $res = $res->withJson($finalArray);
        return $res;
    }

    //retorna array com sugestoes de auto-complete de nome + plain
    public function getNameSuggestions(Request $req, Response $res, array $args): Response
    {
        $params = $req->getQueryParams(); //recebe parametros get da url
        $search = $params['term']; //recebe o termo de busca
        $gameDAO = new GameDAO();
        $nameSuggestions = $gameDAO->getNameSuggestions($search);
        $res = $res->withJson($nameSuggestions);
        return $res;
    }
}
