# BodyCool
BodyCool est une application web créée dans la cadre du projet de fin de formation de l’école Studi. 
Cette application permet à l’entreprise fictive BodyCool de gérer l’accès à ses fonctionnalités aux franchises et structures de sa marque.

![Logo](https://bodycool.devbymoz.com/images/others/logo-bodycool.svg)

[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://choosealicense.com/licenses/mit/)

## Pour commencer
Pour utiliser le projet, vous aurez besoin d’installer [Composer](https://getcomposer.org/download/) >= 2.4.2 et [NPM](https://nodejs.org/en/download/) >= 8.5.0 sur votre machine.

### Pré-requis
L'application a besoin des technos suivantes pour fonctionner :
- PHP >= 8.1
- Symfony >= 6.1

Pour la persistance des données, j'ai utilisé MySQL mais vous pouvez utiliser autre chose.

### Installation du projet
Voici les étapes pour installer correctement le projet en local :

**1. Cloner le projet**
```bash
  git clone https://github.com/devbymoz/bodycool.git
  cd bodycool
```

**2. Installation les dépendances**
```bash
  composer install
  npm install
```

**3. Configuration de la BDD**

Pour configurer la BDD, il faut créer le fichier **.local.env** à la racine du projet, puis ajouter la variable d'environnement `DATABASE_URL`.
Voici un exemple de cette variable avec MySQL :

`DATABASE_URL="mysql://user:password@127.0.0.1:3306/databaseName?serverVersion=5.7"`

*Remplacez user, password et databaseName par les informations de votre BDD.*

**4. Configuration du Mailer**

L'application utilise le composant mailer de Symfony pour envoyer des mails, je l'ai configuré avec le service de mail Sendinblue mais vous pouvez utiliser [un autre service d'envoi](https://symfony.com/doc/current/mailer.html#transport-setup).

La configuration doit être ajoutée au fichier **.local.env**, voici l'exemple avec Sendinblue ;

`MAILER_DSN=sendinblue+api://yourApiKey@default`
`MAILER_DSN=sendinblue+smtp://yourMail:yourPassword@default`

*Remplacez yourApiKey, yourMail et yourPassword par vos informations Sendinblue.*

**5. Création de la BDD et des différentes tables**

```bash
  php bin/console doctrine:database:create
  php bin/console doctrine:migrations:migrate
```

Si vous rencontrez une erreur en lancant cette commande, verifiez que votre BDD utilise le même port indiqué dans la variable d'environnement `DATABASE_URL`.

**6. Création de l'utilisateur SuperAdmin**

```bash
  php bin/console doctrine:fixtures:load --group=groupSuperAdmin --append
```

Pour vous connecter, utiliser l'identifiant et le mot de passe suivant :
- admin@bodycool.fr
- 123456789


**7. Création de fixtures (optionnel)**

Si vous souhaitez remplir l'application avec de fausses données, vous pouvez utiliser les commandes suivantes dans l'ordre :

Cette commande permet de créer 7 permissions :
```bash
  php bin/console doctrine:fixtures:load --group=permission --append
```

Cette commande permet de créer 93 franchises, 153 structures et 31 utilisateurs :

```bash
  php bin/console doctrine:fixtures:load --group=franchise --group=structure --group=user --append
```
## Documentation

- [Manuel d'utilisation ](https://github.com/devbymoz/bodycool/tree/main/assets/pdf/documentation-bodycool.pdf)

## Auteur

👨🏻‍💻 Mohamed Zaoui [@devbymoz](https://github.com/devbymoz)