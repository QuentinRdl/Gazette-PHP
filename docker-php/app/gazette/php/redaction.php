<?php 

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('Le site de désinformation n°1 des étudiants en Licence Info');

echo '<main>
        <section>
            <h2>Le mot de la rédaction</h2>
            <p>Passionnés par le journalisme d\'investigation depuis notre plus jeune âge, nous avons créé ce site pour répondre à un
                réel besoin : celui de fournir une information fiable et précise sur la vie de la 
                <abbr title="Licence Informatique">L-INFO</abbr>
                de l\'<a href="http://www.univ-fcomte.fr" target="_blank">Université de Franche-Comté</a>.</p>
            <p>Découvrez les hommes et les femmes qui composent l\'équipe de choc de la Gazette de L-INFO. </p>
        </section>
        <section>
            <h2>Notre rédacteur en chef</h2>
            <article class="redacteur" id="jbigoude">
                <img src="../images/johnny.jpg" width="150" height="200" alt="Johnny Bigoude">
                <h3>Johnny Bigoude</h3>
                <p>Récemment débarqué de la rédaction d\'iTélé suite au scandale Morandini, Johnny insuffle une vision nouvelle
                et moderne du journalisme au sein de notre rédaction. Leader charismatique et figure incontournable de l\'information
                en France et à l\'étranger, il est diplômé de la Harvard Business School of Bullshit, promotion 1997.</p>
                <p>Véritable puits de sagesse sans fond, Johnny est LA référence dans la rédaction. Présent dans les locaux
                du département info, il suit au plus près l\'actualité de la Licence, et signe la majorité des articles du journal,
                en plus d\'en tracer la ligne éditoriale.</p>
            </article>
        </section>
        <section>
            <h2>Nos premiers violons</h2>
            <article class="redacteur" id="akuz">
                <img src="../images/alex.jpg" width="150" height="200" alt="Alex Kuzbidon">
                <h3>Alex Kuzbidon</h3>
                <h4>Correspondant à l\'étranger</h4>
                <p>Sans cesse sur les théatres d\'opération aux 4 coins du monde, Alex prête régulièrement sa plume à la Gazette de L-INFO 
                    pour nous raconter les trépidentes aventures de nos étudiants de Licence en stage à l\'étranger. </p>
                <p>Il a récemment suivi la trace d\'un groupe d\'étudiants de L3 en Angleterre et décroché une révélation tout à fait 
                    étonnante qui lui vaudra très certainement le prix Pullitzer l\'année prochaine. </p>
                <p>Equipé des derniers gadgets à la mode dans le domaine des technologies mobiles (très envié de nos sous-fifres 
                    <a href="#pheupakeur">Pete</a> et <a href="#yjourdelesse">Yves</a>), Alex s\'infiltre partout, et 
                    approvisionne la rédaction en images les plus époustouflantes venues du monde entier. </p>
                <p>Membre co-fondateur de la rédaction avec <a href="#jbigoude">Johnny</a>, le duo a su imposer la présence 
                    de cet OVNI journalistique qu\'est notre Gazette au sein du département informatique. </p>
            </article>
            <article class="redacteur" id="kdiot">
                <img src="../images/kelly.jpg" width="150" height="200" alt="Kelly Diot">
                <h3>Kelly Diot</h3>
                <h4>Journaliste d\'investigation</h4>
                <p>Ancienne détective privé, Kelly a rejoint l\'équipe l\'été dernier. Mettant à profit ses acquis d\'expérience de sa
                vie professionnelle antérieure, elle est tout particulièrement attachée aux enquêtes spéciales.</p>
                <p>Si ses articles sont rares, ce sont de petits bijoux d\'investigation qui sont régulièrement cités en exemple dans
                toutes les bonnes écoles de journalisme.</p>
                <p>Son meilleur article à ce jour reste son enquête sur une filière clandestine d\'approvisionnements de sujets
                d\'examens, qui a permis de mettre au jour des pratiques plus que douteuses au sein du département informatique. </p>
                <p>Véritable Elise Lucet de notre rédaction, elle n\'hésite pas à faire preuve d\'une ingéniosité sans égale pour pièger ses 
                    cibles et obtenir les confessions de leurs plus noirs secrets.</p>
            
            </article>
        </section>
        <section>
            <h2>Nos sous-fifres</h2>
            <article class="redacteur" id="pheupakeur">
                <img src="../images/pete.jpg" width="150" height="200" alt="Pete Heupakeur">
                <h3>Pete Heupakeur</h3>
                <h4>Photographe officiel</h4>
                <p>Equipé de son reflex dernier cri, Pete est l\'oeil de la Gazette de L-INFO. Ses clichés originaux
                viennent parfaitement illustrer les articles magistrement écrits par nos collaborateurs. </p>
                <p>Son meilleur cliché reste celui du Président Macron juste après avoir appris qu\'il validait sa Licence d\'Informatique.
                </p>
            </article>
            <article class="redacteur" id="yjourdelesse">
                <img src="../images/yves.jpg" width="150" height="200" alt="Yves Jourdelesse">
                <h3>Yves Jourdelesse</h3>
                <h4>Typographe et webmaster</h4>
                <p>Responsable de l\'édition numérique du journal, Yves donne vie à nos articles dans un style CSS inimitable.
                Ancien étudiant de Licence Informatique (comme le laisse deviner son style vestimentaire et capillaire négligé),
                Yves travaille d\'arrache-pied pour offrir monde extérieur un contenu d\'un rendu impeccable. </p>
                <p>Puni suite à un choix d\'illustration &#128286;, Yves passe désormais la moitié de son temps de travail au pilori,
                    devant l\'entrée ouest du bâtiment Propédeutique.</p>
            </article>
        </section>
        <section>
            <h2>La Gazette de L-INFO recrute !</h2>
            <p>Si vous souhaitez vous aussi faire partie de notre team, rien de plus simple. Envoyez-nous
            un mail grâce au lien dans le menu de navigation, et rejoignez l\'équipe. </p>
        </section>
    </main>
    <footer>&copy; Licence Informatique - Février 2024 - Tous droits réservés</footer>
</body>

</html>';