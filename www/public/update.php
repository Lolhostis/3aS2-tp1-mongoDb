<?php

## echo 'modifiez le contenu d\'un document dans la base et retournez sur la liste';

include_once '../init.php';

$manager = getMongoDbManager();
$redis = getRedisClient(); //J'initialise mon client Redis

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];

        $updateData = [
            'auteur' => $_POST['author'],
            'cote' => $_POST['cote'],
            'edition' => isset($_POST['edition']) ? "S. l. ? : [S.n]." : "",
            'langue' => $_POST['langue'],
            'objectid' => $_POST['objectid'],
            'siecle' => $_POST['century'],
            'titre' => $_POST['title']
        ];

        $manager->selectCollection('tp')->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($id)],
            ['$set' => $updateData]
        );

        if ($redis && $redis->exists("manuscrit_{$id}")) {
            $updateData['_id'] = (string) $id;
            $redis->set("manuscrit_{$id}", json_encode($updateData));
        }

        header('Location: /index.php');
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }
}

