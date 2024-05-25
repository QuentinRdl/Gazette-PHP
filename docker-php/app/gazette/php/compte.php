<?php

// chargement des bibliothèques de fonctions
require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

// On vérifie que l'utilisateur est connecté, si non on redirige vers la page d'acceuil
if(!estAuthentifie()) {
    header('Location: ../index.php');
    exit();
}

// si formulaire soumis, traitement de la demande d'inscription
$donnesEnregistrees = false;
if (isset($_POST['btnInfoPerso'])) {
    $erreurs = traitementInfoPerso(); // ne revient pas quand les données soumises sont valides
    // On regarde si on a eu aucune erreurs
    if(count($erreurs) == 0) {
        $donnesEnregistrees = true;
    }
} else {
    $erreurs = null;
}

$erreursMdp = null;
if(isset($_POST['btnMdp'])) {
    $erreursMdp = traitementMdp(); // ne revient pas quand les données soumises sont valides
    // On regarde si on a eu aucune erreurs
    if(count($erreursMdp) == 0) {
        $donnesEnregistrees = true;
    }
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

// génération de la page
affEntete('Mon compte');

affFormulaireInfoPerso($erreurs);
affFormulaireMdp($erreursMdp);
affPiedDePage();
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 *
 * Affiche la box pour changer les différentes informations personnelles
 *
 * En absence de soumission (i.e. lors du premier affichage), $err est égal à null
 * Sinon, $err est un tableau contenant les erreurs détectées lors de la soumission du formulaire
 *
 * @param ?array $err Tableau contenant les erreurs en cas de soumission du formulaire, null lors du premier affichage
 *
 * @return void
 */
function affFormulaireInfoPerso(?array $err): void
{
    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe
    if (isset($_POST['btnInfoPerso'])) {
        $values = htmlProtegerSorties($_POST);
        $values['radSexe'] = (int)($_POST['radSexe'] ?? -1);
        $values['cbSpam'] = isset($_POST['cbSpam']);
    } else {
        $values['pseudo'] = $values['nom'] = $values['prenom'] = $values['email'] = $values['naissance'] = '';
        $values['radSexe'] = -1;
        $values['cbSpam'] = true;
    }

    echo
    '<main>',
    '<section>',
    '<h2>Informations personnelles</h2>',
    '<p>Vous pouvez modifier les informations suivantes.</p>';

    if (is_array($err)) {
        if(count($err) == 0) {
            echo '<div class="succes">Les modifications ont bien été enregistrées.</div>';
        } else {
            echo '<div class="erreur">Les erreurs suivantes ont été relevées :',
            '<ul>';
            foreach ($err as $e) {
                echo '<li>', $e, '</li>';
            }
            echo '</ul>',
            '</div>';
        }
    }


    echo
    '<form method="post" action="compte.php">',
    '<table>';

    echo
    '<tr>',
    '<td>Votre civilité :</td>',
    '<td>';
    $radios = [1 => 'Monsieur', 2 => 'Madame', 3 => 'Non binaire'];
    foreach ($radios as $value => $label) {
        echo '<label><input type="radio" name="radSexe" value="', $value, '"',
        $value === $values['radSexe'] ? ' checked' : '', '> ', $label, '</label> ';
    }
    echo '</td>',
    '</tr>';


    affLigneInput('Votre nom :', array('type' => 'text', 'name' => 'nom', 'value' => $values['nom'], 'required' => null));
    affLigneInput('Votre prénom :', array('type' => 'text', 'name' => 'prenom', 'value' => $values['prenom'], 'required' => null));
    affLigneInput('Votre date de naissance :', array('type' => 'date', 'name' => 'naissance', 'value' => $values['naissance'], 'required' => null));
    affLigneInput('Votre email :', array('type' => 'email', 'name' => 'email', 'value' => $values['email'], 'required' => null));
    //affLigneInput('Choisissez un mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => '',
     //   'placeholder' => LMIN_PASSWORD . ' caractères minimum', 'required' => null));
    //affLigneInput('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2', 'value' => '', 'required' => null));

    echo
    '<tr>',
    '<td colspan="2">',
    '<label><input type="checkbox" name="cbCGU" value="1" required>',
    ' J\'ai lu et j\'accepte les conditions générales d\'utilisation </label>',
    '<label><input type="checkbox" name="cbSpam" value="1"',
    $values['cbSpam'] ? ' checked' : '',
    '> J\'accepte de recevoir des tonnes de mails pourris</label>',
    '</td>',
    '</tr>',
    '<tr>',
    '<td colspan="2">',
    '<input type="submit" name="btnInfoPerso" value="Enregistrer"> ',
    '<input type="reset" value="Réinitialiser">',
    '</td>',
    '</tr>',
    '</table>',
    '</form>',
    '</section>',
    '</main>';
}

/**
 * Traite les informations personnelles soumises par le formulaire
 *
 * @return array Tableau contenant les erreurs détectées lors de la soumission du formulaire
 */
function traitementInfoPerso(): array
{
    if (!parametresControle('post', ['nom', 'prenom', 'naissance',
        'email', 'btnInfoPerso'], ['radSexe', 'cbCGU', 'cbSpam'])) {
        sessionExit();
    }

    $erreurs = [];

    // vérification de la civilité
    if (!isset($_POST['radSexe'])) {
        $erreurs[] = 'Vous devez choisir une civilité.';
    } else if (!(estEntier($_POST['radSexe']) && estEntre($_POST['radSexe'], 1, 3))) {
        sessionExit();
    }

    // vérification des noms et prénoms
    $expRegNomPrenom = '/^[[:alpha:]]([\' -]?[[:alpha:]]+)*$/u';
    $nom = $_POST['nom'] = trim($_POST['nom']);
    $prenom = $_POST['prenom'] = trim($_POST['prenom']);
    verifierTexte($nom, 'Le nom', $erreurs, LMAX_NOM, $expRegNomPrenom);
    verifierTexte($prenom, 'Le prénom', $erreurs, LMAX_PRENOM, $expRegNomPrenom);

    // vérification du format de l'adresse email
    $email = $_POST['email'] = trim($_POST['email']);
    verifierTexte($email, 'L\'adresse email', $erreurs, LMAX_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L\'adresse email n\'est pas valide.';
    }

    // vérification de la date de naissance
    if (empty($_POST['naissance'])) {
        $erreurs[] = 'La date de naissance doit être renseignée.';
    } else {
        if (!preg_match('/^\\d{4}(-\\d{2}){2}$/u', $_POST['naissance'])) { //vieux navigateur qui ne supporte pas le type date ?
            $erreurs[] = 'la date de naissance doit être au format "AAAA-MM-JJ".';
        } else {
            list($annee, $mois, $jour) = explode('-', $_POST['naissance']);
            if (!checkdate($mois, $jour, $annee)) {
                $erreurs[] = 'La date de naissance n\'est pas valide.';
            } else if (mktime(0, 0, 0, $mois, $jour, $annee + AGE_MINIMUM) > time()) {
                $erreurs[] = 'Vous devez avoir au moins ' . AGE_MINIMUM . ' ans pour vous inscrire.';
            }
        }
    }

    /*
    // vérification des mots de passe
    $_POST['passe1'] = trim($_POST['passe1']);
    $_POST['passe2'] = trim($_POST['passe2']);
    if ($_POST['passe1'] !== $_POST['passe2']) {
        $erreurs[] = 'Les mots de passe doivent être identiques.';
    }
    $nb = mb_strlen($_POST['passe1'], encoding: 'UTF-8');
    if ($nb < LMIN_PASSWORD) {
        $erreurs[] = 'Le mot de passe doit être constitué d\'au moins ' . LMIN_PASSWORD . ' caractères.';
    }
    */

    // vérification de la valeur de l'élément cbCGU
    if (!isset($_POST['cbCGU'])) {
        $erreurs[] = 'Vous devez accepter les conditions générales d\'utilisation .';
    } else if ($_POST['cbCGU'] !== '1') {
        sessionExit();
    }

    // vérification de la valeur de $_POST['cbSpam'] si l'utilisateur accepte de recevoir des mails pourris
    if (isset($_POST['cbSpam']) && $_POST['cbSpam'] !== '1') {
        sessionExit();
    }

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    // on vérifie si le pseudo et l'adresse email ne sont pas encore utilisés que si tous les autres champs
    // sont valides car ces 2 dernières vérifications nécessitent une connexion au serveur de base de données
    // consommatrice de ressources système

    // ouverture de la connexion à la base
    $bd = bdConnect();

    // protection des entrées
    $pseudo = $_SESSION['pseudo'];
    $email = mysqli_real_escape_string($bd, $email);

    // On vérifie qu'aucun utilisateur n'utilise la même adresse mail que la nouvelle adresse mail
    $sql = "SELECT utPseudo, utEmail FROM utilisateur WHERE utPseudo = '$pseudo' OR utEmail = '$email'";
    $res = bdSendRequest($bd, $sql);

    while ($tab = mysqli_fetch_assoc($res)) {
        // On vérifie qu'un autre utilisateur n'ait pas le même adresse mail
        if($tab['utEmail'] == $email && $tab['utPseudo'] != $pseudo) {
            $erreurs[] = 'L\'adresse email est déjà utilisée.';
        }
    }
    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($res);

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    /*
    // calcul du hash du mot de passe pour enregistrement dans la base.
    $passe = password_hash($_POST['passe1'], PASSWORD_DEFAULT);

    $passe = mysqli_real_escape_string($bd, $passe);

    */
    $dateNaissance = $annee * 10000 + $mois * 100 + $jour;

    $nom = mysqli_real_escape_string($bd, $nom);
    $prenom = mysqli_real_escape_string($bd, $prenom);

    $civilite = (int)$_POST['radSexe'];
    $civilite = $civilite == 1 ? 'h' : ($civilite == 2 ? 'f' : 'nb');

    $mailsPourris = isset($_POST['cbSpam']) ? 1 : 0;

    /*
    // les valeurs sont écrites en respectant l'ordre de création des champs dans la table usager
    $sql = "INSERT INTO utilisateur (utPseudo, utNom, utPrenom, utEmail, utPasse, utDateNaissance, utRedacteur, utCivilite, utMailsPourris)
            VALUES ('$pseudo2', '$nom', '$prenom', '$email', '$passe', $dateNaissance, 0, '$civilite', $mailsPourris)";
    */
    // On remplace les anciennes valeurs enregistrées par les nouvelles
    $sql = "UPDATE utilisateur SET utNom = '$nom', utPrenom = '$prenom', utEmail = '$email', utDateNaissance = $dateNaissance, utCivilite = '$civilite', utMailsPourris = $mailsPourris WHERE utPseudo = '$pseudo'";

    bdSendRequest($bd, $sql);


    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    return $erreurs;
}

/**
 * Affiche la box pour changer de mot de passe
 *
 * En absence de soumission (i.e. lors du premier affichage), $err est égal à null
 * Sinon, $err est un tableau contenant les erreurs détectées lors de la soumission du formulaire
 *
 * @param ?array $err Tableau contenant les erreurs en cas de soumission du formulaire, null lors du premier affichage
 *
 * @return void
 */
function affFormulaireMdp(? array $err): void {
    echo
    '<main>',
    '<section>',
    '<h2>Mot de passe </h2>',
    '<p>Vous pouvez modifier votre mot de passe ci-dessous.</p>';

    if (is_array($err)) {
        if(count($err) == 0) {
            echo '<div class="succes">Le mot de passe a été changé avec succès.</div>';
        } else {
            echo '<div class="erreur">Les erreurs suivantes ont été relevées :',
            '<ul>';
            foreach ($err as $e) {
                echo '<li>', $e, '</li>';
            }
            echo '</ul>',
            '</div>';
        }
    }


    echo
    '<form method="post" action="compte.php">',
    '<table>';

    echo
    affLigneInput('Choisissez un mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => '',
        'placeholder' => LMIN_PASSWORD . ' caractères minimum', 'required' => null));
    affLigneInput('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2', 'value' => '', 'required' => null));

    echo
    '<tr>',
    '<td colspan="2">', // Colspan = 2 pour centrer le bouton
    '<input type="submit" name="btnMdp" value="Enregistrer"> ',
    '</td>',
    '</tr>',
    '</table>',
    '</form>',
    '</section>',
    '</main>';

}


/**
 * Traite le changement de mot de passe soumis par le formulaire
 *
 * @return array Tableau contenant les erreurs détectées lors de la soumission du formulaire
 */
function traitementMdp(): array {
    $erreurs = [];

    $_POST['passe1'] = trim($_POST['passe1']);
    $_POST['passe1'] = htmlProtegerSorties($_POST['passe1']);
    $_POST['passe2'] = trim($_POST['passe2']);
    $_POST['passe2'] = htmlProtegerSorties($_POST['passe2']);
    if ($_POST['passe1'] !== $_POST['passe2']) {
        $erreurs[] = 'Les mots de passe doivent être identiques.';
    }
    $nb = mb_strlen($_POST['passe1'], encoding: 'UTF-8');
    if ($nb < LMIN_PASSWORD) {
        $erreurs[] = 'Le mot de passe doit être constitué d\'au moins ' . LMIN_PASSWORD . ' caractères.';
    }

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    // ouverture de la connexion à la base
    $bd = bdConnect();

    // calcul du hash du mot de passe pour enregistrement dans la base.
    $passe = password_hash($_POST['passe1'], PASSWORD_DEFAULT);
    $passe = mysqli_real_escape_string($bd, $passe);

    // On remplace l'ancien mot de passe par le nouveau
    $sql = "UPDATE utilisateur SET utPasse = '$passe' WHERE utPseudo = '" . $_SESSION['pseudo'] . "'";
    bdSendRequest($bd, $sql);


    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    return $erreurs;

}