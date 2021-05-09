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
}
