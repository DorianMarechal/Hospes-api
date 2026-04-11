# Hospes API

API REST de gestion de reservations pour hebergeurs independants. Multi-logements, tarification saisonniere, synchronisation iCal, gestion d'equipe avec permissions granulaires.

## Stack

- PHP 8.3 / Symfony 7.4 LTS
- API Platform
- Doctrine ORM / PostgreSQL (btree_gist)
- JWT (LexikJWTAuthenticationBundle)

## Prerequis

- PHP 8.3
- Composer
- PostgreSQL (avec extension btree_gist)
- Symfony CLI

## Installation

```bash
git clone https://github.com/DorianMarechal/Hospes-api.git
cd Hospes-api
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console lexik:jwt:generate-keypair
symfony server:start
```

## Qualite

```bash
# Analyse statique
vendor/bin/phpstan analyse -l 6 src

# Formatage
vendor/bin/php-cs-fixer fix

# Tests
vendor/bin/phpunit --testsuite=unit
vendor/bin/phpunit --testsuite=integration
vendor/bin/phpunit --testsuite=functional
```

## Fonctionnalites

- Gestion multi-logements (hotel, gite, cabane, chambre d'hotes, appartement)
- Tarification saisonniere avec differenciation semaine/week-end
- Calcul de prix nuit par nuit
- Reservation avec TTL 15 min et protection contre les doubles reservations
- Protection contre les nuits orphelines
- Synchronisation calendrier iCal (import/export)
- Gestion d'equipe (staff) avec permissions granulaires et perimetre par logement
- Messagerie hote/client
- Systeme d'avis
- Recherche avancee (lieu, dates, capacite, prix, equipements)
- Statistiques de performance (CA, taux d'occupation, RevPAR)
- Administration plateforme

## Documentation

- [Cahier des charges](https://github.com/DorianMarechal/Hospes-docs/blob/main/hospes-cahier-des-charges.md)
- [Modele de donnees (DBML)](https://github.com/DorianMarechal/Hospes-docs/blob/main/hospes-dbml.dbml)
- [Cartographie API](https://github.com/DorianMarechal/Hospes-docs/blob/main/hospes-endpoints.md)
- [Strategie de tests](https://github.com/DorianMarechal/Hospes-docs/blob/main/hospes-tests.md)

## Licence

MIT