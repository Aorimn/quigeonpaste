# Quigeon Paste

## Fonctionnalités
- Écriture de pastes simplement 
- Visibilité des pastes public/privé
- Lecture unique sur les pastes
- Coloration syntaxique du code (avec geshi)
- Chiffrement des pastes avec [GreaseMonkey](https://addons.mozilla.org/fr/firefox/addon/greasemonkey/) [script](quigeonpaste_clientsideencryption.user.js)
- Gestion des attaques par force brute sur les pastes privés

## Configuration requise 

- Implémentation en PHP côté serveur, pour faciliter l'auto-hébergement
- Aucune base de données requise (néanmoins le développement d'une autre solution de stockage est facilité)
- Support multi-langues
- Suppression automatique des pastes à expiration sans cron

## Installation

1) Téléchargez Quigeon Paste

2) Copiez `conf/local.conf.php.dist` dans `conf/local.conf.php`

3) Éditez `conf/local.conf.php` avec vos préférences

4) Assurez-vous que le dossier `data/` est accessible en écriture par votre serveur Web

5) Changez le paramètre @include de `quigeonpaste_clientsideencryption.user.js`

6) Pastez !

Pour activer le chiffrement, chaque client doit installer
[GreaseMonkey](https://addons.mozilla.org/fr/firefox/addon/greasemonkey/)
et télécharger [le script GM](quigeonpaste_clientsideencryption.user.js).

## Backends

Trois types de backends sont disponibles dans Quigeon Paste :
- auth
- storage
- tpl

Chacun de ces trois backends est représenté par un dossier dans le dossier backends.
Dans chacun de ces dossiers, un fichier basic.class.php désigne quelles fonctions le backend doit implémenter.

Chaque type de backend vient avec un backend par défaut :
- auth : le backend empêche une adresse IP de bruteforce pour trouver des pastes privés
- storage : stocke vos pastes dans des fichiers sur le système de fichiers
- tpl : définit l'apparence de l'application

Chaque backend dispose d'un fichier ``backends/<type du backend>/<nom du backend>/conf.php`` qui peut être configuré à partir de `conf/local.conf.php`.

## Notes sur le plugin de chiffrement GreaseMonkey 

Le chiffrement des pastes sur les pastebin classiques est effectué par un module Javascript fourni par le pastebin. C'est pourquoi il existe un risque d'atteinte à la confidentialité des données par le serveur.

En utilisant le script GreaseMonkey, vous êtes assuré que le chiffrement est effectué côté client uniquement, en confiance. Notez vependant que vous ne pouvez être sûrs à 100% que le chiffrement fonctionne, même avec un script GreaseMonkey.


Utilisation du chiffrement
---------------------------

QUIGEON Paste vous offre la possibilité de chiffrer vos _paste_ : ils seront stockés, chiffrés, sur le serveur d'ARISE.

Pour utiliser ce service, vous devez disposer de l'extension __Greasemonkey__ (disponible sur [Firefox](https://addons.mozilla.org/fr/firefox/addon/greasemonkey/) et [Chrome](https://chrome.google.com/webstore/detail/tampermonkey/dhdgffkkebhmkfjojejmpbldmpobfkfo?hl=fr)).

Vous pouvez trouver le [script de chiffrement ici](https://paste.iiens.net/quigeonpaste_clientsideencryption.user.js). 
* Sous Firefox, vous avez juste à suivre les instructions.
* Sous Chrome, il faut enregistrer le fichier, puis le lancer depuis le navigateur pour afficher la fenêtre d'installation.

Une fois l'extension installée, il vous suffit de cocher la case correspondante au moment où vous éditez votre _paste_. Après validation, la clé sera transmise directement dans le lien du _paste_ que vous transmettrez à votre destinataire.
__Attention__ : votre destinataire doit également pouvoir exécuter le script __Greasemonkey__ pour déchiffrer le _paste_.


