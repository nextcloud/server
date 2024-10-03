# Social login

Make possible create users and login via Telegram, OAuth or OpenID

For OAuth you must create app for certain providers. Login button appear at login page if app id specified. Settings are in "Social login" section of settings page.

## Installation

Login to your NextCloud installation as an administrator and under "Apps" click "Download and enable" next to the "Social Login" app.

See below for setup and configuration instructions.



## Custom OAuth2/OIDC groups

You can use groups from your custom provider. For that you should specify "Groups claim" in custom OAuth2/OIDC provider settings. That claim should be returned from provider in `id_token` or at user info endpoint. Format should be `array` or comma separated string. Eg (with claim named `roles`)

```json
{"roles": ["admin", "user"]}
```
or
```json
{"roles": "admin,user"}
```

Also nested claims is supported. For example `resource_access.client-id.roles` for

```json
"resource_access": {
   "client-id": {
     "roles": [
       "client-role-1",
       "client-role-2"
     ]
   }
}
```

There is also support for setting the displayName:
```
{"roles": [{gid: 1, displayName: "admin"}, {gid: 2, displayName: "user"}]}
```


You can use provider groups in two ways:

1. Map provider groups to existing nextcloud groups
2. Create provider groups in nextcloud and associate it to user (if appropriate option specified)

If you want sync groups on every login do not forget to check "Update user profile every login" setting

## Examples for groups

* You can find example how to configure WSO2IS for return roles claim with OIDC [here](https://medium.com/@dewni.matheesha/claim-mapping-and-retrieving-end-user-information-in-wso2is-cffd5f3937ff)
* [GitLab OIDC allowing specific GitLab groups](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/gitlab.md)

## Built-in OAuth providers

You can copy link of certain login button to get proper "redirect url" for OAuth app setting.

* [Google](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/google.md)
* [Amazon](https://developer.amazon.com/loginwithamazon/console/site/lwa/overview.html)
* [Facebook](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/facebook.md)
* [Twitter](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/twitter.md)
* [GitHub](https://github.com/settings/developers)
* [Discord](#configure-discord)
* [Telegram](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/telegram.md)
* PlexTv - you can use any title as app id
* [Codeberg](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/codeberg.md)

Details about "Allow login only from specified domain" google setting you can find here [#44](https://github.com/zorn-v/nextcloud-social-login/issues/44)
You can use comma separated list for multiple domains

## Config

You can use `'social_login_auto_redirect' => true` setting in `config.php` for auto redirect unauthorized users to social login if only one provider is configured.
If you want to temporary disable this function (e.g. for login as local admin), you can add `noredir=1` query parameter in url for login page. Something like `https://cloud.domain.com/login?noredir=1`

To set options for http client, you can use
```php
  'social_login_http_client' => [
    'timeout' => 45,
    'proxy' => 'socks4://127.0.0.1:9050', // Check https://curl.se/libcurl/c/CURLOPT_PROXY.html for allowed variants
  ],
```
in `config.php`

### Configurate a provider via CLI

You can configure everything from commandline by using the occ utility. To setup a oidc-provider replace the variables and URLs with values that match your deployment.
```bash
php occ config:app:set sociallogin custom_providers --value='{"custom_oidc": [{"name": "gitlab_oidc", "title": "Gitlab", "authorizeUrl": "https://gitlab.my-domain.org/oauth/authorize", "tokenUrl": "https://gitlab.my-domain.org/oauth/token", "userInfoUrl": "https://gitlab.my-domain.org/oauth/userinfo", "logoutUrl": "", "clientId": "$my_application_id", "clientSecret": "$my_super_secret_secret", "scope": "openid", "groupsClaim": "groups", "style": "gitlab", "defaultGroup": ""}]}'
```
to do this with docker you just need to add `docker exec -t -uwww-data CONTAINER_NAME` in front of the command, or run it interactively from `docker exec -it -uwww-data CONTAINER_NAME sh`

To find out how to configure other providers, just configure them in the GUI and take a look at the database afterwards:
```
mysql -u nextcloud -p nextcloud
Password: <yourpassword>

> SELECT * FROM oc_appconfig WHERE appid='sociallogin';
```

Or just run

`docker exec -t -uwww-data CONTAINER_NAME php occ config:app:get sociallogin custom_providers`

### Configure Discord

To properly configure discord you have to:

1. Create new discord application on [DiscordApp developers](https://discordapp.com/developers/applications/me#top)
2. Open tab `Settings -> OAuth2 -> General`. In `Redirects` add new redirection link looking like this: `https://nextcloud.mydomain.com/apps/sociallogin/oauth/discord`.
3. Copy `CLIENT ID` and generate and copy `CLIENT SECRET`
4. Open in Nextcloud `Settings -> Social Login` and paste `CLIENT ID` into field `App id` and `CLIENT SECRET` into `Secret`.
5. Select default group for users created this way.
6. For group mapping check [#395](https://github.com/zorn-v/nextcloud-social-login/pull/395)

## Hint

### About Callback(Reply) Url
You can copy link from specific login button on login page and paste it on provider's website as callback url. To make proper button visible, just fill certain provider settings with random data and change it later.

Some users may get strange reply(Callback) url error from provider even if you pasted the right url, that's because your nextcloud server may generate http urls when you are actually using https.
Please set 'overwriteprotocol' => 'https', in your config.php file.
