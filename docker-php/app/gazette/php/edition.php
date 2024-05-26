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


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titre = $_POST['titre'];
    $resume = $_POST['resume'];
    $texte = $_POST['texte'];
    $idArticle = $_POST['id'];
    $idArticleNonChiffre = $_POST['id'];
    // Utilisez les variables $titre, $resume, $texte et $idArticle comme vous le souhaitez
    // Par exemple, vous pouvez les passer à une autre fonction
    envoyerModifBDD($titre, $resume, $texte, $idArticle);
    echo 'OUAIS !';
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

    // On affiche le formulaire pour modifier le titre avec le titre actuel
    echo '<form method="post" action="edition.php">';
    echo '<input type="hidden" name="id" value="', $idArticle, '">';
    echo '<label for="titre">Titre de l\'article : </label>';
    echo '<input type="text" name="titre" id="titre" value="', $tab['arTitre'], '"><br>';

    // On affiche le formulaire pour modifier le résumé avec le résumé actuel
    echo '<label for="resume">Résumé de l\'article : </label>';
    echo '<input type="text" name="resume" id="resume" value="', $tab['arResume'], '"><br>';


    // On affiche le texte de l'article
    echo '<label for="texte">Texte de l\'article : </label>';
    echo '<textarea name="texte" id="texte" rows="10" cols="50">', $tab['arTexte'], '</textarea><br>';
    echo '<input type="submit" value="Modifier">';
    echo '</form>';
}

