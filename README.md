### CamMotionAlert
Ce script a été développé à l'origine pour combler un problème de [MotionPie](https://github.com/ccrisan/motionpie).<br/>
MotionPie est un système d'exploitation pour Raspberry Pi conçu pour utiliser ce dernier comme caméra de vidéosurveillance avec le module caméra dédié.<br/>
<br/>
MotionPie propose une fonction permettant l'envoi d'un email lorsqu'un nouvement est détecté mais elle ne fonctionnait pas sur la version [20150331](https://github.com/ccrisan/motionpie/releases/tag/20150331).<br/>
MotionPie propose également d'appeler une URL lors d'une détection de mouvement avec la possibilité de passer en paramètre dans l'URL la date et l'heure de la détection.<br/>
Ce sont ces paramètres qui permettent au script de fonctionner correctement, ils sont donc essentiels.<br/>
<br/>
##### Formats Paramètres
###### URL d'appel
Format des paramètres dans l'URL appelée par MotionPie lors d'une détection de mouvement :</br>
`?y=%Y&mo=%m&d=%d&h=%H&mi=%M&s=%S`<br/>
où<br/>
`%Y` = Year<br/>
`%m` = Month<br/>
`%d` = Day<br/>
`%H` = Hour<br/>
`%M` = Minute<br/>
`%S` = Seconde<br/>
###### Variables du script
`y`  = Year<br/>
`mo` = Month<br/>
`d`  = Day<br/>
`h`  = Hour<br/>
`mi` = Minute<br/>
`s`  = Seconde<br/>
<br/>
##### Fonctionnement
Lors de son déclenchement le script se connecte en FTP (le FTP doit être activé dans MotionPie) au stockage local associé à MotionPie (par défaut le stockage sur la carte microSD du Raspberry Pi) puis télécharge les images, correspondantes à la minute de la détection du mouvement, dans un dossier temporaire sur l'espace de stockage où est éxecuté le script PHP.<br/>
Les images sont ensuite envoyées par email au destinataire configuré.<br/>
Les images sont alors supprimées du Raspberry Pi et le dossier temporaire est également vidé.
</br>
Le nombre d'images à récupérer et à ajouter à l'email est modifiable mais attention à tenir compte du poids moyen des images prises par la caméra pour ne pas dépasser ~10-15Mo par email de préférence (selon le serveur mail d'envoi et de réception).
<br/>
###### Erreurs
En cas d'erreur dans l'execution du script un email est également envoyé au destinataire pour l'en informer.<br/>
<br/>
##### Compatibilité
Le script peut être utilisé avec d'autre systèmes/caméras tant que la caméra ou le système qui la gère a la possibilité d'appeller une URL en passant les paramètres requis (date et heure de la détection du mouvement en plusieurs valeurs).
