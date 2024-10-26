# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: sharing
  Background:
    Given using api version "1"
    Given using new dav path

# See sharing-v1-part3.feature

Scenario: Creating a new share of a file shows size and mtime
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    And parameter "shareapi_default_permissions" of app "core" is set to "7"
    When creating a share with
      | path | welcome.txt |
      | shareWith | user1 |
      | shareType | 0 |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Getting info of last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | item_size | A_NUMBER |
      | item_mtime | A_NUMBER |

Scenario: Creating a new share of a file you own shows the file permissions
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    And parameter "shareapi_default_permissions" of app "core" is set to "7"
    When creating a share with
      | path | welcome.txt |
      | shareWith | user1 |
      | shareType | 0 |
    And the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Getting info of last share
    Then the OCS status code should be "100"
    And the HTTP status code should be "200"
    And Share fields of last share match with
      | item_permissions | 27 |

Scenario: Receiving a share of a file gives no create permission
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    And parameter "shareapi_default_permissions" of app "core" is set to "31"
    And file "welcome.txt" of user "user0" is shared with user "user1"
    And sending "GET" to "/apps/files_sharing/api/v1/shares"
    And share 0 is returned with
      | path | /welcome.txt |
      | permissions | 19 |
      | item_permissions | 27 |
    When As an "user1"
    And user "user1" accepts last share
    And sending "GET" to "/apps/files_sharing/api/v1/shares?shared_with_me=true"
    Then the list of returned shares has 1 shares
    And share 0 is returned with
      | path | /welcome (2).txt |
      | permissions | 19 |
      | item_permissions | 27 |

Scenario: Receiving a share of a folder gives create permission
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    And parameter "shareapi_default_permissions" of app "core" is set to "31"
    And file "PARENT/CHILD" of user "user0" is shared with user "user1"
    And sending "GET" to "/apps/files_sharing/api/v1/shares"
    And share 0 is returned with
      | path | /PARENT/CHILD |
      | permissions | 31 |
      | item_permissions | 31 |
    When As an "user1"
    And user "user1" accepts last share
    And sending "GET" to "/apps/files_sharing/api/v1/shares?shared_with_me=true"
    Then the list of returned shares has 1 shares
    And share 0 is returned with
      | path | /CHILD |
      | permissions | 31 |
      | item_permissions | 31 |

# User can remove itself from a share
Scenario: Receiving a share of a file without delete permission gives delete permission anyway
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    And parameter "shareapi_default_permissions" of app "core" is set to "23"
    And file "welcome.txt" of user "user0" is shared with user "user1"
    And sending "GET" to "/apps/files_sharing/api/v1/shares"
    And share 0 is returned with
      | path | /welcome.txt |
      | permissions | 19 |
      | item_permissions | 27 |
    When As an "user1"
    And user "user1" accepts last share
    And sending "GET" to "/apps/files_sharing/api/v1/shares?shared_with_me=true"
    Then the list of returned shares has 1 shares
    And share 0 is returned with
      | path | /welcome (2).txt |
      | permissions | 19 |
      | item_permissions | 27 |

Scenario: Receiving a share of a file without delete permission gives delete permission anyway
    Given user "user0" exists
    And user "user1" exists
    And As an "user0"
    And group "group1" exists
    And user "user1" belongs to group "group1"
    And parameter "shareapi_default_permissions" of app "core" is set to "23"
    And file "welcome.txt" of user "user0" is shared with group "group1"
    And sending "GET" to "/apps/files_sharing/api/v1/shares"
    And share 0 is returned with
      | path | /welcome.txt |
      | permissions | 19 |
      | item_permissions | 27 |
    When As an "user1"
    And user "user1" accepts last share
    And sending "GET" to "/apps/files_sharing/api/v1/shares?shared_with_me=true"
    Then the list of returned shares has 1 shares
    And share 0 is returned with
      | path | /welcome (2).txt |
      | permissions | 19 |
      | item_permissions | 27 |
