<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

try {
    $twig = getTwig();
    $manager = getMongoDbManager();
    $redis = getRedisClient(); //J'initialise mon client Redis

    $manager->selectCollection('tp')->deleteOne(['_id' => new MongoDB\BSON\ObjectId($_GET['id'])]);

    if ($redis) {
        //redis delete _id from cache
        $redis->del("manuscrit_{$_GET['id']}");

        $old_items_number = $redis->get("items_number");
        $redis->set("items_number", $old_items_number - 1);
    }

    header('Location: /index.php');
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}
