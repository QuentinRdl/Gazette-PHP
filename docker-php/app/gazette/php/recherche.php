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

echo '</section>';
echo '</main>';
affPiedDePage();

// envoi du buffer
ob_end_flush();

 
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
            echo  '<div class="success">', '<p>', 'Critère de recherche utilisés : "';
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
            if($mots[0] == "") {return;} // Ne rien retourner si la chaine de recherche est vide
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
        }

        afficherFormulaireRecherche();
        
        if($mots != null) {
            $sql.= " ORDER BY arDatePubli DESC;";
            $bd = bdConnect(); // Ouverture de la connexion à la BDD


            $tab = bdSelectArticlesActus($bd, $sql);

            // pas d'articles --> fin de la fonction
            if ($tab == null || count($tab) == 0) {
                echo '</section>', 
                '<section>', '<h2>', 'Résultats', '</h2>', '<article class="resultatRechercheNulle">';
                echo '<p>Aucun article ne correspondant à vos criètres de recherche.</p>',
                '</section>';
                // return; // ==> fin de la fonction
            } else {
                // echo 'article trouve';
                afficherArticlesTrouve($tab); 
            }

            
        }
}

/**
 * Affiche le formulaire de recherche
 * @param void
 * @return void
 */
function afficherFormulaireRecherche() {
    echo 
    '<div>',
        '<form method="POST" action="">',
        '<input type="text" name="recherche">',
        '<button type="submit">Rechercher</button>',
        '</form>',
    '</div>';
}

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
/**
 * Affiche les articles trouvés par la requête SQL
 * @param array $articles Les articles a afficher
 * @return void
 */
function afficherArticlesTrouve(array $articles) : void {
    $nbArticles = count($articles);
    echo '</section>';
    // Boucle foreach pour parcourir les articles de la page actuelle
    for ($i = 0; $i < $nbArticles; $i++) {
        $article = $articles[$i];
        if(!isset($article['arDatePubli']) || is_null($article['arDatePubli'])) {
            return;
        }
        $date = dateIntToStringAAAAMM($article['arDatePubli']);

        /*
        // Affichage du titre de la section si c'est le premier article de la page
        if ($i === $startIndex) {
            echo '<section>', '<h2>', $date, '</h2>';
        }
        */
        // Affichage du titre de la section si c'est le premier article
        if($i == 0) {
            echo '<section>', '<h2>', $date, '</h2>';
        }
        

        affUnArticle($article['arID'], $article['arTitre'], $article['arResume']);
        
        /*
        // On regarde si on est à la fin de la page
        if($i + 1 >= $nbArticles) {
            afficherBoutonsNavigation($currentPage, $perPage, $totalPages);
            return;
        } 
        */

        // Affichage de la date de début de la nouvelle section si la date de l'article suivant est différente
        if($i + 1 >= $nbArticles) {
            echo '</section>';
        } elseif (dateIntToStringAAAAMM($articles[$i + 1]['arDatePubli']) !== $date) {
            echo '</section>';
            echo '<section>', '<h2>', dateIntToStringAAAAMM($articles[$i + 1]['arDatePubli']), '</h2>', '<article class="resume">';
        }
    }
    
}

//_______________________________________________________________
/**
 * Affiche un article sous la forme actus.php
 *
 * @param   int         id de l'article
 * @param   string      titre de l'article
 * @param   string      résumé de l'article
 *
 * @return  void
 */
function affUnArticle(int $id, string $titre, string $resume) : void {
    $titre = htmlProtegerSorties($titre); // ATTENTION : à ne pas oublier !!!
    // On chiffre l'id le lien de l'article, comme la fonction de chiffrage prend 
    // un string on converti l'entier en string
    $idChiffre = $id . "";
    $idChiffre = chiffrerPourURL($idChiffre);
    $pathImage = "../upload/{$id}.jpg";
    echo
            '<article class="resume">';
            if(!file_exists($pathImage)) { // Si l'image n'existe pas on affiche une image par défaut
                echo '<img src="../images/none.jpg" alt="Photo d\'illustration | ', $titre, '">';

            } else {
                echo '<img src="'. $pathImage. '" alt="Photo d\'illustration | ', $titre, '">';
            }
                echo '<h3>', $titre, '</h3>',
                '<p>', $resume, '</p>',
                '<footer>', '<a href="./article.php?id=', $idChiffre, '">Lire l\'article</a></footer>',
            '</article>';
}