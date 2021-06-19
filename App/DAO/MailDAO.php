<?php

namespace App\DAO;

class MailDAO extends Connection
{
    public function __construct()
    {
        parent::__construct();
    }

    //insere registro de notificacao por email no bd
    public function insertNotification($notification)
    {
        $dateTime = date('Y-m-d H:i:s'); //data e hora atual
        $query = $this->pdo->prepare('INSERT INTO notifications (id_user, inserted_at, 
        error,  log) VALUES (:id_user, :inserted_at, :error, :log)');
        $query->bindValue(':id_user', $notification['id_user']);
        $query->bindValue(':inserted_at', $dateTime);
        $query->bindValue(':error', $notification['error']);
        $query->bindValue(':log', $notification['log']);
        $run = $query->execute();
        return $run; //retorna resultado
    }
}
