<?php

namespace App\DAO;

class WishlistDAO extends Connection
{
    public function __construct()
    {
        parent::__construct();
    }

    //retorna todas as wishlists ativas do bd
    //se withGames for true, retorna junto os jogos de cada lista
    public function getAllWishlists($withGames = false): array
    {
        if ($withGames) {
            //busca wishlists e os jogos de cada uma
            $wishlistsItems = $this->pdo->query('SELECT w.*, u.username, g.id as id_game, g.name, g.plain, g.igdb_cover '
                . 'FROM wishlists AS w INNER JOIN users as u ON(w.id_user = u.id) LEFT JOIN wishlist_games AS wg ON (w.id = wg.id_wishlist) '
                . 'LEFT JOIN games AS g ON (wg.id_game = g.id) WHERE w.active = 1 AND u.active = 1 ORDER BY w.id DESC')
                ->fetchAll(\PDO::FETCH_ASSOC);
            //estrutura wishlists + jogos de cada uma em um array
            $wishlistsGames = $this->makeWishlistArray($wishlistsItems);
            return $wishlistsGames;
        } else {
            //busca apenas wishlists e as retorna
            $wishlists = $this->pdo
                ->query('SELECT w.*, u.username FROM wishlists as w INNER JOIN users as u '
                    . 'ON (w.id_user = u.id) WHERE w.active = 1  AND u.active = 1 ORDER BY id DESC')
                ->fetchAll(\PDO::FETCH_ASSOC);
            return $wishlists;
        }
    }

    //retorna wishlist por id
    public function getWishList($id)
    {
        //busca pelo id a wishlist e seus jogos
        $query = $this->pdo->prepare('SELECT w.*, u.username, g.id as id_game, g.name, g.plain, g.igdb_cover '
            . 'FROM wishlists AS w INNER JOIN users as u ON (w.id_user = u.id) LEFT JOIN wishlist_games AS wg ON (w.id = wg.id_wishlist) LEFT JOIN games AS g '
            . 'ON (wg.id_game = g.id) WHERE w.active = 1  AND w.id = :id ORDER BY w.id DESC');
        $query->bindValue(':id', $id, \PDO::PARAM_INT);
        $run = $query->execute();
        $wishlistsItems = $query->fetchAll(\PDO::FETCH_ASSOC);
        //estrutura wishlists + jogos de cada uma em um array
        $wishlistsGames = $this->makeWishlistArray($wishlistsItems);
        if ($wishlistsGames != null && sizeof($wishlistsGames) > 0) return $wishlistsGames;
        return null;
    }

    //retorna todas as wishlists ativas do usuario
    //se withGames for true, retorna junto os jogos de cada lista
    public function getWishListsByUser($username, $withGames = false): array
    {
        if ($withGames) {
            //busca wishlists e os jogos de cada uma
            $query = $this->pdo->prepare('SELECT w.*, u.username, g.id as id_game, g.name, '
                . 'g.plain, g.igdb_cover FROM wishlists AS w INNER JOIN users as u ON(w.id_user = u.id) '
                . 'LEFT JOIN wishlist_games AS wg ON (w.id = wg.id_wishlist) LEFT JOIN games AS g ON '
                . '(wg.id_game = g.id) WHERE w.active = 1 AND u.active = 1 AND u.username = :username ORDER BY w.id DESC');
            $query->bindValue(':username', $username);
            $run = $query->execute();
            $wishlistsItems = $query->fetchAll(\PDO::FETCH_ASSOC);
            //estrutura wishlists + jogos de cada uma em um array
            $wishlistsGames = $this->makeWishlistArray($wishlistsItems);
            return $wishlistsGames;
        } else {
            //busca apenas wishlists e as retorna
            $query = $this->pdo->prepare('SELECT w.*, u.username FROM wishlists as w INNER JOIN users as u '
                . 'ON (w.id_user = u.id) WHERE w.active = 1  AND u.active = 1 AND '
                . 'u.username = :username ORDER BY id DESC');
            $query->bindValue(':username', $username);
            $run = $query->execute();
            $wishlists = $query->fetchAll(\PDO::FETCH_ASSOC);
            return $wishlists;
        }
    }

    //recebe do db rows de wishlist + games e devolve array estruturado
    public function makeWishlistArray($wishlistsItems)
    {
        $wishlists = array(); //cria array de wishlists
        foreach ($wishlistsItems as $item) { //para cada item da wishlist_game
            if (!isset($wishlists[$item['id']])) { //caso wishlist ainda nao esteja no array de wishlists
                //estrutura informacoes da wishlist
                $wishlistInfo = array(
                    'id' => $item['id'],
                    'id_user' => $item['id_user'],
                    'username' => $item['username'],
                    'email' => $item['email'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'public' => $item['public'],
                    'inserted_at' => $item['inserted_at'],
                    'updated_at' => $item['updated_at']
                );
                //adiciona wishlist ao array de wishlists
                $wishlists[$item['id']] = array(
                    'wishlist_info' => $wishlistInfo,
                    'games' => array()
                );;
            }
            if (isset($item['id_game']) && $item['id_game'] != null) {
                $game = array( //estrutura informacoes do game
                    'id_game' => $item['id_game'],
                    'name' => $item['name'],
                    'plain' => $item['plain'],
                    'cover' => $item['igdb_cover']
                );
                array_push($wishlists[$item['id']]['games'], $game); //adiciona game ao array de games da wishlist
            }
        }
        $wishlistsGames = array(); //cria array final para guardar os dados
        foreach ($wishlists as $wishlist) {
            $wishlistsGames[] = $wishlist; //passa wishlist + games para o array final
        }
        return $wishlistsGames; //retorna array final
    }

    //adiciona jogo a wishlist atraves do id da wishlist e id_game informados
    public function addToWishlist($idWishlist, $idGame)
    {
        $dateTime = date('Y-m-d H:i:s'); //data e hora atual
        $query = $this->pdo->prepare('INSERT INTO wishlist_games (id_wishlist, id_game, inserted_at) 
        VALUES (:id_wishlist, :id_game, :inserted_at)');
        $query->bindValue(':id_wishlist', $idWishlist, \PDO::PARAM_INT);
        $query->bindValue(':id_game', $idGame, \PDO::PARAM_INT);
        $query->bindValue(':inserted_at', $dateTime);
        $run = $query->execute();
        return $run; //retorna resultado
    }

    //remove jogo de todas wishlists do usuario
    public function removeFromWishlist($idGame, $username)
    {
        $query = $this->pdo->prepare('DELETE wg FROM wishlist_games AS wg INNER JOIN wishlists 
        as w ON (wg.id_wishlist = w.id) INNER JOIN users AS u ON (w.id_user = u.id) 
        WHERE u.username = :username AND wg.id_game = :id_game');
        $query->bindValue(':username', $username);
        $query->bindValue(':id_game', $idGame, \PDO::PARAM_INT);
        $run = $query->execute();
        return $run; //retorna resultado
    }

    //insere nova wishlist no banco
    public function insertWishlist($wishlist)
    {
        $dateTime = date('Y-m-d H:i:s'); //data e hora atual
        $query = $this->pdo->prepare('INSERT INTO wishlists (id_user, email, title, description, public, 
        inserted_at, updated_at, active) VALUES (:id_user, :email, :title, :description, 
        :public, :inserted_at, NULL, 1)');
        $query->bindValue(':id_user', $wishlist['id_user'], \PDO::PARAM_INT);
        $query->bindValue(':email', $wishlist['email']);
        $query->bindValue(':title', $wishlist['title']);
        $query->bindValue(':description', $wishlist['description']);
        $query->bindValue(':public', $wishlist['public'], \PDO::PARAM_INT);
        $query->bindValue(':inserted_at', $dateTime);
        $run = $query->execute();
        $id = $this->pdo->lastInsertId(); //retorna id da wishlist inserida
        return $id;
    }

    //retorna array de ids e boolean indicando se estao na wishlist do usuario
    public function checkGamesIDArray($ids, $username)
    {
        $idsString = ''; //string de ids para utilizar na query
        foreach ($ids as $id) { //para cada id do array
            $idsString .= $id . ','; //adiciona id a string
        }
        $idsString = substr($idsString, 0, -1); //remove ultima virgula da string
        //procura na bd se os jogos informados estao em alguma wishlist do usuario
        $query = $this->pdo->prepare('SELECT wg.id_game FROM wishlist_games as wg INNER JOIN wishlists AS w ON (wg.id_wishlist = w.id) 
        INNER JOIN users AS u ON (w.id_user = u.id) WHERE u.username = :username AND wg.id_game IN (' . $idsString . ')');
        $query->bindValue(':username', $username);
        $run = $query->execute();
        $queryResult = $query->fetchAll(\PDO::FETCH_ASSOC);
        $finalIDArray = array(); //estrutura array
        foreach ($queryResult as $row) {
            $finalIDArray[] = $row['id_game'];
        }
        //devolve array de ids que estejam na wishlist do usuario
        return $finalIDArray;
    }
}
