<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient(); // J'initialise mon client Redis

$id = $_GET['id'];
if($id == null || empty($id)){
    echo "Manuscrit introuvable.";
    return;
}

$entity = $redis ?  : null;

if($redis){
    if($redis->exists("manuscrit_{$id}")){
        $entity = json_decode($redis->get("manuscrit_{$id}"), true);
    }
}else{
    $entity = $manager->selectCollection('tp')->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    if($entity){
        $entity['_id'] = (string) $entity['_id']; // Convertir ObjectId en string
    }
}

// Afficher l'entité dans le formulaire avec Twig
try {
    echo $twig->render('update.html.twig', ['entity' => $entity]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}