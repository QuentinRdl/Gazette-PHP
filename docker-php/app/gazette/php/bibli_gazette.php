<?php
/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *        à l'application La gazette de L-INFO           *
 *********************************************************/

// Force l'affichage des erreurs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );

// Phase de développement (IS_DEV = true) ou de production (IS_DEV = false)
define ('IS_DEV', true);

/** Constantes : les paramètres de connexion au serveur MariaDB */
define ('BD_NAME', 'gazette_bd');
define ('BD_USER', 'gazette_user');
define ('BD_PASS', 'gazette_pass');
define ('BD_SERVER', 'mariadb-hostname');

// Définit le fuseau horaire par défaut à utiliser. Disponible depuis PHP 5.1
date_default_timezone_set('Europe/Paris');

// limites liées aux tailles des champs de la table utilisateur
define('LMAX_PSEUDO', 20);    // taille du champ usLogin de la table utilisateur
define('LMAX_NOM', 50);      // taille du champ usNom de la table utilisateur
define('LMAX_PRENOM', 60);   // taille du champ usPrenom de la table utilisateur
define('LMAX_EMAIL', 255);   // taille du champ usMail de la table utilisateur

define('LMIN_PSEUDO', 4);

define('AGE_MINIMUM', 18);

define('LMIN_PASSWORD', 4);

//_______________________________________________________________
/**
 * Affichage du début de la page HTML (head + menu + header).
 *
 * @param  string  $titre       le titre de la page (<head> et <h1>)
 * @param  string  $prefixe     le préfixe du chemin relatif vers la racine du site
 *
 * @return void
 */
function affEntete(string $titre, string $prefixe = '..') : void {

    echo
        '<!doctype html>',
        '<html lang="fr">',
            '<head>',
                '<meta charset="UTF-8">',
                '<title>La gazette de L-INFO | ', $titre, '</title>',
                '<link rel="stylesheet" type="text/css" href="', $prefixe,'/styles/gazette.css">',
            '</head>',
            '<body>';

    affMenu($prefixe);

    echo        '<header>',
                    '<img src="', $prefixe, '/images/titre.png" alt="Image du titre | La gazette de L-INFO" width="780" height="83">',
                    '<h1>', $titre, '</h1>',
                '</header>';
}

//_______________________________________________________________
/**
 * Affichage du menu de navigation.
 *
 * @param  string  $prefixe     le préfixe du chemin relatif vers la racine du site
 *
 * @return void
 */
function affMenu(string $prefixe = '..') : void {

    echo    '<nav><ul>',
                '<li><a href="', $prefixe, '/index.php">Accueil</a></li>',
                '<li><a href="', $prefixe, '/php/actus.php">Toute l\'actu</a></li>',
                '<li><a href="', $prefixe, '/php/recherche.php">Recherche</a></li>',
                '<li><a href="', $prefixe, '/php/redaction.php">La rédac\'</a></li>';
    if (estAuthentifie()){
        echo    '<li><a href="#">', htmlProtegerSorties($_SESSION['pseudo']),'</a>',
                    '<ul>',
                        '<li><a href="', $prefixe, '/php/compte.php">Mon profil</a></li>',
                        $_SESSION['redacteur'] ? "<li><a href='$prefixe/php/nouveau.php'>Nouvel article</a></li>" : '',
                        '<li><a href="', $prefixe, '/php/deconnexion.php">Se déconnecter</a></li>';
        //if()

        echo        '</ul>',
                '</li>';
    }
    else {
        echo    '<li><a href="', $prefixe, '/php/connexion.php">Se connecter</a></li>';
    }
    echo    '</ul></nav>';
}

//_______________________________________________________________
/**
 * Affichage du pied de page.
 *
 * @return  void
 */
function affPiedDePage() : void {

    echo        '<footer>&copy; Licence Informatique - Février 2024 - Tous droits réservés</footer>',
            '</body></html>';
}

//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @return bool     true si l'utilisateur est authentifié, false sinon
*/
function estAuthentifie(): bool {
    return  isset($_SESSION['pseudo']);
}


//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour
 * stocker par exemple l'adresse IP, etc.
 *
 * @param string    $page URL de la page vers laquelle l'utilisateur est redirigé
 *
 * @return void
 */
function sessionExit(string $page = '../index.php'): void {

    // suppression de toutes les variables de session
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        // suppression du cookie de session
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();

    header("Location: $page");
    exit();
}

//_______________________________________________________________
/**
 * Détecte si l'utilisateur est un rédacteur ou non
 *
 * @param void
 * @return string true si l'utilisateur est un rédacteur, faux sinon
 */
function estRedacteur(): bool {
    if(isset($_SESSION['redacteur'])) {
        return($_SESSION['redacteur'] == 1);
    }
    return false;
}

//_______________________________________________________________
/**
 * Renvoie dans un tableau l'id et le titre des articles sélectionnés par une requête SQL
 *
 * @param  mysqli  $bd      référence pointant sur l'objet connecteur à la base de données
 * @param  string  $sql     la requête SQL à envoyer
 *
 * @return array            tableau (clé : id de l'article, valeur associée à la clé : titre de l'article)
 */
function bdSelectArticlesL(mysqli $bd, string $sql) : array {
    $res = [];
    $result = bdSendRequest($bd, $sql);
    while ($t = mysqli_fetch_assoc($result)) {
        $res[$t['arID']] = $t['arTitre'];
        // echo '<p>', $t['arID'], $t['arTitre'], '</p>';
    }

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($result);

    return $res;
}

/**
 * Affiche une erreur
 * @param string $mess le message à afficher
 * @param array $err la / les erreurs à afficher
 * @param bool $list si true, affiche chacune erreur sous forme de liste
 * sinon cela ne fait qu'un seul <li>
 * 
 * @return void
 */
function afficherErreur(string $mess, array $err, bool $list) {
    echo    '<div class="erreur">'.$mess.
    '<ul>';
    $first = true;

    foreach ($err as $e) {
        if($list == true) {
        echo        '<li>', $e, '</li>';
        } else {
            if($first == true) {
                echo '<li>', $e,' ';
            } else {
                echo $e. ' ';
            }
            $first = false;
        } 
    }
    
    if($first == false) {
        echo '</li>';
    }
    echo '</ul>', '</div>';
}

/**
 * Affichage d'un message d'erreur dans une zone dédiée de la page.
 *
 * @param string $message
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
