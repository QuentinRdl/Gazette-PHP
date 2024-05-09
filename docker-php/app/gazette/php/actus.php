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
    //affArticlesDate($tab);

    $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1; // Récupère le numéro de la page depuis l'URL
    $perPage = 4; // Nombre d'articles à afficher par page

    // Appel de la fonction pour afficher les articles paginés
    affArticlesDate($tab, $currentPage, $perPage);

    // Fermeture de la connexion au serveur de BdD
    mysqli_close($bd);

    echo '</main>';
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
/**
 * Affiche les articles paginés
 *
 * @param array $articles     Tableau contenant les informations des articles
 * @param int $currentPage    Numéro de la page actuelle
 * @param int $perPage        Nombre d'articles à afficher par page
 * @return void
 */
function affArticlesDate(array $articles, int $currentPage, int $perPage): void {
    // Calcul de l'index de départ et de fin des articles pour la page actuelle
    $startIndex = ($currentPage - 1) * $perPage;
    $endIndex = $startIndex + $perPage - 1;
    $nbArticles = count($articles);
    $totalPages = ceil($nbArticles / $perPage); // Calcul du nombre de pages

    // Boucle foreach pour parcourir les articles de la page actuelle
    for ($i = $startIndex; $i <= $endIndex && $i < $nbArticles; $i++) {
        $article = $articles[$i];
        if(!isset($article['arDatePubli']) || is_null($article['arDatePubli'])) {
            return;
        }
        $date = dateIntToStringAAAAMM($article['arDatePubli']);

        // Affichage du titre de la section si c'est le premier article de la page
        if ($i === $startIndex) {
            echo '<section>', '<h2>', $date, '</h2>';
        }

        affUnArticle($article['arID'], $article['arTitre'], $article['arResume']);
        
        // On regarde si on est à la fin de la page
        if($i + 1 >= $nbArticles) {
            afficherBoutonsNavigation($currentPage, $perPage, $totalPages);
            return;
        } 

        // Affichage de la date de début de la nouvelle section si la date de l'article suivant est différente
        if ($i < $endIndex && dateIntToStringAAAAMM($articles[$i + 1]['arDatePubli']) !== $date) {
            echo '</section>';
            echo '<section>', '<h2>', dateIntToStringAAAAMM($articles[$i + 1]['arDatePubli']), '</h2>', '<article class="resume">';
        }
    }

    afficherBoutonsNavigation($currentPage, $perPage, $totalPages);
}

/**
 * Affichage des boutons de navigations entre les pages
 * @param  int $page       page courante
 * @param int $perPage     nombre d'articles à afficher par page
 * @param  int $totalPages nombre total de pages
 * @return void
 */
function afficherBoutonsNavigation($page, $perPage, $totalPages) {
    // Affichage des boutons de navigation entre les pages
    
    echo '<div class="pagination">';
    for ($page = 1; $page <= $totalPages; $page++) {
        echo '<a href="?page=' . $page . '">' . $page . '</a>';
    }
    echo '</div>';
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