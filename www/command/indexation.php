<?php

include_once __DIR__.'/../init.php';

$elasticClient = getElasticSearchClient();
$manager = getMongoDbManager();

// Fonction pour indexer les livres dans ElasticSearch
function indexBooksToElastic($manager, $elasticClient) {
    try {
//        echo "Démarrage de l'indexation...\n";

        $list = $manager->selectCollection('tp')->find([]);

        // Boucle pour indexer chaque livre
        foreach ($list as $manuscrit) {
            // Sauvegarder l'ID MongoDB et le retirer du document
            $id = (string) $manuscrit['_id']; // Récupérer l'ID
            unset($manuscrit['_id']); // Supprimer `_id` du corps du document

            // Indexation dans ElasticSearch
            $response = $elasticClient->index([
                'index' => 'manuscripts', // Nom de l'index ElasticSearch
                'id' => $id,             // Utilisation de l'ID MongoDB comme ID ElasticSearch
                'body' => $manuscrit     // Données du livre sans `_id`
            ]);

            // Affichage d'un message de confirmation
//            echo "Livre indexé : " . $id . " - " . ($manuscrit['titre'] ?? 'Titre inconnu') . "\n";
        }

//        echo "Indexation terminée avec succès.\n";
    } catch (Exception $e) {
        echo "Erreur lors de l'indexation : " . $e->getMessage() . "\n";
    }
}

function searchBooksInElastic($elasticClient, $searchQuery, $limit = 10, $offset = 0)
{
    try {
        // Construction de la requête ElasticSearch
        $elasticSearchQuery = [
            'index' => 'manuscripts', // Nom de l'index
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $searchQuery,
                        'fields' => ['titre^2', 'auteur'], // Champs à rechercher
                        'fuzziness' => 'AUTO:3', // Tolère les fautes de frappe jusqu'à 3 caractères
                        'type' => 'best_fields', // Combine les résultats les plus pertinents
                        'prefix_length' => 1, // Autorise les lettres manquantes après le premier caractère
                        'fuzzy_transpositions' => true, // Autorise les transpositions de lettres
                    ]
                ],
                'from' => $offset, // Décalage (pour pagination)
                'size' => $limit // Nombre maximum de résultats
            ]
        ];

        // Exécute la requête
        $response = $elasticClient->search($elasticSearchQuery);
//        var_dump($response['hits']['hits']);

        // Extraction des résultats
        $hits = $response['hits']['hits'] ?? [];
        $total = $response['hits']['total']['value'] ?? 0;

        // Formater les résultats
        $results = array_map(function ($hit) {
            return [
                '_id' => $hit['_id'], // ID du document
                'titre' => $hit['_source']['titre'] ?? 'Titre inconnu',
                'auteur' => $hit['_source']['auteur'] ?? 'Auteur inconnu'
            ];
        }, $hits);

        return [
            'results' => $results,
            'total' => $total
        ];
    } catch (Exception $e) {
        echo "Erreur lors de la recherche : " . $e->getMessage() . "\n";
        return [
            'results' => [],
            'total' => 0
        ];
    }
}

//// Appel de la fonction pour indexer les livres
//indexBooksToElastic($manager, $elasticClient);
////lançons la recherche
//$searchQuery = 'test';
//$searchResult = searchBooksInElastic($elasticClient, $searchQuery, 10, 0);
//echo "Résultats de la recherche pour '$searchQuery' :\n";
//var_dump($searchResult);

//// Define the index settings and mappings for tp
//$indexParamsTp = [
//    'index' => 'tp',
//    'body' => [
//        'settings' => [
//            'analysis' => [
//                'filter' => [
//                    'french_stemmer' => [
//                        'type' => 'stemmer',
//                        'language' => 'light_french'
//                    ]
//                ],
//                'analyzer' => [
//                    'custom_french_analyzer' => [
//                        'tokenizer' => 'standard',
//                        'filter' => [
//                            'lowercase',
//                            'french_stemmer'
//                        ]
//                    ]
//                ]
//            ]
//        ],
//        'mappings' => [
//            'properties' => [
//                'auteur' => [
//                    'type' => 'text',
//                    'analyzer' => 'custom_french_analyzer',
//                    'fields' => [
//                        'keyword' => [
//                            'type' => 'keyword',
//                            'ignore_above' => 256
//                        ]
//                    ]
//                ],
//                'titre' => [
//                    'type' => 'text',
//                    'analyzer' => 'custom_french_analyzer',
//                    'fields' => [
//                        'keyword' => [
//                            'type' => 'keyword',
//                            'ignore_above' => 256
//                        ]
//                    ]
//                ]
//            ]
//        ]
//    ]
//];
//
//// Create the tp index
//$elasticClient->indices()->create($indexParamsTp);
//
//echo "\nIndex tp created successfully\n";
//
//// Define the index settings and mappings for tp2
//$indexParamsTp2 = [
//    'index' => 'tp2',
//    'body' => [
//        'settings' => [
//            'number_of_shards' => 1,
//            'number_of_replicas' => 1
//        ],
//        'mappings' => [
//            'properties' => [
//                'auteur' => [
//                    'type' => 'text',
//                    'fields' => [
//                        'keyword' => [
//                            'type' => 'keyword',
//                            'ignore_above' => 256
//                        ]
//                    ]
//                ],
//                'titre' => [
//                    'type' => 'text',
//                    'fields' => [
//                        'keyword' => [
//                            'type' => 'keyword',
//                            'ignore_above' => 256
//                        ]
//                    ]
//                ]
//            ]
//        ]
//    ]
//];
//
//// Create the tp2 index
//$elasticClient->indices()->create($indexParamsTp2);
//
//echo "\nIndex tp2 created successfully\n";
//
//// Fetch documents from MongoDB and prepare bulk indexing for tp
//$cursorTp = $manager->selectCollection("tp")->find();
//$bulkParamsTp = ['body' => []];
//
//foreach ($cursorTp as $document) {
//    $id = (string)$document['_id'];
//    unset($document['_id']); // Remove _id from the document body
//
//    $bulkParamsTp['body'][] = [
//        'index' => [
//            '_index' => 'tp',
//            '_id' => $id
//        ]
//    ];
//
//    $bulkParamsTp['body'][] = $document;
//}
//
//if (!empty($bulkParamsTp['body'])) {
//    $response = $elasticClient->bulk($bulkParamsTp);
//    if ($response['errors']) {
//        echo "\nErrors occurred during bulk indexation for tp\n";
//    } else {
//        echo "\nBulk indexation for tp completed successfully\n";
//    }
//} else {
//    echo "\nNo documents to index for tp\n";
//}
//
//// Fetch documents from MongoDB and prepare bulk indexing for tp2
//$cursorTp2 = $manager->selectCollection("tp")->find();
//$bulkParamsTp2 = ['body' => []];
//
//foreach ($cursorTp2 as $document) {
//    $id = (string)$document['_id'];
//    unset($document['_id']); // Remove _id from the document body
//
//    $bulkParamsTp2['body'][] = [
//        'index' => [
//            '_index' => 'tp2',
//            '_id' => $id
//        ]
//    ];
//
//    $bulkParamsTp2['body'][] = $document;
//}
//

//
//
//
//