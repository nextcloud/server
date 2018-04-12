Feature: LDAP
  Background:
    Given using api version "2"

  Scenario: Test valid configuration by logging in
    Given having a valid LDAP configuration
    When Logging in using web as "alice"
    And Sending a "GET" to "/remote.php/webdav/welcome.txt" with requesttoken
    Then the HTTP status code should be "200"

  Scenario: Look for a known LDAP user
    Given having a valid LDAP configuration
    And As an "admin"
    And sending "GET" to "/cloud/users?search=alice"
    Then the OCS status code should be "200"
    And looking up details for the first result matches expectations
      | email | alice@nextcloud.ci |
      | displayname | Alice |

  Scenario: Look for a expected LDAP users
    Given having a valid LDAP configuration
    And modify LDAP configuration
      | ldapExpertUsernameAttr | uid |
    And As an "admin"
    And sending "GET" to "/cloud/users"
    Then the OCS status code should be "200"
    And the "users" result should match
      | alice | 1 |
      | ghost | 0 |

  Scenario: Test group filter with one specific group
    Given having a valid LDAP configuration
    And modify LDAP configuration
      | ldapGroupFilter | cn=RedGroup |
      | ldapBaseGroups  | ou=Groups,ou=Ordinary,dc=nextcloud,dc=ci  |
    And As an "admin"
    And sending "GET" to "/cloud/groups"
    Then the OCS status code should be "200"
    And the "groups" result should match
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
    And the "groups" result should match
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
    And the "groups" result should match
      | RedGroup     | 1 |
      | GreenGroup   | 1 |
      | BlueGroup    | 1 |
      | PurpleGroup  | 1 |
      | SquareGroup  | 0 |

    Scenario: Test backup server
      Given having a valid LDAP configuration
      And modify LDAP configuration
        | ldapBackupHost | openldap |
        | ldapBackupPort | 389      |
        | ldapHost       | foo.bar  |
        | ldapPort       | 2456     |
      And Logging in using web as "alice"
      Then the HTTP status code should be "200"

    Scenario: Test backup server offline
      Given having a valid LDAP configuration
      And modify LDAP configuration
        | ldapBackupHost | off.line |
        | ldapBackupPort | 3892     |
        | ldapHost       | foo.bar  |
        | ldapPort       | 2456     |
      Then Expect ServerException on failed web login as "alice"

    Scenario: Test LDAP server offline, no backup server
      Given having a valid LDAP configuration
      And modify LDAP configuration
        | ldapHost       | foo.bar  |
        | ldapPort       | 2456     |
      Then Expect ServerException on failed web login as "alice"

