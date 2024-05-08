<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('Le site de désinformation n°1 des étudiants en Licence Info');

// génération du contenu de la page
affContenuL();

affPiedDePage();

// envoi du buffer
ob_end_flush();


/*********************************************************
 *
 * Définitions des fonctions locales de la page
 *
 *********************************************************/
//_______________________________________________________________
/**
 * Affichage du contenu principal de la page
 *
 * @return  void
 */
function affContenuL() : void {


    echo '<main>';

    $bd = bdConnect();

    // génération des 3 derniers articles
    $sql = 'SELECT arID, arTitre, arResume, arDatePubli
             FROM article
             ORDER BY arDatePubli DESC';

    $tab = bdSelectArticlesActus($bd, $sql);
    affArticlesDate($tab);

    // Fermeture de la connexion au serveur de BdD
    mysqli_close($bd);

    echo '</main>';
}

//_______________________________________________________________
/**
 * Affiche une vignette
 *
 * @param   int         id de l'article
 * @param   string      titre de l'article
 *
 * @return  void
 */
function affUnArticleL(int $id, string $titre) : void {
    $titre = htmlProtegerSorties($titre); // ATTENTION : à ne pas oublier !!!
    // On chiffre l'id le lien de l'article, comme la fonction de chiffrage prend 
    // un string on converti l'entier en string
    $idChiffre = $id . "";
    $idChiffre = chiffrerPourURL($idChiffre);
    echo
            '<a href="./article.php?id=', $idChiffre, '">',
                '<img src="../upload/', $id, '.jpg" alt="Photo d\'illustration | ', $titre, '"><br>',
                $titre,
            '</a>';
}
//_______________________________________________________________
/**
 * Affiche un bloc de 3 vignettes.
 *
 * @param   string      titre du bloc de 3 vignettes
 * @param   array       ids et titres des articles (clé : id de l'article, valeur associée à la clé : titre de l'article)
 *
 * @return  void
 */
function affArticlesDate(array $articles) : void {
    $count = 0;
    $titreBloc = "test";
   

    /*
    int $nbArticles = count($articles);
    for($count = 0; $count < $nbArticles; $count++) {
        $date = ;

        // Extraction de l'année (4 premiers caractères) et du mois (2 caractères suivants)
        $year = substr($date, 0, 4);
        $month = substr($date, 4, 2);

        // Concaténation pour obtenir le format AAAAMM
        $yearMonth = $year . $month;

        echo "Année et mois : $yearMonth";
    }
    */


    /*
    echo    '<section class="centre">',
                '<h2>', $titreBloc, '</h2>';
    //$date = $articles
    foreach($articles as $id => $titre) {
        
        affUnArticleL($id, $titre);
    }
    echo    '</section>';
    */

    // On récupère la date du tout premier article
    $lastDate = $articles[0]['arDatePubli'];
    $lastDate = dateIntToStringAAAAMM($lastDate);

    echo    '<section class="centre">', '<h2>', $lastDate, '</h2>';
    //$date = $articles

    // Boucle foreach pour parcourir chaque article
    foreach ($articles as $article) {
        // Si la date (Année mois) de l'article actuel est le même que celle du dernier article,
        // on les affiche dans le même bloc, sinon on recrée une section avec la nouvelle date
        $date = dateIntToStringAAAAMM($article['arDatePubli']);
        
        if(!strcmp($lastDate, $date) == 0) { 
            echo    '</section>';
            echo    '<section class="centre">', '<h2>', $date, '</h2>';
        }
        echo "<p>ID : " . $article['arID'] . "</p>";
        echo "<p>Titre : " . $article['arTitre'] . "</p>";
        echo "<p>Résumé : " . $article['arResume'] . "</p>";

        $lastDate = $date;
    }
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