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
    $page_number = max(1, intval($_GET['page_number'] ?? 1));
    $raw_query = $_GET['query'] ?? '{}';
    $query = json_decode(urldecode($raw_query), true);
    $items=[];
    $query = json_decode(urldecode($raw_query), true);
    $query_hash = md5(json_encode($query) ?: '{}'); //Je crée un hash de la query pour l'utiliser comme clé Redis

    $key = "search_${query_hash}_page_${page_number}";

    if ($redis) {
        if (!$redis->exists("step")) {
            $redis->set("step", $step);
        }
        if ($redis->exists($key)) {
            $result= json_decode($redis->get($key), true);
            $ids = $result['items_in_this_page'];
            $items = array_map(fn($id) => json_decode($redis->get("manuscrit_${id}"), true), $ids);
            $total_of_elements = $result['total_of_elements'];
        }
        else{
            $total_of_elements = $manager->selectCollection('tp')->count($query);
            $part_of_the_list = $manager->selectCollection('tp')->find($query, ['limit' => $step, 'skip' => ($page_number - 1) * $step])->toArray();
            $items = convertObjectIdsToStrings($part_of_the_list);

            $ids = array_map(fn($item) => (string) $item['_id'], $items);
            foreach ($items as $item) {
                if (!$redis->exists("manuscrit_" . (string) $item['_id'])) {
                    $redis->set("manuscrit_" . (string) $item['_id'], json_encode($item));
                }
            }
            $redis->set($key,  json_encode(['items_in_this_page'=>$ids,'total_of_elements'=>$total_of_elements]));
        }
    } else {
        $total_of_elements = $manager->selectCollection('tp')->count($query);
        $part_of_the_list = $manager->selectCollection('tp')->find($query, ['limit' => $step, 'skip' => ($page_number - 1) * $step])->toArray();
        $items = convertObjectIdsToStrings($part_of_the_list);

    }
    $max_page_number_of_this_query = ceil($total_of_elements/$step);

    if($page_number < 1) {
        header("Location: /index.php?page_number=1&query=" . urlencode($raw_query));
        exit;
    } elseif($page_number > $max_page_number_of_this_query) {
        header("Location: /index.php?page_number=$max_page_number_of_this_query&query=" . urlencode($raw_query));
        exit;
    }

    // Rendu Twig
    echo $twig->render('index.html.twig', [
        'part_of_list_to_display' => $items,
        'page_number' => $page_number,
        'query' => urlencode($raw_query),
        'max_page_number' => $max_page_number_of_this_query,
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

