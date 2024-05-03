<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('Recherche');

// génération du contenu de la page

// Regarder avec table pour le form

echo
    '<main>',
    '<section class="centre">',
    '<h2>', 'Rechercher des articles', '</h2>',
    '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte.</p>',
    '<div>',
    '<form method="POST" action="votre_script_php.php">', // Remplacez "votre_script_php.php" par le nom de votre script PHP de traitement
    '<input type="text" name="recherche">',
    '<button type="submit">Rechercher</button>',
    '</form>',
    '</div>',
    '</section>',
    '</main>';
    
affPiedDePage();

// envoi du buffer
ob_end_flush();