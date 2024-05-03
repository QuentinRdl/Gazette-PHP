<?php
// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();
affEntete('Connexion');
affFormulaireConnexion();

/**
 * Contenu de la page : affichage du formulaire de connexion
 * @return void
 */
function affFormulaireConnexion(): void {

    echo
        '<main>',
            '<section>',
                '<h2>Formulaire de connexion</h2>',
                '<p>Pour vous authentifier, remplissez le formulaire ci-dessous.</p>';
    /*
    if (is_array($err)) {
        echo    '<div class="erreur">Les erreurs suivantes ont été relevées lors de votre inscription :',
                    '<ul>';
        foreach ($err as $e) {
            echo        '<li>', $e, '</li>';
        }
        echo        '</ul>',
                '</div>';
    }
    */


    echo
            '<form method="post" action="connexion.php">',
                '<table>';




    affLigneInput('Votre nom :', array('type' => 'text', 'name' => 'pseudo', 'value' => '', 'required' => null));
    affLigneInput('Votre prénom :', array('type' => 'password', 'name' => 'password', 'value' => '', 'required' => null));


    echo

                        '<td colspan="2">',
                            '<input type="submit" name="btnConnexion" value="Se connecter"> ',
                            '<input type="reset" value="Annuler">',
                        '</td>',
                '</table>',
            '</form>',
            '<p>Pas encore inscrit ? N\'attendez pas,  <a href="./inscription.php">inscrivez-vous</a> !</p>',
        '</section>',
    '</main>';
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
 * et donc entraînent l'appel de la fonction em_sessionExit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent
 *   pas les input de type date
 *
 *  @return array    un tableau contenant les erreurs s'il y en a
 */
function traitementConnexion(): array {
    if( !parametresControle('post', ['pseudo', 'password'], [])) {
sessionExit();
}

$erreurs = [];

// vérification du pseudo
$pseudo = $_POST['pseudo'] = trim($_POST['pseudo']);

if (!preg_match('/^[0-9a-zA-Z]{' . LMIN_PSEUDO . ',' . LMAX_PSEUDO . '}$/u', $pseudo)) {
$erreurs[] = 'Le pseudo doit contenir entre '. LMIN_PSEUDO .' et '. LMAX_PSEUDO . ' caractères alphanumériques, sans signe diacritique.';
}

// vérification de la civilité
if (! isset($_POST['radSexe'])){
$erreurs[] = 'Vous devez choisir une civilité.';
}
else if (! (estEntier($_POST['radSexe']) && estEntre($_POST['radSexe'], 1, 3))){
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

// la validation faite par le navigateur en utilisant le type email pour l'élément HTML input
// est moins forte que celle faite ci-dessous avec la fonction filter_var()
// Exemple : 'l@i' passe la validation faite par le navigateur et ne passe pas
// celle faite ci-dessous
if(! filter_var($email, FILTER_VALIDATE_EMAIL)) {
$erreurs[] = 'L\'adresse email n\'est pas valide.';
}

// vérification de la date de naissance
if (empty($_POST['naissance'])){
$erreurs[] = 'La date de naissance doit être renseignée.';
}
else{
if(! preg_match('/^\\d{4}(-\\d{2}){2}$/u', $_POST['naissance'])){ //vieux navigateur qui ne supporte pas le type date ?
$erreurs[] = 'la date de naissance doit être au format "AAAA-MM-JJ".';
}
else{
list($annee, $mois, $jour) = explode('-', $_POST['naissance']);
if (!checkdate($mois, $jour, $annee)) {
$erreurs[] = 'La date de naissance n\'est pas valide.';
}
else if (mktime(0,0,0,$mois,$jour,$annee + AGE_MINIMUM) > time()) {
$erreurs[] = 'Vous devez avoir au moins '. AGE_MINIMUM. ' ans pour vous inscrire.';
}
}
}

// vérification des mots de passe
$_POST['passe1'] = trim($_POST['passe1']);
$_POST['passe2'] = trim($_POST['passe2']);
if ($_POST['passe1'] !== $_POST['passe2']) {
$erreurs[] = 'Les mots de passe doivent être identiques.';
}
$nb = mb_strlen($_POST['passe1'], encoding:'UTF-8');
if ($nb < LMIN_PASSWORD){
$erreurs[] = 'Le mot de passe doit être constitué d\'au moins '. LMIN_PASSWORD . ' caractères.';
}

// vérification de la valeur de l'élément cbCGU
if (! isset($_POST['cbCGU'])){
$erreurs[] = 'Vous devez accepter les conditions générales d\'utilisation .';
}
else if ($_POST['cbCGU'] !== '1'){
sessionExit();
}

// vérification de la valeur de $_POST['cbSpam'] si l'utilisateur accepte de recevoir des mails pourris
if (isset($_POST['cbSpam']) && $_POST['cbSpam'] !== '1'){
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
$pseudo2 = mysqli_real_escape_string($bd, $pseudo); // fait par principe, mais inutile ici car on a déjà vérifié que le pseudo
                       // ne contenait que des caractères alphanumériques
$email = mysqli_real_escape_string($bd, $email);

$sql = "SELECT utPseudo, utEmail FROM utilisateur WHERE utPseudo = '$pseudo2' OR utEmail = '$email'";
$res = bdSendRequest($bd, $sql);

while($tab = mysqli_fetch_assoc($res)) {
if ($tab['utPseudo'] == $pseudo){
$erreurs[] = 'Le pseudo choisi est déjà utilisé.';
}
if ($tab['utEmail'] == $email){
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

// calcul du hash du mot de passe pour enregistrement dans la base.
$passe = password_hash($_POST['passe1'], PASSWORD_DEFAULT);

$passe = mysqli_real_escape_string($bd, $passe);

$dateNaissance = $annee*10000 + $mois*100 + $jour;

$nom = mysqli_real_escape_string($bd, $nom);
$prenom = mysqli_real_escape_string($bd, $prenom);

$civilite = (int) $_POST['radSexe'];
$civilite = $civilite == 1 ? 'h' : ($civilite == 2 ? 'f' : 'nb');

$mailsPourris = isset($_POST['cbSpam']) ? 1 : 0;

// les valeurs sont écrites en respectant l'ordre de création des champs dans la table usager
$sql = "INSERT INTO utilisateur (utPseudo, utNom, utPrenom, utEmail, utPasse, utDateNaissance, utRedacteur, utCivilite, utMailsPourris)
VALUES ('$pseudo2', '$nom', '$prenom', '$email', '$passe', $dateNaissance, 0, '$civilite', $mailsPourris)";

bdSendRequest($bd, $sql);


// fermeture de la connexion à la base de données
mysqli_close($bd);

// mémorisation du pseudo dans une variable de session (car affiché dans la barre de navigation sur toutes les pages)
// enregistrement dans la variable de session du pseudo avant passage par la fonction mysqli_real_escape_string()
// car, d'une façon générale, celle-ci risque de rajouter des antislashs
// Rappel : ici, elle ne rajoutera jamais d'antislash car le pseudo ne peut contenir que des caractères alphanumériques
$_SESSION['pseudo'] = $pseudo;

$_SESSION['redacteur'] = false; // utile pour l'affichage de la barre de navigation

// redirection vers la page protegee.php : à modifier dans le projet !
header('Location: protegee.php');
exit(); //===> Fin du script
}

affPiedDePage();

// envoi du buffer
ob_end_flush();