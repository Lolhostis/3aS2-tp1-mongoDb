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
        $redis->flushAll();
    }

    header('Location: /index.php');
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}
