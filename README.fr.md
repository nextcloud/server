# Nextcloud Server ☁
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/server/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/server/?branch=master)
[![codecov](https://codecov.io/gh/nextcloud/server/branch/master/graph/badge.svg)](https://codecov.io/gh/nextcloud/server)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/209/badge)](https://bestpractices.coreinfrastructure.org/projects/209)

**Une maison sûre pour toutes vos données.**

![](https://raw.githubusercontent.com/nextcloud/screenshots/master/files/Files%20Sharing.png)

## Pourquoi est-ce si génial? 🤩

* 📁 **Accédez à vos données** YVous pouvez stocker vos fichiers, contacts, calendriers et plus sur un serveur de votre choix.
* 🔄 **Synchronisez vos données** Vous conservez vos fichiers, contacts, calendriers et autres synchronisés entre vos appareils.
* 🙌 **Partagez vos données** … en donnant aux autres accès aux documents que vous voulez qu’ils voient ou avec lesquels ils collaborent.
* 🚀 **Extensible avec des centaines d’applications** ...comme [Calendrier](https://github.com/nextcloud/calendar), [Contacts](https://github.com/nextcloud/contacts), [Mail](https://github.com/nextcloud/mail), [Chat Vidéo](https://github.com/nextcloud/spreed) et tous ceux que vous pouvez découvrir dans notre [App Store](https://apps.nextcloud.com)
* 🔒 **Sécurité** avec nos mécanismes de chiffrement, [HackerOne bounty program](https://hackerone.com/nextcloud) et authentification à deux facteurs.

Vous voulez en savoir plus sur la façon dont vous pouvez utiliser Nextcloud pour accéder, partager et protéger vos fichiers, calendriers, contacts, communications et plus à la maison et dans votre organisation? [**Learn about all our Features**](https://nextcloud.com/athome/).

## Obtenez votre Nextcloud🚚

- ☑️ [**Il suffit de vous inscrire**](https://nextcloud.com/signup/) chez l’un de nos fournisseurs soit via notre site Web ou via les applications directement.
-    [**Installer** un serveur par vous-même](https://nextcloud.com/install/#instructions-server) sur votre propre matériel ou en utilisant l’un de nos prêts à l’emploi
**appareils**
- 📦 Achetez l’un des [impressionnants **appareils** à venir avec un Nextcloud préinstallé](https://nextcloud.com/devices/)
- 🏢 Trouvez un [service **provider**](https://nextcloud.com/providers/) qui héberge Nextcloud pour vous ou votre entreprise.

Entreprise? Secteur public ou utilisateur de l’éducation? Vous pouvez jeter un coup d’œil à [**Nextcloud Enterprise**](https://nextcloud.com/enterprise/) fourni par Nextcloud GmbH.

## Entrer en contact 💬

* [📋 Forum](https://help.nextcloud.com)
* [👥 Facebook](https://facebook.com/nextclouders)
* [🐣 Twitter](https://twitter.com/Nextclouders)
* [🐘 Mastodon](https://mastodon.xyz/@nextcloud)

Vous pouvez également [obtenir le support de Nextcloud](https://nextcloud.com/support)!


## rejoindre l'équipe 👪

Il y a beaucoup de façons de contribuer, dont le développement n’en est qu’une! Découvrez [comment participer] (https://nextcloud.com/contribute/), notamment en tant que traducteur, concepteur, testeur, aidant les autres et bien plus encore! 😍


### Development setup 👩‍💻

1. 🚀 [Configurez votre environnement de développement local](https://docs.nextcloud.com/server/latest/developer_manual/getting_started/devenv.html)
2. 🐛 [Choisissez un bon premier numéro](https://github.com/nextcloud/server/labels/good%20first%20issue)
3. 👩‍🔧 Créez une branche et faites vos modifications. N’oubliez pas de signer vos commits en utilisant `git commit -sm "Votre message de validation"`
4. ⬆ Créer une [demande de tirage](https://opensource.guide/how-to-contribute/#opening-a-pull-request) et `@mentionner` les personnes de la question à examiner
5. 👍 Régler les problèmes qui surviennent pendant l’examen
6. 🎉 Attendez qu’il soit fusionné !

Les composants tiers sont gérés comme des sous-modules git qui doivent d’abord être initialisés. Ainsi, en dehors de la commande `git submodule update --init` ou une commande similaire, pour plus de détails, voir la documentation Git.

Plusieurs applications qui sont incluses par défaut dans les versions régulières telles que [Assistant de première exécution](https://github.com/nextcloud/firstrunwizard) ou [Activité](https://github.com/nextcloud/activity) sont manquants dans `master` et doivent être installés manuellement en les clonant dans le sous-dossier « apps ».

Par conséquent, les checkouts git peuvent être traités de la même manière que les archives release, en utilisant les branches `stable*`. Notez qu’ils ne doivent jamais être utilisés sur les systèmes de production..


### Créer un code front-end  🏗

Nous nous dirigeons de plus en plus vers l’utilisation de Vue.js dans le frontend, en commençant par Settings. Pour compiler le code sur les modifications, utilisez ces commandes de terminal dans le dossier racine :

``` bash
# installer des dépendances
make dev-setup

# construire pour le développement
make build-js

# construire pour le développement et regarder les modifications
make watch-js

# construction pour la production avec minification
make build-js-production
```

**Lorsque vous effectuez des modifications, propagez également les fichiers compilés !**

Nous utilisons toujours des modèles de guidon à certains endroits dans Fichiers et Paramètres. Nous les remplacerons pas à pas avec Vue.js, mais en attendant, vous devez les compiler séparément.

Si vous n’avez pas encore installé Handlebars, vous pouvez le faire avec cette commande de terminal :
```
sudo npm install -g handlebars
```

Ensuite, dans le dossier racine de votre installation de développement Nextcloud locale, exécutez cette commande dans le terminal chaque fois que vous changez un fichier `.handlebars` pour le compiler :
```
./build/compile-handlebars-templates.sh
```

Avant de vérifier les modifications de JS, assurez-vous de construire également pour la production :
```
make build-js-production
```
Ajoutez ensuite les fichiers compilés pour la validation.

Pour gagner du temps, pour reconstruire uniquement pour une application spécifique, utilisez ce qui suit et remplacez le module par le nom de l’application :
```
MODULE=user_status make build-js-production
```

Veuillez noter que si vous avez utilisé `make build-js` ou `make watch-js` auparavant, vous remarquerez que de nombreux fichiers ont été marqués comme ayant été modifiés, alors vous devrez peut-être d’abord vider l’espace de travail.

###  outils que nous utilisons 🛠

- [👀 BrowserStack](https://browserstack.com) pour les tests croisés
- [🌊 WAVE](https://wave.webaim.org/extension/) pour les tests d’accessibilité
- [🚨 Lighthouse](https://developers.google.com/web/tools/lighthouse/) pour tester le rendement, l’accessibilité et plus encore


##  lignes directrices des contributions 📜

Toutes les contributions à ce dépôt du 16 juin 2016 sont considérées comme
sous licence AGPLv3 ou toute version ultérieure.

Nextcloud ne nécessite pas de CLA (Contributor License Agreement).
Le droit d’auteur appartient à tous les contributeurs individuels. Par conséquent, nous recommandons
que chaque contributeur ajoute la ligne suivante à l’en-tête d’un fichier, s’il
l’a modifié substantiellement :

```
@copyright Copyright (c) < année>, <ton nom> (<ton mail>)
```

Veuillez lire le [Code de conduite] (https://nextcloud.com/community/code-of-conduct/). Ce document offre quelques conseils pour s’assurer que les participants de Nextcloud peuvent coopérer efficacement dans une atmosphère positive et inspirante, et pour expliquer comment ensemble nous pouvons nous renforcer et nous soutenir mutuellement.

Veuillez consulter les [directives pour contribuer] (.github/CONTRIBUTING.md) à ce dépôt.

Pour en savoir plus sur la façon de contribuer : [https://nextcloud.com/contribute/](https://nextcloud.com/contribute/)
