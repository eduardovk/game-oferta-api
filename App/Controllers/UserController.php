<?php

namespace App\Controllers;

use App\DAO\UserDAO;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Http\Response as Response;
// use Psr\Http\Message\ResponseInterface as Response;

final class UserController
{

    //retorna todos os users cadastrados no bd
    //Ex.: game-oferta-api/users  ou  game-oferta-api/users&only_active=false
    public function getAllUsers(Request $req, Response $res, array $args): Response
    {
        $params = $req->getQueryParams(); //recebe parametros get da url
        //se only_active = false, retorna tambem users inativos
        $onlyActive = true;
        if (isset($params['only_active']) && $params['only_active'] === 'false') {
            $onlyActive = false;
        }
        $userDAO = new UserDAO();
        $users = $userDAO->getAllUsers($onlyActive);
        $res = $res->withJson($users);
        return $res;
    }

    //retorna informacoes do usuario de acordo com o username informado
    public function getUser(Request $req, Response $res, array $args): Response
    {
        $username = $args['username'];
        $userDAO = new UserDAO();
        $user = $userDAO->getUser($username);
        $res = $res->withJson($user);
        return $res;
    }

    //insere user no bd
    public function createUser(Request $req, Response $res, array $args): Response
    {
        $data = $req->getParsedBody(); //recebe corpo do post
        //cria array com informacoes do usuario
        $user = array(
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => sha1($data['password']),
            'inserted_at' => date('Y-m-d H:i:s') //Formata data e hora atual
        );
        $userDAO = new UserDAO();
        $queryResult = $userDAO->insertUser($user); //insere user no db e recebe resposta
        $msg = 'Usuário criado com sucesso!';
        $code = 201; //codigo http 201 (created)
        //caso insert tenha dado erros
        if (!$queryResult) {
            $msg = 'Erro ao inserir usuário';
            $code = 500; //codigo http 500 (server error)
        }
        return $res->withJson($msg, $code); //devolve msg e codigo de resposta)
    }
}
