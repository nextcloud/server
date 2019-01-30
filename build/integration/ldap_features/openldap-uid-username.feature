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
