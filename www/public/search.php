<?php

include_once '../init.php';

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

$twig = getTwig();
$manager = getMongoDbManager();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchQuery = $_POST['search'] ?? '';

    var_dump($searchQuery);

    // Construction de la requête ElasticSearch
    $elasticSearchQuery = [
        'index' => 'manuscripts',
        'body' => [
            'query' => [
                'multi_match' => [
                    'query' => $searchQuery,
                    'fields' => ['titre^2', 'auteur'],// Priorité plus élevée pour 'titre' avec le boost ^2
                    'fuzziness' => 'AUTO', // Tolère les fautes de frappe
                    'type' => 'best_fields', // Combine les résultats les plus pertinents
                ]
            ]
        ]
    ];
    var_dump($elasticSearchQuery); // Inspecte la requête ElasticSearch

    // Recherche dans ElasticSearch
    if ($elasticClient) {
        $response = $elasticClient->search($elasticSearchQuery);
        var_dump($response); // Inspecte la réponse complète d'ElasticSearch
        $hits = $response['hits']['hits'];
        $ids = array_map(fn($hit) => $hit['_id'], $hits);// Récupération des IDs des manuscrits trouvés
        var_dump($ids); // Vérifie les IDs extraits

//        if (!empty($ids)) {
//            $query = ['_id' => ['$in' => array_map(fn($id) => new MongoDB\BSON\ObjectId($id), $ids)]];
//        } else {
//            $query = [];
//        }

        $encodedQuery = urlencode(json_encode(['_id' => ['$in' => $ids]]));
        var_dump("Redirecting to: index.php?page_number=1&query=$encodedQuery");
    } else {
        //on veut que title contienne le mot recherché
        $searchQuery = new MongoDB\BSON\Regex($searchQuery, 'i'); // i pour insensible à la casse
        $query = ['titre' => $searchQuery];
        $encodedQuery = urlencode(json_encode($query));
    }

    var_dump($encodedQuery); // Inspecte la query encodée

    try {
        header("Location: index.php?page_number=1&query=$encodedQuery");
        exit();
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }
} else {
    echo $twig->render('index.html.twig');
}
