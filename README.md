# retroquest

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

