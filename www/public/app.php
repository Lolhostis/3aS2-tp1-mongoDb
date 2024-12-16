<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

// render template
try {
    $twig = getTwig();
    $manager = getMongoDbManager();
    $redis = getRedisClient(); //J'initialise mon client Redis

    $step = 10;
    $page_number = $_GET['page_number'] ?? 1;
    $raw_query = $_GET['query'] ?? '{}';
    var_dump($raw_query);
    $raw_query = urldecode($raw_query);
//    $query = !empty($_GET['query']) ? json_decode($_GET['query'], true) : [];
    $expirationTime = 10 * 60; // 10 minutes

// Je génère une clé Redis unique pour la page et la requête, comme ça je peux stocker les données en cache
    $cacheKey = "page:{$page_number}:query:{$raw_query}";
    if ($redis && $raw_query!="{}") {
        $redis->set("current_key", $raw_query); //Je stocke la requête actuelle dans Redis pour pouvoir la récupérer dans get.php
        echo "current_key: " . $redis->get("current_key") . "<br>";
        var_dump($raw_query);
    }

// Je vérifie si les données sont déjà en cache
    if ($redis && $redis->exists($cacheKey)) {
        // Récupérer les données depuis Redis
        $part_of_list_to_display = json_decode($redis->get($cacheKey), true); //Je récupère les données stockées dans Redis sous format JSON pour les convertir en un tableau PHP.
        $part_of_list_to_display = convertObjectIdsToStringsFromRedis($part_of_list_to_display); //Je convertis les ObjectIds en chaînes de caractères sinon Twig ne les affiche pas

        $max_page_number = $redis->get("max_page_number"); //Je récupère le nombre de pages maximum dans Redis pour l'utiliser dans index.html.twig
    } else {
        // Sinon, récupérer les données depuis MongoDB
        $list = $manager->selectCollection('tp')->find(json_decode($raw_query))->toArray();
        $total = count($list);
        $max_page_number = ceil($total / $step);

        // Sélectionner les 10 éléments à afficher
        $part_of_list_to_display = array_slice($list, ($page_number - 1) * $step, $step);

        // Mettre les données en cache Redis avec expiration de 10 minutes (600 secondes)
        if ($redis) {
            $part_of_list_to_display_clean = convertObjectIdsToStrings($part_of_list_to_display); //Je convertis les ObjectIds en chaînes de caractères sinon Twig ne les affiche pas
//TODO : Uncomment the following lines
//        $redis->setex($cacheKey, $expirationTime, json_encode($part_of_list_to_display_clean)); //Je stocke les données dans Redis sous format JSON
//        $redis->setex("max_page_number", $expirationTime, $max_page_number);
            $redis->set($cacheKey, json_encode($part_of_list_to_display_clean)); //Je stocke les données dans Redis sous format JSON
            $redis->set("max_page_number", $max_page_number);


        }
    }

    echo $twig->render('index.html.twig', ['part_of_list_to_display' => $part_of_list_to_display, 'page_number' => $page_number, 'query' => urlencode($raw_query), 'max_page_number' => $max_page_number]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}

function convertObjectIdsToStrings($data) {
    foreach ($data as &$item) {
        if (isset($item['_id']) && $item['_id'] instanceof \MongoDB\BSON\ObjectId) {
            // Converttir ObjectId en string
            $item['_id'] = (string) $item['_id'];
        }
    }
    return $data;
}

function convertObjectIdsToStringsFromRedis($data) {
    foreach ($data as &$item) {
        if (isset($item['_id']) && $item['_id'] instanceof \MongoDB\BSON\ObjectId) {
            // Convertir ObjectId en string
            $item['_id'] = (string) $item['_id'];
        }
    }
    return $data;
}