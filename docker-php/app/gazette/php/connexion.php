<?php
// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

// si l'utilisateur est déjà authentifié
if (estAuthentifie()){
    header ('Location: ../index.php');
    exit();
}

// si formulaire soumis, traitement de la demande de connexion
if (isset($_POST['btnConnexion'])) {
    $err = traitementConnexion(); // ne retoure rien quand les données soumises sont valides
} else{
    $err = null;
}

affEntete('Connexion');
affFormulaireConnexion($err);
affPiedDePage();

// envoi du buffer
ob_end_flush();

/**
 * Contenu de la page : affichage du formulaire de connexion
 * @return void
 */
function affFormulaireConnexion(?array $err): void {

    echo
        '<main>',
            '<section>',
                '<h2>Formulaire de connexion</h2>',
                '<p>Pour vous authentifier, remplissez le formulaire ci-dessous.</p>';
    
    if (is_array($err) && count($err) > 0) {
        echo    '<div class="erreur">Les erreurs suivantes ont été relevées lors de votre inscription :',
                    '<ul>';
        foreach ($err as $e) {
            echo        '<li>', $e, '</li>';
        }
        echo        '</ul>',
                '</div>';
    }
    
    echo
            '<form method="post" action="connexion.php">',
                '<table>';

    affLigneInput('Pseudo :', array('type' => 'text', 'name' => 'pseudo', 'value' => '', 'required' => null));
    affLigneInput('Mot de passe :', array('type' => 'password', 'name' => 'password', 'value' => '', 'required' => null));

    echo '<td colspan="2">', '<input type="submit" name="btnConnexion" value="Se connecter"> ', '<input type="reset" value="Annuler">','</td>', '</table>', '</form>', '<p>Pas encore inscrit ? N\'attendez pas,  <a href="./inscription.php">inscrivez-vous</a> !</p>', '</section>', '</main>';
}

/**
 * Traitement d'une demande de connexion
 *
 * Vérification de la validité des données
 * Si on trouve des erreurs => return un tableau les contenant
 * Sinon
 *     Connexion au site
 * FinSi
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage
 * et donc entraînent l'appel de la fonction em_sessionExit()
 *
 *  @return array    un tableau contenant les erreurs s'il y en a
 */
function traitementConnexion(): array {
    if(parametresControle('post', ['pseudo', 'password'], [])) {
        sessionExit();
    }

    $erreurs = [];

    // vérification du pseudo
    $pseudo = $_POST['pseudo'] = trim($_POST['pseudo']);

    if (!preg_match('/^[0-9a-zA-Z]{' . LMIN_PSEUDO . ',' . LMAX_PSEUDO . '}$/u', $pseudo)) {
        $erreurs[] = 'Le pseudo doit contenir entre '. LMIN_PSEUDO .' et '. LMAX_PSEUDO . ' caractères alphanumériques, sans signe diacritique.';
    }

    // vérification des mots de passe
    $_POST['password'] = trim($_POST['password']);
    $nb = mb_strlen($_POST['password'], encoding:'UTF-8');
    if ($nb < LMIN_PASSWORD) {
        $erreurs[] = 'Le mot de passe doit être constitué d\'au moins '. LMIN_PASSWORD . ' caractères.';
    }

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    // ouverture de la connexion à la base
    $bd = bdConnect();

    // Vérification des données de connexion
    $pseudo = mysqli_real_escape_string($bd, $_POST['pseudo']);

    // Exécution de la requête pour récupérer les informations de l'utilisateur
    $sql = "SELECT utPseudo, utPasse, utRedacteur FROM utilisateur WHERE utPseudo = '$pseudo'";
    $resultat = bdSendRequest($bd, $sql);

    // Vérification si un utilisateur correspondant au pseudo existe dans la base de données
    if(mysqli_num_rows($resultat) == 1) {
        $donneesUtilisateur = mysqli_fetch_assoc($resultat);
        $reussite=0;
        // Vérification du mot de passe hashé
        if(password_verify($_POST['password'], $donneesUtilisateur['utPasse'])) {
            // Mot de passe correct, connexion réussie
            $reussite=1;
        } else {
            // echo "Mot de passe incorrect.";
            $erreurs[] = 'Mot de passe incorrect';
        }
    } else {
        // echo "Aucun utilisateur trouvé avec ce pseudo.";
        $erreurs[] = 'Aucun utilisateur trouvé avec ce pseudo';
    }

    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($resultat);

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    // mémorisation du pseudo et redacteur dans une variable de session 

    $_SESSION['pseudo'] = $donneesUtilisateur['utPseudo'];
    $_SESSION['redacteur'] = $donneesUtilisateur['utRedacteur'];
    $_SESSION['connecte'] = 1;
    
    // redirection vers la page précédente ou index.php !

    if(empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'http://localhost:8080/gazette/php/connexion.php') !== false) {
        header('Location: ../index.php'); // Si la page précédente n'existe pas ou est connexion.php, on redirige vers index.php
    } else {
        $referer = $_SERVER['HTTP_REFERER'];
        header("Location: $referer"); // Redirection vers la page précédente
    }

    exit(); //===> Fin du script
    
}