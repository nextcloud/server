Feature: ratelimiting

  Background:
    Given user "user0" exists
    Given As an "admin"
    Given invoking occ with "app:enable --force testing"

  Scenario: Accessing a page with only an AnonRateThrottle as user
    Given user "user0" exists
    # First request should work
    When requesting "/index.php/apps/testing/anonProtected" with "GET" using basic auth
    Then the HTTP status code should be "200"
    # Second one should fail
    When requesting "/index.php/apps/testing/anonProtected" with "GET" using basic auth
    Then the HTTP status code should be "429"
    # After 11 seconds the next request should work
    And Sleep for "11" seconds
    When requesting "/index.php/apps/testing/anonProtected" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario: Accessing a page with only an AnonRateThrottle as guest
    Given Sleep for "11" seconds
    # First request should work
    When requesting "/index.php/apps/testing/anonProtected" with "GET"
    Then the HTTP status code should be "200"
    # Second one should fail
    When requesting "/index.php/apps/testing/anonProtected" with "GET" using basic auth
    Then the HTTP status code should be "429"
    # After 11 seconds the next request should work
    And Sleep for "11" seconds
    When requesting "/index.php/apps/testing/anonProtected" with "GET" using basic auth
    Then the HTTP status code should be "200"

  Scenario: Accessing a page with UserRateThrottle and AnonRateThrottle
    # First request should work as guest
    When requesting "/index.php/apps/testing/userAndAnonProtected" with "GET"
    Then the HTTP status code should be "200"
    # Second request should fail as guest
    When requesting "/index.php/apps/testing/userAndAnonProtected" with "GET"
    Then the HTTP status code should be "429"
    # First request should work as user
    When requesting "/index.php/apps/testing/userAndAnonProtected" with "GET" using basic auth
    Then the HTTP status code should be "200"
    # Second request should work as user
    When requesting "/index.php/apps/testing/userAndAnonProtected" with "GET" using basic auth
    Then the HTTP status code should be "200"
    # Third request should work as user
    When requesting "/index.php/apps/testing/userAndAnonProtected" with "GET" using basic auth
    Then the HTTP status code should be "200"
    # Fourth request should work as user
    When requesting "/index.php/apps/testing/userAndAnonProtected" with "GET" using basic auth
    Then the HTTP status code should be "200"
    # Fifth request should work as user
    When requesting "/index.php/apps/testing/userAndAnonProtected" with "GET" using basic auth
    Then the HTTP status code should be "200"
    # Sixth request should fail as user
    When requesting "/index.php/apps/testing/userAndAnonProtected" with "GET"
    Then the HTTP status code should be "429"
