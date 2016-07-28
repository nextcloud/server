## Submitting issues

If you have questions about how to install or use Nextcloud, please direct these to our [forum][forum]. We are also available on [IRC][irc].

### Short version

 * The [**issue template can be found here**][template] but be aware of the different repositories! See list below. Please always use the issue template when reporting issues.

### Guidelines
* Please search the existing issues first, it's likely that your issue was already reported or even fixed.
  - Go to one of the repositories, click "issues" and type any word in the top search/command bar.
  - You can also filter by appending e. g. "state:open" to the search string.
  - More info on [search syntax within github](https://help.github.com/articles/searching-issues)
* This repository ([server](https://github.com/nextcloud/server/issues)) is *only* for issues within the Nextcloud Server code. This also includes the apps: files, encryption, external storage, sharing, deleted files, versions, LDAP, and WebDAV Auth
* __SECURITY__: Report any potential security bug to us via [our HackerOne page](https://hackerone.com/nextcloud) or security@nextcloud.com following our [security policy](https://nextcloud.com/security/) instead of filing an issue in our bug tracker.
* The issues in other components should be reported in their respective repositories: You will find them in our [GitHub Organization](https://github.com/nextcloud/)

* Report the issue using our [template][template], it includes all the information we need to track down the issue.

Help us to maximize the effort we can spend fixing issues and adding new features, by not reporting duplicate issues.

[template]: https://raw.github.com/nextcloud/core/master/issue_template.md
[forum]: https://help.nextcloud.com/
[irc]: https://webchat.freenode.net/?channels=nextcloud

## Contributing to Source Code

Thanks for wanting to contribute source code to Nextcloud. That's great!

Please read the [Developer Manuals][devmanual] to learn how to create your first application or how to test the Nextcloud code with PHPUnit.

In order to constantly increase the quality of our software we can no longer accept pull request which submit un-tested code.
It is a must have that changed and added code segments are unit tested.
In some areas unit testing is hard (aka almost impossible) as of today - in these areas refactoring WHILE fixing a bug is encouraged to enable unit testing.

[devmanual]: https://docs.nextcloud.org/server/10/developer_manual/

## Translations
Please submit translations via [Transifex][transifex].

[transifex]: https://www.transifex.com/nextcloud
