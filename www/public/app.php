<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
include_once __DIR__ . '/../command/indexation.php';

// render template
try {
    $twig = getTwig();
    $manager = getMongoDbManager();
    $redis = getRedisClient(); //J'initialise mon client Redis
    $elasticClient = getElasticSearchClient();

    $step = 10;
    $page_number = max(1, intval($_GET['page_number'] ?? 1));

    $raw_search = $_GET['search'] ?? ''; // Récupération de la recherche utilisateur
    $search_hash = md5(json_encode($raw_search) ?: '{}');//Je crée un hash de la recherche pour l'utiliser comme clé Redis
    $key = "search_${search_hash}_page_${page_number}";

    if ($redis && !$redis->exists("step")) {
        $redis->set("step", $step);
    }

    if ($redis && $redis->exists("step")) {
        (int) $step = $redis->get("step");
    }

    $offset = ($page_number - 1) * $step;

    if ($redis && $redis->exists($key)) {
        $result = json_decode($redis->get($key), true);
        $ids = $result['items_in_this_page'];
        $items = array_map(fn($id) => json_decode($redis->get("manuscrit_${id}"), true), $ids);
        $total_of_elements = $result['total_of_elements'];

        $max_page_number_of_this_query = ceil($total_of_elements/$step);
        echo $twig->render('index.html.twig', [
            'part_of_list_to_display' => $items,
            'page_number' => $page_number,
            'search' => urlencode($raw_search),
            'max_page_number' => $max_page_number_of_this_query
        ]);
        exit();
    }

    // Recherche ElasticSearch si une requête est spécifiée
    if ($elasticClient && !empty($raw_search)) {
        indexBooksToElastic($manager, $elasticClient);
        $searchResult = searchBooksInElastic($elasticClient, $raw_search, $step, $offset);
        $part_of_the_list = $searchResult['results'];
        $total_of_elements = $searchResult['total'];
    }else{
        // Si ElasticSearch n'est pas disponible, recherche via MongoDB
        if(!empty($raw_search)){
            $searchRegex = new MongoDB\BSON\Regex($raw_search, 'i'); // i pour insensible à la casse
            $query = [
                '$or' => [
                    ['titre' => $searchRegex],
                    ['auteur' => $searchRegex],
                ],
            ];
        }else{
            $query = [];
        }
        $total_of_elements = $manager->selectCollection('tp')->count($query);
        $part_of_the_list = $manager->selectCollection('tp')->find($query, ['limit' => (int) $step, 'skip' => (int) $offset])->toArray();
    }

    $max_page_number_of_this_query = ceil($total_of_elements/$step);

    $items = convertObjectIdsToStrings($part_of_the_list);

    if ($redis && !$redis->exists($key)) {
        $ids = array_map(fn($item) => (string) $item['_id'], $items);
        foreach ($items as $item) {
            if (!$redis->exists("manuscrit_" . (string) $item['_id'])) {
                $redis->set("manuscrit_" . (string) $item['_id'], json_encode($item));
            }
        }
        $redis->set($key,  json_encode(['items_in_this_page'=>$ids,'total_of_elements'=>$total_of_elements]));
    }

    if($page_number < 1) {
        header("Location: /index.php?page_number=1&search=" . urlencode($raw_search));
        exit;
    } elseif($page_number > $max_page_number_of_this_query) {
        header("Location: /index.php?page_number=$max_page_number_of_this_query&search=" . urlencode($raw_search));
        exit;
    }

    echo $twig->render('index.html.twig', [
        'part_of_list_to_display' => $items,
        'page_number' => $page_number,
        //'query' => urlencode(json_encode($query)),
        'search' => urlencode($raw_search),
        'max_page_number' => $max_page_number_of_this_query
    ]);
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

