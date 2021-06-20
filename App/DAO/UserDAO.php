<?php

namespace App\DAO;

class UserDAO extends Connection
{
    public function __construct()
    {
        parent::__construct();
    }

    //retorna todos os users cadastrados no bd
    public function getAllUsers($onlyActive = true): array
    {
        //caso onlyActive seja true, retorna registros de user ativos no bd
        $where = $onlyActive ? ' WHERE active = 1' : '';
        $users = $this->pdo
            ->query('SELECT username,email,admin, active FROM users ' . $where)
            ->fetchAll(\PDO::FETCH_ASSOC);
        return $users;
    }

    //retorna informacoes do usuario de acordo com o username/id informado
    public function getUser($identifier, $type = "username")
    {
        $query = $this->pdo->prepare('SELECT id,username,email,test,admin, active '
            . 'FROM users WHERE ' . $type . ' = :' . $type . ' LIMIT 1');
        $query->bindValue(':' . $type, $identifier);
        $run = $query->execute();
        $queryResult = $query->fetch(\PDO::FETCH_ASSOC);
        return $queryResult;
    }

    //verifica se usuario ou email ja existem
    public function alreadyExists($email, $username)
    {
        $query = $this->pdo->prepare('SELECT * FROM users WHERE email = :email 
        OR username = :username LIMIT 1');
        $query->bindValue(':email', $email);
        $query->bindValue(':username', $username);
        $run = $query->execute();
        $queryResult = $query->fetch(\PDO::FETCH_ASSOC);
        if ($queryResult != null && sizeof($queryResult) > 0) return true;
        return false;
    }

    //confere email e senha e devolve dados do usuario 
    public function authenticateUser($email, $password)
    {
        $query = $this->pdo->prepare('SELECT username,email,admin, active '
            . 'FROM users WHERE (email = :email OR username = :email) AND password = :password LIMIT 1');
        $query->bindValue(':email', $email);
        $query->bindValue(':password', $password);
        $run = $query->execute();
        $queryResult = $query->fetch(\PDO::FETCH_ASSOC);
        return $queryResult;
    }

    //insere user no bd
    public function insertUser($user)
    {
        $query = $this->pdo->prepare('INSERT INTO users (username, email, password, '
            . 'inserted_at, active) VALUES (:username, :email, :password, :inserted_at, 1)');
        $query->bindValue(':username', $user['username']);
        $query->bindValue(':email', $user['email']);
        $query->bindValue(':password', $user['password']);
        $query->bindValue(':inserted_at', $user['inserted_at']);
        $run = $query->execute();
        return $run; //retorna resultado
    }
}
