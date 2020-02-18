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

Scenario: Test LDAP group retrieval with numeric group ids and nesting
  # Nesting does not play a role here really
  Given modify LDAP configuration
    | ldapBaseGroups                | ou=NumericGroups,dc=nextcloud,dc=ci |
    | ldapGroupFilter               | (objectclass=groupOfNames) |
    | ldapGroupMemberAssocAttr      | member |
    | ldapNestedGroups              | 1 |
    | useMemberOfToDetectMembership | 1 |
  And As an "admin"
  And sending "GET" to "/cloud/groups"
  Then the OCS status code should be "200"
  And the "groups" result should match
    | 2000 | 1 |
    | 3000 | 1 |
    | 3001 | 1 |
    | 3002 | 1 |

Scenario: Test LDAP group membership with intermediate groups not matching filter, numeric group ids
  Given modify LDAP configuration
    | ldapBaseGroups                | ou=NumericGroups,dc=nextcloud,dc=ci |
    | ldapGroupFilter               | (&(cn=2000)(objectclass=groupOfNames)) |
    | ldapNestedGroups              | 1 |
    | useMemberOfToDetectMembership | 1 |
    | ldapUserFilter                | (&(objectclass=inetorgperson)(!(uid=alice))) |
    | ldapGroupMemberAssocAttr      | member |
  And As an "admin"
  # for population
  And sending "GET" to "/cloud/groups"
  And sending "GET" to "/cloud/groups/2000/users"
  Then the OCS status code should be "200"
  And the "users" result should match
    | 92379 | 0 |
    | 54172 | 1 |
    | 50194 | 1 |
    | 59376 | 1 |
    | 59463 | 1 |
