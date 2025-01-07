<?php

include_once __DIR__.'/../init.php';

$elasticClient = getElasticSearchClient();
$manager = getMongoDbManager();

// Define the index settings and mappings for tp
$indexParamsTp = [
    'index' => 'tp',
    'body' => [
        'settings' => [
            'analysis' => [
                'filter' => [
                    'french_stemmer' => [
                        'type' => 'stemmer',
                        'language' => 'light_french'
                    ]
                ],
                'analyzer' => [
                    'custom_french_analyzer' => [
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'french_stemmer'
                        ]
                    ]
                ]
            ]
        ],
        'mappings' => [
            'properties' => [
                'auteur' => [
                    'type' => 'text',
                    'analyzer' => 'custom_french_analyzer',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 256
                        ]
                    ]
                ],
                'titre' => [
                    'type' => 'text',
                    'analyzer' => 'custom_french_analyzer',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 256
                        ]
                    ]
                ]
            ]
        ]
    ]
];

// Create the tp index
$elasticClient->indices()->create($indexParamsTp);

echo "\nIndex tp created successfully\n";

// Define the index settings and mappings for tp2
$indexParamsTp2 = [
    'index' => 'tp2',
    'body' => [
        'settings' => [
            'number_of_shards' => 1,
            'number_of_replicas' => 1
        ],
        'mappings' => [
            'properties' => [
                'auteur' => [
                    'type' => 'text',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 256
                        ]
                    ]
                ],
                'titre' => [
                    'type' => 'text',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 256
                        ]
                    ]
                ]
            ]
        ]
    ]
];

// Create the tp2 index
$elasticClient->indices()->create($indexParamsTp2);

echo "\nIndex tp2 created successfully\n";

// Fetch documents from MongoDB and prepare bulk indexing for tp
$cursorTp = $manager->selectCollection("tp")->find();
$bulkParamsTp = ['body' => []];

foreach ($cursorTp as $document) {
    $id = (string)$document['_id'];
    unset($document['_id']); // Remove _id from the document body

    $bulkParamsTp['body'][] = [
        'index' => [
            '_index' => 'tp',
            '_id' => $id
        ]
    ];

    $bulkParamsTp['body'][] = $document;
}

if (!empty($bulkParamsTp['body'])) {
    $response = $elasticClient->bulk($bulkParamsTp);
    if ($response['errors']) {
        echo "\nErrors occurred during bulk indexation for tp\n";
    } else {
        echo "\nBulk indexation for tp completed successfully\n";
    }
} else {
    echo "\nNo documents to index for tp\n";
}

// Fetch documents from MongoDB and prepare bulk indexing for tp2
$cursorTp2 = $manager->selectCollection("tp")->find();
$bulkParamsTp2 = ['body' => []];

foreach ($cursorTp2 as $document) {
    $id = (string)$document['_id'];
    unset($document['_id']); // Remove _id from the document body

    $bulkParamsTp2['body'][] = [
        'index' => [
            '_index' => 'tp2',
            '_id' => $id
        ]
    ];

    $bulkParamsTp2['body'][] = $document;
}

if (!empty($bulkParamsTp2['body'])) {
    $response = $elasticClient->bulk($bulkParamsTp2);
    if ($response['errors']) {
        echo "\nErrors occurred during bulk indexation for tp2\n";
    } else {
        echo "\nBulk indexation for tp2 completed successfully\n";
    }
} else {
    echo "\nNo documents to index for tp2\n";
}

echo "\nIndexation terminée\n";







// $params = [
//     'index' => 'tp',
//     'body' => [
//         'from' => ($page - 1) * 50,
//         'size' => 50,
//         'query' => [
//             'multi_match' => [
//                 'query' => "*$search*",
//                 'fields' => [
//                     'titre^2',
//                     'auteur'
//                 ],
//                 'fuzziness' => 'AUTO:3,6',
//                 'prefix_length' => 1, // Allow for missing letters after the first character
//                 'operator' => 'AND',

//                 'fuzzy_transpositions' => true,
//             ]
//         ]
//     ]
// ];