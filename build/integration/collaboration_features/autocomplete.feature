Feature: autocomplete
  Background:
    Given using api version "2"
    And group "commongroup" exists
    And user "admin" belongs to group "commongroup"
    And user "auto" exists
    And user "autocomplete" exists
    And user "autocomplete2" exists
    And user "autocomplete2" belongs to group "commongroup"

  Scenario: getting autocomplete
    Given As an "admin"
    When get autocomplete for "auto"
      | id | source |
      | auto | users |
      | autocomplete | users |
      | autocomplete2 | users |
    And user "autocomplete" has status "dnd"
    When parameter "shareapi_restrict_user_enumeration_full_match" of app "core" is set to "no"
    Then get autocomplete for "auto"
      | id            | source | status |
      | auto          | users  | ""     |
      | autocomplete  | users  | {"status":"dnd","message":null,"icon":null,"clearAt":null} |
      | autocomplete2 | users  | ""     |


  Scenario: getting autocomplete without enumeration
    Given As an "admin"
    When parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |
    Then get autocomplete for "autocomplete"
      | id | source |
      | autocomplete | users |
    When parameter "shareapi_restrict_user_enumeration_full_match" of app "core" is set to "no"
    Then get autocomplete for "auto"
      | id | source |
    Then get autocomplete for "autocomplete"
      | id | source |

  Scenario: getting autocomplete emails from address book with enumeration
    Given As an "admin"
    And sending "PUT" to "/cloud/users/autocomplete" with
      | key | email |
      | value | autocomplete@example.com |
    And there is a contact in an addressbook
    Then get email autocomplete for "example"
      | id | source |
      | autocomplete | users |
      | user@example.com | emails |
    Then get email autocomplete for "auto"
      | id | source |
      | autocomplete | users |
    Then get email autocomplete for "example"
      | id | source |
      | autocomplete | users |
      | user@example.com | emails |
    Then get email autocomplete for "autocomplete@example.com"
      | id | source |
      | autocomplete | users |

  Scenario: getting autocomplete emails from address book without enumeration
    Given As an "admin"
    And sending "PUT" to "/cloud/users/autocomplete" with
      | key | email |
      | value | autocomplete@example.com |
    And there is a contact in an addressbook
    And parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
    When parameter "shareapi_restrict_user_enumeration_full_match" of app "core" is set to "no"
    Then get email autocomplete for "example"
      | id | source |
      | user@example.com | emails |
    When parameter "shareapi_restrict_user_enumeration_full_match" of app "core" is set to "yes"
    Then get email autocomplete for "auto"
      | id | source |
    Then get email autocomplete for "example"
      | id | source |
      | user@example.com | emails |
    Then get email autocomplete for "autocomplete@example.com"
      | id | source |
      | autocomplete | users |

  Scenario: getting autocomplete with limited enumeration by group
    Given As an "admin"
    When parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |
      | autocomplete2 | users |
    Then get autocomplete for "autocomplete"
      | id | source |
      | autocomplete | users |
      | autocomplete2 | users |
    Then get autocomplete for "autocomplete2"
      | id | source |
      | autocomplete2 | users |
    When parameter "shareapi_restrict_user_enumeration_full_match" of app "core" is set to "no"
    Then get autocomplete for "autocomplete"
      | id | source |
      | autocomplete2 | users |
    Then get autocomplete for "autocomplete2"
      | id | source |
      | autocomplete2 | users |


  Scenario: getting autocomplete with limited enumeration by phone
    Given As an "admin"
    When parameter "shareapi_restrict_user_enumeration_to_phone" of app "core" is set to "yes"
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |

    # autocomplete stores their phone number
    Given As an "autocomplete"
    And sending "PUT" to "/cloud/users/autocomplete" with
      | key | phone |
      | value | +49 711 / 25 24 28-90 |
    And the HTTP status code should be "200"
    And the OCS status code should be "200"

    Given As an "admin"
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |

    # admin populates they have the phone number
    When search users by phone for region "DE" with
      | random-string1 | 0711 / 252 428-90 |
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |
      | autocomplete | users |

    When parameter "shareapi_restrict_user_enumeration_full_match" of app "core" is set to "no"
    Then get autocomplete for "auto"
      | id | source |
      | autocomplete | users |


  Scenario: getting autocomplete with limited enumeration by group or phone
    Given As an "admin"
    When parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
    And parameter "shareapi_restrict_user_enumeration_to_phone" of app "core" is set to "yes"

    # autocomplete stores their phone number
    Given As an "autocomplete"
    And sending "PUT" to "/cloud/users/autocomplete" with
      | key | phone |
      | value | +49 711 / 25 24 28-90 |
    And the HTTP status code should be "200"
    And the OCS status code should be "200"
    # admin populates they have the phone number
    Given As an "admin"
    When search users by phone for region "DE" with
      | random-string1 | 0711 / 252 428-90 |

    Then get autocomplete for "auto"
      | id | source |
      | auto | users |
      | autocomplete | users |
      | autocomplete2 | users |

    When parameter "shareapi_restrict_user_enumeration_full_match" of app "core" is set to "no"
    Then get autocomplete for "auto"
      | id | source |
      | autocomplete | users |
      | autocomplete2 | users |


  Scenario: getting autocomplete with limited enumeration but sharing is group restricted
    Given As an "admin"
    When parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
    And parameter "shareapi_restrict_user_enumeration_to_phone" of app "core" is set to "yes"

    # autocomplete stores their phone number
    Given As an "autocomplete"
    And sending "PUT" to "/cloud/users/autocomplete" with
      | key | phone |
      | value | +49 711 / 25 24 28-90 |
    And the HTTP status code should be "200"
    And the OCS status code should be "200"
    # admin populates they have the phone number
    Given As an "admin"
    When search users by phone for region "DE" with
      | random-string1 | 0711 / 252 428-90 |

    Then get autocomplete for "auto"
      | id | source |
      | auto | users |
      | autocomplete | users |
      | autocomplete2 | users |
    When parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"
    Then get autocomplete for "auto"
      | id | source |
      | autocomplete2 | users |


  Scenario: getting autocomplete with limited enumeration by phone but user changes it
    Given As an "admin"
    When parameter "shareapi_restrict_user_enumeration_to_phone" of app "core" is set to "yes"
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |

    # autocomplete stores their phone number
    Given As an "autocomplete"
    And sending "PUT" to "/cloud/users/autocomplete" with
      | key | phone |
      | value | +49 711 / 25 24 28-90 |
    And the HTTP status code should be "200"
    And the OCS status code should be "200"

    Given As an "admin"
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |

    # admin populates they have the phone number
    When search users by phone for region "DE" with
      | random-string1 | 0711 / 252 428-90 |
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |
      | autocomplete | users |

    # autocomplete changes their phone number
    Given As an "autocomplete"
    And sending "PUT" to "/cloud/users/autocomplete" with
      | key | phone |
      | value | +49 711 / 25 24 28-91 |
    And the HTTP status code should be "200"
    And the OCS status code should be "200"

    Given As an "admin"
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |

    # admin populates they have the new phone number
    When search users by phone for region "DE" with
      | random-string1 | 0711 / 252 428-91 |
    Then get autocomplete for "auto"
      | id | source |
      | auto | users |
      | autocomplete | users |


  Scenario: getting autocomplete without enumeration and sharing is group restricted
    Given As an "admin"
    When parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
    And parameter "shareapi_only_share_with_group_members" of app "core" is set to "yes"

    Then get autocomplete for "auto"
      | id | source |
    Then get autocomplete for "autocomplete"
      | id | source |
    Then get autocomplete for "autocomplete2"
      | id | source |
      | autocomplete2 | users |
