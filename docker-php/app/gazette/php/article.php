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
if(isset($_POST['comment'])) {
    if(!isset($_SESSION['pseudo'])) {
        echo 'Session pseudo not set';
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
    echo 'Commentaire publié';
    echo 'comment = '.$comment;
    echo 'date = '.$date;
    echo 'Commentaire : ', $_POST['comment'];
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
        affErreurL('Il faut utiliser une URL de la forme : http://..../php/article.php?id=XXX');
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

    // Protection contre les attaques XSS
    $auteur = htmlProtegerSorties($auteur);
    $tab = htmlProtegerSorties($tab);

    // On converti le texte BBCode en HTML
    $newTexte = BBCodeToHTML($tab['arTexte']);

    // On regarde s'il y a une image qui correspond à l'article en construisant le path
    $pathImage = "../upload/";
    $pathImage .= $tab['arID'];
    $pathImage .= ".jpg";

    echo
        '<main id="article">',
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
    if (isset($tab['coID'])) {
        echo '<ul>';
        while ($tab = mysqli_fetch_assoc($result)) {
            echo '<li>',
                    '<p>Commentaire de <strong>', htmlProtegerSorties($tab['coAuteur']),
                        '</strong>, le ', dateIntToStringL($tab['coDate']),
                    '</p>',
                    '<blockquote>', UnicodeBBCodeToHTML(htmlProtegerSorties($tab['coTexte'])), '</blockquote>',
                // On utilise la fonction UnicodeBBCodetoHTML car elle converti seulement les codes unicode en HTML
                // et pas les balises BBCode, de cette manière les utilisateurs ne peuvent pas insérer de balises HTML
                // en utilisant du BBCode dans leur commentaires
                '</li>';
        }
        echo '</ul>';
    }
    // Sinon on indique qu'il n'y a pas de commentaires
    else {
        echo '<p>Il n\'y a pas de commentaire pour cet article. </p>';
    }

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
        echo 'Vs etes authentifie\n';
        if($redacteur) {
            echo 'Vs etes redacteru';
        }
        // Display a form for adding a comment
        echo
        '<section>',
        '<div class="commentaire_form">',
        '<h5>Ajouter un commentaire</h5>',
        '<form method="post" action="">', // L'action est vide pour soumettre le formulaire à la même page
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