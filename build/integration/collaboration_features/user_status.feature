Feature: user_status
  Background:
    Given using api version "2"
    And user "user0" exists
    And user "user0" has status "dnd"

  Scenario: listing recent user statuses with default settings
    Then user statuses for "admin" list "user0" with status "dnd"

  Scenario: empty recent user statuses with disabled/limited user enumeration
    When parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "no"
    Then user statuses for "admin" are empty
    When parameter "shareapi_allow_share_dialog_user_enumeration" of app "core" is set to "yes"
    When parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "yes"
    Then user statuses for "admin" are empty
    When parameter "shareapi_restrict_user_enumeration_to_group" of app "core" is set to "no"
    When parameter "shareapi_restrict_user_enumeration_to_phone" of app "core" is set to "yes"
    Then user statuses for "admin" are empty
    When parameter "shareapi_restrict_user_enumeration_to_phone" of app "core" is set to "no"
    Then user statuses for "admin" list "user0" with status "dnd"
