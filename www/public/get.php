<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

// @todo implementez la récupération des données d'une entité et la passer au template
// petite aide : https://github.com/VSG24/mongodb-php-examples
$entity = $manager->selectCollection('tp')->findOne(['_id' => new MongoDB\BSON\ObjectId($_GET['id'])]);

// render template
try {
    echo $twig->render('get.html.twig', ['entity' => $entity]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}