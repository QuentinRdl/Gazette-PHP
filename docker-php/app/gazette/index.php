<?php

// chargement des bibliothèques de fonctions
require_once('./php/bibli_gazette.php');
require_once('./php/bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('Le site de désinformation n°1 des étudiants en Licence Info', '.');

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
    $sql0 = 'SELECT arID, arTitre
             FROM article
             ORDER BY arDatePubli DESC
             LIMIT 0, 3';
    $tab0 = bdSelectArticlesL($bd, $sql0);
    affBlocTroisArticlesL('&Agrave; la Une', $tab0);

    // génération des 3 articles les plus commentés
    $sql1 = 'SELECT arID, arTitre
             FROM article
             LEFT OUTER JOIN commentaire ON coArticle = arID
             GROUP BY arID
             ORDER BY COUNT(coArticle) DESC, rand()
             LIMIT 0, 3';
    $tab1 = bdSelectArticlesL($bd, $sql1);
    affBlocTroisArticlesL('L\'info brûlante', $tab1);

    // génération des 3 articles parmi les articles restants
    $sql2 = 'SELECT arID, arTitre FROM article
             WHERE arID NOT IN (' . join(',',array_keys($tab0)) . ',' . join(',',array_keys($tab1)) . ')
             ORDER BY rand() LIMIT 0, 3';
    $tab2 = bdSelectArticlesL($bd, $sql2);

    // Fermeture de la connexion au serveur de BdD
    mysqli_close($bd);

    affBlocTroisArticlesL('Les incontournables', $tab2);

    affHoroscopeL();

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
    // On regarde s'il y a une image pour l'article
    if (!file_exists('./upload/' . $id . '.jpg')) {
        // Pas d'image on met la none.jpg
        echo
            '<a href="./php/article.php?id=', $idChiffre, '">',
                '<img src="images/none.jpg" alt="Pas de photo ! | ', $titre, '"><br>',
                $titre,
            '</a>';
    } else {
        // Il y a une image, on l'affiche
        echo
        '<a href="./php/article.php?id=', $idChiffre, '">',
        '<img src="upload/', $id, '.jpg" alt="Photo d\'illustration | ', $titre, '"><br>',
        $titre,
        '</a>';
    }
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
function affBlocTroisArticlesL(string $titreBloc, array $articles) : void {
    echo    '<section class="centre">',
                '<h2>', $titreBloc, '</h2>';
    foreach($articles as $id => $titre){
        affUnArticleL($id, $titre);
    }
    echo    '</section>';
}

//_______________________________________________________________
/**
 * Affichage de l'horoscope
 *
 * @return  void
 */
function affHoroscopeL() : void {
    echo
    '<section>',
            '<h2>Horoscope de la semaine</h2>',

            '<p>Vous l\'attendiez tous, voici l\'horoscope du semestre pair de l\'année 2023-2024. Sans surprise, il n\'est pas terrible...</p>',

            '<table id="horoscope">',
                '<tr>',
                    '<td>Signe</td>',
                    '<td>Date</td>',
                    '<td>Votre horoscope</td>',
                '</tr>',
                '<tr>',
                    '<td>&#9800; Bélier</td>',
                    '<td>du 21 mars<br>au 19 avril</td>',
                    '<td rowspan="4">',
                        '<p>Après des vacances bien méritées, l\'année reprend sur les chapeaux de roues. Tous les signes sont concernés. </p>',
                        '<p>Jupiter s\'aligne avec Saturne, péremptoirement à Venus, et nous promet un semestre qui ne sera pas de tout repos. ',
                        'Février sera le mois le plus tranquille puisqu\'il ne comporte que 29 jours.</p>',
                        '<p>Les fins de mois seront douloureuses pour les natifs du 2e décan au moment où tomberont les tant-attendus résultats ',
                            'du module d\'<em>Algorithmique et Structures de Données</em> du semestre 3.</p>',
                    '</td>',
                '</tr>',
                '<tr>',
                    '<td>&#9801; Taureau</td>',
                    '<td>du 20 avril<br>au 20 mai</td>',
                '</tr>',
                '<tr>',
                    '<td>...</td>',
                    '<td>...</td>',
                '</tr>',
                '<tr>',
                    '<td>&#9811; Poisson</td>',
                    '<td>du 20 février<br>au 20 mars</td>',
                '</tr>',
            '</table>',

            '<p>Malgré cela, notre équipe d\'astrologues de choc vous souhaite à tous un bon semestre.</p>',
        '</section>';
}
