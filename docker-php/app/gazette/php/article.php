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
// On regarde si le formulaire correspondant aux commentaires a été envoyé
if(isset($_POST['comment'])) {
    ajouterCommentaire(); // Gère l'ajout de commentaires
}

if(isset($_POST['set_coID'])) {
    deleteComment($_POST['coID']); // Si le formulaire de suppression de commentaire a été envoyé
}

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
        affErreurL('Il faut utiliser une URL de la forme : http://.../php/article.php?id=XXX');
        return; // ==> fin de la fonction
    }

    // Déchiffrer l'identifiant de l'article à partir de l'URL
    $idArticle = dechiffrerURL($_GET['id']);

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
    // et de ses éventuels commentaires
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

    // On regarde si l'auteur de l'article est l'utilisateur de la session
    if(isset($_SESSION['pseudo']) && $_SESSION['pseudo'] == $tab['arAuteur']) {
        // On peut afficher le bandeau de suppression / modification de l'article
        bandeauSuppressionArticle($tab['arID']);
    }

    // Mise en forme du prénom et du nom de l'auteur pour affichage dans le pied du texte de l'article
    // Exemple :
    // - pour 'johNnY' 'bigOUde', cela donne 'J. Bigoude'
    // - pour 'éric' 'merlet', cela donne 'É. Merlet'
    $auteur = upperCaseFirstLetterLowerCaseRemainderL(mb_substr($tab['utPrenom'], 0, 1, encoding:'UTF-8')) . '. ' . upperCaseFirstLetterLowerCaseRemainderL($tab['utNom']);

    // Protection contre les attaques XSS
    $auteur = htmlProtegerSorties($auteur);
    $tab = htmlProtegerSorties($tab);

    // On convertit le texte BBCode en HTML
    $newTexte = BBCodeToHTML($tab['arTexte']);

    // On regarde s'il y a une image qui correspond à l'article en construisant le path
    $pathImage = "../upload/";
    $pathImage .= $tab['arID'];
    $pathImage .= ".jpg";

    echo
            '<article>',
                '<h3>', $tab['arTitre'], '</h3>';
    // On affiche seulement l'image de l'article si elle existe
    if(file_exists($pathImage)) {
        echo '<img src="../upload/', $tab['arID'], '.jpg" alt="Photo d\'illustration | ', $tab['arTitre'], '">';
    }
                //$tab['arTexte'],
    echo $newTexte, '<br>',
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
    afficherCommentaires($result, $tab);

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($result);

    // On regarde si l'user est authentifié, puis si c'est un auteur
    $authentifie = $redacteur = 0;
    if(isset($_SESSION['connecte']) && $_SESSION['connecte'] == 1) {
        $authentifie = 1;
        if(isset($_SESSION['redacteur']) && $_SESSION['redacteur'] == 1) {
            $redacteur = 1;
        }
    }

    if(!$authentifie) {
        echo
        '<p>',
        '<a href="./connexion.php">Connectez-vous</a> ou <a href="./inscription.php">inscrivez-vous</a> pour pouvoir commenter cet article !',
        '</p>',
        '</section>';
        }
    else {
        echo
        '<section>',
        '<div class="commentaire_form">',
        '<h5>Ajouter un commentaire</h5>',
        '<form method="post" action="">', // L'action est vide pour send le form à la mm page
        '<textarea id="comment" name="comment"></textarea>',
        '<input id="submit" type="submit" value="Publier ce commentaire">',
        '</form>',
        '</div>',
        '</section>';

    }
    echo '</main>';
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

/**
 * Affiche les commentaires d'un article
 * @param  mysqli_result $result résultat de la requête SQL
 * @param  array $tab tableau contenant les informations de l'article
 * @return void
 */
function afficherCommentaires($result, $tab) : void {
    if (isset($tab['coID'])) {
        echo '<ul>';
        while ($tab = mysqli_fetch_assoc($result)) {
            $auteur = htmlProtegerSorties($tab['coAuteur']);
            // On regarde si l'auteur du commentaire est un rédacteur ou si c'est l'utilisateur de la session
            if((isset($_SESSION['redacteur']) && $_SESSION['redacteur'] == 1) || (isset($_SESSION['pseudo']) && $_SESSION['pseudo'] == $tab['coAuteur'])) {
            // On affiche le commentaire et un bouton pour le supprimer au survol du commentaire
            echo
            '<li>',
            '<p>Commentaire de <strong>', $auteur,
            '</strong>, le ', dateIntToStringL($tab['coDate']),
            '</p>',
            '<blockquote>', UnicodeBBCodeToHTML(htmlProtegerSorties($tab['coTexte'])), '</blockquote>',
                '<form class="delete-form" method="post" action="', $_SERVER['REQUEST_URI'], '">',
                     '<input type="hidden" name="coID" value="', $tab['coID'], '">',
                     '<button type="submit" name="set_coID">Supprimer</button>',
                     '</form>',

            '</li>';
            continue;
            }

            echo $auteur;
            echo '<li>',
            '<p>Commentaire de <strong>', $auteur,
            '</strong>, le ', dateIntToStringL($tab['coDate']),
            '</p>',
            '<blockquote>', UnicodeBBCodeToHTML(htmlProtegerSorties($tab['coTexte'])), '</blockquote>',
                // On utilise la fonction UnicodeBBCodetoHTML car elle convertit seulement les codes unicode en HTML
                // et pas les balises BBCode, de cette manière les utilisateurs ne peuvent pas insérer de balises HTML
                // en utilisant du BBCode dans leurs commentaires
            '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Il n\'y a pas de commentaires pour cet article. </p>';
    }
}
/**
 * Ajoute un commentaire à un article
 * Cette fonction est appelée si le formulaire de commentaire a été envoyé
 * Et que l'utilisateur est authentifié
 * Le contenu du commentaire est contenu dans la variable $_POST['comment']
 *
 * @return void
 */
function ajouterCommentaire() : void {

    if(!isset($_SESSION['pseudo'])) {
        echo 'Non authentifie';
        return;
    }
    // On enlève les balises HTML
    $comment = strip_tags($_POST['comment']);
    // On protège les sorties
    $comment = htmlentities($comment);
    // On récupère la date sous la forme DD MM YYYY HH MM
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    $heure = date('H');
    $minute = date('i');
    $date = $year . $month . $day . $heure . $minute;

    // On ouvre la connexion à la BDD
    $bd = bdConnect();

    // On insère le commentaire dans la BDD
    $idArticle = dechiffrerURL($_GET['id']);

    // Table commentaire : coID, coAuteur, coTexte, coDate, coArticle
    // Avec coID auto incrémenté, coAuteur l'id de l'auteur, coTexte le texte du commentaire, coDate la date du commentaire, coArticle l'id de l'article

    $sql = "INSERT INTO commentaire (coID, coAuteur, coTexte, coDate, coArticle) VALUES (NULL, '".$_SESSION['pseudo']."', '".$comment."', '".$date."', '".$idArticle."')";
    $result = bdSendRequest($bd, $sql);
    // On ferme la connexion à la BDD
    mysqli_close($bd);

    // Le commentaire a été ajouté, il faut maintenant rafraichir la page
    $url_encoded= $_SERVER['REQUEST_URI'];
    header('Location: ', $url_encoded);
}

/**
 * Supprime un commentaire
 * Cette fonction est appelée si le formulaire de suppression de commentaire a été envoyé
 * Et que l'utilisateur est authentifié
 * L'id du commentaire à supprimer est contenu dans la variable $_POST['coID']
 *
 * @param int $IDCommentaire l'id du commentaire à supprimer
 * @return void
 */
function deleteComment($IDCommentaire) : void {
    // On ouvre la connexion à la BDD
    $bd = bdConnect();
    // On supprime le commentaire ayant coID = $IDCommentaire
    $sql = "DELETE FROM commentaire WHERE coID = $IDCommentaire";

    bdSendRequest($bd, $sql);

    // On ferme la connexion à la BDD
    mysqli_close($bd);

    // Le commentaire a été supprimé, il faut maintenant rafraichir la page
    $url_encoded= $_SERVER['REQUEST_URI'];
    header('Location: ', $url_encoded);
}

/**
 * Affiche un bouton pour supprimer un article
 * Cette fonction doit être appelée après avoir vérifié que l'utilisateur est bien l'auteur de l'article
 * Pour éviter l'utilisation de la BDD à l'intérieur de cette fonction
 * @param $idArticle
 * @return void
 */
function bandeauSuppressionArticle($idArticle): void {
    // On chiffre l'id de l'article pour le passer en parametres de la page edition.php
    $idChiffre = chiffrerPourURL($idArticle);
    echo  '<main id="article">',
            '<article>',
    '<p> Vous êtes auteur de cet article, <a href="./edition.php?id='.$idChiffre. '">cliquez ici pour le modifier</a> </p>',
    '</article>';
}