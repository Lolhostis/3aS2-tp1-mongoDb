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
        $edition_bool = $_POST['edition'];
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

        // Si Redis est activé, je supprime les données en cache pour que les données soient mises à jour
        if ($redis) {
            $redis->flushAll();
        }

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

