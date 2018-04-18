# Nextcloud Server
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/server/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/server/?branch=master)
[![codecov](https://codecov.io/gh/nextcloud/server/branch/master/graph/badge.svg)](https://codecov.io/gh/nextcloud/server)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/209/badge)](https://bestpractices.coreinfrastructure.org/projects/209)
[![irc](https://img.shields.io/badge/IRC-%23nextcloud%20on%20freenode-orange.svg)](https://webchat.freenode.net/?channels=nextcloud)
[![irc](https://img.shields.io/badge/IRC-%23nextcloud--dev%20on%20freenode-blue.svg)](https://webchat.freenode.net/?channels=nextcloud-dev)

**A safe home for all your data.**

![](https://github.com/nextcloud/screenshots/blob/master/files/filelist.png)

## Why is this so awesome?

* :file_folder: **Access your Data** You can store your files, contacts, calendars and more on a server of your choosing.
* :package: **Sync your Data** You keep your files, contacts, calendars and more synchronized amongst your devices.
* :arrows_counterclockwise: **Share your Data** …by giving others access to the stuff you want them to see or to collaborate with.
* :rocket: **Expandable with dozens of Apps** ...like [Calendar](https://github.com/nextcloud/calendar), [Contacts](https://github.com/nextcloud/contacts), [Mail](https://github.com/nextcloud/mail) and all those you can discover in our [App Store](https://apps.nextcloud.com)
* :lock: **Security** with our encryption mechanisms, [HackerOne bounty program](https://hackerone.com/nextcloud) and two-factor authentication.

*You want to learn more about how you can use Nextcloud to access, share and protect your files, calendars, contacts, communication & more at home and at your Enterprise?* [**Learn about all our Features**](https://nextcloud.com/features).

## Get your Nextcloud

- [**Install** a server by yourself on your own hardware or by using one of our ready to use **Appliances**](https://nextcloud.com/install/#instructions-server)
- [Buy one of the awesome **devices** coming with a preinstalled Nextcloud](https://nextcloud.com/devices/)
- [Find a service **provider** who is hosting Nextcloud for you or your company](https://nextcloud.com/providers/)

*Enterprise? Public Sector or Education user? You may want to have a look into the [**Enterprise Support Subscription**](https://nextcloud.com/enterprise/) provided by the Nextcloud GmbH*

## Get in touch

* :clipboard: [Forum](https://help.nextcloud.com)
* :busts_in_silhouette: [Facebook](https://facebook.com/nextclouders)
* :hatching_chick: [Twitter](https://twitter.com/Nextclouders)
* :elephant: [Mastodon](https://mastodon.xyz/@nextcloud)

[…learn more about how to get support for Nextcloud here!](https://nextcloud.com/support)

## Contribution Guidelines

All contributions to this repository from June, 16 2016 on are considered to be
licensed under the AGPLv3 or any later version.

Nextcloud doesn't require a CLA (Contributor License Agreement).
The copyright belongs to all the individual contributors. Therefore we recommend
that every contributor adds following line to the header of a file, if they
changed it substantially:

```
@copyright Copyright (c) <year>, <your name> (<your email address>)
```

Please read the [Code of Conduct](https://nextcloud.com/community/code-of-conduct/). This document offers some guidance to ensure Nextcloud participants can cooperate effectively in a positive and inspiring atmosphere, and to explain how together we can strengthen and support each other.

Please review the [guidelines for contributing](https://github.com/nextcloud/server/blob/master/CONTRIBUTING.md) to this repository.

More information how to contribute: [https://nextcloud.com/contribute/](https://nextcloud.com/contribute/)

## Running master checkouts

Third-party components are handled as git submodules which have to be initialized first. So aside from the regular git checkout invoking `git submodule update --init` or a similar command is needed, for details see Git documentation.

Several apps that are included by default in regular releases such as [firstrunwizard](https://github.com/nextcloud/firstrunwizard) or [gallery](https://github.com/nextcloud/gallery) are missing in `master` and have to be installed manually.

That aside Git checkouts can be handled the same as release archives.

Note they should never be used on production systems.
