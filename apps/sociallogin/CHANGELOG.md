
## 5.6.6
- Bump v5.6.6
- Check if user not in group before adding to default group
- Fix deprecation

## 5.6.5
- Allow remove default providers
- Fix(l10n): Update translations from Transifex
- Default provider remove draft
- Add remove button for default providers (#466)

## 5.6.4
- Bump v5.6.4
- Change group string split (#458)

## 5.6.3
- Prevent password policy error on create user
- Fix(l10n): Update translations from Transifex
- Add Codeberg connecting instructions (#449)

## 5.6.2
- Merge branch 'master' of github.com:zorn-v/nextcloud-social-login
- Fix discord useGuildNames option

## 5.6.1
- Bump v5.6.1
- Fix discord "use guild nick" checkbox style

## 5.6.0
- Bump v5.6.0
- Build settings
- Merge branch 'master' of github.com:zorn-v/nextcloud-social-login
- Add apple provider
- Discord guild nicknames (#437)

## 5.5.4
- Bump v5.5.4
- CustomOauth2 add more identifier and displayName variants (#432)

## 5.5.3
- Bump v5.5.3
- fix malformed redirectUrl with query param (#427)

## 5.5.2
- Update phone and website account props
- Fix(l10n): Update translations from Transifex

## 5.5.1
- Bump v5.5.1
- Fix save settings without default providers
- Fix(l10n): Update translations from Transifex

## 5.5.0
- Bump v5.5.0
- Discord group mapping
- Default provider fixes
- Hide unused default providers in settings
- GroupMapping.vue component

## 5.4.3
- Merge branch 'master' of github.com:zorn-v/nextcloud-social-login
- Add userid for oauth2 provider

## 5.4.2
- Update npm deps

## 5.4.1
- Create primary key in first migration

## 5.4.0
- Primary key for sociallogin_connect table
- Merge pull request #389 from pktiuk/master
- docs: Describe configuration for discord

## 5.3.0
- Added `sub` field as oauth2 user identifier

## 5.2.0
- Update last login timestamp

## 5.1.6
- Merge branch 'master' of github.com:zorn-v/nextcloud-social-login
- Update deps
- Fix(l10n): 🔠 Update translations from Transifex
- [tx-robot] updated from transifex

## 5.1.5
- Translations
- Update deps

## 5.1.4
- Bump v5.1.4
- Revert "l10n: Remove blank before questionmark."
- Merge branch 'master' of github.com:zorn-v/nextcloud-social-login
- Do not reset forms data after save settings
- Merge pull request #382 from rakekniven/patch-1
- l10n: Remove blank before questionmark.

## 5.1.3
- Use callback url as client id for PlexTv
- [tx-robot] updated from transifex

## 5.1.2
- Do not store pin id for PlexTv

## 5.1.1
- Do not store pin id for PlexTV
- Fix readme

## 5.1.0
- Add PlexTv provider

## 5.0.5
- Bump v5.0.5
- [tx-robot] updated from transifex
- Downgrade @nextcloud/dialogs to 1.4.0
- Fixed settings checkboxes
- Update deps

## 5.0.4
- Fix social_login_auto_redirect

## 5.0.3
- Fix social_login_auto_redirect
- Cleanup

## 5.0.2
- Fixed custom login urls

## 5.0.1
- Hide body on telegram login

## 5.0.0
- Bump v5.0.0
- [tx-robot] updated from transifex
- Default telegram button style
- Remove import dialog styles
- [tx-robot] updated from transifex
- Build js
- Fix "None" translation
- Adapt for transifex
- [tx-robot] updated from transifex
- Adapt settings for transifex
- Use static strings for "t" function

## 4.18.2
- Cleanup
- .l10nignore 3rdparty
- l10n/.gitkeep
- Transifex config
- Button text without prefix option

## 4.18.1
- Bump v4.18.1
- Fix telegram button
- BitBucket button icon

## 4.18.0
- NC 25 compat

## 4.17.1
- Return empty response instead of "null" in api

## 4.17.0
- Allow set config via api

## 4.16.2
- Ignore all errors on set avatar

## 4.16.1
- Js builded

## 4.16.0
- Added "hd" auth param config to mailru
- Merge pull request #366 from zorn-v/dependabot/npm_and_yarn/terser-5.14.2
- Bump terser from 5.11.0 to 5.14.2

## 4.15.3
- Bump v4.15.3
- Merge pull request #355 from WhoAmI0501/patch-1
- reformatted all l10n files
- improved german translation + fix for some typos
- add createRememberMeToken call at login

## 4.15.2
- Bump v4.15.2
- Merge pull request #350 from vtavernier/fix/login-redirect-oc-webroot
- fix(login): take custom webroot into account when redirecting

## 4.15.1
- Do not require php 7.4

## 4.15.0
- BitBucket support
- Merge pull request #346 from lavaux/bitbucket_pr
- Add support for Bitbucket
- Merge pull request #345 from zorn-v/dependabot/npm_and_yarn/minimist-1.2.6
- Bump minimist from 1.2.5 to 1.2.6

## 4.14.0
- NC 23+ fixes

## 4.13.1
- Fix zh_CN/zh_TW l10n

## 4.13.0
- Auth params for yandex

## 4.12.3
- Change update user address check

## 4.12.2
- Fix set user address
- Cleanup

## 4.12.1
- Allow use nextcloud as oauth2 provider

## 4.12.0
- [Discord] Allow login only for specified guilds
- Call setSystemEMailAddress if exists
- Merge pull request #336 from zorn-v/dependabot/npm_and_yarn/follow-redirects-1.14.8
- Bump follow-redirects from 1.14.7 to 1.14.8

## 4.11.0
- Bump v4.11.0
- Merge pull request #332 from qwertzdenek/oauth2-identity-user-id
- Seznam.cz login use oauth_user_id

## 4.10.1
- Fix connect account with several custom providers
- Merge pull request #330 from zorn-v/dependabot/npm_and_yarn/nanoid-3.2.0
- Bump nanoid from 3.1.25 to 3.2.0
- Merge pull request #329 from zorn-v/dependabot/npm_and_yarn/follow-redirects-1.14.7
- Bump follow-redirects from 1.14.4 to 1.14.7

## 4.10.0
- Bump version
- Add discourse provider type
- Merge pull request #323 from paroga/discourse
- Add support for Discourse

## 4.9.7
- Preffer displayname_claim in oauth2

## 4.9.6
- Display name claim for OAuth2

## 4.9.5
- Fix custom OIDC photoURL

## 4.9.4
- Fix incoming photoURL as array

## 4.9.3
- Fix deprecation

## 4.9.2
- Bump v4.9.2
- Cleanup deprecations
- Fix social ligin connect in postgre

## 4.9.0
- Allow proxy for http client

## 4.8.10
- Revert hybridauth downgrade

## 4.8.9
- Bump v4.8.9
- Downgrade htbridauth to 3.3.0

## 4.8.8
- Bump v4.8.8
- Merge pull request #305 from pka23/yandex
- Add yandex provider
- Update hybridauth lib

## 4.8.7
- Fix translate

## 4.8.6
- Update npm deps

## 4.8.5
- Fix cron fail

## 4.8.4
- Bump v4.8.4
- Merge pull request #295 from Ollienator/patch-1
- Update CustomOpenIDConnect.php

## 4.8.3
- Take userId for OAuth2 identifier

## 4.8.2
- Photo url fix

## 4.8.1
- Fill email from user info url only if not filled before

## 4.8.0
- Bump v4.8.0
- displayname_claim option
- Fix inconsitencies in CustomOpenIDConnect
- Tabs to spaces
- Upgrade to webpack 5
- CS fixes

## 4.7.0
- Stop using \OC_App::registerLogIn

## 4.6.20
- Fix some deprecations

## 4.6.19
- Merge branch 'master' of github.com:zorn-v/nextcloud-social-login
- Bump v4.6.19
- Merge pull request #289 from MBcom/MBcom-avatar-update-patch
- fix profile image update for connected accounts

## 4.6.18
- Fix link telegram account

## 4.6.17
- Add telegramm button only with login form
- git push on release
- Update ru translation

## 4.6.16, tag: v4.6.15
- Fix hide_default_login with only telegram configured

## 4.6.14
- Fix telegram include without any other alt logins

## 4.6.13
- Bump v4.6.13
- Merge pull request #286 from Geonov/master

## 4.6.12
- Fixed fill user groups with userinfo url specified

## 4.6.11
- Update groups from user info for oidc
- Prefer profile from user info in oidc

## 4.6.10
- Bump v4.6.9

## 4.6.9
- Fill identifier on get user profile if needed in oidc

## 4.6.8
- Bump v4.6.8
- Revert preferred_username priority for OIDC
- Fuck dependabot
- Merge pull request #271 from zorn-v/dependabot/npm_and_yarn/browserslist-4.16.6
- Bump browserslist from 4.16.0 to 4.16.6

## 4.6.7
- Fixed typo
- Return getting displayName from name in oidc token

## 4.6.6, tag: v4.6.5
- Display name fix for OIDC

## 4.6.4
- Get preferred_username by user info in OIDC

## 4.6.3
- Change priority for preferred_username in OIDC

## 4.6.2
- Improved custom oauth

## 4.6.1
- Fix "Invalid argument supplied for foreach"

## 4.6.0
- Bump v4.6.0
- Update npm deps
- restrict_users_wo_assigned_groups option
- Update npm deps

## 4.5.3
- Fix first time login

## 4.5.2
- Fix typo
- Try to fix NC 20.0.1 issue

## 4.5.1
- Set default login provider for user session
- Tabs to spaces

## 4.5.0
- Bump v4.5.0
- Merge pull request #250 from giz-berlin/feature/database-migrations
- npm audit fix
- Split SocialConnectDAO into mapper and entity
- Migrate database to using database migrations

## 4.4.1
- Fix cron with hide_default_login

## 4.4.0
- Bump v4.4.0
- Add Mailru provider
- Added CLI Configuration Example
- Update npm deps

## 4.3.2
- Bump v4.3.2
- fix: button styling color not applying

## 4.3.1
- Bump v4.3.1
- show default login via button

## 4.3.0
- Bump v4.3.0
- German localization
- setting to "Hide default login" (can be made visible again with /login?showDefault=1)
- Fix CS

## 4.2.1
- Fix extended groups check

## 4.2.0
- Bump v4.2.0
- GitHub orgs fixes
-  Login only for specified GitHub organizations
- Readme org
- Add GitLab OIDC example to Readme.md

## 4.1.0
- Bump v4.1.0
- Replace `!isset` for `empty`
- Add check on gid is set
- group mapping bugfix
- Update documentation
- Add sync of group displayName

## 4.0.3
- Fix LightOpenID deprecation

## 4.0.2
- Downgrade hybridauth to 3.3.0 cause phpseclib conflict

## 4.0.1
- Use IEventDispatcher

## 4.0.0
- Settings page tweaks
- Use NC 20 bootstrapping
- Update npm deps
- Update hybridauth

## 3.6.0
- Remeber me while login

## 3.5.3
- Bump v3.5.3
- Correction orthographe/grammaire

## 3.5.2
- Update npm deps

## 3.5.1
- Auto map ext group if it exists
- Update dep

## 3.5.0
- Doc social_login_http_client config
- social_login_http_client => timeout config
- Update npm deps

## 3.4.2
- Bump v3.4.2
- Fix ja_JP.json pluralForm
- Add Japanese translation

## 3.4.1
- Bump v3.4.1
- fix stupid typo

## 3.4.0
- Bump v3.4.0
- Fix CS
- Cleanup and fix routes
- add ocs-api Route to unlink user-ids to logins
- add ocs-api Route to link user-ids to logins
- Update README.md

## 3.3.0
- Bump v3.3.0
- Fix CS
- Now update user street address if exists (#197)

## 3.2.8
- Update front deps
- Translate Log in with in french

## 3.2.7
- Revert setting on completeLogin

## 3.2.6
- Bump v3.2.6
- Set password as uid on completeLogin
- Bump lodash from 4.17.15 to 4.17.19

## 3.2.5
- Bump v3.2.5
- Import missing CustomOAuth1 class

## 3.2.4
- Fix adding custom provider if it was empty

## 3.2.3
- Changed keycloak button color
- Exclude node_modules in app scss webpack rule
- Optimize keycloak icon

## 3.2.2
- Added keycloak button style

## 3.2.1
- export-ignore webpack.js
- Build styles from scss
- Remove api_base_url for oauth1

## 3.2.0
- Bump v3.2.0
- ProviderService::getAuthUrl
- Add custom oauth1 providers type
- Custom login cia single controller method
- Custom login via single method
- Login loginc moved to provider service

## 3.1.3
- Bump v3.1.3
- Fix empty settings page
- Add telegram link to docs

## 3.1.2
- Telegram refactoring

## 3.1.1
- Custom providers refactored

## 3.1.0
- Bump v3.1.0
- Vue app for settings page
- Include notification styles in bundle
- Remove vue

## 3.0.1
- Fix info.xml

## 3.0.0
- Bump v3.0.0
- Do not use jquery in settings.js
- const -> var
- Build personal.js via webpack
- Do not use jquery for telegram
- Update hybridauth

## 2.4.8
- Update max NC version to 21

## 2.4.7
- Do not fail first login if mailer not properly configured

## 2.4.6
- Fix noredir

## 2.4.5
- Allow local login if social_login_auto_redirect enabled

## 2.4.4
- Bump v2.4.4
- Fix converting JWT token

## 2.4.3
- Fix app register

## 2.4.2
- Fix NC18 deprecation

## 2.4.1
- Bump v2.4.1
- manually change autoload, add QQ and slack
- modify lang file to fit in different language

## 2.4.0
- Bump v2.4.0
- Change QQ button bg color
- Rename qq images to lowercase
- Change slack button bg color
- Merge pull request #143 from mai1015/qqProvider
- Merge branch 'master' into qqProvider
- Add slack provider to hybridauth copy
- Add Slack as preconfigured OAuth provider
- add qq provider
- Hash profile id if illegal symbols in it
- indicate that verifying site is optional

## 2.3.0
- Allow create users with disabled account

## 2.2.3
- Fix mysql exception on install

## 2.2.2
- Fix undefined index logout_url
- Merge pull request #128 from didnt1able/patch-1

## 2.2.1
- Bump v2.2.1
- Fix delete custom provider in NC 17
- Update README.md

## 2.2.0
- Disable notify admins setting
- Trim allowed host domains for google

## 2.1.5
- Fix undefined index for custom providers

## 2.1.4
- Fix undefined index 'defaultGroup' warning

## 2.1.3
- Fix undefined index 'style' warning

## 2.1.2
- preferred_username as one of display name claim for OIDC

## 2.1.1
- Fix nested groups claim
- Telegram login button before alternative logins form

## 2.1.0
- Allow nested groups claim

## 2.0.3
- Fix prune user groups if incorrect groups claim defined for provider
- Fix some app:check errors

## 2.0.2
- Bump v2.0.2
- Allow multiple domains for google login

## 2.0.1
- Fix for nextcloud 17

## 2.0.0
- Bump v2.0.0
- Updated screenshot
- Fix styles
- Allow prune all user groups if empty group claim return from provider
- Merge pull request #115 from ochorocho/master
- Add disconnect logins styles again
- Check empty groups fix
- Add translation for aria-label, remove useless translation in js
- Add aria-label
- Hide icons only on mobile
- Merge branch 'master' of https://github.com/zorn-v/nextcloud-social-login
- Optimize responsiveness
- Add guest.css styles on login page
- Use interface to query l10n service
- Update README.md
- Merge pull request #114 from josephdpurcell/feature/facebook-sso-docs
- Merge branch 'master' into feature/facebook-sso-docs
- Adjust Google SSO setup docs according to feedback
- Set correct redirect URL for Google SSO setup
- Add Twitter SSO setup instructions
- Fix slip text to the right for buttons without icons and styles fixes in personal settings page
- Add Facebook SSO setup instructions
- Add ru translation for "Log in with"
- Add german translation
- Rework/streamline button style on personal settings page
- Prevent "undefined index 'style'" warnings after update
- Move "Log in with" label to "sociallogin" app
- Add more predefined icons, fix dropdown settings after click on '+'
- Fix template label 'Add group mapping' in template, add button style dropdown
- Add more specified selector
- Add optimized icons
- Add streamlines icons and button design
- Add german translation
- Rework/streamline button style on personal settings page
- Prevent "undefined index 'style'" warnings after update
- Move "Log in with" label to "sociallogin" app
- Absolute url for google sso docs
- Ignore docs directory for dist
- Add Google SSO setup instructions
- Add installation instructions to README
- Add more predefined icons, fix dropdown settings after click on '+'
- Fix template label 'Add group mapping' in template, add button style dropdown
- Add more specified selector
- Add optimized icons
- Merge branch 'master' of https://github.com/ochorocho/nextcloud-social-login
- Add streamlines icons and button design

## 1.16.7
- Bump v1.16.7
- Add Brazilian Portuguese translation

## 1.16.6
- Fix undefined offset: 1

## 1.16.5
- Parse auth url params for oidc

## 1.16.4
- Do not prune not available user groups on login setting

## 1.16.3
- Restrict login for users without mapped groups setting
- Fix readme

## 1.16.2
- Bump v1.16.2
- Little refactoring
- Simplify logic
- Add possibility to autocreate groups and use mapping

## 1.16.1
- Groups and logout url for custom oauth2 provider
- Update hybridauth to 3.0.0

## 1.16.0
- Bump v1.16.0
- OIDC logout url support
- Notify admins on user create
- Skip migration if not needed
- NC_KEY_FILE as var in release script
- Release script fix
- Custom providers moved to top in admin settings page
- Fix getting comma separated values

## 1.15.1
- Add user to default group on login
- Drop global default group setting
- Providers default group migration
- Default group provider setting
- Add scope for group mapping css classes

## 1.15.0
- Bump v1.15.0
- Add prefix on group creation
- Fix typo
- Load existing group mapping in admin settings
- Save group mapping
- Remove group mapping handler
- Add group mapping button
- Added oidc groups claim admin setting
- Sync oidc provider groups
- Readme fix
- Fix typo
- Readme for custom oidc groups
- Get groups in custom oidc provider

## 1.14.5
- Fixed redirects to personal settings

## 1.14.4
- Moved personal settings to sociallogin section

## 1.14.3
- Clear session storage on domain mismatch error
- Removed tabs in auth method

## 1.14.2
- Bump v1.14.2
- Improved restiriction by domain on login
- check email domain during social login
- Personal settings via info.xml

## 1.14.1
- Bump v1.14.1
- Fix custom oauth2 and openid connect logins (#83)

## 1.14.0
- Bump v1.14.0
- Changed update_on_login config name
- "Update user data every login" ru translation
- Endpoints as array in custom providers
- Update hybridauth to 3.0.0-rc.10
- Only update when boolean is set
- Add checkbox for updating the user
- Update data on login
- Removed unused method

## 1.13.0
- Built-in amazon oauth provider

## 1.12.0
- Allow connect telegram login to existing account
- Set display name as identifier if empty on account creation
- Allow create account and login via telegram
- Telegram login button on login page
- Admin settings for telegram login

## 1.11.4
- Bump 1.11.4
- Decreased input width in settings page
- Create zh_CN.json
- Create zh_CN.js
- Updated hybridauth to rc.9

## 1.11.3
- Changed google scope

## 1.11.2
- Changed nextcloud max-version to 15

## 1.11.1
- Do not pass empty auth params

## 1.11.0
- Bump v.1.11.0
- Allow login only from specified domain for google provider
- Changed confirm delete provider text
- Provider icons in admin settings page
- Another workaround for custom oauth2
- add german translation
- Added missed comma in fr translation

## 1.10.1
- Fixed removing last custom provider
- Update zh_TW.json
- Update fr.json
- Update README.md

## 1.10.0
- Some tweaks for prevent_create_email_exists
- Added admin setting for enabling/disabling preventing creating account if email exists
- prevent creating account if email exists

## 1.9.5
- Fix social_login_auto_redirect for cli

## 1.9.4
- Merge branch 'master' of github.com:zorn-v/nextcloud-social-login
- Fixed custom oauth workaround
- Update README.md

## 1.9.3
- Bump version
- Merge branch 'master' of github.com:zorn-v/nextcloud-social-login
- Workaround for custom oauth id

## 1.9.2
- Bump v1.9.2
- Update zh_TW.json
- Update zh_TW.js
- Update zh_TW.json

## 1.9.1
- Check that custom providers is array before save admin settings
- Refactored render personal settings

## 1.9.0
- Some refactoring
- Fixes for custom oauth2
- Merge pull request #37 from portrino/custom-oauth2-provider
- Renamed query param redirect_url to login_redirect_url
- Use nexcloud session for hybridauth storage
- Added `@UseSession` annotations
- Redirect to redirect_url if provided
- [FEATURE] adds the possibility to use a custom oauth2 provider
- Merge branch 'master' of github.com:zorn-v/nextcloud-social-login
- Try to implement client login flow

## 1.8.1
- Use md5 of profileId if uid longer 64 chars
- Fixed notices if user_info_url provided

## 1.8.0
- Implemented user info endpoint
- Fixed typos
- Make all texts in admin settings page translatable
- Fields in settings template for oidc user info endpoint

## 1.7.1
- Fixed exception after creating user

## 1.7.0
- Minor fix in release script
- Set last password confirm on login
- Save personal settings
- Fixed chinese translation
- Disable password confirmation for users created via social login
- Personal option for disable password confirmation on settings change
- Check allow_login_connect setting in login controller

## 1.6.5
- Updated version
- Hints about credentials in release script
- Checkout to master before release

## 1.6.4
- Generate CHANGELOG.md on release
- Updated app info
- Removed unnecessary queries in each request

## 1.6.3
- Fix facebook scope
- Refactored login controller

## 1.6.2
- Check for name of all providers for duplicate on save

## 1.6.1
- Alignment fix

## 1.6.0
- Icons for oauth providers

## 1.5.5
- Added Discord provider

## 1.5.4
- Fixed "undefined index: password" in error log

## 1.5.3
- Updated version
- Merge pull request #14 from thomas-lb/french-translation
- Merge pull request #13 from thomas-lb/issue-12
- Add French translation
- Replace title and name providers (fixes #12)

## 1.5.2
- Proper handling providers name and title
- Repair step for separate user configured providers internal name and title
- Universal release script

## 1.5.1
- updated version
- Removed already unnecessary hybridauth fixes
- Updated hybridauth to 3.0.0-rc.5
- Fixed unknown provider translation
- Check for oauth provider existence
- Improved release script

## 1.5.0
- social_login_auto_redirect in config.php
- Merge pull request #8 from sutoiku/master

## 1.4.1
- Clickable links for nexcloud app store description
- Merge remote-tracking branch 'upstream/master'
- Allow automatic redirection if only one alternative login

## 1.4.0
- Check providers titles before saving
- Some minor fixes
- Merge pull request #6 from sutoiku/master
- Remove authenticate override
- Merge remote-tracking branch 'upstream/master'
- Added github link in readme
- Merge remote-tracking branch 'upstream/master'
- Rename to OpenIdConnect

## 1.3.5
- Insert README.md in info.xml description on release
- Get profile from id_token attribute
- Change "oauth2" to "custom_oauth"
- Throw LoginException on empty identifier from provider
- Implement custom OAuth2 login
- Save oauth2 settings
- Add admin settings

## 1.3.4
- Check all saved providers settings before using

## 1.3.3
- Prevent log flood with invalid app config values
- Updated version
- Merge pull request #4 from JanGross/master
- Fix invalid argument if no OpenID providers present

## 1.3.1
- Fixed database.xml

## 1.3.0
- Remove connected social logins on user delete
- Posibility to disconnect connected logins
- Connect social login to account
- List of avail providers on personal settings page
- Render personal settings page
- Allow login connect setting
- DAO for connect logins
- Create table for connect logins
- Translated login exceptions
- Basic check for connect social login to exsisting account
- Disable auto create new users setting
- LoginException on unknown OpenID provider

## 1.2.4
- Fixed issues in admin settings
- Login without password
- Throw LoginException on login error

## 1.2.3
- Updated version
- Extended OpenID provider

## 1.2.2
- Updated version
- Generate forgotten uid

## 1.2.1
- Fix OpenID auth

## 1.2.0
- Try to login via generic OpenID provider
- Renamed login controller
- Adding openid providers
- Remove openid provider button
- Removed preconfigured open id providers

## 1.1.0
- Updated version
- PaypalOpenID support

## 1.0.2
- Default value for oauth providers setting

## 1.0.1
- Fix log error while no providers configured
- Script for make release
- Added max version

## 1.0.0
- Updated version
- Tip about redirect url
- Updated README
- Added twitter provider
- Added GitHub provider
- Listen for password change
- Set email address for new user
- Added screenshot to info.xml
- Added screenshot
- Adding new user to default group
- Set avatar for new user
- Updated readme
- Update version
- Save password in user config
- Login with new created user
- Create new user
- Fixed session issue
- Custom session storage
- Changed oauth login url
- Try to auth
- Refactored oauth providers settings
- Settings for facebook and google
- New user group setting
- Init
