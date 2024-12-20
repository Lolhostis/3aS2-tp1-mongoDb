<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient(); //J'initialise mon client Redis

// $entity = $manager->selectCollection('tp')->findOne(['_id' => new MongoDB\BSON\ObjectId($_GET['id'])]);

// Récupérer l'entité dans le cache de Redis
$entity = json_decode($redis->get("manuscrit_{$_GET['id']}"), true);
if ($entity) {
    // Convertir l'_id de l'entité en ObjectId
    if (isset($entity['_id']) && $entity['_id'] instanceof \MongoDB\BSON\ObjectId) {
        // Convertir ObjectId en string
        $entity['_id'] = (string) $entity['_id'];
    }

    // Afficher avec Twig
    try {
        echo $twig->render('update.html.twig', ['entity' => $entity]);
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }
}


