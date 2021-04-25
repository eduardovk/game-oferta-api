<?php

namespace App\Controllers;

use App\DAO\DealDAO;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
// use Psr\Http\Message\ResponseInterface as Response;

final class DealController
{

    public function getDeals(Request $req, Response $res, array $args): Response
    {
        $dealDAO = new DealDAO();
        $deals = $dealDAO->getAllDeals();

        $res = $res->withJson($deals);
        return $res;
    }
}
