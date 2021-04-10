Feature: LDAP
  Background:
    Given using api version "2"
    And having a valid LDAP configuration

  Scenario: Test valid configuration by logging in
    Given Logging in using web as "alice"
    And Sending a "GET" to "/remote.php/webdav/welcome.txt" with requesttoken
    Then the HTTP status code should be "200"

  Scenario: Test valid configuration with port in the hostname by logging in
    Given modify LDAP configuration
      | ldapHost | openldap:389 |
    And cookies are reset
    And Logging in using web as "alice"
    And Sending a "GET" to "/remote.php/webdav/welcome.txt" with requesttoken
    Then the HTTP status code should be "200"

  Scenario: Test valid configuration with LDAP protocol by logging in
    Given modify LDAP configuration
      | ldapHost | ldap://openldap |
    And cookies are reset
    And Logging in using web as "alice"
    And Sending a "GET" to "/remote.php/webdav/welcome.txt" with requesttoken
    Then the HTTP status code should be "200"

  Scenario: Test valid configuration with LDAP protocol and port by logging in
    Given modify LDAP configuration
      | ldapHost | ldap://openldap:389 |
    And cookies are reset
    And Logging in using web as "alice"
    And Sending a "GET" to "/remote.php/webdav/welcome.txt" with requesttoken
    Then the HTTP status code should be "200"

  Scenario: Look for a known LDAP user
    Given As an "admin"
    And sending "GET" to "/cloud/users?search=alice"
    Then the OCS status code should be "200"
    And looking up details for the first result matches expectations
      | email           | alice@nextcloud.ci |
      | displayname     | Alice              |

  Scenario: Test group filter with one specific group
    Given modify LDAP configuration
      | ldapGroupFilter          | cn=RedGroup |
      | ldapGroupMemberAssocAttr | member |
      | ldapBaseGroups           | ou=Groups,ou=Ordinary,dc=nextcloud,dc=ci  |
    And As an "admin"
    And sending "GET" to "/cloud/groups"
    Then the OCS status code should be "200"
    And the "groups" result should match
      | RedGroup     | 1 |
      | GreenGroup   | 0 |
      | BlueGroup    | 0 |
      | PurpleGroup  | 0 |

  Scenario: Test group filter with two specific groups
    Given modify LDAP configuration
      | ldapGroupFilter          | (\|(cn=RedGroup)(cn=GreenGroup)) |
      | ldapGroupMemberAssocAttr | member |
      | ldapBaseGroups           | ou=Groups,ou=Ordinary,dc=nextcloud,dc=ci |
    And As an "admin"
    And sending "GET" to "/cloud/groups"
    Then the OCS status code should be "200"
    And the "groups" result should match
      | RedGroup     | 1 |
      | GreenGroup   | 1 |
      | BlueGroup    | 0 |
      | PurpleGroup  | 0 |

  Scenario: Test group filter ruling out a group from a different base
    Given modify LDAP configuration
      | ldapGroupFilter          | (objectClass=groupOfNames) |
      | ldapGroupMemberAssocAttr | member |
      | ldapBaseGroups           | ou=Groups,ou=Ordinary,dc=nextcloud,dc=ci |
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
    Given modify LDAP configuration
      | ldapBackupHost | openldap |
      | ldapBackupPort | 389      |
      | ldapHost       | foo.bar  |
      | ldapPort       | 2456     |
    And Logging in using web as "alice"
    Then the HTTP status code should be "200"

  Scenario: Test backup server offline
    Given modify LDAP configuration
      | ldapBackupHost | off.line |
      | ldapBackupPort | 3892     |
      | ldapHost       | foo.bar  |
      | ldapPort       | 2456     |
    Then Expect ServerException on failed web login as "alice"

  Scenario: Test LDAP server offline, no backup server
    Given modify LDAP configuration
      | ldapHost       | foo.bar  |
      | ldapPort       | 2456     |
    Then Expect ServerException on failed web login as "alice"

  Scenario: Test LDAP group membership with intermediate groups not matching filter
    Given modify LDAP configuration
      | ldapBaseGroups                | ou=OtherGroups,dc=nextcloud,dc=ci |
      | ldapGroupFilter               | (&(cn=Gardeners)(objectclass=groupOfNames)) |
      | ldapNestedGroups              | 1 |
      | useMemberOfToDetectMembership | 1 |
      | ldapUserFilter                | (&(objectclass=inetorgperson)(!(uid=alice))) |
      | ldapExpertUsernameAttr        | uid |
      | ldapGroupMemberAssocAttr      | member |
    And As an "admin"
    # for population
    And sending "GET" to "/cloud/groups"
    And sending "GET" to "/cloud/groups/Gardeners/users"
    Then the OCS status code should be "200"
    And the "users" result should match
      | alice  | 0 |
      | clara  | 1 |
      | elisa  | 1 |
      | gustaf | 1 |
      | jesper | 1 |

  Scenario: Test LDAP group membership with intermediate groups not matching filter and without memberof
    Given modify LDAP configuration
      | ldapBaseGroups                | ou=OtherGroups,dc=nextcloud,dc=ci |
      | ldapGroupFilter               | (&(cn=Gardeners)(objectclass=groupOfNames)) |
      | ldapNestedGroups              | 1 |
      | useMemberOfToDetectMembership | 0 |
      | ldapUserFilter                | (&(objectclass=inetorgperson)(!(uid=alice))) |
      | ldapExpertUsernameAttr        | uid |
      | ldapGroupMemberAssocAttr      | member |
    And As an "admin"
    # for population
    And sending "GET" to "/cloud/groups"
    And sending "GET" to "/cloud/groups/Gardeners/users"
    Then the OCS status code should be "200"
    And the "users" result should match
      | alice  | 0 |
      | clara  | 1 |
      | elisa  | 1 |
      | gustaf | 1 |
      | jesper | 1 |

  Scenario: Test LDAP group membership with intermediate groups not matching filter, numeric group ids
    Given modify LDAP configuration
      | ldapBaseGroups                | ou=NumericGroups,dc=nextcloud,dc=ci |
      | ldapGroupFilter               | (&(cn=2000)(objectclass=groupOfNames)) |
      | ldapNestedGroups              | 1 |
      | useMemberOfToDetectMembership | 1 |
      | ldapUserFilter                | (&(objectclass=inetorgperson)(!(uid=alice))) |
      | ldapExpertUsernameAttr        | uid |
      | ldapGroupMemberAssocAttr      | member |
    And As an "admin"
    # for population
    And sending "GET" to "/cloud/groups"
    And sending "GET" to "/cloud/groups/2000/users"
    Then the OCS status code should be "200"
    And the "users" result should match
      | alice  | 0 |
      | clara  | 1 |
      | elisa  | 1 |
      | gustaf | 1 |
      | jesper | 1 |

