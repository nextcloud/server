Feature: maintenance-mode

  Background:
    Given Maintenance mode is enabled
    Then the command was successful

  Scenario: Accessing /index.php with maintenance mode enabled
    When requesting "/index.php" with "GET"
    Then the HTTP status code should be "503"
    Then Maintenance mode is disabled
    And the command was successful

  Scenario: Accessing /remote.php/webdav with maintenance mode enabled
    When requesting "/remote.php/webdav" with "GET"
    Then the HTTP status code should be "503"
    Then Maintenance mode is disabled
    And the command was successful

  Scenario: Accessing /remote.php/dav with maintenance mode enabled
    When requesting "/remote.php/dav" with "GET"
    Then the HTTP status code should be "503"
    Then Maintenance mode is disabled
    And the command was successful

  Scenario: Accessing /ocs/v1.php with maintenance mode enabled
    When requesting "/ocs/v1.php" with "GET"
    Then the HTTP status code should be "503"
    Then Maintenance mode is disabled
    And the command was successful

  Scenario: Accessing /ocs/v2.php with maintenance mode enabled
    When requesting "/ocs/v2.php" with "GET"
    Then the HTTP status code should be "503"
    Then Maintenance mode is disabled
    And the command was successful

  Scenario: Accessing /public.php/webdav with maintenance mode enabled
    When requesting "/public.php/webdav" with "GET"
    Then the HTTP status code should be "503"
    Then Maintenance mode is disabled
    And the command was successful
