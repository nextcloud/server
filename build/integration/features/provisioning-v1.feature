# SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
Feature: provisioning
  Background:
    Given using api version "1"
    Given parameter "whitelist_0" of app "bruteForce" is set to "127.0.0.1"
    Given parameter "whitelist_1" of app "bruteForce" is set to "::1"
    Given parameter "apply_allowlist_to_ratelimit" of app "bruteforcesettings" is set to "true"

  Scenario: Getting an not existing user
    Given As an "admin"
    When sending "GET" to "/cloud/users/test"
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Listing all users
    Given As an "admin"
    When sending "GET" to "/cloud/users"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Create a user
    Given As an "admin"
    And user "brand-new-user" does not exist
    When sending "POST" to "/cloud/users" with
      | userid | brand-new-user |
      | password | 123456 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And user "brand-new-user" exists

  Scenario: Create an existing user
    Given As an "admin"
    And user "brand-new-user" exists
    When sending "POST" to "/cloud/users" with
      | userid | brand-new-user |
      | password | 123456 |
    Then the OCS status code should be "102"
    And the HTTP status code should be "200"
    And user "brand-new-user" has
      | id | brand-new-user |
      | displayname | brand-new-user |
      | email |  |
      | phone |  |
      | address |  |
      | website |  |
      | twitter |  |

  Scenario: Get an existing user
    Given As an "admin"
    When sending "GET" to "/cloud/users/brand-new-user"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Getting all users
    Given As an "admin"
    And user "brand-new-user" exists
    And user "admin" exists
    When sending "GET" to "/cloud/users"
    Then users returned are
      | brand-new-user |
      | admin |

  Scenario: Get editable fields
    Given As an "admin"
    And user "brand-new-user" exists
    Then user "brand-new-user" has editable fields
      | displayname |
      | email |
      | additional_mail |
      | phone |
      | address |
      | website |
      | twitter |
      | fediverse |
      | organisation |
      | role |
      | headline |
      | biography |
      | profile_enabled |
      | pronouns |
    Given As an "brand-new-user"
    Then user "brand-new-user" has editable fields
      | displayname |
      | email |
      | additional_mail |
      | phone |
      | address |
      | website |
      | twitter |
      | fediverse |
      | organisation |
      | role |
      | headline |
      | biography |
      | profile_enabled |
      | pronouns |
    Then user "self" has editable fields
      | displayname |
      | email |
      | additional_mail |
      | phone |
      | address |
      | website |
      | twitter |
      | fediverse |
      | organisation |
      | role |
      | headline |
      | biography |
      | profile_enabled |
      | pronouns |

  Scenario: Edit a user
    Given As an "admin"
    And user "brand-new-user" exists
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | displayname |
      | value | Brand New User |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | quota |
      | value | 12MB |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | email |
      | value | no-reply@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | no.reply@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | noreply@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | phone |
      | value | +49 711 / 25 24 28-90 |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | address |
      | value | Foo Bar Town |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | website |
      | value | https://nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | twitter |
      | value | Nextcloud |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then user "brand-new-user" has
      | id | brand-new-user |
      | displayname | Brand New User |
      | email | no-reply@nextcloud.com |
      | additional_mail | no.reply@nextcloud.com;noreply@nextcloud.com |
      | phone | +4971125242890 |
      | address | Foo Bar Town |
      | website | https://nextcloud.com |
      | twitter | Nextcloud |
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | organisation |
      | value | Nextcloud GmbH |
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | role |
      | value | Engineer |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then user "brand-new-user" has the following profile data
      | userId | brand-new-user |
      | displayname | Brand New User |
      | organisation | Nextcloud GmbH |
      | role | Engineer |
      | address | Foo Bar Town |
      | timezone | UTC |
      | timezoneOffset | 0 |
      | pronouns | NULL |

  Scenario: Edit a user account properties scopes
    Given user "brand-new-user" exists
    And As an "brand-new-user"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | phoneScope |
      | value | v2-private |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | twitterScope |
      | value | v2-local |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | addressScope |
      | value | v2-federated |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | emailScope |
      | value | v2-published |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | email |
      | value | no-reply@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    # Duplicating primary address
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | no-reply@nextcloud.com |
    And the OCS status code should be "101"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | no.reply2@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    # Duplicating another additional address
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | no.reply2@nextcloud.com |
    And the OCS status code should be "101"
    And the HTTP status code should be "200"
    Then user "brand-new-user" has
      | id | brand-new-user |
      | phoneScope | v2-private |
      | twitterScope | v2-local |
      | addressScope | v2-federated |
      | emailScope | v2-published |

  Scenario: Edit a user account multivalue property scopes
    Given user "brand-new-user" exists
    And As an "brand-new-user"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | no.reply3@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | noreply4@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    When sending "PUT" to "/cloud/users/brand-new-user/additional_mailScope" with
      | key | no.reply3@nextcloud.com |
      | value | v2-federated |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    When sending "PUT" to "/cloud/users/brand-new-user/additional_mailScope" with
      | key | noreply4@nextcloud.com |
      | value | v2-published |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then user "brand-new-user" has
      | id | brand-new-user |
      | additional_mailScope | v2-federated;v2-published |

  Scenario: Edit a user account properties scopes with invalid or unsupported value
    Given user "brand-new-user" exists
    And As an "brand-new-user"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | phoneScope |
      | value | invalid |
    Then the OCS status code should be "101"
    And the HTTP status code should be "200"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | displaynameScope |
      | value | v2-private |
    Then the OCS status code should be "101"
    And the HTTP status code should be "200"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | emailScope |
      | value | v2-private |
    Then the OCS status code should be "101"
    And the HTTP status code should be "200"

  Scenario: Edit a user account multi-value property scopes with invalid or unsupported value
    Given user "brand-new-user" exists
    And As an "brand-new-user"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | no.reply5@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    When sending "PUT" to "/cloud/users/brand-new-user/additional_mailScope" with
      | key | no.reply5@nextcloud.com |
      | value | invalid |
    Then the OCS status code should be "102"
    And the HTTP status code should be "200"

  Scenario: Delete a user account multi-value property value
    Given user "brand-new-user" exists
    And As an "brand-new-user"
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | no.reply6@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And sending "PUT" to "/cloud/users/brand-new-user" with
      | key | additional_mail |
      | value | noreply7@nextcloud.com |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    When sending "PUT" to "/cloud/users/brand-new-user/additional_mail" with
      | key | no.reply6@nextcloud.com |
      | value | |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then user "brand-new-user" has
      | additional_mail | noreply7@nextcloud.com |
    Then user "brand-new-user" has not
      | additional_mail | no.reply6@nextcloud.com |

  Scenario: An admin cannot edit user account property scopes
    Given As an "admin"
    And user "brand-new-user" exists
    When sending "PUT" to "/cloud/users/brand-new-user" with
      | key | phoneScope |
      | value | v2-private |
    Then the OCS status code should be "113"
    And the HTTP status code should be "200"

  Scenario: Search by phone number
    Given As an "admin"
    And user "phone-user" exists
    And sending "PUT" to "/cloud/users/phone-user" with
      | key | phone |
      | value | +49 711 / 25 24 28-90 |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then search users by phone for region "DE" with
      | random-string1 | 0711 / 123 456 78 |
      | random-string1 | 0711 / 252 428-90 |
      | random-string2 | 0711 / 90-824 252 |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then phone matches returned are
      | random-string1 | phone-user@localhost:8080 |

  Scenario: Create a group
    Given As an "admin"
    And group "new-group" does not exist
    When sending "POST" to "/cloud/groups" with
      | groupid | new-group |
      | password | 123456 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And group "new-group" exists
    And group "new-group" has
      | displayname | new-group |

  Scenario: Create a group with custom display name
    Given As an "admin"
    And group "new-group" does not exist
    When sending "POST" to "/cloud/groups" with
      | groupid | new-group |
      | password | 123456 |
      | displayname | new-group-displayname |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And group "new-group" exists
    And group "new-group" has
      | displayname | new-group-displayname |

  Scenario: Create a group with special characters
    Given As an "admin"
    And group "España" does not exist
    When sending "POST" to "/cloud/groups" with
      | groupid | España |
      | password | 123456 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And group "España" exists
    And group "España" has
      | displayname | España |

  Scenario: adding user to a group without sending the group
    Given As an "admin"
    And user "brand-new-user" exists
    When sending "POST" to "/cloud/users/brand-new-user/groups" with
      | groupid |  |
    Then the OCS status code should be "101"
    And the HTTP status code should be "200"

  Scenario: adding user to a group which doesn't exist
    Given As an "admin"
    And user "brand-new-user" exists
    And group "not-group" does not exist
    When sending "POST" to "/cloud/users/brand-new-user/groups" with
      | groupid | not-group |
    Then the OCS status code should be "102"
    And the HTTP status code should be "200"

  Scenario: adding user to a group without privileges
    Given user "brand-new-user" exists
    And As an "brand-new-user"
    When sending "POST" to "/cloud/users/brand-new-user/groups" with
      | groupid | new-group |
    Then the OCS status code should be "403"
    And the HTTP status code should be "200"

  Scenario: adding user to a group
    Given As an "admin"
    And user "brand-new-user" exists
    And group "new-group" exists
    When sending "POST" to "/cloud/users/brand-new-user/groups" with
      | groupid | new-group |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: getting groups of an user
    Given As an "admin"
    And user "brand-new-user" exists
    And group "new-group" exists
    When sending "GET" to "/cloud/users/brand-new-user/groups"
    Then groups returned are
      | new-group |
    And the OCS status code should be "100"

  Scenario: adding a user which doesn't exist to a group
    Given As an "admin"
    And user "not-user" does not exist
    And group "new-group" exists
    When sending "POST" to "/cloud/users/not-user/groups" with
      | groupid | new-group |
    Then the OCS status code should be "103"
    And the HTTP status code should be "200"

  Scenario: getting a group
    Given As an "admin"
    And group "new-group" exists
    When sending "GET" to "/cloud/groups/new-group"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Getting all groups
    Given As an "admin"
    And group "new-group" exists
    And group "admin" exists
    When sending "GET" to "/cloud/groups"
    Then groups returned are
      | España |
      | admin |
      | hidden_group |
      | new-group |

  Scenario: create a subadmin
    Given As an "admin"
    And user "brand-new-user" exists
    And group "new-group" exists
    When sending "POST" to "/cloud/users/brand-new-user/subadmins" with
      | groupid | new-group |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: get users using a subadmin
    Given As an "admin"
    And user "brand-new-user" exists
    And group "new-group" exists
    And user "brand-new-user" belongs to group "new-group"
    And user "brand-new-user" is subadmin of group "new-group"
    And As an "brand-new-user"
    When sending "GET" to "/cloud/users"
    Then users returned are
      | brand-new-user |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: removing a user from a group which doesn't exists
    Given As an "admin"
    And user "brand-new-user" exists
    And group "not-group" does not exist
    When sending "DELETE" to "/cloud/users/brand-new-user/groups" with
      | groupid | not-group |
    Then the OCS status code should be "102"

  Scenario: removing a user from a group
    Given As an "admin"
    And user "brand-new-user" exists
    And group "new-group" exists
    And user "brand-new-user" belongs to group "new-group"
    When sending "DELETE" to "/cloud/users/brand-new-user/groups" with
      | groupid | new-group |
    Then the OCS status code should be "100"
    And user "brand-new-user" does not belong to group "new-group"

  Scenario: create a subadmin using a user which not exist
    Given As an "admin"
    And user "not-user" does not exist
    And group "new-group" exists
    When sending "POST" to "/cloud/users/not-user/subadmins" with
      | groupid | new-group |
    Then the OCS status code should be "101"
    And the HTTP status code should be "200"

  Scenario: create a subadmin using a group which not exist
    Given As an "admin"
    And user "brand-new-user" exists
    And group "not-group" does not exist
    When sending "POST" to "/cloud/users/brand-new-user/subadmins" with
      | groupid | not-group |
    Then the OCS status code should be "102"
    And the HTTP status code should be "200"

  Scenario: Getting subadmin groups
    Given As an "admin"
    And user "brand-new-user" exists
    And group "new-group" exists
    When sending "GET" to "/cloud/users/brand-new-user/subadmins"
    Then subadmin groups returned are
      | new-group |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Getting subadmin groups of a user which not exist
    Given As an "admin"
    And user "not-user" does not exist
    And group "new-group" exists
    When sending "GET" to "/cloud/users/not-user/subadmins"
    Then the OCS status code should be "404"
    And the HTTP status code should be "200"

  Scenario: Getting subadmin users of a group
    Given As an "admin"
    And user "brand-new-user" exists
    And group "new-group" exists
    When sending "GET" to "/cloud/groups/new-group/subadmins"
    Then subadmin users returned are
      | brand-new-user |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Getting subadmin users of a group which doesn't exist
    Given As an "admin"
    And user "brand-new-user" exists
    And group "not-group" does not exist
    When sending "GET" to "/cloud/groups/not-group/subadmins"
    Then the OCS status code should be "101"
    And the HTTP status code should be "200"

  Scenario: Removing subadmin from a group
    Given As an "admin"
    And user "brand-new-user" exists
    And group "new-group" exists
    And user "brand-new-user" is subadmin of group "new-group"
    When sending "DELETE" to "/cloud/users/brand-new-user/subadmins" with
      | groupid | new-group |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: Delete a user
    Given As an "admin"
    And user "brand-new-user" exists
    When sending "DELETE" to "/cloud/users/brand-new-user"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And user "brand-new-user" does not exist

  Scenario: Delete a group
    Given As an "admin"
    And group "new-group" exists
    When sending "DELETE" to "/cloud/groups/new-group"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And group "new-group" does not exist

  Scenario: Delete a group with special characters
    Given As an "admin"
    And group "España" exists
    When sending "DELETE" to "/cloud/groups/España"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And group "España" does not exist

  Scenario: get enabled apps
    Given As an "admin"
    When sending "GET" to "/cloud/apps?filter=enabled"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And apps returned are
      | cloud_federation_api |
      | comments |
      | contactsinteraction |
      | dashboard |
      | dav |
      | federatedfilesharing |
      | federation |
      | files |
      | files_reminders |
      | files_sharing |
      | files_trashbin |
      | files_versions |
      | lookup_server_connector |
      | profile |
      | provisioning_api |
      | settings |
      | sharebymail |
      | systemtags |
      | testing |
      | theming |
      | twofactor_backupcodes |
      | updatenotification |
      | user_ldap |
      | user_status |
      | viewer |
      | workflowengine |
      | webhook_listeners |
      | weather_status |
      | files_external |
      | oauth2 |

  Scenario: get app info
    Given As an "admin"
    When sending "GET" to "/cloud/apps/files"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"

  Scenario: get app info from app that does not exist
    Given As an "admin"
    When sending "GET" to "/cloud/apps/this_app_should_never_exist"
    Then the OCS status code should be "998"
    And the HTTP status code should be "200"

  Scenario: enable an app
    Given invoking occ with "app:disable testing"
    Given As an "admin"
    And app "testing" is disabled
    When sending "POST" to "/cloud/apps/testing"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And app "testing" is enabled

  Scenario: enable an app that does not exist
    Given As an "admin"
    When sending "POST" to "/cloud/apps/this_app_should_never_exist"
    Then the OCS status code should be "998"
    And the HTTP status code should be "200"

  Scenario: disable an app
    Given invoking occ with "app:enable testing"
    Given As an "admin"
    And app "testing" is enabled
    When sending "DELETE" to "/cloud/apps/testing"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And app "testing" is disabled
    Given invoking occ with "app:enable testing"

  Scenario: disable an user
    Given As an "admin"
    And user "user1" exists
    When sending "PUT" to "/cloud/users/user1/disable"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And user "user1" is disabled

  Scenario: enable an user
    Given As an "admin"
    And user "user1" exists
    And assure user "user1" is disabled
    When sending "PUT" to "/cloud/users/user1/enable"
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And user "user1" is enabled

  Scenario: Subadmin should be able to enable or disable an user in their group
    Given As an "admin"
    And user "subadmin" exists
    And user "user1" exists
    And group "new-group" exists
    And user "subadmin" belongs to group "new-group"
    And user "user1" belongs to group "new-group"
    And Assure user "subadmin" is subadmin of group "new-group"
    And As an "subadmin"
    When sending "PUT" to "/cloud/users/user1/disable"
    Then the OCS status code should be "100"
    Then the HTTP status code should be "200"
    And As an "admin"
    And user "user1" is disabled

  Scenario: Subadmin should not be able to enable or disable an user not in their group
    Given As an "admin"
    And user "subadmin" exists
    And user "user1" exists
    And group "new-group" exists
    And group "another-group" exists
    And user "subadmin" belongs to group "new-group"
    And user "user1" belongs to group "another-group"
    And Assure user "subadmin" is subadmin of group "new-group"
    And As an "subadmin"
    When sending "PUT" to "/cloud/users/user1/disable"
    Then the OCS status code should be "998"
    Then the HTTP status code should be "200"
    And As an "admin"
    And user "user1" is enabled

  Scenario: Subadmins should not be able to disable users that have admin permissions in their group
    Given As an "admin"
    And user "another-admin" exists
    And user "subadmin" exists
    And group "new-group" exists
    And user "another-admin" belongs to group "admin"
    And user "subadmin" belongs to group "new-group"
    And user "another-admin" belongs to group "new-group"
    And Assure user "subadmin" is subadmin of group "new-group"
    And As an "subadmin"
    When sending "PUT" to "/cloud/users/another-admin/disable"
    Then the OCS status code should be "998"
    Then the HTTP status code should be "200"
    And As an "admin"
    And user "another-admin" is enabled

  Scenario: Admin can disable another admin user
    Given As an "admin"
    And user "another-admin" exists
    And user "another-admin" belongs to group "admin"
    When sending "PUT" to "/cloud/users/another-admin/disable"
    Then the OCS status code should be "100"
    Then the HTTP status code should be "200"
    And user "another-admin" is disabled

  Scenario: Admin can enable another admin user
    Given As an "admin"
    And user "another-admin" exists
    And user "another-admin" belongs to group "admin"
    And assure user "another-admin" is disabled
    When sending "PUT" to "/cloud/users/another-admin/enable"
    Then the OCS status code should be "100"
    Then the HTTP status code should be "200"
    And user "another-admin" is enabled

  Scenario: Admin can disable subadmins in the same group
    Given As an "admin"
    And user "subadmin" exists
    And group "new-group" exists
    And user "subadmin" belongs to group "new-group"
    And user "admin" belongs to group "new-group"
    And Assure user "subadmin" is subadmin of group "new-group"
    When sending "PUT" to "/cloud/users/subadmin/disable"
    Then the OCS status code should be "100"
    Then the HTTP status code should be "200"
    And user "subadmin" is disabled

  Scenario: Admin can enable subadmins in the same group
    Given As an "admin"
    And user "subadmin" exists
    And group "new-group" exists
    And user "subadmin" belongs to group "new-group"
    And user "admin" belongs to group "new-group"
    And Assure user "subadmin" is subadmin of group "new-group"
    And assure user "another-admin" is disabled
    When sending "PUT" to "/cloud/users/subadmin/disable"
    Then the OCS status code should be "100"
    Then the HTTP status code should be "200"
    And user "subadmin" is disabled

  Scenario: Admin user cannot disable himself
    Given As an "admin"
    And user "another-admin" exists
    And user "another-admin" belongs to group "admin"
    And As an "another-admin"
    When sending "PUT" to "/cloud/users/another-admin/disable"
    Then the OCS status code should be "101"
    And the HTTP status code should be "200"
    And As an "admin"
    And user "another-admin" is enabled

  Scenario:Admin user cannot enable himself
    Given As an "admin"
    And user "another-admin" exists
    And user "another-admin" belongs to group "admin"
    And assure user "another-admin" is disabled
    And As an "another-admin"
    When sending "PUT" to "/cloud/users/another-admin/enable"
    And As an "admin"
    Then user "another-admin" is disabled

  Scenario: disable an user with a regular user
    Given As an "admin"
    And user "user1" exists
    And user "user2" exists
    And As an "user1"
    When sending "PUT" to "/cloud/users/user2/disable"
    Then the OCS status code should be "403"
    And the HTTP status code should be "200"
    And As an "admin"
    And user "user2" is enabled

  Scenario: enable an user with a regular user
    Given As an "admin"
    And user "user1" exists
    And user "user2" exists
    And assure user "user2" is disabled
    And As an "user1"
    When sending "PUT" to "/cloud/users/user2/enable"
    Then the OCS status code should be "403"
    And the HTTP status code should be "200"
    And As an "admin"
    And user "user2" is disabled

  Scenario: Subadmin should not be able to disable himself
    Given As an "admin"
    And user "subadmin" exists
    And group "new-group" exists
    And user "subadmin" belongs to group "new-group"
    And Assure user "subadmin" is subadmin of group "new-group"
    And As an "subadmin"
    When sending "PUT" to "/cloud/users/subadmin/disable"
    Then the OCS status code should be "101"
    Then the HTTP status code should be "200"
    And As an "admin"
    And user "subadmin" is enabled

  Scenario: Subadmin should not be able to enable himself
    Given As an "admin"
    And user "subadmin" exists
    And group "new-group" exists
    And user "subadmin" belongs to group "new-group"
    And Assure user "subadmin" is subadmin of group "new-group"
    And assure user "subadmin" is disabled
    And As an "subadmin"
    When sending "PUT" to "/cloud/users/subadmin/enabled"
    And As an "admin"
    And user "subadmin" is disabled

  Scenario: Making a ocs request with an enabled user
    Given As an "admin"
    And user "user0" exists
    And As an "user0"
    When sending "GET" to "/cloud/capabilities"
    Then the HTTP status code should be "200"
    And the OCS status code should be "100"

  Scenario: Making a web request with an enabled user
    Given As an "admin"
    And user "user0" exists
    And As an "user0"
    When sending "GET" with exact url to "/index.php/apps/files"
    Then the HTTP status code should be "200"

  Scenario: Making a ocs request with a disabled user
    Given As an "admin"
    And user "user0" exists
    And assure user "user0" is disabled
    And As an "user0"
    When sending "GET" to "/cloud/capabilities"
    Then the OCS status code should be "997"
    And the HTTP status code should be "401"

  Scenario: Making a web request with a disabled user
    Given As an "admin"
    And user "user0" exists
    And assure user "user0" is disabled
    And As an "user0"
    When sending "GET" with exact url to "/index.php/apps/files"
    And the HTTP status code should be "401"
