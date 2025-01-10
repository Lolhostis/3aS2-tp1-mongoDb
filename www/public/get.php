<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient(); //J'initialise mon client Redis
//Je veux definir id
$id = $_GET['id'] ?? null;
$entity = null;

try{
    if($redis){
        if ($id == null || empty($id)) {
            //prendre le dernier element de redis
            // throw new Exception("ID invalide.");
            // echo "ID invalide.";
            // echo "The latest element will be displayed.";)
            $manusrits = $redis->keys("manuscrit_*");
            $latest_manusrit = end($manusrits);
            $id = explode("_", $latest_manusrit)[1]; //get the id of the latest manuscrit
        }

        if($redis->exists("manuscrit_{$id}")){
            $entity = json_decode($redis->get("manuscrit_{$id}"), true);
        } 
    }else{
        if ($id == null || empty($id)) {
            //prendre le dernier element de mongodb
            $reverse_list = $manager->selectCollection("tp")->find([], ['limit' => 1, 'sort' => ['_id' => -1]]);
            $iterator = iterator_to_array($reverse_list);
            $id = $iterator[0]['_id'];
        }

        $entity = $manager->selectCollection('tp')->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    }

    if ($entity) {
        if (isset($entity['_id']) && $entity['_id'] instanceof \MongoDB\BSON\ObjectId) {
            // Convertir ObjectId en string
            $entity['_id'] = (string) $entity['_id'];
        }

        echo $twig->render('get.html.twig', ['entity' => $entity]);
    } else {
        echo "Entité non trouvée.";
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
} 
