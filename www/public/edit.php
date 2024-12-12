<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

$entity = $manager->selectCollection('tp')->findOne(['_id' => new MongoDB\BSON\ObjectId($_GET['id'])]);

try {
    echo $twig->render('update.html.twig', ['entity' => $entity]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}

