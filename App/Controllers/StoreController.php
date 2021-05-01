<?php

namespace App\Controllers;

use App\DAO\StoreDAO;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
// use Psr\Http\Message\ResponseInterface as Response;

final class StoreController
{

    //retorna todas as lojas do bd
    //Ex.: game-oferta-api/stores
    public function getStores(Request $req, Response $res, array $args): Response
    {
        $storeDAO = new StoreDAO();
        $stores = $storeDAO->getAllStores();
        $res = $res->withJson($stores);
        return $res;
    }
}
