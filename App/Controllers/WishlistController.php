<?php

namespace App\Controllers;

use App\DAO\WishlistDAO;
use App\DAO\GameDAO;
use App\Controllers\GameController;
use App\DAO\UserDAO;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
// use Psr\Http\Message\ResponseInterface as Response;

final class WishlistController
{

    // /wishlists  ->  todas as wishlists (sem jogos nem deals)
    // /wishlists?id=1  ->  wishlist de ID 1
    // /wishlists?id=1&games=true  ->  wishlist de ID 1 (com jogos)
    // /wishlists?id=1&deals=true  ->  wishlist de ID 1 (com jogos e deals)
    // /wishlists?username=fulano&deals=true  ->  wishlist do usuario 'fulano' (com jogos e deals)
    public function getWishlists(Request $req, Response $res, array $args): Response
    {
        $params = $req->getQueryParams(); //recebe parametros get da url
        //recebe parametro deals
        $withDeals = (isset($params['deals']) && $params['deals'] === 'true') ? true : false;
        //recebe parametro de busca
        $term = (isset($params['term']) && $params['term'] != '') ? $params['term'] : false;
        $detailed = (isset($params['detailed']) && $params['detailed'] === 'true') ? true : false;
        $wishlists = null;
        if (isset($params['id']) && $params['id'] != '') { //caso informado id da wishlist
            $wishlists = $this->getWishlist($params['id'], $term); //recebe wishlist conforme id
        } else {
            //se games ou deals = true, retorna junto os jogos de cada wishlist
            $withGames = (isset($params['games']) && $params['games'] === 'true') ? true : false;
            if (isset($params['username']) && $params['username'] != '') { //caso haja parametro de usuario
                //recebe wishlists do usuario
                $wishlists = $this->getWishlistsByUser($params['username'], ($withGames || $withDeals), $term);
            } else { //caso nao tenha sido informado usuario
                $wishlists = $this->getAllWishlists(($withGames || $withDeals), $term); //recebe todas wishlists
            }
        }
        if ($withDeals) { //se deals = true, retorna as deals ativas de cada jogo da wishlist
            foreach ($wishlists as &$wishlist) { //para cada wishlist retornada
                $gameIDArray = array(); //cria array de ids de jogo da wishlist
                foreach ($wishlist['games'] as $game) { //para cada jogo da wishlist
                    $gameIDArray[] = $game['id_game']; //adiciona id do jogo ao array de ids
                }
                $wishlist['games'] = $gameIDArray; //array de game ids adicionado a wishlist
            }
            $gameDAO = new GameDAO();
            foreach ($wishlists as &$wishlist) { //para cada wishlist retornada
                //solicita ao bd os jogos e suas deals por meio do array de ids
                $gamesAndDeals = $gameDAO->getGamesDealsByIDArray($wishlist['games'], 'rating_count', 'DESC');
                $gameController = new GameController();
                //cria array estruturado de games + deals
                $wishlist['games'] = $gameController->createGameDealsArray($gamesAndDeals);
            }
        }
        //para cada game da wishlist, configura campo on_wishlist como true
        if (sizeof($wishlists) > 0) {
            foreach ($wishlists as &$wishlist) {
                if (isset($wishlist['games']) && sizeof($wishlist['games']) > 0) {
                    foreach ($wishlist['games'] as &$game) {
                        $game['on_wishlist'] = true;
                    }
                }
            }
        }
        if ($detailed) { //se detailed = true, retorna todos dados das wishlists do usuario + jogos
            $res = $res->withJson($wishlists);
        } else if (($withGames || $withDeals) && sizeof($wishlists) > 0) { //se detailed = false, retorna apenas os jogos da primeira wishlist
            $res = $res->withJson($wishlists[0]['games']);
        } else { //caso contrario retorna array vazio
            $res = $res->withJson(array());
        }
        return $res;
    }

    //retorna todas as wishlists do bd
    public function getAllWishlists($withGames, $term)
    {
        $wishlistDAO = new WishlistDAO();
        $wishlists = $wishlistDAO->getAllWishlists($withGames, $term);
        return $wishlists;
    }

    //retorna a wishlist conforme id informado
    public function getWishlist($id, $term)
    {
        $wishlistDAO = new WishlistDAO();
        $wishlist = $wishlistDAO->getWishList($id, $term);
        return $wishlist;
    }

    //retorna a wishlist conforme username informado
    public function getWishlistsByUser($username, $withGames, $term)
    {
        $wishlistDAO = new WishlistDAO();
        $wishlist = $wishlistDAO->getWishListsByUser($username, $withGames, $term);
        return $wishlist;
    }

    //adiciona jogo a wishlist conforme id da wishlist e id_game
    public function addToWishlist(Request $req, Response $res, array $args): Response
    {
        $data = $req->getParsedBody(); //recebe corpo do post
        $idWishlist = isset($data['id_wishlist']) ? $data['id_wishlist'] : false;
        $idGame = isset($data['id_game']) ? $data['id_game'] : false;
        $username = isset($data['username']) ? $data['username'] : false;
        $wishlistDAO = new WishlistDAO();
        if (!$idGame) //id do jogo nao foi informado, retorna  msg de erro e codigo 400 (bad request)
            return $res->withJson('O ID do jogo não foi informado corretamente!', 400);
        if (!$idWishlist) { //se o id da wishlist nao foi informado
            if (!$username) { //username nao informado, retorna  msg de erro e codigo 400 (bad request)
                return $res->withJson('Não foram informados o username nem a id da wishlist!', 400);
            } else {
                //busca as wishlists do usuario
                $userWishlists = $wishlistDAO->getWishListsByUser($username);
                if (sizeof($userWishlists) > 0) { //se user possuir wishlists
                    $idWishlist = $userWishlists[0]['id']; //pega a primeira wishlist do usuario
                } else { //se user ainda nao possuir nenhuma wishlist
                    $userDAO = new UserDAO();
                    $user = $userDAO->getUser($username); //busca informacoes do usuario
                    $newWishlist = array( //cria nova wishlist
                        'id_user' => $user['id'],
                        'email' => $user['email'],
                        'title' => 'Lista de desejos de ' . $username,
                        'description' => '',
                        'public' => 1
                    );
                    //insere nova wishlist e recebe o id dela
                    $idWishlist = $wishlistDAO->insertWishlist($newWishlist);
                }
            }
        }
        $queryResult = $wishlistDAO->addToWishlist($idWishlist, $idGame); //insere no bd
        if ($queryResult) //tudo ok, retorna msg de sucesso e codigo 201 (created)
            return $res->withJson('Jogo adicionado à Wishlist com sucesso!', 201);
        //erro na query, retorna msg de erro e codigo 500 (server error)
        return $res->withJson('Erro ao adicionar jogo à Wishlist.', 500);
    }

    //remove jogo das wishlists do usuario conforme id_game e username informados
    public function removeFromWishlist(Request $req, Response $res, array $args): Response
    {
        $params = $req->getQueryParams(); //recebe parametros get da url
        $idGame = isset($params['id_game']) ? $params['id_game'] : false;
        $username = isset($params['username']) ? $params['username'] : false;
        if (!$idGame || !$username) { //id_game ou username nao informados, retorna erro 400 (bad request)
            return $res->withJson('O ID do jogo ou username não foi informado corretamente!', 400);
        }
        $wishlistDAO = new WishlistDAO();
        $queryResult = $wishlistDAO->removeFromWishlist($idGame, $username);
        if ($queryResult) //tudo ok, retorna msg de sucesso e codigo 200 (OK)
            return $res->withJson('Jogo removido da Wishlist com sucesso!', 200);
        //erro na query, retorna msg de erro e codigo 500 (server error)
        return $res->withJson('Erro ao remover jogo da Wishlist.', 500);
    }
}
