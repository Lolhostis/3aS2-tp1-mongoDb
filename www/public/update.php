<?php

## echo 'modifiez le contenu d\'un document dans la base et retournez sur la liste';

include_once '../init.php';

$manager = getMongoDbManager();

if (!empty($_POST)) {
    try {
        $author = $_POST['author'];
        $cote = $_POST['cote'];
        $edition_bool = isset($_POST['edition']) ? ($_POST['edition'] == true) : false;
        $langue = $_POST['langue'];
        $objectid = $_POST['objectid'];
        $century = $_POST['century'];
        $title = $_POST['title'];

        $dataToUpdate = [
            'auteur' => $author,
            'cote' => $cote,
            'edition' => $edition_bool ? "S. l. ? : [S.n]." : "",
            'langue' => $langue,
            'objectid' => $objectid,
            'siecle' => $century,
            'titre' => $title,
        ];

        $manager->selectCollection('tp')->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_POST['id'])],
            ['$set' => $dataToUpdate]
        );

        header('Location: /index.php');
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
        echo $e->getMessage();
    }
}
