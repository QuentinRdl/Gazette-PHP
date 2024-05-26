<?php
// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

// Si l'utilisateur n'est pas identifié ou si ce n'est pas un rédacteur
if (!estAuthentifie() || !isset($_SESSION['redacteur']) || !$_SESSION['redacteur']) {
    header ('Location: ../index.php');
    exit();
}

affEntete('Rédaction d\'un article');



$errors = [];
$title = $summary = $content = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Valider le titre
    if (empty($_POST["title"])) {
        $errors[] = "Le titre est requis.";
    } else {
        $title = validateInput($_POST["title"]);
        if ($title != strip_tags($title)) {
            $errors[] = "Le titre ne doit pas contenir de tags HTML.";
        }
    }

    // Valider le résumé
    if (empty($_POST["summary"])) {
        $errors[] = "Le résumé est requis.";
    } else {
        $summary = validateInput($_POST["summary"]);
        if ($summary != strip_tags($summary)) {
            $errors[] = "Le résumé ne doit pas contenir de tags HTML.";
        }
    }

    // Valider le texte de l'article
    if (empty($_POST["content"])) {
        $errors[] = "Le texte est requis.";
    } else {
        $content = validateInput($_POST["content"]);
        if ($content != strip_tags($content)) {
            $errors[] = "Le texte ne doit pas contenir de tags HTML.";
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Valider le titre
        if (empty($_POST["title"])) {
            $errors[] = "Le titre est requis.";
        } else {
            $title = validateInput($_POST["title"]);
            if ($title != strip_tags($title)) {
                $errors[] = "Le titre ne doit pas contenir de tags HTML.";
            }
        }

        // Valider le résumé
        if (empty($_POST["summary"])) {
            $errors[] = "Le résumé est requis.";
        } else {
            $summary = validateInput($_POST["summary"]);
            if ($summary != strip_tags($summary)) {
                $errors[] = "Le résumé ne doit pas contenir de tags HTML.";
            }
        }

        // Valider le texte de l'article
        if (empty($_POST["content"])) {
            $errors[] = "Le texte est requis.";
        } else {
            $content = validateInput($_POST["content"]);
            if ($content != strip_tags($content)) {
                $errors[] = "Le texte ne doit pas contenir de tags HTML.";
            }
        }
        $idArticle = -1;
        if(count($errors) == 0) {
            $idArticle = creerArticle($title, $summary, $content);
        }

        // Gestion de l'upload de l'image
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {
            $file = $_FILES["image"];
            $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $uploadDir = dirname(__DIR__) . '/upload/';

            // On renomme l'image avec l'id de l'article
            $uploadFile = $uploadDir . $idArticle . '.' . $fileType;

            // Valider le format de l'image
            if ($fileType != "jpg" && $fileType != "jpeg") {
                $errors[] = "L'image doit être au format JPG.";
            }

            // Valider la taille de l'image
            if ($file["size"] > 100 * 1024) {
                $errors[] = "L'image doit peser moins de 100Ko.";
            }

            // Valider le format 4/3 de l'image
            list($width, $height) = getimagesize($file["tmp_name"]);
            if ($width == 0 || $height == 0) {
                $errors[] = "L'image est invalide.";
            } else if ($width / $height != 4 / 3) {
                $errors[] = "L'image doit être au format 4/3.";
            }

            // Redimensionner l'image si nécessaire
            if (empty($errors)) {
                $newWidth = 248;
                $newHeight = 186;
                $imageResource = imagecreatefromjpeg($file["tmp_name"]);
                $resizedImage = imagescale($imageResource, $newWidth, $newHeight);
                if ($resizedImage) {
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    if (imagejpeg($resizedImage, $uploadFile)) {
                        // echo "Image redimensionnée et sauvegardée avec succès.";
                    } else {
                        $errors[] = "Erreur lors de la sauvegarde de l'image redimensionnée.";
                    }
                    imagedestroy($imageResource);
                    imagedestroy($resizedImage);
                } else {
                    $errors[] = "Erreur lors du redimensionnement de l'image.";
                }
            }
        } else {
            if ($_FILES["image"]["error"] != UPLOAD_ERR_NO_FILE) {
                $errors[] = "Erreur lors de l'upload de l'image : " . $_FILES["image"]["error"];
            }
        }

        // Si pas d'erreurs, traiter la soumission de l'article (stockage en base de données, etc.)
        if (empty($errors)) {
            // Traitement à faire pour stocker l'article, par exemple :
            // $db->storeArticle($title, $summary, $content, $uploadFile);

            echo "Article soumis avec succès!";
            // Redirection ou autre action après soumission réussie
        }
    }

    if(count($errors) == 0) {
        // On a crée le nouvel article avec succès, on redirige l'utilisateur vers celui ci
        $idArticle = chiffrerPourURL($idArticle);
        header('Location: article.php?id=' . $idArticle);
    }
}


// génération du contenu de la page

affContenuPageNouveau($errors);

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
 * @param $errors array le tableau contenant les erreurs trouvées s'il y en a
 * @return void
 */
function affContenuPageNouveau($errors) : void {
   echo '<main> <section>' ;
   if(count($errors) > 0) {
       // S'il y a des erreurs, on les affiches
       afficherErreur("Les erreurs suivantes on été détectées lors de la soumission de votre article !", $errors, true);
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

/**
 * Permet de valider les inputs de l'utilisateur
 * @param $data
 * @return string
 */
function validateInput($data) : string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Créer un article dans la base de données
 * Retourne l'id de l'article pour gérer la photo liée à l'article (s'il y en a une)
 * @param $title
 * @param $summary
 * @param $content
 * @return int l'ID de l'article
 */
function creerArticle($title, $summary, $content) : int {
    $idArticle = -1;

    // On se connecte a la BDD
    $bd = bdConnect();

    // On récupère la date sous la forme DD MM YYYY HH MM
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    $heure = date('H');
    $minute = date('i');
    $date = $year . $month . $day . $heure . $minute;


    // On insère l'article dans la base de données
    // arID, arTitre, arResume, arTexte, arDatePublication, arDateModif (NULL), arRedacteur ($_SESSION['pseudo'])
    $sql = 'INSERT INTO article (arTitre, arResume, arTexte, arDatePubli, arDateModif, arAuteur)
            VALUES ("' . $title . '", "' . $summary . '", "' . $content . '", ' . $date . ', NULL, "' . $_SESSION['pseudo'] . '")';
    $result = bdSendRequest($bd, $sql);
    if ($result) {
        $idArticle = mysqli_insert_id($bd);
        echo 'OUAIS', $idArticle;
    } else {
        affErreurL('Erreur lors de la création de l\'article dans la base de données');
    }

    return $idArticle;
}
