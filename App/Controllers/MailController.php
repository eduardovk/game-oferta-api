<?php

namespace App\Controllers;

use App\Mailer;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
// use Psr\Http\Message\ResponseInterface as Response;

final class MailController
{
    //retorna todas as deals do  de acordo com o limite informado
    //Ex.: 
    public function sendEmail(Request $req, Response $res, array $args): Response
    {
        $mailer = new Mailer();
        $result = $mailer->sendEmail();
        $res = $res->withJson($result);
        return $res;
    }
}