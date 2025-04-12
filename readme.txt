=== UsersWP Custom Fields Display ===
Contributors: pikkendorff-wordpress-org
Tags: userswp, custom fields, admin, user list, sortable columns
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.13
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Affiche les champs personnalisés de UsersWP dans la liste des utilisateurs de l’admin WordPress avec une page de réglages pour choisir les champs.

== Description ==
Ce plugin étend UsersWP en permettant d’afficher les champs personnalisés dans la liste des utilisateurs de l’administration (`wp-admin/users.php`). Il inclut une page de réglages pour sélectionner les champs à afficher et permet de trier ces colonnes. Fonctionnalités :
- Affichage des champs de `wpuwp_usermeta`.
- Tri des colonnes personnalisées.
- Support multilingue (fichier français inclus).
- Bouton "Faire un don au développeur".

== Installation ==
1. Téléchargez le dossier `userswp-custom-fields-display` depuis le répertoire WordPress ou GitHub.
2. Placez-le dans `wp-content/plugins/`.
3. Activez le plugin depuis l’administration WordPress.
4. Allez dans `UsersWP > Affichage des champs personnalisés` pour configurer les champs.

== Frequently Asked Questions ==
= Est-ce que ce plugin nécessite UsersWP ? =
Oui, il faut que UsersWP soit installé et activé.

= Comment modifier l’URL du don ? =
Éditez la ligne `$donate_url` dans `userswp-custom-fields-display.php`.

== Changelog ==
= 1.13 =
* Ajout du bouton "Faire un don au développeur" dans la page de configuration.
= 1.12 =
* Format de date en `jj/mm/aaaa`, correction des caractères d’échappement, support de traduction.
= 1.11 =
* Correction du tri des colonnes.

== Screenshots ==
1. Page de configuration dans l’admin.
2. Liste des utilisateurs avec champs personnalisés.