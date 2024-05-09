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
if (isset($_POST["recherche"])) {
    // Appel de la fonction de traitement
    $err = recuperationMotsNonValide($_POST["recherche"]);
} else {
    $err = NULL;
}
 
/*
// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_POST["recherche"] = trim($_POST["recherche"]); // Enleve les espaces blancs
    $_POST["recherche"] = strip_tags($_POST["recherche"]); // Enleve les balises HTML et PHP

    // Vérifie si le champ de recherche est défini et non vide
    if (isset($_POST["recherche"])) {
        // Appel de la fonction de traitement
        $err = recuperationMotsNonValide($_POST["recherche"]);
        afficherFormulaireRecher($err);
    } else {
        $err[] = "Le champ de recherche est vide.";
    }
}
*/
afficherFormulaireRecher($err);

/** Récupération des mots non valides
 * @param string $recherche
 * 
 * @return array $motsNonValides
 */
function recuperationMotsNonValide(string $recherche) : ?array {
    if(empty($recherche)) return NULL; // Si le sting de recherche est vide cad l'utilisateur n'a rien tapé mais a soumis le form
    $motsNonValides = array();
    $mots = explode(" ", $recherche);
    $mots = array_unique($mots); // Supprime les doublons
    $motsFinal = array();
    foreach ($mots as $mot) {
        if (strlen($mot) < 3) {
            $motsNonValides[] = $mot;
        }
    }
    return $motsNonValides;
}

/** Récupération des mots valides
 * @param string $recherche
 * 
 * @return array $motsValides
 */
function recuperationMotsValide(string $recherche) : array {
    $motsValides = array();
    $mots = explode(" ", $recherche);
    $mots = array_unique($mots); // Supprime les doublons
    $motsFinal = array();
    foreach ($mots as $mot) {
        if (strlen($mot) > 2) {
            $motsValides[] = $mot;
        }
    }
    return $motsValides;
}

/**
 * Verification de la recherche
 * @param string $recherche
 * 
 * @return array $motsValides
 */
// Fonction de traitement de la recherche
function verifierRecherche(string $recherche): array {
    $err = array();
    // Effectuer le traitement de la recherche ici
    $mots = explode(" ", $recherche);
    $mots = array_unique($mots); // Supprime les doublons
    $motsFinal = array();
    $motsNonValide = array();
    foreach ($mots as $mot) {
        if (strlen($mot) > 2) {
            $motsFinal[] = $mot; // On retient seulement les mots >= 3 caractères
        } else {
            $motsNonValide[] = $mot; // On retient les mots non valide pour les afficher si aucun mot n'est valide
        }
    }
    if (empty($motsFinal) && !empty($motsNonValide)) {
        echo "Le ou les critères de recherche sont invalides.";
    } else {
        echo "Les mots valides sont :";
        foreach($motsFinal as $mot) {
            echo "Mot valide = : ". $mot. "<br>";
        }
    }
    return $motsFinal;
    /*
    foreach($motsFinal as $mot) {
        echo "Mot valide = : ". $mot. "<br>";
    }
    foreach($motsNonValide as $mot) {
        echo "Mot non valide = : ". $mot. "<br>";
    }
    echo "Traitement de la recherche pour : " . $recherche;
    */
}

function afficherFormulaireRecher(?array $err): void {
    echo
        '<main>',
        '<section class="centre">',
        '<h2>', 'Rechercher des articles', '</h2>',
        '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte.</p>';
        if (is_array($err) && count($err) == 1) afficherErreur("Le critère suivant n'a pas été pris en compte :", $err);
        elseif(is_array($err) && count($err) > 1) afficherErreur("Les critères suivant n'ont pas été pris en compte :", $err);
        /*
        if (is_array($err) && count($err) == 1) {
            echo    '<div class="erreur">Le critère suivant n\'a pas été pris en compte :';
        */
        /*
        if (is_array($err) && count($err) > 0) {
            echo    '<div class="erreur">Le critère suivant n\'a pas été pris en compte :',
                        '<ul>';
            foreach ($err as $e) {
                echo        '<li>', $e, '</li>';
            }
            echo        '</ul>',
                    '</div>';
        }*/
        echo '<div>',
        '<form method="POST" action="">',
        '<input type="text" name="recherche">',
        '<button type="submit">Rechercher</button>',
        '</form>',
        '</div>',
        '</section>',
        '</main>';
}
affPiedDePage();

// envoi du buffer
ob_end_flush();