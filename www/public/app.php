<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$step = 10;
$page_number = $_GET['page_number'] ?? 1;
$query = !empty($_GET['query']) ? json_decode($_GET['query'], true) : [];
$encodedQuery = json_encode($query);

// @todo implementez la récupération des données dans la variable $list
// petite aide : https://github.com/VSG24/mongodb-php-examples

$list = $manager->selectCollection('tp')->find($query)->toArray();
$total = count($list);
$max_page_number = ceil($total / $step);

// select only the first 10 elements
$part_of_list_to_display = array_slice($list, $page_number * $step, $step);

// render template
try {
    echo $twig->render('index.html.twig', ['part_of_list_to_display' => $part_of_list_to_display, 'page_number' => $page_number, 'query' => $encodedQuery, 'max_page_number' => $max_page_number]);
} catch (LoaderError|RuntimeError|SyntaxError $e) {
    echo $e->getMessage();
}



