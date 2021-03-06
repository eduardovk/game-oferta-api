<?php

use function src\slimConfiguration;
use App\Controllers\GameController;

$app = new \Slim\App(slimConfiguration());

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

//-------- ROTAS ---------------------

$app->get('/games', '\App\Controllers\GameController:getGames');
$app->get('/games/{plain}', '\App\Controllers\GameController:getGameByPlain');
$app->get('/games_deals', '\App\Controllers\GameController:getGamesAndDeals');
$app->get('/search_name', '\App\Controllers\GameController:getNameSuggestions');

$app->get('/users', '\App\Controllers\UserController:getAllUsers');
$app->get('/users/{username}', '\App\Controllers\UserController:getUser');
$app->post('/user', '\App\Controllers\UserController:createUser');

$app->post('/login', '\App\Controllers\UserController:login');

$app->post('/email', '\App\Controllers\MailController:sendEmail');

$app->get('/get_homepage', '\App\Controllers\GameController:getHomePageGames');
$app->post('/update_homepage', '\App\Controllers\GameController:updateHomePageGames');

$app->get('/wishlists', '\App\Controllers\WishlistController:getWishlists');
$app->post('/wishlist_games', '\App\Controllers\WishlistController:addToWishlist');
$app->delete('/wishlist_games', '\App\Controllers\WishlistController:removeFromWishlist');

$app->get('/stores', '\App\Controllers\StoreController:getStores');
$app->get('/deals', '\App\Controllers\DealController:getDeals');

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($req, $res) {
    $handler = $this->notFoundHandler;
    return $handler($req, $res);
});

//------------------------------------

$app->run();
