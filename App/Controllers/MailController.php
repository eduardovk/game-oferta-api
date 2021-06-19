<?php

namespace App\Controllers;

use App\Mailer;
use App\DAO\GameDAO;
use App\DAO\UserDAO;
use App\DAO\MailDAO;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
// use Psr\Http\Message\ResponseInterface as Response;

final class MailController
{

    //envia email de notificacao de oferta
    public function sendEmail(Request $req, Response $res, array $args): Response
    {
        $data = $req->getParsedBody(); //recebe corpo do post
        //cria array com informacoes do email/notificacao
        $notification = array(
            'id_user' => $data['id_user'],
            'new_offers' => $data['new_offers']
        );
        $gameIDs = array();
        $userDAO = new UserDAO();
        $userInfo = $userDAO->getUser($notification['id_user'], 'id'); //recebe dados do user a partir do id informado
        if ($userInfo['test']) { //se for usuario teste, nao envia email
            $res = $res->withJson("Usuário de teste; E-mail não enviado.");
            return $res;
        }
        $email = $userInfo['email'];
        $message = "<h2>O seguinte jogo da sua Lista de Desejos entrou em oferta: </h2>";
        $subject = "Um jogo da sua lista de desejos entrou em oferta!";
        if (sizeof($notification['new_offers']) > 1) { //caso seja mais de um jogo em oferta, altera titulo do email
            $message = "<h2>Os seguintes jogos da sua Lista de Desejos entraram em oferta: </h2>";
            $subject = sizeof($notification['new_offers']) . " jogos da sua lista de desejos entraram em oferta!";
        }
        $gameDAO = new GameDAO();
        foreach ($notification['new_offers'] as $offer) { //para cada jogo em oferta
            array_push($gameIDs, $offer['id_game']); //adiciona id do jogo ao array de ids
            $gameInfo = $gameDAO->getGame($offer['id_game'], 'id'); //busca informacoes do jogo a partir de seu id
            //insere no corpo do email informacoes do jogo / oferta
            $message .=  "<h3><strong>" . $gameInfo['name'] . "</strong></h3><ul>";
            foreach ($offer['deals'] as $deal) {
                $priceOld = str_replace('.', ',', $deal['price_old']);
                $priceNew = str_replace('.', ',', $deal['price_new']);
                $shop = $deal['shop']['name'];
                $message .= '<li><strong>' . $shop . '</strong>: de R$' . $priceOld . ' por <strong>R$' . $priceNew . ' [<span style="color:green">-' . $deal['price_cut'] . '%</span>]</strong></li>';
            }
            $message .= "</ul>";
        }
        $mailer = new Mailer();
        //envia o email e recebe resultado da operacao
        $result = $mailer->sendEmail($message, $email, $subject);
        if (!$result['error']) { //se nao houveram erros no envio de email
            $result['log'] = 'Jogos notificados: ';
            for ($i = 0; $i < sizeof($gameIDs) - 1; $i++) //para cada id de jogo, adiciona no log
                $result['log'] .= $gameIDs[$i] . ",";
            $result['log'] .= $gameIDs[sizeof($gameIDs) - 1];
        }
        $result['id_user'] = $notification['id_user'];
        $mailDAO = new MailDAO();
        //insere registro de envio de email no bd
        $queryResult = $mailDAO->insertNotification($result);
        //codigo http 201 (created) ou 500 (server error)
        $code = $queryResult ? 201 : 500;
        $res = $res->withJson('', $code);
        return $res;
    }
}
