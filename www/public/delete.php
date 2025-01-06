<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

try {
    $twig = getTwig();
    $manager = getMongoDbManager();
    $redis = getRedisClient(); //J'initialise mon client Redis
    $id = $_GET['id'] ?? null;

    try {
        if($redis){
            if ($id == null || empty($id)) {
                //prendre le dernier element de redis
                // throw new Exception("ID invalide.");
                // echo "ID invalide.";
                // echo "The latest element will be deleted.";
                $reverse_list = $redis->sort("manuscrits", ['limit' => [0, 1], 'order' => 'desc']);
                $id = $reverse_list[0];

                $redis->del("manuscrit_{$id}");

                $old_items_number = $redis->get("items_number");
                $redis->set("items_number", $old_items_number - 1);
            }
        }else{
            if ($id == null || empty($id)) {
                //prendre le dernier element de mongodb
                $reverse_list = $manager->selectCollection("tp")->find([], ['limit' => 1, 'sort' => ['_id' => -1]]);
                $iterator = iterator_to_array($reverse_list);
                $id = $iterator[0]['_id'];
            }
        }

        $manager->selectCollection('tp')->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);

        header('Location: /index.php');
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}