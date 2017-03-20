Feature: provisioning
  Background:
    Given using api version "2"

  Scenario: Getting an not existing user
    Given As an "admin"
    When sending "GET" to "/cloud/users/test"
    Then the HTTP status code should be "404"

  Scenario: get app info from app that does not exist
    Given As an "admin"
    When sending "GET" to "/cloud/apps/this_app_should_never_exist"
    Then the OCS status code should be "998"
    And the HTTP status code should be "404"

  Scenario: enable an app that does not exist
    Given As an "admin"
    When sending "POST" to "/cloud/apps/this_app_should_never_exist"
    Then the OCS status code should be "998"
    And the HTTP status code should be "404"

