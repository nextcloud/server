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
      | elisa | 1 |
      | ghost | 0 |

  Scenario: check default home of an LDAP user
    Given As an "admin"
    And sending "GET" to "/cloud/users/alice"
    Then the OCS status code should be "200"
    And the record's fields should match
      | storageLocation | /dev/shm/nc_int/alice |

  Scenario: check custom relative home of an LDAP user
    Given modify LDAP configuration
      | homeFolderNamingRule | sn |
    And As an "admin"
    And sending "GET" to "/cloud/users/alice"
    Then the OCS status code should be "200"
    And the record's fields should match
      | storageLocation | /dev/shm/nc_int/Alfgeirdottir |

  Scenario: check custom absolute home of an LDAP user
    Given modify LDAP configuration
      | homeFolderNamingRule | roomNumber |
    And As an "admin"
    And sending "GET" to "/cloud/users/elisa"
    Then the OCS status code should be "200"
    And the record's fields should match
      | storageLocation | /dev/shm/elisa-data |

  Scenario: Fetch all users, invoking pagination
    Given modify LDAP configuration
      | ldapBaseUsers  | ou=PagingTest,dc=nextcloud,dc=ci |
      | ldapPagingSize | 2                                |
    And As an "admin"
    And sending "GET" to "/cloud/users"
    Then the OCS status code should be "200"
    And the "users" result should match
      | ebba    | 1 |
      | eindis  | 1 |
      | fjolnir | 1 |
      | gunna   | 1 |
      | juliana | 1 |
      | leo     | 1 |
      | stigur  | 1 |

  Scenario: Fetch all users, invoking pagination
    Given modify LDAP configuration
      | ldapBaseUsers  | ou=PagingTest,dc=nextcloud,dc=ci |
      | ldapPagingSize | 2                                |
    And As an "admin"
    And sending "GET" to "/cloud/users?limit=10"
    Then the OCS status code should be "200"
    And the "users" result should match
      | ebba    | 1 |
      | eindis  | 1 |
      | fjolnir | 1 |
      | gunna   | 1 |
      | juliana | 1 |
      | leo     | 1 |
      | stigur  | 1 |

  Scenario: Fetch from second batch of all users, invoking pagination
    Given modify LDAP configuration
      | ldapBaseUsers  | ou=PagingTest,dc=nextcloud,dc=ci |
      | ldapPagingSize | 2                                |
    And As an "admin"
    And sending "GET" to "/cloud/users?limit=10&offset=2"
    Then the OCS status code should be "200"
    And the "users" result should contain "5" of
      | ebba    |
      | eindis  |
      | fjolnir |
      | gunna   |
      | juliana |
      | leo     |
      | stigur  |

  Scenario: Fetch from second batch of all users, invoking pagination with two bases
    Given modify LDAP configuration
      | ldapBaseUsers  | ou=PagingTest,dc=nextcloud,dc=ci;ou=PagingTestSecondBase,dc=nextcloud,dc=ci |
      | ldapPagingSize | 2                                |
    And As an "admin"
    And sending "GET" to "/cloud/users?limit=10&offset=2"
    Then the OCS status code should be "200"
    And the "users" result should contain "5" of
      | ebba    |
      | eindis  |
      | fjolnir |
      | gunna   |
      | juliana |
      | leo     |
      | stigur  |
    And the "users" result should contain "3" of
      | allisha   |
      | dogukan   |
      | lloyd     |
      | priscilla |
      | shannah   |

  Scenario: Fetch from second batch of all users, invoking pagination with two bases, third page
    Given modify LDAP configuration
      | ldapBaseUsers  | ou=PagingTest,dc=nextcloud,dc=ci;ou=PagingTestSecondBase,dc=nextcloud,dc=ci |
      | ldapPagingSize | 2                                |
    And As an "admin"
    And sending "GET" to "/cloud/users?limit=10&offset=4"
    Then the OCS status code should be "200"
    And the "users" result should contain "3" of
      | ebba    |
      | eindis  |
      | fjolnir |
      | gunna   |
      | juliana |
      | leo     |
      | stigur  |
    And the "users" result should contain "1" of
      | allisha   |
      | dogukan   |
      | lloyd     |
      | priscilla |
      | shannah   |

  Scenario: Deleting an unavailable LDAP user
    Given As an "admin"
    And sending "GET" to "/cloud/users"
    And modify LDAP configuration
      | ldapUserFilter | (&(objectclass=inetorgperson)(!(uid=alice))) |
    And invoking occ with "ldap:check-user alice"
    And the command output contains the text "Clean up the user's remnants by"
    And invoking occ with "user:delete alice"
    Then the command output contains the text "The specified user was deleted"

  Scenario: Search only with group members - allowed
    Given modify LDAP configuration
      | ldapGroupFilter               | cn=Orcharding |
      | ldapGroupMemberAssocAttr      | member |
      | ldapBaseGroups                | ou=OtherGroups,dc=nextcloud,dc=ci  |
      | ldapAttributesForUserSearch   | employeeNumber                  |
      | useMemberOfToDetectMembership | 1 |
    And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
    And As an "alice"
    When getting sharees for
      # "5" is part of the employee number of some LDAP records
      | search | 5 |
      | itemType | file |
    Then the OCS status code should be "200"
    And the HTTP status code should be "200"
    And "exact users" sharees returned is empty
    And "users" sharees returned are
      | Elisa | 0 | elisa |
    And "exact groups" sharees returned is empty

