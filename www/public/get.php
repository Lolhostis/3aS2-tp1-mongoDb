<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient(); //J'initialise mon client Redis

//$entity = $manager->selectCollection('tp')->findOne(['_id' => new MongoDB\BSON\ObjectId($_GET['id'])]);

// Récupérer l'entité
$current_key = $redis->get("current_key");
$data_from_current_key = $redis->get($current_key);
$part_of_list_to_display = json_decode($data_from_current_key, true); //Je récupère les données stockées dans Redis sous format JSON pour les convertir en un tableau PHP.
$part_of_list_to_display = convertObjectIdsToStrings($part_of_list_to_display); //Je convertis les ObjectIds en chaînes de caractères sinon Twig ne les affiche pas

//print_r($part_of_list_to_display);
// Chercher l'entité avec l'ID spécifié
$entity = null;
foreach ($part_of_list_to_display as $item) {
    if (trim((string) $item['_id']) === trim((string) $_GET['id'])) {
        $entity = $item;
        break;
    }
}

if ($entity) {
    // Convertir l'_id de l'entité en ObjectId
    $entity['_id'] = new MongoDB\BSON\ObjectId($entity['_id']);

    // Afficher avec Twig
    try {
        echo $twig->render('get.html.twig', ['entity' => $entity]);
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }
} else {
    echo "Entité non trouvée.";
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