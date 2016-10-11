Feature: ocs
  Background:
    Given using api version "1"

  Scenario: Default output is xml
    Given user "user0" exists
    And As an "user0"
    When sending "GET" to "/cloud/config"
    And the HTTP status code should be "200"
    And the Content-Type should be "text/xml; charset=UTF-8"

  Scenario: Get XML when requesting XML
    Given user "user0" exists
    And As an "user0"
    When sending "GET" to "/cloud/config?format=xml"
    And the HTTP status code should be "200"
    And the Content-Type should be "text/xml; charset=UTF-8"

  Scenario: Get JSON when requesting JSON
    Given user "user0" exists
    And As an "user0"
    When sending "GET" to "/cloud/config?format=json"
    And the HTTP status code should be "200"
    And the Content-Type should be "application/json; charset=utf-8"
