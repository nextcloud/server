# SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
Feature: sharees
  Background:
    Given using api version "1"
    And user "test" exists
    And user "Sharee1" exists
    And group "ShareeGroup" exists
    And user "test" belongs to group "ShareeGroup"
    And user "Sharee2" exists
    And As an "admin"
    And sending "PUT" to "/cloud/users/Sharee2" with
      | key   | email |
      | value | sharee2@system.com |
    And sending "PUT" to "/cloud/users/Sharee2" with
      | key   | additional_mail |
      | value | sharee2@secondary.com |

  Scenario: Search without exact match
    Given As an "test"
    When getting sharees for
      | search | Sharee |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned are
      | Sharee1 | 0 | Sharee1 | Sharee1 |
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
    And "exact groups" sharees returned is empty
    And "groups" sharees returned are
      | ShareeGroup | 1 | ShareeGroup |
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search without exact match not-exact casing
    Given As an "test"
    When getting sharees for
      | search | sharee |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned are
      | Sharee1 | 0 | Sharee1 | Sharee1 |
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
    And "exact groups" sharees returned is empty
    And "groups" sharees returned are
      | ShareeGroup | 1 | ShareeGroup |
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search only with group members - denied
    Given As an "test"
    And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
    When getting sharees for
      | search | sharee |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned are
      | ShareeGroup | 1 | ShareeGroup |
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search only with group members - allowed
    Given As an "test"
    And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
    And user "Sharee1" belongs to group "ShareeGroup"
    When getting sharees for
      | search | sharee |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned are
      | Sharee1 | 0 | Sharee1 | Sharee1 |
    And "exact groups" sharees returned is empty
    And "groups" sharees returned are
      | ShareeGroup | 1 | ShareeGroup |
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search only with group members - allowed with exact match
    Given As an "test"
    And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
    And user "Sharee1" belongs to group "ShareeGroup"
    When getting sharees for
      | search | Sharee1 |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned are
      | Sharee1 | 0 | Sharee1 | Sharee1 |
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search only with group members - no group as non-member
    Given As an "Sharee1"
    And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
    When getting sharees for
      | search | sharee |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search without exact match no iteration allowed
    Given As an "test"
    And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
    When getting sharees for
      | search | Sharee |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search with exact match no iteration allowed
    Given As an "test"
    And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
    When getting sharees for
      | search | Sharee1 |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned are
      | Sharee1 | 0 | Sharee1 | Sharee1 |
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search with exact match group no iteration allowed
    Given As an "test"
    And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
    When getting sharees for
      | search | ShareeGroup |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned are
      | ShareeGroup | 1 | ShareeGroup |
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search with exact match
    Given As an "test"
    When getting sharees for
      | search | Sharee1 |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned are
      | Sharee1 | 0 | Sharee1 | Sharee1 |
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned is empty
    Then "remotes" sharees returned is empty

  Scenario: Search with exact match not-exact casing
    Given As an "test"
    When getting sharees for
      | search | sharee1 |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned are
      | Sharee1 | 0 | Sharee1 | Sharee1 |
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned is empty
    Then "remotes" sharees returned is empty

  Scenario: Search with exact match not-exact casing group
    Given As an "test"
    When getting sharees for
      | search | shareegroup |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned is empty
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned are
      | ShareeGroup | 1 | ShareeGroup |
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned is empty
    Then "remotes" sharees returned is empty

  Scenario: Search with "self"
    Given As an "Sharee1"
    When getting sharees for
      | search | Sharee1 |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned are
      | Sharee1 | 0 | Sharee1 | Sharee1 |
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned is empty
    Then "remotes" sharees returned is empty

  Scenario: Remote sharee for files
    Given As an "test"
    When getting sharees for
      | search | test@localhost |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned is empty
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned are
      | test (localhost) | 6 | test@localhost |
    Then "remotes" sharees returned is empty

  Scenario: Remote sharee for calendars
    Given As an "test"
    When getting sharees for
      | search | test@localhost |
      | itemType | calendar |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    Then "exact users" sharees returned is empty
    Then "users" sharees returned is empty
    Then "exact groups" sharees returned is empty
    Then "groups" sharees returned is empty
    Then "exact remotes" sharees returned are
      | test (localhost) | 6 | test@localhost |
    Then "remotes" sharees returned is empty

  Scenario: Group sharees not returned when group sharing is disabled
    Given As an "test"
    And parameter "shareapi_allow_group_sharing" of app "core" is set to "no"
    When getting sharees for
      | search | sharee |
      | itemType | file |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned are
      | Sharee1 | 0 | Sharee1 | Sharee1 |
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty

  Scenario: Search user by system e-mail address
    Given As an "test"
    When getting sharees for
      | search    | sharee2@system.com |
      | itemType  | file |
      | shareType | 0 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    # UserPlugin provides two identical results (except for the field order, but
    # that is hidden by the check).
    # MailPlugin does not add a result if there is already one for that user.
    And "exact users" sharees returned are
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search user by system e-mail address without exact match
    Given As an "test"
    When getting sharees for
      | search    | sharee2@system.c |
      | itemType  | file |
      | shareType | 0 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    # MailPlugin does not add a result if there is already one for that user.
    And "users" sharees returned are
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search user by secondary e-mail address
    Given As an "test"
    When getting sharees for
      | search    | sharee2@secondary.com |
      | itemType  | file |
      | shareType | 0 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    # UserPlugin only searches in the system e-mail address, but not in
    # secondary addresses.
    And "exact users" sharees returned are
      | Sharee2 (sharee2@secondary.com) | 0 | Sharee2 | sharee2@secondary.com |
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search user by secondary e-mail address without exact match
    Given As an "test"
    When getting sharees for
      | search    | sharee2@secondary.c |
      | itemType  | file |
      | shareType | 0 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    # UserPlugin only searches in the system e-mail address, but not in
    # secondary addresses.
    # MailPlugin adds a result for every e-mail address of the contact unless
    # there is an exact match.
    And "users" sharees returned are
      | Sharee2 (sharee2@system.com)    | 0 | Sharee2 | sharee2@system.com |
      | Sharee2 (sharee2@secondary.com) | 0 | Sharee2 | sharee2@secondary.com |
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search e-mail
    Given As an "test"
    When getting sharees for
      | search    | sharee2@unknown.com |
      | itemType  | file |
      | shareType | 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned are
      | sharee2@unknown.com | 4 | sharee2@unknown.com |
    And "emails" sharees returned is empty

  Scenario: Search e-mail when sharebymail app is disabled
    Given app "sharebymail" enabled state will be restored once the scenario finishes
    And sending "DELETE" to "/cloud/apps/sharebymail"
    And app "sharebymail" is disabled
    And As an "test"
    When getting sharees for
      | search    | sharee2@unknown.com |
      | itemType  | file |
      | shareType | 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search e-mail matching system e-mail address of user
    Given As an "test"
    When getting sharees for
      | search    | sharee2@system.com |
      | itemType  | file |
      | shareType | 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search e-mail partially matching system e-mail address of user
    Given As an "test"
    When getting sharees for
      | search    | sharee2@system.c |
      | itemType  | file |
      | shareType | 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned are
      | sharee2@system.c | 4 | sharee2@system.c |
    And "emails" sharees returned is empty

  Scenario: Search e-mail matching secondary e-mail address of user
    Given As an "test"
    When getting sharees for
      | search    | sharee2@secondary.com |
      | itemType  | file |
      | shareType | 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search e-mail partially matching secondary e-mail address of user
    Given As an "test"
    When getting sharees for
      | search    | sharee2@secondary.c |
      | itemType  | file |
      | shareType | 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned are
      | sharee2@secondary.c | 4 | sharee2@secondary.c |
    And "emails" sharees returned is empty

  Scenario: Search user and e-mail matching system e-mail address of user
    Given As an "test"
    When getting sharees for
      | search     | sharee2@system.com |
      | itemType   | file |
      | shareTypes | 0 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    # UserPlugin provides two identical results (except for the field order, but
    # that is hidden by the check)
    And "exact users" sharees returned are
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search user and e-mail matching system e-mail address of user when sharebymail app is disabled
    Given app "sharebymail" enabled state will be restored once the scenario finishes
    And sending "DELETE" to "/cloud/apps/sharebymail"
    And app "sharebymail" is disabled
    And As an "test"
    When getting sharees for
      | search     | sharee2@system.com |
      | itemType   | file |
      | shareTypes | 0 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    # UserPlugin provides two identical results (except for the field order, but
    # that is hidden by the check)
    And "exact users" sharees returned are
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
      | Sharee2 | 0 | Sharee2 | sharee2@system.com |
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search user and e-mail matching secondary e-mail address of user
    Given As an "test"
    When getting sharees for
      | search     | sharee2@secondary.com |
      | itemType   | file |
      | shareTypes | 0 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned are
      | Sharee2 (sharee2@secondary.com) | 0 | Sharee2 | sharee2@secondary.com |
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty

  Scenario: Search user and e-mail matching secondary e-mail address of user when sharebymail app is disabled
    Given app "sharebymail" enabled state will be restored once the scenario finishes
    And sending "DELETE" to "/cloud/apps/sharebymail"
    And app "sharebymail" is disabled
    And As an "test"
    When getting sharees for
      | search     | sharee2@secondary.com |
      | itemType   | file |
      | shareTypes | 0 4 |
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And "exact users" sharees returned are
      | Sharee2 (sharee2@secondary.com) | 0 | Sharee2 | sharee2@secondary.com |
    And "users" sharees returned is empty
    And "exact groups" sharees returned is empty
    And "groups" sharees returned is empty
    And "exact remotes" sharees returned is empty
    And "remotes" sharees returned is empty
    And "exact emails" sharees returned is empty
    And "emails" sharees returned is empty
