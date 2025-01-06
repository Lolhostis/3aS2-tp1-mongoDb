<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient(); // J'initialise mon client Redis

$entity = $redis ? json_decode($redis->get("manuscrit_{$_GET['id']}"), true) : null;

if (!$entity) {
    $entity = $manager->selectCollection('tp')->findOne(['_id' => new MongoDB\BSON\ObjectId($_GET['id'])]);
    if ($entity) {
        $entity['_id'] = (string) $entity['_id']; // Convertir ObjectId en string
    }
}

if (!$entity) {
    echo "Manuscrit introuvable.";
    return;
}

// Afficher l'entité dans le formulaire avec Twig
try {
    echo $twig->render('update.html.twig', ['entity' => $entity]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}