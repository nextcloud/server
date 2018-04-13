Feature: LDAP
  Background:
    Given using api version "2"
    And having a valid LDAP configuration
    And modify LDAP configuration
      | ldapExpertUsernameAttr | uid |

  Scenario: Look for a expected LDAP users
    Given As an "admin"
    And sending "GET" to "/cloud/users"
    Then the OCS status code should be "200"
    And the "users" result should match
      | alice | 1 |
      | ghost | 0 |
