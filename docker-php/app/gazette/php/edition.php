<?php
// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('Edition');

$idArticle = NULL;
$err = 0;

// On regarde si l'utilisateur est connecté et si c'est un rédacteur
if (!isset($_SESSION['redacteur']) || !$_SESSION['redacteur']) {
    // Si ce n'est pas le cas, on le redirige vers la page d'accueil
    affErreurL('Vous devez être connecté en tant que rédacteur pour accéder à cette page');
    $err++;
} else if (!isset($_GET['id'])) {
    // On regarde si la page a été appellate avec un paramètre id
    if($_SERVER["REQUEST_METHOD"] != "POST") {
        // Si ce n'est pas le cas, on ajoute une erreur
        affErreurL('Aucun paramètre id n\'a été transmis à la page');
        $err++;
    }
    // Si on ne passe pas dans le 'if', on vient de soumettre la modification d'un article

} else {
    // Si c'est le cas, on récupère l'id de l'article
    $idArticle = $_GET['id'];
    // On déchiffre l'url
    $idArticle = dechiffrerURL($idArticle);
    // On vérifie que l'id est bien un nombre
    if (!is_numeric($idArticle)) {
        // Si ce n'est pas le cas, on ajoute une erreur
        affErreurL('La fonction de déchiffrage n\'a pas réussi a déchiffre cette page');
        $err++;
    }
}

// On vérifie si l'utilisateur a soumis le formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // L'utilisateur veut supprimer l'article
        $idArticle = $_POST['id'];
        supprimerArticle($idArticle);
        echo '<p>L\'article a été supprimé.</p>';
    } else {
        // L'utilisateur veut modifier l'article
        $titre = $_POST['titre'];
        $resume = $_POST['resume'];
        $texte = $_POST['texte'];
        $idArticle = $_POST['id'];
        $idArticleNonChiffre = $_POST['id'];
        // Utilisez les variables $titre, $resume, $texte et $idArticle comme vous le souhaitez
        // Par exemple, vous pouvez les passer à une autre fonction
        envoyerModifBDD($titre, $resume, $texte, $idArticle);
    }
}

if($err == 0) {
    // S'il n'y a pas d'erreur, on affiche le contenu de l'édition
    afficherContenuEdition($idArticle);
}
// Affichage des erreurs
// génération du contenu de la page

// affContenuL();

// envoi du buffer
ob_end_flush();

/**
 * Affiche le contenu de l'article et on peut le modifier
 * @param   int     $idArticle   Identifiant de l'article à modifier
 * @return  void
 */
function afficherContenuEdition($idArticle) : void {
    $bd = bdConnect();
    // On récupère les informations sur l'article
    $sql = 'SELECT arID, arTitre, arTexte, arResume
            FROM article
            WHERE arID = ' . $idArticle;
    $result = bdSendRequest($bd, $sql);

    // pas d'articles → fin de la fonction
    if (mysqli_num_rows($result) == 0) {
        affErreurL('L\'identifiant de l\'article n\'a pas été trouvé dans la base de données');
        // Libération de la mémoire associée au résultat de la requête
        mysqli_free_result($result);
        return;
    }

    $tab = mysqli_fetch_assoc($result);
    mysqli_close($bd);

    echo '<main> <section class="article-edit"> <h2>Édition de l\'article</h2>';
    // On affiche le formulaire pour modifier le titre
    echo '<form method="post" action="edition.php">';
    echo '<input type="hidden" name="id" value="', $idArticle, '">';
    echo '<div class="form-group">';
    echo '<label for="titre">Titre de l\'article : </label>';
    echo '<input type="text" name="titre" id="titre" value="', $tab['arTitre'], '"><br>';
    echo '</div>';

    // On affiche le formulaire pour modifier le résumé avec le résumé actuel
    echo '<div class="form-group">';
    echo '<label for="resume">Résumé de l\'article : </label>';
    echo '<input type="text" name="resume" id="resume" value="', $tab['arResume'], '"><br>';
    echo '</div>';

    // On affiche le texte de l'article
    echo '<div class="form-group">';
    echo '<label for="texte">Texte de l\'article : </label>';
    echo '<textarea name="texte" id="texte" rows="10" cols="50">', $tab['arTexte'], '</textarea><br>';
    echo '</div>';
    echo '<input type="submit" value="Modifier">';
    echo '</form>';

    // Formulaire de confirmation de suppression
    echo '<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF']), '">';
    echo '<input type="hidden" name="id" value="', htmlspecialchars($idArticle), '">';
    echo '<input type="hidden" name="action" value="delete">';
    echo '<input type="submit" value="Supprimer l\'article" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cet article ?\');">';
    echo '</form>';

    echo '</section> </main>';

}

/**
 * Envoie les modifications à la base de données
 * @param   string  $titre      Titre de l'article
 * @param   string  $resume     Résumé de l'article
 * @param   string  $texte      Texte de l'article
 * @param   int     $idArticle  Identifiant de l'article
 * @return  void
 */
function envoyerModifBDD($titre, $resume, $texte, $idArticle) : void {
    // On enlève les balises HTML
    $titre = strip_tags($titre);
    $resume = strip_tags($resume);
    $texte = strip_tags($texte);

    // On regarde si aucun des champs n'est vide
    if (empty($titre) || empty($resume) || empty($texte)) {
        echo '</main>';
        affErreurL('Un ou plusieurs champs sont vides');
        header('Location: ./edition.php?id=' . chiffrerPourURL($idArticle));
        return;
    }
    // On récupère la date sous la forme DD MM YYYY HH MM
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    $heure = date('H');
    $minute = date('i');
    $date = $year . $month . $day . $heure . $minute;

    $bd = bdConnect();
    $sql = 'UPDATE article
            SET arTitre = "' . $titre . '",
                arResume = "' . $resume . '",
                arTexte = "' . $texte . '",
                arDateModif = ' . $date . '
            WHERE arID = ' . $idArticle;
    bdSendRequest($bd, $sql);
    mysqli_close($bd);

    // On redirige l'utilisateur vers la page de l'article
    header('Location: ./article.php?id=' . chiffrerPourURL($idArticle));
}

/**
 * Supprime un article de la base de données
 * @param $idArticle
 * @return void
 */
function supprimerArticle($idArticle) : void {
    // On se connecte à la BDD
    $bd = bdConnect();

    // On supprime d'abord les commentaires associés à l'article
    $sql = 'DELETE FROM commentaire WHERE coArticle = ' . $idArticle;
    bdSendRequest($bd, $sql);

    // Ensuite, on supprime l'article
    $sql = 'DELETE FROM article WHERE arID = ' . $idArticle;
    bdSendRequest($bd, $sql);

    // On ferme la connexion
    mysqli_close($bd);

    // On redirige l'utilisateur vers la page d'accueil
    header('Location: ../index.php');
}
