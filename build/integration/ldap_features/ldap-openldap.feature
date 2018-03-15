Feature: LDAP
  Background:
    Given using api version "2"

  Scenario: Test valid configuration by logging in
    Given having a valid LDAP configuration
    When Logging in using web as "alice"
    #When As an "alice"
    And sending "GET" to "/cloud/user"
    Then the HTTP status code should be "200"
