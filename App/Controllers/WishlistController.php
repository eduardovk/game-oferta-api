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
        $wishlists = null;
        if (isset($params['id']) && $params['id'] != '') { //caso informado id da wishlist
            $wishlists = $this->getWishlist($params['id']); //recebe wishlist conforme id
        } else {
            //se games ou deals = true, retorna junto os jogos de cada wishlist
            $withGames = (isset($params['games']) && $params['games'] === 'true') ? true : false;
            if (isset($params['username']) && $params['username'] != '') { //caso haja parametro de usuario
                //recebe wishlists do usuario
                $wishlists = $this->getWishlistsByUser($params['username'], ($withGames || $withDeals));
            } else { //caso nao tenha sido informado usuario
                $wishlists = $this->getAllWishlists(($withGames || $withDeals)); //recebe todas wishlists
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
        $res = $res->withJson($wishlists);
        return $res;
    }

    //retorna todas as wishlists do bd
    public function getAllWishlists($withGames)
    {
        $wishlistDAO = new WishlistDAO();
        $wishlists = $wishlistDAO->getAllWishlists($withGames);
        return $wishlists;
    }

    //retorna a wishlist conforme id informado
    public function getWishlist($id)
    {
        $wishlistDAO = new WishlistDAO();
        $wishlist = $wishlistDAO->getWishList($id);
        return $wishlist;
    }

    //retorna a wishlist conforme username informado
    public function getWishlistsByUser($username, $withGames)
    {
        $wishlistDAO = new WishlistDAO();
        $wishlist = $wishlistDAO->getWishListsByUser($username, $withGames);
        return $wishlist;
    }

    //adiciona jogo a wishlist conforme id da wishlist e id_game
    public function addToWishlist(Request $req, Response $res, array $args): Response
    {
        $data = $req->getParsedBody(); //recebe corpo do post
        $idWishlist = isset($data['id_wishlist']) ? $data['id_wishlist'] : false;
        $idGame = isset($data['id_game']) ? $data['id_game'] : false;
        $username = isset($data['username']) ? $data['username'] : false;
        $code = 400; //codigo http 400 (bad request)
        $wishlistDAO = new WishlistDAO();
        if (!$idGame) //id do jogo nao foi informado, retorna  msg de erro e codigo 400 (bad request)
            return $res->withJson('O ID do jogo não foi informado corretamente!', $code);
        if (!$idWishlist) { //se o id da wishlist nao foi informado
            if (!$username) { //username nao informado, retorna  msg de erro e codigo 400 (bad request)
                return $res->withJson('Não foram informados o username nem a id da wishlist!', $code);
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
}
