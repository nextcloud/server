Feature: LDAP
  Background:
    Given using api version "2"
    And having a valid LDAP configuration
    And modify LDAP configuration
      | ldapExpertUsernameAttr | employeeNumber |
      | ldapLoginFilter        | (&(objectclass=inetorgperson)(employeeNumber=%uid)) |

# Those tests are dedicated to ensure Nc is working when it is provided with
# users having numerical IDs

Scenario: Look for a expected LDAP users
  Given As an "admin"
  And sending "GET" to "/cloud/users"
  Then the OCS status code should be "200"
  And the "users" result should match
    | 92379 | 1 |
    | 50194 | 1 |

Scenario: check default home of an LDAP user
  Given As an "admin"
  And sending "GET" to "/cloud/users/92379"
  Then the OCS status code should be "200"
  And the record's fields should match
    | storageLocation | /dev/shm/nc_int/92379 |

Scenario: Test by logging in
  Given cookies are reset
  And Logging in using web as "92379"
  And Sending a "GET" to "/remote.php/webdav/welcome.txt" with requesttoken
  Then the HTTP status code should be "200"
