import http from 'k6/http';
import { check } from 'k6';
import { sleep } from 'k6';

export let options = {
    stages: [
        { duration: '5s', target: 5 },  // Monter à 5 utilisateurs en 5s
        { duration: '10s', target: 10 }, // Maintenir 10 utilisateurs pendant 10s
        { duration: '10s', target: 50 }, // Monter à 50 utilisateurs pendant 10s
        { duration: '5s', target: 10 },  // Redescendre à 10 utilisateurs en 5s
        { duration: '5s', target: 5 },   // Redescendre à 5 utilisateurs en 5s
    ],
};

// Fonction pour générer un délai aléatoire
function randomSleep(min, max) {
    const duration = Math.random() * (max - min) + min;
    sleep(duration);
}

export default function () {
    console.log("Début du test K6 - Scénario complet");

   // 1. Affichage de la liste des livres
    let response = http.get('http://tpmongo-php:80/', { headers: { Accepts: 'application/json' } });
    check(response, { 'Liste des livres - statut 200': (r) => r.status === 200 });

    //randomSleep(1, 5);
    // sleep(1);

    // 2. Affichage de la page 30
    response = http.get('http://tpmongo-php:80?page_number=30', { headers: { Accepts: 'application/json' } });
    check(response, { 'Page 30 - statut 200': (r) => r.status === 200 });

    //randomSleep(1, 5);
    // sleep(1);

    // 3. Consultation des détails d'un livre
   // const bookId = '67595e0456953c9057782960'; // Exemple d'ID existant 677ac07a4c9b5dc74009e272
    // response = http.get(`http://tpmongo-php:80/get.php?id=${bookId}`, { headers: { Accepts: 'application/json' } });
    response = http.get(`http://tpmongo-php:80/get.php?`, { headers: { Accepts: 'application/json' } });
    check(response, { 'Détails du livre - statut 200': (r) => r.status === 200 });

    //randomSleep(1, 5);
    // sleep(1);

    // 4. Retour à la liste
    response = http.get('http://tpmongo-php:80/', { headers: { Accepts: 'application/json' } });
    check(response, { 'Retour à la liste - statut 200': (r) => r.status === 200 });

    //randomSleep(1, 5);
    // sleep(1);

    //do delete 1 times out of 1000
    let random = Math.floor(Math.random() * 1000);
    if (random == 1) {
            
        // 5. Suppression d'un livre
        //response = http.del(`http://tpmongo-php:80/delete.php?id=${bookId}`, null, { headers: { Accepts: 'application/json' } });
        response = http.del(`http://tpmongo-php:80/delete.php?`, null, { headers: { Accepts: 'application/json' } });
        check(response, { 'Suppression d\'un livre - statut 200': (r) => r.status === 200 });

    // randomSleep(1, 5);
        // sleep(1);

        // 6. Ajout d'un nouveau livre
        const newBookPayload = {
            author: 'Auteur Test',
            cote: 'MS 100',
            edition: '1',
            langue: 'français',
            objectid: '67890',
            century: '20',
            title: 'Livre Test K6',
        };

        response = http.post(
            'http://tpmongo-php:80/create.php',
            newBookPayload,
            { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
        );
    }


    // Log de la réponse pour diagnostic
    // console.log('Response headers:', response.headers);
    // console.log('Response body:', response.body);

    check(response, {
        'Ajout d\'un livre - statut 200': (r) => r.status === 200,
    });
    //randomSleep(1, 5);
    // sleep(1);

    const getResponse = http.get(`http://tpmongo-php:80/get.php?`, { headers: { Accepts: 'application/json' } });
    check(getResponse, { 'Consultation du livre ajouté - statut 200': (r) => r.status === 200 });
    // const idMatch = response.body.match(/Element ajoute ID\s*([\w]+)/);
    // let newBookId = "";
    // if (idMatch) {
    //     newBookId = idMatch[1];
    //     console.log(`ID extrait depuis la réponse : ${newBookId}`);
    //     const getResponse = http.get(`http://tpmongo-php:80/get.php?id=${newBookId}`, { headers: { Accepts: 'application/json' } });
    //     check(getResponse, { 'Consultation du livre ajouté - statut 200': (r) => r.status === 200 });
    // } else {
    //     console.error('Impossible de trouver la variable new_id dans la réponse HTML.');
    //     const getResponse = http.get(`http://tpmongo-php:80/get.php?`, { headers: { Accepts: 'application/json' } });
    //     check(getResponse, { 'Consultation du livre ajouté - statut 200': (r) => r.status === 200 });
    // }



    console.log("Fin du test K6 - Scénario complet");
}

