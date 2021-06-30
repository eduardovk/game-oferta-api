<?php

namespace App\Controllers;

use App\DAO\GameDAO;
use App\DAO\WishlistDAO;
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
    //Ex.: game-oferta-api/games_deals?game=doom
    public function getGameByPlain(Request $req, Response $res, array $args): Response
    {
        $gameDAO = new GameDAO();
        $plain = $args['plain']; //recebe plain como parametro get da url
        $games = $gameDAO->getGame($plain);
        $res = $res->withJson($games);
        return $res;
    }

    //retorna o jogo informado pela plain e suas ofertas ativas
    //Ex.: game-oferta-api/games_deals?game=falloutii&all_deals=true&group_stores=true
    public function getGameDealsByPlain(Request $req, Response $res, array $args): Response
    {
        $params = $req->getQueryParams(); //recebe parametros get da url
        $plain = $params['game']; //recebe plain como parametro get da url
        //array com informacoes do jogo
        $gameDeals =  array(
            'plain' => $plain,
            'name' => '',
            'id' => '',
            'cover' => '',
            'deals' => array() //array de ofertas do jogo
        );
        $gameDAO = new GameDAO();
        //verifica se ha parametro para filtro de lojas
        $storeFilter = isset($params['store_filter']) && $params['store_filter'] != 'false' ? $params['store_filter'] : false;
        $allDeals = false; //caso haja parametro all_deals e seja true, retorna todas as deals
        if (isset($params['all_deals']) && $params['all_deals'] == 'true') $allDeals = true;
        $allOffers = false; //caso haja parametro all_offers e seja true, retorna apenas as ofertas
        if (isset($params['all_offers']) && $params['all_offers'] == 'true') $allOffers = true;
        $groupStores = false; //caso haja parametro group_stores e seja true, agroupa as deals por loja
        if (isset($params['group_stores']) && $params['group_stores'] == 'true') $groupStores = true;
        $gameInfo = $gameDAO->getGame($plain); //pega informacoes do jogo no bd
        if ($gameInfo) { //se encontrar o jogo no bd
            //preenche array com informacoes do jogo
            $gameDeals['name'] = $gameInfo['name'];
            $gameDeals['id'] = $gameInfo['id'];
            $gameDeals['cover'] = 'https:' . $gameInfo['igdb_cover'];
            $deals = $gameDAO->getGameDealsByPlain($plain, $allDeals, $allOffers, $storeFilter); //busca as deals do jogo
            foreach ($deals as $deal) { //para cada deal encontrada
                $phpdate = strtotime($deal['inserted_at']); //formata data
                $date = date('d/m/Y H:i:s', $phpdate);
                //seleciona informacoes relevantes da deal e insere no array
                $dealInfo = array(
                    'id_deal' => $deal['id'],
                    'id_store' => $deal['id_store'],
                    'store_name' => $deal['store_name'],
                    'store_plain' => $deal['id_itad'],
                    'price_old' => $deal['price_old'],
                    'price_new' => $deal['price_new'],
                    'price_cut' => $deal['price_cut'],
                    'url' => isset($deal['url']) ? $deal['url'] : null,
                    'active' => $deal['current_deal'] === "1" ? true : false,
                    'date' => $date
                );
                if ($groupStores) { //se group_stores = true, agrupa as deals por loja
                    $store = $deal['id_itad'];
                    if (!isset($gameDeals['deals'][$store])) {
                        $gameDeals['deals'][$store] = [];
                    }
                    array_push($gameDeals['deals'][$store], $dealInfo);
                } else {
                    //insere array da deal no array principal
                    array_push($gameDeals['deals'], $dealInfo);
                }
            }
        }
        $res = $res->withJson($gameDeals);
        return $res;
    }

    //retorna jogos + deals de acordo com ordenacao e limite informados
    //caso haja parametro 'term', busca por jogos de nome igual ou semelhante
    //caso haja parametro 'username', exibe se o jogo ja esta na wishlist
    //Ex.: game-oferta-api/games_deals?orderby=rating_count&order=desc
    //ou game-oferta-api/games_deals?term=gta&limit=5
    //ou game-oferta-api/games_deals?term=gta&username=fulano
    public function getGamesAndDeals(Request $req, Response $res, array $args): Response
    {
        $params = $req->getQueryParams(); //recebe parametros get da url
        if (isset($params['game']) && $params['game'] != '') {
            return $this->getGameDealsByPlain($req, $res, $args);
        }
        $ids = array(); //cria array de ids dos jogos a serem pesquisados
        //verifica se ha parametros orderby e order, caso contrario utiliza padrao
        $orderBy = isset($params['orderby']) ? $params['orderby'] : 'rating_count';
        $order = (isset($params['order']) && strtoupper($params['order']) == 'ASC') ? 'ASC' : 'DESC';
        //verifica se ha parametro para filtro de lojas
        $storeFilter = isset($params['store_filter']) && $params['store_filter'] != 'false' ? $params['store_filter'] : false;
        //verifica se ha parametro de desconto minimo
        $minDiscount = isset($params['min_discount']) && $params['min_discount'] != '' ? $params['min_discount'] : 0;
        //verifica se ha parametros de faixa de preco
        $priceRange = isset($params['price_range']) && $params['price_range'] != '' ? $params['price_range'] : false;
        $limit = 20; //limite de jogos caso parametro nao seja informado
        if (isset($params['limit']) && $params['limit'] > 0 && $params['limit'] <= 20) {
            $limit = $params['limit']; //recebe parametro limit
        }
        //se houver parametro de busca
        if (isset($params['term']) && $params['term'] != '') {
            $search = $params['term']; //recebe o termo de busca
            $gameDAO = new GameDAO();
            //busca jogo no bd e retorna ids dos resultados
            $ids = $gameDAO->searchGame($search, $limit, $storeFilter, $minDiscount, $priceRange);
        } else {
            //retorna deals ativas de todos os jogos de acordo com o limite
            //e o criterio de ordem especificados nos parametros get na url
            $gameDAO = new GameDAO();
            //recebe do bd um array com ids de jogos que possuem deals ativas conforme limite e criterio de ordem
            $ids = $gameDAO->getIDsArray($limit, $orderBy, $order, $storeFilter, $minDiscount, $priceRange);
        }
        if (!$ids || sizeof($ids) < 1) return $res->withJson(array()); //se nao houver resultados, retorna vazio
        $results = $gameDAO->getGamesDealsByIDArray($ids, $orderBy, $order);
        $finalArray = $this->createGameDealsArray($results); //cria array estruturado de jogos + deals
        //ha parametro de usuario (se user esta logado)
        if (isset($params['username']) && $params['username'] != '') {
            //para cada jogo, verifica se esta presente na wishlsit do usuario (caso logado)
            $finalArray = $this->checkIfOnWishlist($finalArray, $params['username']);
        }
        $res = $res->withJson($finalArray); //retorna array
        return $res;
    }


    //retorna 6 jogos mais procurados, 6 jogos temporariamente gratuitos, 6 jogos recentes, 6 jogos free to play
    public function getHomePageGames(Request $req, Response $res, array $args): Response
    {
        $username = isset($params['username']) && $params['username'] != '' ? $params['username'] : false;
        $gameDAO = new GameDAO();
        $gamesTypesIDs = $gameDAO->getHomePageGames(); //TODO CRIAR METODO GETHOMEPAGEGAMES
        foreach ($gamesTypesIDs as &$typeIDs) { //para cada categoria e seus ids (ex.: jogos recentes > 34, 657, 4356)
            $gamesDeals = $gameDAO->getGamesDealsByIDArray($typeIDs, 'rating_count', 'DESC'); //procura as deals e info dos jogos
            $typeIDs = $this->createGameDealsArray($gamesDeals); //cria array estruturado de jogos + deals
            if ($username) //para cada jogo, verifica se esta presente na wishlsit do usuario (caso logado)
                $typeIDs = $this->checkIfOnWishlist($typeIDs, $username);
        }
        $res = $res->withJson($gamesTypesIDs); //retorna array
        return $res;
    }


    //recebe sinal da Engine para atualizar jogos da homepage 
    //(6 mais desejados, 6 temporariamente grauitos, 6 recentess, 6 free to play)
    public function updateHomePageGames(Request $req, Response $res, array $args): Response
    {
        $gameDAO = new GameDAO();
        $result = $gameDAO->updateHomePageGames();
        $msg = $result;
        $code = 200;
        if (!$result) {
            $msg = "Erro ao atualizar jogos da homepage!";
            $code = 500;
        }
        return $res->withJson($msg, $code);
    }

    //retorna array estruturado de jogos + deals
    public function createGameDealsArray($queryResults)
    {
        $games = []; //cria array de jogos selecionados
        foreach ($queryResults as $result) { //para cada resultado da query
            if (!isset($games[$result['game_plain']])) { //caso game ainda nao esteja no array
                $games[$result['game_plain']] = array( //adiciona game no array com suas informacoes
                    'plain' => $result['game_plain'],
                    'name' => $result['game_name'],
                    'id' => $result['id_game'],
                    'cover' => $result['game_cover'],
                    'on_wishlist' => false,
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
                'price_cut' => $result['price_cut'],
                'url' => isset($result['url']) ? $result['url'] : null
            );
            //insere informacoes da deal no array deals do jogo
            array_push($games[$result['game_plain']]['deals'], $dealInfo);
        }
        //organiza em um novo array e devolve como resposta
        $finalArray = array();
        foreach ($games as $game) {
            $finalArray[] = $game;
        }
        return $finalArray;
    }

    //adiciona campo indicando se jogo esta presente na wishlist do usuario
    public function checkIfOnWishlist($gamesArray, $username)
    {
        $ids = array(); //cria array de ids dos jogos
        foreach ($gamesArray as &$game) {
            $ids[] = $game['id']; //para cada jogo, adiciona id ao array
        }
        $wishlistDAO = new WishlistDAO();
        //verifica quais dos ids de games estao na wishlist do usuario
        $idsOnWishlist = $wishlistDAO->checkGamesIDArray($ids, $username);
        foreach ($gamesArray as &$game) {
            if (in_array($game['id'], $idsOnWishlist)) {
                $game['on_wishlist'] = true;
            }
        }
        return $gamesArray;
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
