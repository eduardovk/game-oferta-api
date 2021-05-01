<?php

namespace App\Controllers;

use App\DAO\DealDAO;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
// use Psr\Http\Message\ResponseInterface as Response;

final class DealController
{
    //retorna todas as deals do  de acordo com o limite informado
    //Ex.: 
    public function getDeals(Request $req, Response $res, array $args): Response
    {
        $params = $req->getQueryParams(); //recebe parametros get da url
        $limit = 100; //limite de jogos caso parametro nao seja informado
        if (isset($params['limit']) && $params['limit'] > 0 && $params['limit'] <= 100) {
            $limit = $params['limit']; //recebe parametro limit
        }
        $dealDAO = new DealDAO();
        $deals = $dealDAO->getAllDeals($limit);
        $res = $res->withJson($deals);
        return $res;
    }
}
