<?php
    /* ----- INFO ----- */
    /* URL d'accès : http://YOUR-URL?y=%Y&mo=%m&d=%d&h=%H&mi=%M&s=%S */
    
    /* ******************
       VARIABLE À CHANGER
       ****************** */
    /* ----- Variable Générale ----- */
    $IP_PERSO = 'xx.xxx.xx.xx';
    
    /* ----- Variables FTP ----- */
    $FTP_SERVER   = "subdomain.domain.com";
    $FTP_USER     = "user";
    $FTP_PASSWORD = "password";
    
    /* ----- Variables Email ----- */   
    $FROM_NAME  = stripslashes("Camera Home"); // Nom Expéditeur
    $FROM_EMAIL = "xxxx@xxxx.com";             // Email Expéditeur 
    $TO_EMAIL   = "xxxx@xxxx.com";             // Email Destinataire
    $EMAIL_SUBJECT = stripslashes("Mouvement détecté !"); // Sujet du mail 
    
    $NB_FILES_LIMIT = 20; // Limite du nombre de fichiers dans le mail
    
    /* ----- Variables pour la récupération des images ----- */
    /* Dossier sur le serveur FTP (Lié à la caméra) */
    $server_folder_name = "$year-$monthNum-$day"; <-- // Changez le format du nom du dossier contenant les images 
    $server_folder_path = "/path_to_the_folder/$server_folder_name"; <-- // Changez 'path_to_the_folder' !
                
    // Dossier temporaire en local (Serveur d'execution du script)
    $local_folder_name = "tmp_$year-$monthNum-$day"."_".$hour."h".$minute; <-- // Si vous le souhaitez (pas obligatoire), changez le format du nom du dossier contenant les images.
    $local_folder_path = $_SERVER['DOCUMENT_ROOT']."/name_of_the_folder/$local_folder_name"; <-- // Changez 'name_of_the_folder' !

    /* **************************
       MODE D'EXECUTION DU SCRIPT
       ************************** */
    /* 'DEBUG' ou 'PROD' */
//  $mode = 'DEBUG';
    $mode = 'PROD';



    /* *******************
       FONCTIONS DU SCRIPT
       ******************* */
    function getEncodedContent($file)
    { 
        /* Lecture du fichier en mode binaire */
        $f = fopen($file, "rb"); 
        $content = fread( $f, filesize($file)); 
        fclose($f); 
        
        /* Retourne le contenu lu en codage 64 bits */
        return chunk_split(base64_encode($content)); 
    }




    /* Vérification que l'appel provient bien de l'adresse IP de chez soi, via une requête POST et que donc le $_POST existe */
    if (($_SERVER['REMOTE_ADDR'] == $IP_PERSO) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST)))
    {
        /* *****************************   
           Variables reçues de la caméra
           ***************************** */
        $year       = $_POST['y'];
        $monthNum   = $_POST['mo'];
        $day        = $_POST['d'];
        $hour       = $_POST['h'];
        $minute     = $_POST['mi'];
        $seconde    = $_POST['s'];

        ############### DEBUG ###############
        if ($mode == 'DEBUG') {
            mail($TO_EMAIL, "Script appelé", "Le script de détection vient d'être appelé à $hour"."h"."$minute"." et ".$seconde." seconde(s) un mail devrait NORMALEMENT arriver sous peu", "From:$FROM_NAME<$FROM_EMAIL>"); }
        /*
        $year       = "2015";
        $monthNum   = "05";
        $day        = "26";
        $hour       = "20";
        $minute     = "14";
        $seconde    = "19";
        */
        #####################################
                
    /* **********************************    
       Variables pour la création du mail
       ********************************** */
    /* Conversion n° du mois en nom du mois */
    $monthsNameListFR = array("janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre");
    $monthName = $monthsNameListFR[$monthNum-1];                    
        
    /* En-têtes */
    $headers  = "From:$FROM_NAME<$FROM_EMAIL>\n";
    
    $headers .= "X-Priority: 1 \n";
    $headers .= "MIME-Version: 1.0 \n";

    $headers .= "Content-Type: multipart/mixed; "; 
    $headers .= "boundary=\"Message-Boundary\"\n"; 
    $headers .= "Content-Transfer-Encoding: 7bit\n";
        
    /* Contenu du mail */
    $body_top  = "--Message-Boundary\n"; 
    $body_top .= "Content-Type: text/html; charset=US-ASCII\n"; 
    $body_top .= "Content-Transfer-Encoding: 64BIT\n"; 
    $body_top .= "Content-Description: Mail Message Body\n\n";
        
    $message   = "Un mouvement a été détecté le $day $monthName $year à ".$hour."h".$minute." et ".$seconde."seconde(s).<br/><br/>";
    $body_top .= stripslashes($message)."\n";
        
    $mail_content = $body_top;
        
    /* Tableau des images à joindre au mail */
    $attachments = array();
        
        
    //set_time_limit(200);
    /* Mise en "pause" du script quelques secondes pour attendre que
       l'ensembe des images capturées par la caméra soient enregistrées */
    //sleep(70);

        
        
        
    /* ***********************
       RÉCUPÉRATION IMAGES FTP
       *********************** */
    /* Connexion au serveur FTP ou arrêt du script si echec */
    $ftp_connection = ftp_connect($FTP_SERVER) or exit(/* === DEBUG >>> *//*"Erreur : Impossible de se connecter à $ftp_server.<br/>"*/ mail($TO_EMAIL, "Erreur Connexion", "Le script PHP n'a pas réussi à se connecter à $FTP_SERVER.", "From:$FROM_NAME<$FROM_EMAIL>"));
    
        /* Connexion Réussie (sinon cette partie n'est pas exécutée) */
        if ($ftp_connection != FALSE)
        {
            /* Login sur le serveur FTP */
            if (ftp_login($ftp_connection, $FTP_USER, $FTP_PASSWORD))
            {
                ############### DEBUG ###############
                if ($mode == 'DEBUG') {
                    echo "OK : Connecté en tant que $FTP_USER sur $FTP_SERVER<br/><br/>"; }
                #####################################
     
                /* Activation du mode FTP passif */
                if (ftp_pasv($ftp_connection, true))
                {
                    // Récupération de la liste des fichiers dans le dossier sur le serveur
                    $server_files_list = ftp_nlist($ftp_connection, $server_folder_path);
                
                    if ($server_files_list != FALSE)
                    {
                        sort($server_files_list); /* Tri des résultats par ordre alphabétique (tout les serveurs ne renvoient pas les résultat triés) */
                        ############### DEBUG ###############
                        if ($mode == 'DEBUG')
                        {
                            $pwd = ftp_pwd($ftp_connection);
                            echo("FTP : Dossier courant : ".$pwd.'<br/>');
                            echo("FTP : Chemin vers le dossier des images : $server_folder_path".'<br/>');
                            echo "Local : Dossier courant : ".getcwd().'<br/>';
                            echo("Local : Chemin vers le dossier temporaire des images : $local_folder_path".'<br/><br/>');
                        }
                        #####################################

                        /* Création du dossier temporaire pour le stockage des images qui vont être téléchargées */
                        if (mkdir($local_folder_path, 0777)) /* Si la création réussie */
                        {
                            /* Tableau des images téléchargées en local (Serveur d'execution du script) */
                            $downloaded_files = array();
                            
                            ############### DEBUG ###############
                            if ($mode == 'DEBUG') {
                                echo "Liste des fichiers présents sur le serveur :<br/>"; }
                            #####################################
                        
                            /* Parcours de la liste des fichiers du dossier sur le serveur */
                            foreach($server_files_list as $server_file)
                            {
                                $server_file_exploded = explode("/", $server_file);
                                $server_file = $server_file_exploded[count($server_file_exploded)-1];
                                
                                if (count($downloaded_files) < $NB_FILES_LIMIT)
                                {
                                    ############### DEBUG ###############
                                    if ($mode == 'DEBUG') {
                                        echo 'Fichier actuel : '.$server_file.'<br/>'; }
                                    #####################################
                                    /* Si le nom du fichier parcouru contient l'heure et la minute de la détection ayant déclenchée le script */
                                    if (substr_count($server_file, "$hour-$minute-") >= 1)
                                    { 
                                        /* Téléchargement du fichier du serveur vers le client */
                                        if(ftp_get($ftp_connection, "$local_folder_path/$server_file", "$server_folder_path/$server_file", FTP_BINARY))
                                        {
                                            /* Enregistrement du nom du fichier téléchargé dans un tableau */
                                            $downloaded_files[] = $server_file;
                                        }
                                    }
                                }
                                else
                                {
                                    break; /* Nombre maximum de fichiers atteint, inutile de continuer le parcours */
                                }
                                ############### DEBUG ###############
                                if ($mode == 'DEBUG') {                                
                                    echo('Nombre de fichier téléchargés : '.count($downloaded_files).'<br/>'); }
                                #####################################
                            } 
                             
                            ############### DEBUG ###############
                            if ($mode == 'DEBUG') {
                                echo '<br/>Contenu du tableau des fichiers téléchargés :<br/>';
                                print_r($downloaded_files);
                                echo('<br/>'); }
                            #####################################




                            /* ****************
                               Création du mail
                               **************** */
                            /* Création du tableau des pièces jointes avec nom et type */
                            for ($i = 0; $i < count($downloaded_files); $i++)
                            {
                                $attachments[$i] = array('name'=>$downloaded_files[$i], 'type'=>"image/jpeg");
                                ############### DEBUG ###############
                                if ($mode == 'DEBUG') {
                                    echo 'Pièce jointe n°'.($i+1).' : '.$attachments[$i]['name'].'<br/>'; }
                                #####################################
                            }
                            
                            ############### DEBUG ###############
                            if ($mode == 'DEBUG') {
                                echo '<br/>Toutes les pièces jointes :<br/>';
                                print_r($attachments); }
                            #####################################

                            $message_attachments = "";
                            
                            /* Parcours du tableau des images à joindre et ajout dans le mail  */
                            for($i = 0; $i < count($attachments); $i++) 
                            { 
                                if ($attachments[$i] != null)
                                { 
                                    $name_attachment = $attachments[$i]['name'];
                                    $type_attachment = $attachments[$i]['type'];
                                    
                                    $message_attachments .= "--Message-Boundary\r\n";
                                    $message_attachments .= "Content-Type: $type_attachment; name=\"$name_attachment\"\r\n"; 
                                    $message_attachments .= "Content-Transfer-Encoding: BASE64\r\n"; 
                                    $message_attachments .= "Content-Disposition: attachment\n\n";
                                    
                                    /* Importation du contenu binaire du fichier à attacher (voir fonction)  */
                                    $message_attachments .= getEncodedContent("$local_folder_path/$name_attachment")."\n\n"; 
                                }
                            }
                            
                            /* Ajout des pièces jointes au contenu du mail */
                            $mail_content .= $message_attachments;
                            
                            /* *************
                               Envoi du mail
                               ************* */
                            if (mail($TO_EMAIL, $EMAIL_SUBJECT, $mail_content, $headers)) /* Si l'envoi est réussi */
                            {
                                ############### DEBUG ###############
                                if ($mode == 'DEBUG') {
                                    echo '<br/><br/>Envoi à '.$TO_EMAIL.' réussi !<br/>'; }
                                #####################################
                                #if ($mode == 'DEBUG')
                                if ($mode == 'PROD')
                                {
                                    /* ************************
                                       Suppression des fichiers
                                       ************************ */
                                    /* Parcours du tableau des fichiers téléchargés  */
                                    foreach ($downloaded_files as $downloaded_file)
                                    {
                                        /* Suppression en local */
                                        unlink("$local_folder_path/$downloaded_file");
                                        
                                        /* Suppression sur le serveur */
                                        ftp_delete($ftp_connection, "$server_folder_path/$downloaded_file");
                                    }
                                    
                                    /* ************************    
                                       Suppression des dossiers
                                       ************************ */
                                    /* *************
                                       Dossier Local
                                       ************* */
                                    $local_folder_content = scandir($local_folder_path);
                                    
                                    ############### DEBUG ###############
                                    if ($mode == 'DEBUG') {
                                        echo('<br/>Contenu du dossier local (FTP OVH) après suppression de son contenu : ');
                                        print_r($local_folder_content);
                                        echo('<br/>'); }
                                    #####################################
                                        
                                    $nb_files = 0;
                                    
                                    foreach ($local_folder_content as $file)
                                    {
                                        if ($file != "." && $file != "..")
                                        {
                                            $nb_files++;
                                        }
                                    }
                                    
                                    if ($nb_files == 0) /* Si dossier vide, il est supprimé */
                                    {
                                        if (! rmdir($local_folder_path))
                                        {
                                            mail($TO_EMAIL, "Erreur Suppression Dossier", "Le script PHP n'a pas réussi à supprimer le dossier local", "From:$FROM_NAME<$FROM_EMAIL>");
                                        }
                                    }
                                    
                                    /* ***************
                                       Dossier Serveur
                                       *************** */
                                    /* Récupération de la liste des fichiers dans le dossier sur le serveur */
                                    $server_folder_content = ftp_nlist($ftp_connection, $server_folder_path);
                                    
                                    ############### DEBUG ###############
                                    if ($mode == 'DEBUG') {
                                        echo('<br/>Contenu du dossier sur le serveur FTP après suppression de son contenu : ');
                                        print_r($server_folder_content);
                                        echo('<br/>'); }
                                    #####################################
                                                     
                                    if ($server_folder_content != FALSE)
                                    {
                                        $nb_files = 0;
                                        
                                        foreach($server_folder_content as $file)
                                        {
                                            $server_file_exploded = explode("/", $file);
                                            $file = $server_file_exploded[count($server_file_exploded)-1];
                                            
                                            if ($file != "." && $file != "..")
                                            {
                                                $nb_files++;
                                            }
                                        }
                                        
                                        if ($nb_files == 0) /* Si dossier vide, il est supprimé */
                                        {
                                            if(! ftp_rmdir($ftp_connection, $server_folder_path))
                                            {
                                                mail($TO_EMAIL, "Erreur Suppression Dossier", "Le script PHP n'a pas réussi à supprimer le dossier sur le serveur", "From:$FROM_NAME<$FROM_EMAIL>");
                                            }
                                        }
                                    }
                                } /* Fin de si mode = 'PROD' */
                            } /* Fin de si 'mail bien envoyé' */
                            else
                            {
                                mail($TO_EMAIL, "Erreur Envoi Mail", "Le script PHP n'a pas réussi à envoyer le mail", "From:$FROM_NAME<$FROM_EMAIL>");
                            }
                        } /* Fin de si 'création dossier local' */
                        else
                        {
                            mail($TO_EMAIL, "Erreur Création Dossier", "Le script PHP n'a pas réussi à créer le dossier local", "From:$FROM_NAME<$FROM_EMAIL>");
                        }
                    } /* Fin de si 'récupération liste fichiers serveur OK' */
                } /* Fin de si 'activation mode FTP Pasif' */
            }
            else
            {
                ############### DEBUG ###############
                if ($mode == 'DEBUG') {
                    echo "Erreur : Impossible de se connecter en tant que $FTP_USER sur $FTP_SERVER\n<br/>"; }
                #####################################
            
                mail($TO_EMAIL, "Erreur Login", "Le script PHP n'a pas réussi à se connecter en tant que $FTP_USER sur $FTP_SERVER.", "From:$FROM_NAME<$FROM_EMAIL>");
            } /* Fin de si 'login FTP' */
            
            /* Fermeture de la connexion au serveur */
            ftp_close($ftp_connection);
        } /* Fin de si 'connexion FTP' */
    } /* Fin de si 'provenance Ok' */
?>
