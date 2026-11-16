# ClairStock — application de gestion de stock

Application Laravel 12 en français pour la gestion des produits, stocks, achats, ventes, paiements, dépenses et rapports d’un commerce.

## Prérequis

- PHP 8.3 ou supérieur avec extensions PDO MySQL, Mbstring, OpenSSL, Tokenizer, XML, Ctype, JSON et Fileinfo
- Composer 2
- MySQL 8

## Installation locale

1. Copier `.env.example` dans `.env` puis renseigner l’accès à MySQL (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
2. Installer les dépendances avec `composer install`.
3. Générer la clé de chiffrement : `php artisan key:generate`.
4. Créer la base MySQL configurée dans `.env`.
5. Lancer les migrations : `php artisan migrate`.
6. Choisir un nom, une adresse et un mot de passe initial d’au moins 12 caractères dans `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL` et `INITIAL_ADMIN_PASSWORD`.
7. Créer les rôles et le compte initial : `php artisan db:seed`.
8. Installer les outils front-end : `npm install`, puis compiler les fichiers avec `npm run build`.
9. Créer le lien de stockage public des images : `php artisan storage:link`.
10. Lancer le serveur local : `php artisan serve`.

La création de l’administrateur est volontairement conditionnée aux variables d’environnement ; aucun compte ou mot de passe universel n’est livré. Configurez également un transport mail réel avant d’utiliser la réinitialisation du mot de passe en production. En local, le transport `log` écrit le lien dans les journaux Laravel.

Pour peupler une base locale vide avec des données d’exemple, après l’initialisation du compte administrateur, lancez `php artisan db:seed --class=DemoDataSeeder`. Ce seeder est limité aux environnements `local` et `testing`; les mots de passe temporaires des deux employés sont générés aléatoirement et affichés une seule fois dans la sortie de commande.

## Modules réalisés

### Phase 1 — Fondations

- Connexion, déconnexion, limitation des tentatives et réinitialisation du mot de passe via le système de notifications Laravel.
- Profil : modification du nom, de l’adresse e-mail et du mot de passe.
- Rôles `administrateur` et `employe`, middleware de rôle et `UserPolicy`.
- Création du compte administrateur par seeder configurable ; création d’utilisateurs par l’administrateur.
- Layout responsive Bootstrap, navigation française et tableau de bord de démarrage.

### Phase 2 — Catégories, produits et stock

- CRUD des catégories et produits avec recherche, filtres, pagination et relations Eloquent.
- SKU unique, catégories protégées contre la suppression lorsqu’elles sont utilisées et produits archivables par désactivation.
- Historique transactionnel des entrées, sorties et ajustements du stock, avec contrôle de stock négatif.
- Stock initial des produits à zéro ; l’ajout de stock passe toujours par le service de stock et crée un mouvement.
- Accès lecture catalogue pour l’employé ; seuls les administrateurs gèrent les fiches et catégories. Les utilisateurs connectés peuvent enregistrer les mouvements manuels avec motif.
- Factories de catégories et de produits, dix catégories initiales et tests fonctionnels du catalogue et du stock.

Les paiements multiples, les dépenses, le tableau de bord enrichi, les rapports et les paramètres sont décrits dans les phases suivantes.

### Phase 3 — Clients, fournisseurs, achats et ventes

- CRUD clients et fournisseurs avec recherche, pagination, relations et protection contre la suppression des fiches déjà référencées.
- Création, consultation, modification des brouillons, réception et annulation d’achats ; une réception crée les mouvements d’entrée et une annulation d’un achat reçu crée des mouvements correctifs soumis au contrôle de stock.
- Création et consultation des ventes comptoir ou client, avec lignes de produits, remises, montants payés/restants et statut de paiement initial.
- Calculs monétaires côté serveur, prix de vente pris depuis le catalogue et coût d’achat historisé par ligne ; le stock n’est modifié que via `StockService`, dans une transaction avec l’opération commerciale.
- Les employés peuvent consulter le catalogue/clients et enregistrer des ventes. Les achats et la gestion des fournisseurs restent réservés à l’administrateur.
- Les paiements multiples, les dépenses et le tableau de bord enrichi sont développés dans la phase 4.

### Phase 4 — Paiements, dépenses et tableau de bord

- Registre de paiements datés pour les ventes et achats reçus, avec moyen, référence, utilisateur, historique et recalcul transactionnel des montants payés/restants.
- Conservation des paiements partiels saisis à la création des ventes ; le registre est alimenté dès l’enregistrement d’une vente et les paiements existants sont repris à la migration.
- CRUD des dépenses avec catégories initiales (loyer, transport, électricité, salaires, fournitures, entretien et autres), filtres, justificatifs JPG/PNG/PDF et total filtré.
- Tableau de bord alimenté par le chiffre d’affaires, les ventes, le stock, les marges estimées, les dépenses, les alertes de stock, les créances anciennes, les meilleurs produits, les ventes par catégorie et les dernières opérations.
- Les dépenses et les informations financières de bénéfice/dettes fournisseurs sont réservées aux administrateurs. Les créances de plus de 30 jours sont signalées comme anciennes, en l’absence d’échéances configurables.

### Phase 5 — Rapports, paramètres et journal d’activité

- Rapports filtrables par jour, semaine, mois ou période personnalisée pour les ventes, achats, stock, dépenses et mouvements ; export CSV UTF-8 compatible avec les tableurs.
- Paramètres du commerce : identité, contacts, adresse, logo, devise, format de date et seuil de stock par défaut. La devise et le format de date sont utilisés dans l’interface.
- Journal d’activité des principales modifications, avec utilisateur, date, adresse IP et valeurs avant/après ; consultation et paramètres réservés à l’administrateur.
- Les bénéfices des rapports sont des estimations fondées sur les coûts enregistrés sur les lignes de vente ; ils ne remplacent pas une comptabilité complète.

### Phase 6 — Qualité, sécurité et exploitation

- Tests fonctionnels couvrant l’authentification, les rôles, le catalogue, les mouvements, les ventes, les achats, les paiements, les dépenses, les rapports et les paramètres.
- Factories clients/fournisseurs et seeder de démonstration réservé au développement ; le seeder commercial standard ne crée pas de comptes de démonstration.
- Requêtes de dates normalisées pour que le dernier jour d’une période soit inclus dans le tableau de bord et les rapports.
- Commande `composer test` pour lancer la suite PHPUnit.

## Vérifications et déploiement

En local, lancez `composer test` et `npm run build`. Avant un déploiement, configurez `APP_ENV=production`, `APP_DEBUG=false`, un `APP_KEY` et un transport mail réel ; servez l’application derrière HTTPS. Accordez au compte du serveur l’écriture sur `storage` et `bootstrap/cache`, appliquez les migrations avec `php artisan migrate --force`, compilez les assets et mettez en place des sauvegardes régulières de la base et des fichiers `storage/app/public`. Après configuration, les commandes `php artisan optimize` et `php artisan storage:link` préparent les caches et le stockage public.
