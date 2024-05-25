<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('Article');

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
    // echo "Identifiant de l'article :", $idArticle;

    if (! parametresControle('get', ['id'])){
        affErreurL('Il faut utiliser une URL de la forme : http://..../php/article.php?id=XXX');
        return; // ==> fin de la fonction
    }

    // Déchiffrer l'identifiant de l'article à partir de l'URL
    $idArticle = dechiffrerIdArticleURL($_GET['id']);

    if ($idArticle == FALSE){ // FALSE car la fonction de déchiffrage retourne false en cas d'erreur
        affErreurL('La fonction de déchiffrage n\'a pas réussi a déchiffre cet id');
        return; // ==> fin de la fonction
    }

    $id = (int)$idArticle;

    if ($id <= 0){
        affErreurL('L\'identifiant doit être un entier strictement positif');
        return; // ==> fin de la fonction
    }

    // ouverture de la connexion à la base de données
    $bd = bdConnect();

    // Récupération de l'article, des informations sur son auteur,
    // et de ses éventuelles commentaires
    // $id est un entier, donc pas besoin de le protéger avec mysqli_real_escape_string()
    $sql = "SELECT *
            FROM (article INNER JOIN utilisateur ON arAuteur = utPseudo)
            LEFT OUTER JOIN commentaire ON arID = coArticle
            WHERE arID = $id
            ORDER BY coDate DESC, coID DESC";

    $result = bdSendRequest($bd, $sql);

    // Fermeture de la connexion au serveur de BdD, réalisée le plus tôt possible
    mysqli_close($bd);

    // pas d'articles --> fin de la fonction
    if (mysqli_num_rows($result) == 0) {
        affErreurL('L\'identifiant de l\'article n\'a pas été trouvé dans la base de données');
        // Libération de la mémoire associée au résultat de la requête
        mysqli_free_result($result);
        return; // ==> fin de la fonction
    }

    $tab = mysqli_fetch_assoc($result);

    // Mise en forme du prénom et du nom de l'auteur pour affichage dans le pied du texte de l'article
    // Exemple :
    // - pour 'johNnY' 'bigOUde', cela donne 'J. Bigoude'
    // - pour 'éric' 'merlet', cela donne 'É. Merlet'
    // À faire avant la protection avec htmlentities() à cause des éventuels accents
    $auteur = upperCaseFirstLetterLowerCaseRemainderL(mb_substr($tab['utPrenom'], 0, 1, encoding:'UTF-8')) . '. ' . upperCaseFirstLetterLowerCaseRemainderL($tab['utNom']);

    // ATTENTION : protection contre les attaques XSS
    $auteur = htmlProtegerSorties($auteur);

    // ATTENTION : protection contre les attaques XSS
    $tab = htmlProtegerSorties($tab);

    echo
        '<main id="article">',
            '<article>',
                '<h3>', $tab['arTitre'], '</h3>',
                '<img src="../upload/', $tab['arID'], '.jpg" alt="Photo d\'illustration | ', $tab['arTitre'], '">',
                $tab['arTexte'],
                '<footer>',
                    'Par <a href="redaction.php#', $tab['utPseudo'], '">', $auteur, '</a>. ',
                    'Publié le ', dateIntToStringL($tab['arDatePubli']),
                    isset($tab['arDateModif']) ? ', modifié le '. dateIntToStringL($tab['arDateModif']) : '',
                '</footer>',
            '</article>';

    //pour accéder une seconde fois au premier enregistrement de la sélection
    mysqli_data_seek($result, 0);

    // Génération du début de la zone de commentaires
    echo '<section>',
            '<h2>Réactions</h2>';

    // s'il existe des commentaires, on les affiche un par un.
    if (isset($tab['coID'])) {
        echo '<ul>';
        while ($tab = mysqli_fetch_assoc($result)) {
            echo '<li>',
                    '<p>Commentaire de <strong>', htmlProtegerSorties($tab['coAuteur']),
                        '</strong>, le ', dateIntToStringL($tab['coDate']),
                    '</p>',
                    '<blockquote>', htmlProtegerSorties($tab['coTexte']), '</blockquote>',
                '</li>';
        }
        echo '</ul>';
    }
    // sinon on indique qu'il n'y a pas de commentaires
    else {
        echo '<p>Il n\'y a pas de commentaire pour cet article. </p>';
    }

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($result);

    echo
        '<p>',
            '<a href="./connexion.php">Connectez-vous</a> ou <a href="./inscription.php">inscrivez-vous</a> pour pouvoir commenter cet article !',
        '</p>',
        '</section>',
    '</main>';
}

//___________________________________________________________________
/**
 * Renvoie une copie de la chaîne UTF8 transmise en paramètre après avoir mis sa
 * première lettre en majuscule et toutes les suivantes en minuscule
 *
 * @param  string   $str    la chaîne à transformer
 *
 * @return string           la chaîne résultat
 */
function upperCaseFirstLetterLowerCaseRemainderL(string $str) : string {
    $str = mb_strtolower($str, encoding:'UTF-8');
    $fc = mb_strtoupper(mb_substr($str, 0, 1, encoding:'UTF-8'));
    return $fc.mb_substr($str, 1, mb_strlen($str), encoding:'UTF-8');
}

//_______________________________________________________________
/**
 * Affichage d'un message d'erreur dans une zone dédiée de la page.
 *
 * @param  string  $msg    le message d'erreur à afficher.
 *
 * @return void
 */
function affErreurL(string $message) : void {
    echo
        '<main>',
            '<section>',
                '<h2>Oups, il y a eu une erreur...</h2>',
                '<p>La page que vous avez demandée a terminé son exécution avec le message d\'erreur suivant :</p>',
                '<blockquote>', $message, '</blockquote>',
            '</section>',
        '</main>';
}

//_______________________________________________________________
/**
 * Convertir un string de BBCode en HTML.
 *
 * @param  string  $msg    le message d'erreur à afficher.
 *
 * @return void
 */
function convertBBCodeToHTML(string $BBCode) : string {
    // On échape les caractères HTML pour éviter les attaques XSS

    // On remplace les balises [b] par des balises <strong>
    $BBCode = preg_replace('#\[b\](.+)\[/b\]#Us', '<strong>$1</strong>', $BBCode);
    // On remplace les balises [i] par des balises <em>
    $BBCode = preg_replace('#\[i\](.+)\[/i\]#Us', '<em>$1</em>', $BBCode);
    // On remplace les balises [u] par des balises <u>
    $BBCode = preg_replace('#\[u\](.+)\[/u\]#Us', '<u>$1</u>', $BBCode);
    // On remplace les balises [s] par des balises <del>
    $BBCode = preg_replace('#\[s\](.+)\[/s\]#Us', '<del>$1</del>', $BBCode);
    // On remplace les balises [url] par des balises <a>
    $BBCode = preg_replace('#\[url\](.+)\[/url\]#Us', '<a href="$1">$1</a>', $BBCode);
    // On remplace les balises [url=...] par des balises <a>
    $BBCode = preg_replace('#\[url=(.+)\](.+)\[/url\]#Us', '<a href="$1">$2</a>', $BBCode);
    // On remplace les balises [img] par des balises <img>
    $BBCode = preg_replace('#\[img\](.+)\[/img\]#Us', '<img src="$1" alt="Image">', $BBCode);
    // On remplace les balises [quote] par des balises <blockquote>
    $BBCode = preg_replace('#\[quote\](.+)\[/quote\]#Us', '<blockquote>$1</blockquote>', $BBCode);
    // On remplace les balises [code] par des balises <pre>
    $BBCode = preg_replace('#\[code\](.+)\[/code\]#Us', '<pre>$1</pre>', $BBCode);
    // On remplace les balises [size=...] par des balises <span>
    $BBCode = preg_replace('#\[size=(\d+)\](.+)\[/size\]#Us', '<span style="font-size: $1px">$2</span>', $BBCode);
    // On remplace les balises [color=...] par des balises <span>
    $BBCode = preg_replace('#\[color=(.+)\](.+)\[/color\]#Us', '<span style="color: $1">$2</span>', $BBCode);
    // On remplace les balises [list] par des balises <ul>
    $BBCode = preg_replace('#\[list\](.+)\[/list\]#Us', '<ul>$1</ul>', $BBCode);
    // On remplace les balises [*] par des balises <li>
    $BBCode = preg_replace('#\[\*\](.+)\[/\*\]#Us', '<li>$1</li>', $BBCode);
    // On remplace les balises [center] par des balises <div>
    $BBCode = preg_replace('#\[center\](.+)\[/center\]#Us', '<div style="text-align: center">$1</div>', $BBCode);
    // On remplace les balises [right] par des balises <div>
    $BBCode = preg_replace('#\[right\](.+)\[/right\]#Us', '<div style="text-align: right">$1</div>', $BBCode);
    // On remplace les balises [justify] par des balises <div>
    $BBCode = preg_replace('#\[justify\](.+)\[/justify\]#Us', '<div style="text-align: justify">$1</div>', $BBCode);
    // On remplace les balises [left] par des balises <div>
    $BBCode = preg_replace('#\[left\](.+)\[/left\]#Us', '<div style="text-align: left">$1</div>', $BBCode);
    // On replace les balises deezer par des balises <iframe>
    $BBCode = preg_replace('#\[deezer\](.+)\[/deezer\]#Us', '<iframe src="https://www.deezer.com/plugins/player?format=classic&autoplay=false&playlist=true&width=700&height=350&color=007FEB&layout=dark&size=medium&type=playlist&id=$1&app_id=1" width="700" height="350" frameborder="0"></iframe>', $BBCode);

}