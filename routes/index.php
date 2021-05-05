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
$app->get('/game/{plain}', '\App\Controllers\GameController:getGameByPlain');
$app->get('/games_deals', '\App\Controllers\GameController:getGamesDeals');
$app->get('/game_deals/{plain}', '\App\Controllers\GameController:getGameDealsByPlain');
$app->get('/search_game', '\App\Controllers\GameController:getNameSuggestions');

$app->get('/stores', '\App\Controllers\StoreController:getStores');
$app->get('/deals', '\App\Controllers\DealController:getDeals');

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($req, $res) {
    $handler = $this->notFoundHandler;
    return $handler($req, $res);
});

//------------------------------------

$app->run();
