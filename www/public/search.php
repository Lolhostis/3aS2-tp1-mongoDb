<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();

if (!empty($_POST)) {
    $titleToSearch = $_POST['search'] ?? '';
    //on veut que title contienne le mot recherché
    $titleToSearch = new MongoDB\BSON\Regex($titleToSearch, 'i'); // i pour insensible à la casse
    $query = ['titre' => $titleToSearch];
    $encodedQuery = urlencode(json_encode($query));

    try {
        //render index.html.twig with the search query in the URL and the page number set to 1
        header("Location: index.php?page_number=1&query=$encodedQuery", true, 303);
        exit();
       // header("Location: index.php?page_number=1&query=$encodedQuery");
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }
    return;
} else {
    try {
        echo $twig->render('index.html.twig');
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }
}