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
                $manusrits = $redis->keys("manuscrit_*");
                $latest_manusrit = end($manusrits);
                $id = explode("_", $latest_manusrit)[1]; //get the id of the latest manuscrit
            }

            if($redis->exists("manuscrit_{$id}")){
                $redis->del("manuscrit_{$id}");

                //On supprime l'id de l'élément dans chaque query où il apparait (si il apparait) ET on décrément alors $total_of_elements
//                $keys = $redis->keys("search_*");
//                foreach ($keys as $key) {
//                    $result = json_decode($redis->get($key), true);
//                    if (in_array($id, $result['items_in_this_page'])) {
//                        $result['items_in_this_page'] = array_diff($result['items_in_this_page'], [$id]);
//                        $result['total_of_elements'] -= 1;
//                        if($result['total_of_elements'] == 0) {
//                            $redis->del($key);
//                        }else{
//                            $redis->set($key, json_encode($result));
//                        }
//                    }
//                }
                //On a besoin de sudecrementer tous les totaux des query qui sont identiques à celles qui ont été modifiées
                //donc pour faire plus simple : flush toutes les query
                $keys = $redis->keys("search_*");
                foreach ($keys as $key) {
                    $redis->del($key);
                }
            }
        }elseif ($id == null || empty($id)) {
            //prendre le dernier element de mongodb
            $reverse_list = $manager->selectCollection("tp")->find([], ['limit' => 1, 'sort' => ['_id' => -1]]);
            $iterator = iterator_to_array($reverse_list);
            $id = $iterator[0]['_id'];
        }

        $manager->selectCollection('tp')->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);

        header('Location: /index.php');
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}