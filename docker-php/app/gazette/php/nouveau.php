<?php
// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('Rédaction d\'un article');

// génération du contenu de la page

affContenuPageNouveau();
// Si l'utilisateur n'est pas identifié ou si ce n'est pas un rédacteur
if (!estAuthentifie() || !isset($_SESSION['redacteur']) || !$_SESSION['redacteur']) {
    header ('Location: ../index.php');
    exit();
}

affPiedDePage();

// envoi du buffer
ob_end_flush();

/*********************************************************
 *
 * Définitions des fonctions locales de la page
 *
 *********************************************************/

/**
 * Affiche le contenu de la page 'nouveau.php'
 * @return void
 */
function affContenuPageNouveau() : void {
   echo '<main> <section>' ;
    if (!empty($errors)) {
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . $error . '</li>';
        }
        echo '</ul>';
    }

    echo '<form action="nouveau.php" method="post" enctype="multipart/form-data">',
        '<label for="title">Titre:</label>',
        '<input type="text" id="title" name="title" value=""><br>',

        '<label for="summary">Résumé:</label>',
        '<textarea id="summary" name="summary"></textarea><br>',

        '<label for="content">Texte:</label>',
        '<textarea id="content" name="content"></textarea><br>',

        '<label for="image">Image d\'illustration (JPG, moins de 100Ko, format 4/3):</label>',
        '<input type="file" id="image" name="image"><br>',
        '<input type="submit" value="Soumettre">',
    '</form>', '</section> </main>';

}
