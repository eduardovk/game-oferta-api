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
        //verifica se email ou usuario ja existem no bd
        $alreadyExists = $userDAO->alreadyExists($user['email'], $user['username']);
        //caso email ou usuario ja existam
        if ($alreadyExists) {
            $msg = 'Erro: Nome de usuário ou E-mail já existem!';
            $code = 409; //codigo http 409 (recurso duplicado)
        } else {
            $queryResult = $userDAO->insertUser($user); //insere user no db e recebe resposta
            $msg = array('username' => $data['username'], 'email' => $data['email']);
            $code = 201; //codigo http 201 (created)
            //caso insert tenha dado erros
            if (!$queryResult) {
                $msg = 'Erro ao inserir usuário';
                $code = 500; //codigo http 500 (server error)
            }
        }
        return $res->withJson($msg, $code); //devolve msg e codigo de resposta)
    }

    public function login(Request $req, Response $res, array $args): Response
    {
        $data = $req->getParsedBody(); //recebe corpo do post
        $email = isset($data['email']) ? $data['email'] : false; //recebe email
        $password = isset($data['password']) ? sha1($data['password']) : false; //recebe password
        if (!$email || !$password) { //se email ou password nao forem informados
            $msg = 'E-mail ou senha não informado(s)!';
            $code = 400; //codigo http 400 (bad request)
            return $res->withJson($msg, $code); //devolve msg e codigo de resposta)
        }
        $userDAO = new UserDAO();
        $user = $userDAO->authenticateUser($email, $password);
        if (!$user) { //se nao encontrou usuario
            $msg = 'E-mail ou senha inválido(s)!';
            $code = 401; //codigo http 401 (Unauthorized)
            return $res->withJson($msg, $code); //devolve msg e codigo de resposta)
        }
        $res = $res->withJson($user);
        return $res;
    }
}
