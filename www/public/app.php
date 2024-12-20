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
    $query = urldecode($raw_query);

// Je vérifie si les données sont déjà en cache
    if ($redis && $redis->exists("saved_page_number_{$page_number}")) {
        // Récupérer les données depuis Redis
        $page_items_number = $redis->get("saved_page_number_{$page_number}");
        //pour chaque élément de la page, je récupère les données depuis Redis

        $get_all_manuscripts = $redis->keys("manuscrit_*");
        usort($get_all_manuscripts, function ($a, $b) {
            // Extraire l'_id de chaque clé et comparer
            $idA = str_replace('manuscrit_', '', $a);
            $idB = str_replace('manuscrit_', '', $b);
            return strcmp($idA, $idB); // Comparaison lexicographique croissante entre manuscrits et leurs ids respectifs
        });

        $step = $redis->get("step");
        //prendre les 10 premiers éléments de la page actuelle
        $encoded_part_of_list_to_display = array_slice($get_all_manuscripts, ($page_number - 1) * $step, $step);

        $part_of_list_to_display = [];
        foreach ($encoded_part_of_list_to_display as $key) {
            $part_of_list_to_display[] = json_decode($redis->get($key), true); // Récupère et décode les données
        }

        $total = $redis->get("items_number"); //Je récupère le nombre total d'éléments dans Redis pour l'utiliser dans index.html.twig
    } else {
        // Sinon, récupérer les données depuis MongoDB
        $list = $manager->selectCollection('tp')->find(json_decode($raw_query), ['sort' => ['_id' => 1]])->toArray();

        $total = count($list);
        $max_page_number = ceil($total / $step);

        // Sélectionner les 10 éléments à afficher
        $part_of_list_to_display = array_slice($list, ($page_number - 1) * $step, $step);

        // Mettre les données en cache Redis avec expiration de 10 minutes (600 secondes)
        if ($redis) {
            $part_of_list_to_display_clean = convertObjectIdsToStrings($part_of_list_to_display); //Je convertis les ObjectIds en chaînes de caractères sinon Twig ne les affiche pas
            $page_items_number = count($part_of_list_to_display);

            if(!$redis->exists("step")) {
                $redis->set("step", $step);
            }

            $redis->set("items_number", $total);

            $redis->set("saved_page_number_{$page_number}", $page_items_number);

            foreach ($part_of_list_to_display_clean as $item) {
                $item_number = (string) $item['_id'];
                $redis->set("manuscrit_{$item_number}", json_encode($item));
            }
        }
    }

    $max_page_number = ceil($total / $step);

    echo $twig->render('index.html.twig', ['part_of_list_to_display' => $part_of_list_to_display, 'page_number' => $page_number, 'query' => urlencode($query), 'max_page_number' => $max_page_number]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}

function convertObjectIdsToStrings($data) {
    foreach ($data as &$item) { //Je convertis les ObjectIds en chaînes de caractères sinon Twig ne les affiche pas
        if (isset($item['_id']) && $item['_id'] instanceof \MongoDB\BSON\ObjectId) {
            // Convertir ObjectId en string
            $item['_id'] = (string) $item['_id'];
        }
    }
    return $data;
}

