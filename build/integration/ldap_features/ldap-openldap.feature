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
