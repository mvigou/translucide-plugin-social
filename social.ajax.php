<?php

if(@$_GET['mode'] || @$_POST) {
    include_once("../../config.php");// Variables
    include_once("../../api/function.php");// Fonctions
    include_once("../../api/db.php");// Connexion à la db
}

//------------------------
// MODE APPEL XHR GET
switch(@$_GET['mode'])
{
    default:	
	break;

    // connecter un compte
    case 'connexion':

        unset($_SESSION['nonce']);// Pour éviter les interférences avec un autre nonce de session

        login('medium');

        // Si on doit recharger la page avant de lancer le mode édition
        if(isset($_REQUEST['callback']) and $_REQUEST['callback'] == "reload_edit")
		{
			// Pose un cookie pour demander l'ouverture de l'admin automatiquement au chargement
			setcookie("autoload_edit", "true", time() + 60*60, $GLOBALS['path'], $GLOBALS['domain']);

        }
        else {
            
            $sel_membre = $connect->query("SELECT * FROM ".$table_user." WHERE id=".$_SESSION['uid']);
            $res_membre = $sel_membre->fetch_assoc();

            // initialisation des infos de session
            $_SESSION['nom'] = $res_membre['name'];
            $_SESSION['info'] = json_decode($res_membre['info'],true);

            echo '<script>reload();</script>';
        }

    break; 

    // récupération mot de passe
    case 'oublie':
    break; 

    // publication
    case 'publication':

        //@todo : 
        $post = json_decode($_POST['publication'],true);

        // créastion du titre du post
        if($post['type']=='post') $title = $_SESSION['uid'].time(); else $title = $post['titre'];

        // On creer le contenu dans la BDD
        $sql = "INSERT INTO ".$table_content." 
                (state, title, tpl, url, lang, robots, type, content, user_insert, date_insert)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $connect->prepare($sql);

        // assignation des valeurs
        $stmt->bind_param(  
            "ssssssssis", 
            $statut,
            $title,
            $tpl,
            $url,
            $post_lang,
            $robots,
            $type,
            $content ,
            $user_insert,
            $date_insert
        );

        $statut = 'active';
        $title = addslashes($title);
        $tpl = addslashes('publication');
        $url = $title;
        $post_lang = $lang;
        $robots = 'noindex,nofollow';
        $type = $post['type'];
        $content = $_POST['publication'];
        $user_insert = (int)$_SESSION['uid'];
        $date_insert = date("Y-m-d H:i:s");

        // execution de la requete
        $stmt->execute();
		
		if($connect->error)// Si il y a une erreur
			echo htmlspecialchars($sql)."\n<script>error(\"".htmlspecialchars($connect->error)."\");</script>";
        else {
            $lastId = $stmt->insert_id;
            $stmt->close();
        }
            
        // On creer le tag associé sil s'agit d'une catégorie
        if($post['dans'] == 'Actualité' || $post['dans'] == 'Annonce' || $post['dans'] == 'Petite annonce') {

            $sql = "INSERT INTO ".$table_tag." 
                (id, zone, encode, name, ordre)
                VALUES (?, ?, ?, ?, ?)";

            $stmt = $connect->prepare($sql);    

            // assignation des valeurs
            $stmt->bind_param(  
                "isssi", 
                $id,
                $zone,
                $encode,
                $name,
                $ordre
            );

            $id = $lastId;
            $zone = 'categorie';
            $encode = make_url($post['dans']);
            $name = $post['dans'];
            $ordre = 1;

            // execution de la requete
            $stmt->execute();

            if($connect->error)// Si il y a une erreur
			    echo htmlspecialchars($sql)."\n<script>error(\"".htmlspecialchars($connect->error)."\");</script>";
        }
    
        echo "<script>reload();</script>";
        
    break;

    // modifier profil
    case 'profil-modifier':

        // préparation de la requête
        $sql = "UPDATE ".$table_user." SET name = ? , info = ? WHERE id = ". $_SESSION['uid'];
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ss", $name, $info);

        // alimentation des champs
        $name = $_POST['nom'];
        $info = $_POST['info'];

        // execution de la requete
        $stmt->execute();

		if($connect->error)// Si il y a une erreur
			echo htmlspecialchars($sql)."\n<script>error(\"".htmlspecialchars($connect->error)."\");</script>";
        else {

            // mise à jour des informations du profil
            $_SESSION['nom'] = $_POST['nom'];
            $_SESSION['info'] = json_decode($_POST['info'],true);

            $stmt->close();

           echo "<script>reload();</script>";
        }
    break;

    case 'profil-photo' :

        print_r($_POST);
    break;

    // deconnexion
    case 'deconnexion':

        logout();

        ?>

        <script>reload();</script>

        <?php

    break;

}

?>