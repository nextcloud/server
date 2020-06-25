Feature: LDAP
  Background:
    Given using api version "2"

  Scenario: Creating an new, empty configuration
    Given As an "admin"
    When sending "POST" to "/apps/user_ldap/api/v1/config"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    And the response should contain a tag "configID"

  Scenario: Delete a non-existing configuration
    Given As an "admin"
    When sending "DELETE" to "/apps/user_ldap/api/v1/config/s666"
    Then the OCS status code should be "404"
    And the HTTP status code should be "404"

  Scenario: Create and delete a configuration
    Given As an "admin"
    And creating an LDAP configuration at "/apps/user_ldap/api/v1/config"
    When deleting the LDAP configuration
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"

  Scenario: Create and modify a configuration
    Given As an "admin"
    And creating an LDAP configuration at "/apps/user_ldap/api/v1/config"
    When setting the LDAP configuration to
      | configData[ldapHost] | ldaps://my.ldap.server |
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"

  Scenario: Modifying a non-existing configuration
    Given As an "admin"
    When sending "PUT" to "/apps/user_ldap/api/v1/config/s666" with
      | configData[ldapHost] | ldaps://my.ldap.server |
    Then the OCS status code should be "404"
    And the HTTP status code should be "404"

  Scenario: Modifying an existing configuration with malformed configData
    Given As an "admin"
    And creating an LDAP configuration at "/apps/user_ldap/api/v1/config"
    When setting the LDAP configuration to
      | configData | ldapHost=ldaps://my.ldap.server |
    Then the OCS status code should be "400"
    And the HTTP status code should be "400"

  Scenario: create, modify and get a configuration
    Given As an "admin"
    And creating an LDAP configuration at "/apps/user_ldap/api/v1/config"
    And setting the LDAP configuration to
      | configData[ldapHost] | ldaps://my.ldap.server |
      | configData[ldapLoginFilter] | (&(\|(objectclass=inetOrgPerson))(uid=%uid)) |
      | configData[ldapAgentPassword] | psst,secret |
    When getting the LDAP configuration with showPassword "0"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    And the response should contain a tag "ldapHost" with value "ldaps://my.ldap.server"
    And the response should contain a tag "ldapLoginFilter" with value "(&(|(objectclass=inetOrgPerson))(uid=%uid))"
    And the response should contain a tag "ldapAgentPassword" with value "***"

  Scenario: receiving password in plain text
    Given As an "admin"
    And creating an LDAP configuration at "/apps/user_ldap/api/v1/config"
    And setting the LDAP configuration to
      | configData[ldapAgentPassword] | psst,secret |
    When getting the LDAP configuration with showPassword "1"
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    And the response should contain a tag "ldapAgentPassword" with value "psst,secret"
