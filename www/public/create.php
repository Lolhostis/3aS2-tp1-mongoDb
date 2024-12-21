<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient(); //J'initialise mon client Redis

if (!empty($_POST)) {
    try {
        $author = $_POST['author'];
        $cote = $_POST['cote'];
        $edition_bool = isset($_POST['edition']) ? ($_POST['edition'] == true) : false; //Je mets une valeur par defaut à false si la checkbox n'est pas cochée
        $langue = $_POST['langue'];
        $objectid = $_POST['objectid'];
        $century = $_POST['century'];
        $title = $_POST['title'];

        if(empty($title) || empty($author) || empty($century) || empty($objectid) || empty($langue) || empty($cote)) {
            $erreur = 'Veuillez remplir TOUS les champs';
            echo $twig->render('create.html.twig', ['erreur' => $erreur]);
            return;
        }

        $dataToInsert = [
            'auteur' => $author,
            'cote' => $cote,
            'edition' => $edition_bool ? "S. l. ? : [S.n]." : "",
            'langue' => $langue,
            'objectid' => $objectid,
            'siecle' => $century,
            'titre' => $title,
        ];

        $manager->selectCollection('tp')->insertOne($dataToInsert);

        //$encodedQuery = urlencode(json_encode($dataToInsert));
        $entity = $manager->selectCollection('tp')->findOne($dataToInsert);

        $item_number = (string) $entity['_id'];
        $entity['_id'] = $item_number;

        // Si Redis est activé, je mets à jour les données en cache
        if ($redis) {
            $redis->set("manuscrit_{$item_number}", json_encode($entity));
        }
        $old_items_number = $redis->get("items_number");
        $redis->set("items_number", $old_items_number + 1);
        header('Location: /index.php');
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }
} else {
// render template
    try {
        echo $twig->render('create.html.twig');
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }
}

