# retroquest

## Installation :
- make up -> pour lancer l'app
- make init -> pour initialiser l'app (fixtures + assests)
La clef API RAWG est dans le .env (volontairement push sur github)
## Executer les tests :
- make test -> création de la bdd de test + exécution des tests.

## Si make n'est pas disponible : 
### Installation : 
- docker compose up -d
- docker compose exec php composer install
- docker compose exec php php bin/console doctrine:database:create --if-not-exists
- docker compose exec php php bin/console doctrine:migrations:migrate
- docker compose exec php php bin/console doctrine:fixtures:load --append
- docker compose exec php php bin/console assets:install --symlink public
- docker compose exec php npm install
- docker compose exec php npm run build

### Executer les tests :
1. préparation de la base de donnée : 
- docker compose exec php php bin/console doctrine:database:drop --env=test --force --if-exists
- docker compose exec php php bin/console doctrine:database:create --env=test --if-not-exists
- docker compose exec php php bin/console doctrine:migrations:migrate --env=test --no-interaction
2. execution des tests :
- docker compose exec php php bin/phpunit

## Stack tech: 
- Symfony 7.4 (Monolithique, pas d'API REST)
- Base de données relationnelle MySQL
- Vue.js avec Symfony UX
- [datatables](https://datatables.net/manual/vue)

## clef api rawg.io (deja dans le .env volontairement commit): 
    7ed5cc4a22894491881a735919d2e539
    https://api.rawg.io/docs/

## compte utilisateurs : 
Mot de passe unique RetroPassword123! \
comptes disponibles :
- 9 compte collector (collector1@example.com, collector2@example.com, ...)
- 2 compte modérateur (moderator1@example.com, moderator2@example.com)
- 1 compte administrateur (admin@example.com)

## Implémentation : 
1. Gestion utilisateur hiérarchique: 
    - L'Administrateur : Il a le contrôle total. Il gère les rôles et les accès des membres.
        * gestion des membres dans la page [Gérer les droits des utilisateurs](http://localhost/administrator/users)
    - Le Modérateur : Il veille au bon comportement de la communauté et à la propreté du catalogue. Il dispose d'un espace pour valider ou supprimer les avis laissés par les collectionneurs, et peut masquer une fiche de jeu si elle lui est signalée comme obsolète ou erronée.
        * gestion des commentaires dans la page [Modération des avis](http://localhost/moderator/review)
        * gestion des jeux dans la page [Gestion du catalogue de jeux](http://localhost/moderator/games)
            + TODO : Signaler un jeu
    - Le Collectionneur : Il gère son activité de membre. Il peut ajouter des jeux à sa collection personnelle, laisser des avis, et proposer ou réaliser des échanges directement avec d'autres membres.
        * gestion de sa collection dans la page [Ma collection](http://localhost/collector/myCollection)
        * Ajouter un avis sur un jeu dans la page du jeu en question [Fiche jeu](http://localhost/collector/game/1)
        * Rechercher des jeu a echanger sur la page [Rechercher un Échange](http://localhost/collector/exchange/search)
        * Proposer un echange sur la page [Proposer un échange](http://localhost/collector/exchange/propose/20)
        * Consulter et gerer (Annuler) les echanges envoyer sur la page [Mes Demandes Envoyées](http://localhost/collector/exchange/sent)
        * Consulter et gerer (accepter/refuser) les echanges recu sur la page [Mes Demandes Reçues](http://localhost/collector/exchange/received)
2. Route ouverte & API Externe (Le "Price Guide")
    - L'application doit proposer une route accessible sans authentification qui affiche les
    tendances ou les prix du marché rétro. \
    • Scénario : Utilisation d'une API publique (ex: RAWG, ou une API de conversion de devises/marché financier) pour enrichir les fiches ou afficher des données externes globales. \
    • Contrainte : Utilisation obligatoire du HttpClient de Symfony. L'application doit gérer les erreurs si l'API externe est indisponible sans interrompre le rendu de la page
        * La page d'accueil affiche les jeu recement ajouter aux collection utilisateur et le prix moyen payer (en devise) [Page d'accueil](http://localhost/)
        * Utilisation de RAWG pour récupérer la description des jeux
3. Datatable paginé (Le Catalogue de la Guilde)
    - Une page réservée aux utilisateurs connectés doit lister l'intégralité des jeux et consoles de la base de données globale.\
    • Cette table doit obligatoirement intégrer une pagination, un tri par colonne (par console, par titre, par année de sortie), et un champ de recherche textuel pour filtrer les données
        * Catalogue des jeu (affiche tous les jeu de la table 'game') disponible sur la page [Catalogue de la Guilde](http://localhost/collector/GuildCatalog)
            + TODO : Ajouter le possibilité d'ajouter un jeu au catalogue
4. Tests Unitaires et Fonctionnels
    - Tests Unitaires : Valider la logique métier (ex: l'éligibilité d'un échange direct entre deux membres selon les pièces possédées dans leur collection respective, ou le calcul de la valeur estimée d'un inventaire).
        * Le service [ExchangeService.php](retroquest/src/Service/ExchangeService.php) défini les régles métier afin de valider un echange (et les actions associées lors de la validation)
        * Les tests de ce service sont disponibles dans le fichier [ExchangeServiceTest.php](retroquest/tests/Service/ExchangeServiceTest.php)
        * Le service [EstimationService.php](retroquest/src/Service/EstimationService.php) défini les régles métier afin de calculer la valeur estimée d'un inventaire.
        * Les tests de ce service sont disponibles dans le fichier [EstimationServiceTest.php](retroquest/tests/Service/EstimationServiceTest.php)
    - Tests Fonctionnels : Valider la sécurité des routes (ex: refus d'accès 403 pour un rôle non autorisé sur les pages d'administration ou de modération) et le parcours nominal d'ajout d'une pièce à la collection d’un membre.
        * Les test permetant de valider la sécurité des routes sont disponibles dans le fichier [RouteSecurityTest.php](retroquest/tests/RouteSecurityTest.php)
        * Les test permetant de valider le parcours nominal d'ajout d'une pièce à la collection d’un membre sont disponibles dans le fichier [AddCollectionItemControllerTest.php](retroquest/tests/Controller/Collector/Collection/AddCollectionItemControllerTest.php)

### information supplémentaire :
- L'utilisation de fos-router + package npm associé génère des warning de securité lors du 'npm install', ces warnings sont liés à des dépendances, le projet étant un prototype je n'ai pas corrigé, mais le problème est connu.
- Exemple d'utilisation d'une exception personnalisé:
    * [InvalidStateExchangeException.php](retroquest/src/Exception/InvalidStateExchangeException.php)
    * [ExchangeService.php](retroquest/src/Service/ExchangeService.php) ligne 49

## étapes : 
1. init Git
2. préparation makefile
3. mise en place docker :
    1. Database
    2. PHP
    3. nginx
4. init symfony 7.4.* -> composer create-project symfony/skeleton:"7.4.*" 
5. installation des bundles -> composer require webapp
6. passage a webpack :
    1. retrait asset mapper -> composer remove symfony/ux-turbo symfony/asset-mapper symfony/stimulus-bundle
    2. installation webpack -> composer require symfony/webpack-encore-bundle symfony/ux-turbo symfony/stimulus-bundle
7. ajout npm image PHP docker. 
8. instalation bundle vue :
    1. composer require symfony/ux-vue
    2. npm install -D vue-loader --force
9. Ajout homeController
10. Création des diagrames :
    1. useCases
    2. class
11. Gestion connection db.
12. création des entitées
13. modification des entitées pour créer les relations
14. mise en place du security.yaml
15. création des fixtures
16. mise en place des enums
17. retrait enum gameConsoles -> les collectioneurs définise la console (donnée libre)
18. implémentation Price Guide
    1. Préparation des querry
    2. Récupération et envoi des donné a twig
    3. utilisation des donnée avec vue js
19. Ajout utils dans make pour gestion base de donnée
20. mise en place du formulaire de connection
21. mise en place bootstrap + style formulaires connetion / inscription.
22. mise en place navbar + auth button.
23. netoyage css GameCard
24. Ajout de la description aux GameCards (depuis RAWG)
25. mise en place menu navigation. 
    1. création composant vue
    2. ajout route dynamiques
26. Mise en place de GuildCatalog.
27. implémentation de maCollection
28. correction de GuildCatalog => on affiche les jeu et non les itemCollection
29. Implémentation du formulaire d'ajout d'item à maCollection
30. correction des test et mise en place test ajout item à maCollection
31. correction gestion cas date vide ajout item itemCollection + implémentation app.flashes
32. implémentation route suppression itemCollection + test
33. implémentation routes admin et moderation
34. implémentation test sécurité des routes
35. implémentation service echange
36. implémentation test service echange
37. implémentation hiérarchie des roles
38. implémentation gestion des roles utilisateurs
39. implémentation listing des commentaire pour le modérateur
40. implémentation suppression des commentaire par le modérateur
41. implémentation validation des commentaire par le modérateur
42. implémentation de la fiche d'un jeu
43. refactorisation des controlleur qui devenais trop volumineux
44. implémentation du formulaire de dépots d'avis
45. refactorisation 2 des controller, normage des nommage + separation des responsabilités
46. implémentation recherche d'item à échanger (TODO formulaire de proposition d'échange).
47. refactorisation des tests.
48. implémentation formulaire d'échange et correction logique echanges
49. correction logique métier d'échange
50. implémentation mes echanges envoyer
51. implémentation mes echanges reçu
52. vérification et correction test echanges
53. implémentation validation d'un echange dans le service
54. implémentation de la validation d'un echange (controller + front)
55. implémentation service estimation
56. implémentation test service estimation
57. implémentation front estimation (composant vue)
58. refactorisation structure fichier vue.js
59. ajout role collector a l'inscription 
60. gestion teardown des tests
