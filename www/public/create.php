<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();
$redis = getRedisClient(); //J'initialise mon client Redis

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requiredFields = ['author', 'cote', 'langue', 'objectid', 'century', 'title'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo $twig->render('create.html.twig', ['erreur' => "Le champ $field est obligatoire."]);
            return;
        }
    }

    try {
        $dataToInsert = [
            'auteur' => $_POST['author'],
            'cote' => $_POST['cote'],
            'edition' => isset($_POST['edition']) ? "S. l. ? : [S.n]." : "",
            'langue' => $_POST['langue'],
            'objectid' => $_POST['objectid'],
            'siecle' => $_POST['century'],
            'titre' => $_POST['title']
        ];

        $result = $manager->selectCollection('tp')->insertOne($dataToInsert);
        if ($redis) {
            // Ajouter l'ID généré automatiquement
            $dataToInsert['_id'] = (string) $result->getInsertedId();

            $redis->set("manuscrit_{$dataToInsert['_id']}", json_encode($dataToInsert));
        }

        header('Location: /index.php?new_id=' . (string) $result->getInsertedId(), true, 302);
    } catch (Exception $e) {
        error_log("Erreur d'insertion : " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => "Erreur interne."]);
        echo $twig->render('create.html.twig', ['erreur' => "Erreur lors de l'ajout : " . $e->getMessage()]);
    }
} else {
    echo $twig->render('create.html.twig');
}

