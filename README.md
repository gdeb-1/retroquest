# retroquest

## Installation :
- make up -> pour lancer l'app
- make init -> pour initialiser l'app (fixtures + assests)
La clef API RAWG est dans le .env (volontairement push sur github)
## Executer les tests :
- make test -> création de la bdd de test + exécution des tests.

## Stack tech: 
- Symfony 7.4 (Monolithique, pas d'API REST)
- Base de données relationnelle MySQL
- Vue.js avec Symfony UX
- [datatables](https://datatables.net/manual/vue)

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


## clef api rawg.io: 
    7ed5cc4a22894491881a735919d2e539
    https://api.rawg.io/docs/
