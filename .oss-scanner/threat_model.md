# Threat model

## What this project does and where untrusted input enters
Nextcloud is a self-hosted file sync and collaboration platform written in PHP.
- Many users with different rights share one instance. Each user's files and data must stay within what they shared, and with whom.
- Official threat model and accepted risks: https://nextcloud.com/security/threat-model/

Untrusted input enters through:
- Unauthenticated requests: login and password reset, public share links (`index.php/s/<token>`, `public.php/dav`), OCS and status endpoints.
- Authenticated users with no admin rights, via WebDAV (`remote.php/dav`), OCS (`ocs/v2.php`), and app controllers.
- File content uploaded by users: previews, metadata extraction, and archive handling.
- Federated servers (federated sharing, `ocm-provider`) and remote storages.

## Principals and trust
From least to most privileged:
- **Anonymous visitor.** Can sign in, reset a password, and see what public endpoints expose.
- **Share link holder.** Holds a public share token, sometimes with a password, sometimes upload-only ("file drop"). Must reach only the shared node, with only the permissions of the share, and only after the password check.
- **Federated server.** Untrusted, including its administrators. Must reach only what was shared with its users.
- **User.** Must stay within their own files and what others shared with them, at the permissions of the share. Reshares must not exceed the permissions of the share they came from.
- **App password or scoped token.** Must not exceed the scope it was created with, e.g. one created without filesystem access must not read files.
- **Admin.** Fully trusted, including group admins (subadmins) and delegated admins, and expected to be able to execute arbitrary code. The same goes for the server operator, `config/config.php`, the database, `occ`, and installed apps, which are not isolated from each other.

## Components that matter most / least
- Most important:
  - authentication, sessions, and app passwords (`lib/private/Authentication`, `core/`)
  - sharing and permission checks (`lib/private/Share20`, `apps/files_sharing`)
  - WebDAV (`apps/dav`)
  - the files and storage layer, including path handling (`lib/private/Files`)
  - server-side encryption (`apps/encryption`)
  - the security middleware (CSRF, CSP, brute-force protection, rate limits) under `lib/private/AppFramework` and `lib/private/Security`
  - outgoing requests: `IClientService` and the local address checks in `IRemoteHostValidator`
- Asymmetries: a restriction enforced on one path to data or an action but not on another.
- Oracles: any difference in a response that reveals something the requester cannot see. User enumeration is an accepted risk.
- Also in scope: the apps shipped under `apps/`.
- Also in scope: the vendored `3rdparty/sabre/` (WebDAV, CalDAV, CardDAV, vObject, XML) and `3rdparty/nextcloud/` packages.
- The rest of `3rdparty/` is in scope only where Nextcloud code reaches a defect in it in an exploitable way.
- Out of scope: `build/`, `tests/`, the development tools in `vendor-bin/`, and the compiled frontend bundles in `dist/`.

## How to exercise it
- Run `apachectl start` first. Apache then serves `/src` at `http://localhost/` with `.htaccess` enabled.
- The instance uses SQLite. Its data directory is `/var/lib/nextcloud/data`, outside the web root.
- Accounts: `admin`/`admin`, and `alice`/`alice` and `bob`/`bob` as plain users, each in a group of their own.
- Enabled: the apps from `apps/` that are enabled by default, plus admin_audit, encryption (enabled, but server-side encryption is not switched on), files_external and user_ldap.
- The `testing` app is a CI helper, not enabled and out of scope.
- Shipped apps that live in their own repositories (activity, notifications, text, circles, …) are not installed.
- Try `curl -u alice:alice -X PROPFIND http://localhost/remote.php/dav/files/alice/`.
- `occ` runs `/src/occ` as `www-data`, the user Apache runs as, and `as-www-data` runs any other command as that user. Use `occ` to create more users, groups and shares, and to enable other apps from `apps/` a scenario needs.
- PHPUnit: `as-www-data composer run test -- tests/lib/<Area>` or `-- apps/<app>/tests`. Some tests are written for CI and can leave the instance unusable. Run only the tests for the code you are looking at, and run `nextcloud-reset` if the instance stops working.
- PHP errors are not shown in responses. Nextcloud logs to `/var/lib/nextcloud/data/nextcloud.log`.
- Not available here: Redis, an LDAP server, and the S3, Swift and Azure object stores. Tests that need them are skipped.

## How you rate severity
Score every finding with CVSS 3.1 and report the vector.

Examples from published advisories:
- GHSA-99gw-ww6p-f2rr, view-only shares on external storage or team folders are writeable: `CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:N/I:H/A:L` (7.1, high).
- GHSA-x43f-gmgh-vvjj, SQL injection in a column type parameter: `CVSS:3.1/AV:N/AC:H/PR:L/UI:N/S:C/C:H/I:H/A:N` (8.2, high).
- GHSA-jgcj-v42r-9922, two-factor authentication bypass via pending session token replay: `CVSS:3.1/AV:N/AC:H/PR:L/UI:N/S:U/C:L/I:H/A:N` (5.9, medium).
- GHSA-vq3v-jv6f-6xp2, mail contact autocomplete bypasses the admin's user enumeration restrictions: `CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:L/I:N/A:N` (4.3, medium).
- GHSA-p3qw-7gwx-wg24, file drop links for end-to-end encrypted folders could drop files into other folders of the share owner: `CVSS:3.1/AV:N/AC:L/PR:L/UI:R/S:U/C:N/I:L/A:N` (3.5, low).
- GHSA-8mpv-ggq8-hf3w, view-only guests could see deleted Collectives pages in the trash bin: `CVSS:3.1/AV:N/AC:H/PR:L/UI:R/S:U/C:L/I:N/A:N` (2.6, low).

## Anything to leave alone
- Self-XSS, missing hardening headers, clickjacking on pages without sensitive actions, and anything only exploitable with `debug` enabled.
- Weak settings that are configurable by the admin (password policy, share expiry) are not vulnerabilities.
- The `admin`/`admin` account and other choices made only for this image.
- What admins can do by design: install and enable apps, configure external storages, run `occ`, execute code, and read data on the server.
- Accepted risks from the official threat model:
  - user enumeration
  - version disclosure
  - content spoofing
  - brute force from rotating IP addresses
  - one app reading another app's tables or config
- SSRF: many features fetch from other hosts by design, so a blind request to a public host is not a finding. Full SSRF is in scope, including reaching local or private addresses.
- Local external storage follows symlinks with web server privileges and is trusted.
- Features that are disabled by default and carry an insecurity warning, such as the LibreOffice preview provider.

## Reports and patches
- One report per root cause, naming the commit, the affected file and function, the principal, the boundary crossed, and every affected route.
- The build leaves `.htaccess` (the install appends its `ErrorDocument` block), `lib/composer/composer/installed.json`, `installed.php` and `InstalledVersions.php` (the development install) changed in `/src`. Leave them out of patches.
- Include a reproducer: HTTP requests against the instance above, or a PHPUnit test that fails before the fix.
- Patches should be minimal, fix the check where it is enforced rather than in one caller, and say whether sibling routes have the same gap. Match the surrounding code: tabs, and an SPDX header in new files.
