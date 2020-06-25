Feature: remote

  Scenario: Get status of remote server
    Given using remote server "REMOTE"
    Then the remote version should be "__current_version__"
    And the remote protocol should be "http"

  Scenario: Get status of a non existing server
    Given using remote server "NON_EXISTING"
    Then the request should throw a "OC\Remote\Api\NotFoundException"

  Scenario: Get user info for a remote user
    Given using remote server "REMOTE"
    And user "user0" exists
    And using credentials "user0", "123456"
    When getting the remote user info for "user0"
    Then the remote user should have userid "user0"

  Scenario: Get user info for a non existing remote user
    Given using remote server "REMOTE"
    And user "user0" exists
    And using credentials "user0", "123456"
    When getting the remote user info for "user_non_existing"
    Then the request should throw a "OC\Remote\Api\NotFoundException"

  Scenario: Get user info with invalid credentials
    Given using remote server "REMOTE"
    And user "user0" exists
    And using credentials "user0", "invalid"
    When getting the remote user info for "user0"
    Then the request should throw a "OC\ForbiddenException"

  Scenario: Get capability of remote server
    Given using remote server "REMOTE"
    And user "user0" exists
    And using credentials "user0", "invalid"
    Then the capability "theming.name" is "Nextcloud"
