# Not All Shipped Apps Are in This Repository

> [!NOTE]
> While the `apps` folder contains many of the core apps maintained as part of the main server codebase, **not all shipped apps are included directly in this repository**. Some shipped apps are developed and maintained in independent repositories within the [Nextcloud](https://github.com/nextcloud) GitHub organization. These additional apps are added into official release artifacts at build time. The authoritative list of all shipped apps is defined in [`core/shipped.json`](https://github.com/nextcloud/server/blob/master/core/shipped.json).

Additional information on shipped apps is below.

---

## Table of Contents

- [Shipped Apps Not Located in This Repository](#shipped-apps-not-located-in-this-repository)
- [All Shipped Apps: Names and Descriptions](#all-shipped-apps-names-and-descriptions)
- [Shipped Apps: Activation Status Matrix](#shipped-apps-activation-status-matrix)
- [References](#references)

---

## Shipped Apps Not Located in This Repository

The following **shipped apps** are _not included in this repository's_ `apps` folder. Links to their source code are provided:

| App Name                         | Repo Link                                                          |
|-----------------------------------|--------------------------------------------------------------------|
| activity                         | [nextcloud/activity](https://github.com/nextcloud/activity)        |
| app_api                          | [nextcloud/app_api](https://github.com/nextcloud/app_api)          |
| bruteforcesettings               | [nextcloud/bruteforcesettings](https://github.com/nextcloud/bruteforcesettings) |
| circles                          | [nextcloud/circles](https://github.com/nextcloud/circles)          |
| files_downloadlimit              | [nextcloud/files_downloadlimit](https://github.com/nextcloud/files_downloadlimit) |
| files_pdfviewer                  | [nextcloud/files_pdfviewer](https://github.com/nextcloud/files_pdfviewer) |
| firstrunwizard                   | [nextcloud/firstrunwizard](https://github.com/nextcloud/firstrunwizard) |
| logreader                        | [nextcloud/logreader](https://github.com/nextcloud/logreader)      |
| nextcloud_announcements          | [nextcloud/nextcloud_announcements](https://github.com/nextcloud/nextcloud_announcements) |
| notifications                    | [nextcloud/notifications](https://github.com/nextcloud/notifications) |
| password_policy                  | [nextcloud/password_policy](https://github.com/nextcloud/password_policy) |
| photos                           | [nextcloud/photos](https://github.com/nextcloud/photos)            |
| privacy                          | [nextcloud/privacy](https://github.com/nextcloud/privacy)          |
| recommendations                  | [nextcloud/recommendations](https://github.com/nextcloud/recommendations) |
| related_resources                | [nextcloud/related_resources](https://github.com/nextcloud/related_resources) |
| serverinfo                       | [nextcloud/serverinfo](https://github.com/nextcloud/serverinfo)    |
| support                          | [nextcloud/support](https://github.com/nextcloud/support)          |
| survey_client                    | [nextcloud/survey_client](https://github.com/nextcloud/survey_client) |
| suspicious_login                 | [nextcloud/suspicious_login](https://github.com/nextcloud/suspicious_login) |
| text                             | [nextcloud/text](https://github.com/nextcloud/text)                |
| twofactor_nextcloud_notification | [nextcloud/twofactor_nextcloud_notification](https://github.com/nextcloud/twofactor_nextcloud_notification) |
| twofactor_totp                   | [nextcloud/twofactor_totp](https://github.com/nextcloud/twofactor_totp) |
| viewer                           | [nextcloud/viewer](https://github.com/nextcloud/viewer)            |

---

## All Shipped Apps: Names and Descriptions

Descriptions for **all** shipped apps. Drawn from app metadata and/or repository info.

| App Name                         | Description                                                                              |
|-----------------------------------|------------------------------------------------------------------------------------------|
| activity                         | ⚡ Activity app for Nextcloud                                                             |
| admin_audit                      | Provides logging abilities for Nextcloud such as logging file accesses or otherwise sensitive actions. |
| app_api                          | Nextcloud AppAPI                                                                         |
| bruteforcesettings               | 🕵️ Allows admins to configure the brute force settings                                   |
| circles                          | 👪 Create groups with other users on a Nextcloud instance                                |
| cloud_federation_api             | Enable clouds to communicate with each other and exchange data.                          |
| comments                         | Files app plugin to add comments to files.                                               |
| contactsinteraction              | Manages interaction between accounts and contacts, collecting data to provide an address book. |
| dashboard                        | Start your day informed: a customizable dashboard for your overview, appointments, emails, chats, and more. |
| dav                              | WebDAV endpoint for Nextcloud files, calendars, and contacts.                            |
| encryption                       | Default encryption module for Nextcloud Server-side Encryption (SSE).                    |
| federatedfilesharing             | Provides federated file sharing across servers.                                          |
| federation                       | Connect with other trusted servers to exchange the account directory; used for auto-complete in federated sharing. |
| files                            | File management: browse, upload, and organize your files and folders.                    |
| files_downloadlimit              | Nextcloud link share download counter                                                    |
| files_external                   | Adds basic external storage support (FTP, S3, WebDAV, etc.) for mounting external data.  |
| files_pdfviewer                  | 📖 A PDF viewer for Nextcloud                                                            |
| files_reminders                  | Set reminders for files. Requires the Notifications app to show scheduled notifications. |
| files_sharing                    | Enables people to share files and folders within Nextcloud, via local sharing or public links. |
| files_trashbin                   | Enables users to restore files deleted from the system, with options to restore, permanently delete, and automatic cleanup. |
| files_versions                   | Automatically maintains older versions of files, lets users restore previous file versions, and manages quota usage. |
| firstrunwizard                   | 🔮 The first impression matters. The firstrunwizard is the first Nextcloud impression.    |
| logreader                        | 📜 Log reader for Nextcloud                                                              |
| lookup_server_connector          | Sync public account information with the lookup server.                                  |
| nextcloud_announcements          | ℹ️ The latest Nextcloud news directly in your notifications                              |
| notifications                    | 🔔 Notifications app for Nextcloud                                                       |
| oauth2                           | Allows OAuth2 compatible authentication from other web applications.                     |
| password_policy                  | Policy enforcement for passwords.                                                        |
| photos                           | Photo gallery and browsing features.                                                     |
| privacy                          | Privacy configuration and information.                                                   |
| profile                          | Provides a customizable user profile interface.                                          |
| provisioning_api                 | Provides APIs for external systems to create, edit, delete, and query accounts, groups, apps, and quotas. |
| recommendations                  | App and feature recommendations.                                                         |
| related_resources                | Related resources integration for files.                                                 |
| serverinfo                       | Server information display app.                                                          |
| settings                         | Nextcloud settings interface, covers configuration for users and administrators.         |
| sharebymail                      | Share provider which allows you to share files by mail.                                  |
| support                          | Nextcloud support integration.                                                           |
| survey_client                    | User survey and feedback client.                                                         |
| suspicious_login                 | Notification and handling of suspicious logins.                                          |
| systemtags                       | Collaborative tagging functionality which shares tags among people. Great for teams.      |
| theming                          | Adjust the Nextcloud theme to customize appearance.                                      |
| text                             | Rich-text editor app for Nextcloud.                                                      |
| twofactor_backupcodes            | Provides two-factor authentication backup codes as an authentication provider.           |
| twofactor_nextcloud_notification | Nextcloud notification service for two-factor authentication.                            |
| twofactor_totp                   | TOTP (Time-based One-Time Password) authentication.                                      |
| updatenotification               | Displays update notifications for Nextcloud, app updates, and provides SSO for the updater. |
| user_ldap                        | Enables administrators to connect Nextcloud to LDAP/AD user directories for authentication and provisioning users and groups. |
| user_status                      | Allows users to set and display custom status messages.                                  |
| viewer                           | Universal file viewer app (Office, media, etc.).                                        |
| weather_status                   | Weather forecast integration in your dashboard, can be used in other apps like Calendar. |
| webhook_listeners                | Send notifications to external services when important Nextcloud events occur; manage webhooks for real-time automation and integration. |
| workflowengine                   | Nextcloud workflow engine: automate actions and processes based on file and system events.|

---

## Shipped Apps: Activation Status Matrix

This table shows the default state for **all** shipped Nextcloud apps, based on [`core/shipped.json`](https://github.com/nextcloud/server/blob/master/core/shipped.json):

| App Name                         | Always Enabled | Default Enabled | Shipped Only |
|-----------------------------------|:-------------:|:--------------:|:------------:|
| activity                         |               |      ✔         |              |
| admin_audit                      |               |                |      ✔       |
| app_api                          |               |      ✔         |              |
| bruteforcesettings               |               |      ✔         |              |
| circles                          |               |      ✔         |              |
| cloud_federation_api             |      ✔        |                |              |
| comments                         |               |                |      ✔       |
| contactsinteraction              |               |                |      ✔       |
| dashboard                        |               |                |      ✔       |
| dav                              |      ✔        |                |              |
| encryption                       |               |                |      ✔       |
| federatedfilesharing             |      ✔        |                |              |
| federation                       |               |                |      ✔       |
| files                            |      ✔        |                |              |
| files_downloadlimit              |               |      ✔         |              |
| files_external                   |               |                |      ✔       |
| files_pdfviewer                  |               |      ✔         |              |
| files_reminders                  |               |                |      ✔       |
| files_sharing                    |               |                |      ✔       |
| files_trashbin                   |               |                |      ✔       |
| files_versions                   |               |                |      ✔       |
| firstrunwizard                   |               |      ✔         |              |
| logreader                        |               |      ✔         |              |
| lookup_server_connector          |      ✔        |                |              |
| nextcloud_announcements          |               |      ✔         |              |
| notifications                    |               |      ✔         |              |
| oauth2                           |      ✔        |                |              |
| password_policy                  |               |      ✔         |              |
| photos                           |               |      ✔         |              |
| privacy                          |               |      ✔         |              |
| profile                          |      ✔        |                |              |
| provisioning_api                 |      ✔        |                |              |
| recommendations                  |               |      ✔         |              |
| related_resources                |               |      ✔         |              |
| serverinfo                       |               |      ✔         |              |
| settings                         |      ✔        |                |              |
| sharebymail                      |               |                |      ✔       |
| support                          |               |      ✔         |              |
| survey_client                    |               |      ✔         |              |
| suspicious_login                 |               |                |      ✔       |
| systemtags                       |               |                |      ✔       |
| theming                          |      ✔        |                |              |
| text                             |               |      ✔         |              |
| twofactor_backupcodes            |      ✔        |                |              |
| twofactor_nextcloud_notification |               |                |      ✔       |
| twofactor_totp                   |               |                |      ✔       |
| updatenotification               |               |                |      ✔       |
| user_ldap                        |               |                |      ✔       |
| user_status                      |               |                |      ✔       |
| viewer                           |      ✔        |      ✔         |              |
| weather_status                   |               |                |      ✔       |
| webhook_listeners                |               |                |      ✔       |
| workflowengine                   |      ✔        |                |              |

Legend:  
✔ = Yes  
Blank = No  
- **Always Enabled**: Enabled system-wide and cannot be disabled  
- **Default Enabled**: Automatically enabled on new instances, but can be disabled  
- **Shipped Only**: Shipped with Nextcloud, but not enabled by default  

---

## References

- Shipped apps list: [`core/shipped.json`](https://github.com/nextcloud/server/blob/master/core/shipped.json)
- Nextcloud GitHub organization: https://github.com/nextcloud
- See also: [Browse apps/](https://github.com/nextcloud/server/tree/master/apps)
