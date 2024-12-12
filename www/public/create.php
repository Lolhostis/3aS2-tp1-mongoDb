<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

// petite aide : https://github.com/VSG24/mongodb-php-examples

if (!empty($_POST)) {
    // @todo coder l'enregistrement d'un nouveau livre en lisant le contenu de $_POST
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

