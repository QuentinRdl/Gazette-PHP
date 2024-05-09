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
    $_POST["recherche"] = trim($_POST["recherche"]); // Enleve les espaces blancs
    $_POST["recherche"] = strip_tags($_POST["recherche"]); // Enleve les balises HTML et PHP
    $err = recuperationMotsNonValide($_POST["recherche"]);
    afficherFormulaireRecher($err, $_POST["recherche"]);
} else {
    $err = NULL;
    afficherFormulaireRecher($err);
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

function afficherFormulaireRecher(?array $err, ?string $mots=null): void {
    echo
        '<main>',
        '<section class="centre">',
        '<h2>', 'Rechercher des articles', '</h2>',
        '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte.</p>';
        if (is_array($err) && count($err) == 1) afficherErreur("Le critère suivant n'a pas été pris en compte :", $err, false);
        elseif(is_array($err) && count($err) > 1) afficherErreur("Les critères suivant n'ont pas été pris en compte :", $err, false);
        if ($mots != null) {
            $first = true;
            $mots = recuperationMotsValide($mots);
            echo  '<div class="affichageResultats">', '<p>', 'Critère de recherche utilisés : "';
            foreach($mots as $mot) {
                if($first) {
                    $first = false;
                } else {echo " ";}
                echo $mot;
            }
            echo '".','</p>', '</div>';
        }
        // On traite le cas ou on recherche qu'un seul mot
        $sql = "VIDE";
        if($mots != NULL && count($mots) == 1) {
            $sql = "SELECT arID, arTitre, arResume, arDatePubli FROM article WHERE arTitre LIKE '%$mots[0]%' OR ArResume LIKE '%$mots[0]%' ";
            //LIKE '%$mots[0]%' OR ArResume LIKE '%$mots[0]%'"
        } else if ($mots != NULL && count($mots) > 1) {
            // On traite le cas ou on recherche plusieurs mots
            $sql = "SELECT arID, arTitre, arResume, arDatePubli FROM article";
            $first = true;
            foreach($mots as $mot) {
                if($first) {
                    $first = false;
                    //$sql.= ' WHERE (';
                    $sql.=  ' WHERE (arTitre ';
                } else {
                    $sql.= ' AND (arTitre ';
                }
                $sql.= "LIKE '%$mot%' OR ArResume LIKE '%$mot%')";
            }
            
            echo $sql;
            // exit();
        }

        

        if($mots != null) {
            $sql.= " ORDER BY arDatePubli DESC;";
            $bd = bdConnect(); // Ouverture de la connexion à la BDD


            $tab = bdSelectArticlesActus($bd, $sql);
            $article = $tab[0];
            echo "RESRSERSERESRESRSE\n\n\n\n";
            echo $article['arDatePubli'];

            /*
            $result = bdSendRequest($bd, $sql); // On envoie la requête
            mysqli_close($bd); // On ferme la connexion
            */
            // pas d'articles --> fin de la fonction
            if (mysqli_num_rows($result) == 0) {
                echo 'Aucun article correspondent n\'a été trouvé';
                // Libération de la mémoire associée au résultat de la requête
                mysqli_free_result($result);
                return; // ==> fin de la fonction
            }

            while ($t = mysqli_fetch_assoc($result)) {
                $res[$t['arID']] = $t['arTitre'];
                // echo '<p>', $t['arID'], $t['arTitre'], '</p>';
            }
            echo 'article trouve';
            mysqli_free_result($result); // On libère le résultat
        }

        
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

/**
 * Renvoie dans un tableau l'id, le titre, le résumé et la date de publication des articles sélectionnés par une requête SQL
 *
 * @param  mysqli  $bd      référence pointant sur l'objet connecteur à la base de données
 * @param  string  $sql     la requête SQL à envoyer
 *
 * @return array            tableau contenant les informations des articles sélectionnés
 */
function bdSelectArticlesActus(mysqli $bd, string $sql): array {
    $res = [];
    $result = bdSendRequest($bd, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $articleInfo = [
            'arID' => $row['arID'],
            'arTitre' => $row['arTitre'],
            'arResume' => $row['arResume'],
            'arDatePubli' => $row['arDatePubli']
        ];
        $res[] = $articleInfo;
    }

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($result);

    return $res;
}