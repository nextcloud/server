Feature: LDAP
  Background:
    Given using api version "2"

  Scenario: Test valid configuration by logging in
    Given having a valid LDAP configuration
    When Logging in using web as "alice"
    Then the HTTP status code should be "200"

  Scenario: Look for a known LDAP user
    Given having a valid LDAP configuration
    And As an "admin"
    And sending "GET" to "/cloud/users?search=alice"
    Then the OCS status code should be "200"
    And looking up details for the first result matches expectations
      | email | alice@nextcloud.ci |
      | displayname | Alice |

  Scenario: Test group filter with one specific group
    Given having a valid LDAP configuration
    And modify LDAP configuration
      | ldapGroupFilter | cn=RedGroup |
      | ldapBaseGroups  | ou=Groups,ou=Ordinary,dc=nextcloud,dc=ci  |
    And As an "admin"
    And sending "GET" to "/cloud/groups"
    Then the OCS status code should be "200"
    And the group result should
      | RedGroup     | 1 |
      | GreenGroup   | 0 |
      | BlueGroup    | 0 |
      | PurpleGroup  | 0 |

  Scenario: Test group filter with two specific groups
    Given having a valid LDAP configuration
    And modify LDAP configuration
      | ldapGroupFilter | (\|(cn=RedGroup)(cn=GreenGroup)) |
      | ldapBaseGroups  | ou=Groups,ou=Ordinary,dc=nextcloud,dc=ci  |
    And As an "admin"
    And sending "GET" to "/cloud/groups"
    Then the OCS status code should be "200"
    And the group result should
      | RedGroup     | 1 |
      | GreenGroup   | 1 |
      | BlueGroup    | 0 |
      | PurpleGroup  | 0 |

  Scenario: Test group filter ruling out a group from a different base
    Given having a valid LDAP configuration
    And modify LDAP configuration
      | ldapGroupFilter | (objectClass=groupOfNames) |
      | ldapBaseGroups  | ou=Groups,ou=Ordinary,dc=nextcloud,dc=ci  |
    And As an "admin"
    And sending "GET" to "/cloud/groups"
    Then the OCS status code should be "200"
    And the group result should
      | RedGroup     | 1 |
      | GreenGroup   | 1 |
      | BlueGroup    | 1 |
      | PurpleGroup  | 1 |
      | SquareGroup  | 0 |
