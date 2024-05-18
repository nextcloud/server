Feature: avatar

  Background:
    Given user "user0" exists

  Scenario: get default user avatar
    When user "user0" gets avatar for user "user0"
    Then The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 0 |
    And last avatar is a square of size 512
    And last avatar is not a single color

  Scenario: get default user avatar as an anonymous user
    When user "anonymous" gets avatar for user "user0"
    Then The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 0 |
    And last avatar is a square of size 512
    And last avatar is not a single color



  Scenario: get temporary non-square user avatar before cropping it
    Given Logging in using web as "user0"
    And logged in user posts temporary avatar from file "data/coloured-pattern-non-square.png"
    When logged in user gets temporary avatar
    Then The following headers should be set
      | Content-Type | image/png |
    # "last avatar" also includes the last temporary avatar
    And last avatar is not a square
    And last avatar is not a single color

  Scenario: get non-square user avatar before cropping it
    Given Logging in using web as "user0"
    And logged in user posts temporary avatar from file "data/coloured-pattern-non-square.png"
    # Avatar needs to be cropped to finish setting it
    When user "user0" gets avatar for user "user0"
    Then The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 0 |
    And last avatar is a square of size 512
    And last avatar is not a single color

  Scenario: set square user avatar from file
    Given Logging in using web as "user0"
    When logged in user posts temporary avatar from file "data/green-square-256.png"
    And user "user0" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    # Last avatar size is 512 by default when getting avatar without size parameter
    And last avatar is a square of size 512
    And last avatar is a single "#00FF00" color
    And user "anonymous" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#00FF00" color

  Scenario: set square user avatar from internal path
    Given user "user0" uploads file "data/green-square-256.png" to "/internal-green-square-256.png"
    And Logging in using web as "user0"
    When logged in user posts temporary avatar from internal path "internal-green-square-256.png"
    And user "user0" gets avatar for user "user0" with size "64"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 64
    And last avatar is a single "#00FF00" color
    And user "anonymous" gets avatar for user "user0" with size "64"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 64
    And last avatar is a single "#00FF00" color

  Scenario: set non-square user avatar from file
    Given Logging in using web as "user0"
    When logged in user posts temporary avatar from file "data/coloured-pattern-non-square.png"
    And logged in user crops temporary avatar
      | x | 384 |
      | y | 256 |
      | w | 128 |
      | h | 128 |
    Then logged in user gets temporary avatar with 404
    And user "user0" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#FF0000" color
    And user "anonymous" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#FF0000" color

  Scenario: set non-square user avatar from internal path
    Given user "user0" uploads file "data/coloured-pattern-non-square.png" to "/internal-coloured-pattern-non-square.png"
    And Logging in using web as "user0"
    When logged in user posts temporary avatar from internal path "internal-coloured-pattern-non-square.png"
    And logged in user crops temporary avatar
      | x | 704 |
      | y | 320 |
      | w | 64 |
      | h | 64 |
    Then logged in user gets temporary avatar with 404
    And user "user0" gets avatar for user "user0" with size "64"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 64
    And last avatar is a single "#00FF00" color
    And user "anonymous" gets avatar for user "user0" with size "64"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 64
    And last avatar is a single "#00FF00" color

  Scenario: cropped user avatar needs to be squared
    Given Logging in using web as "user0"
    And logged in user posts temporary avatar from file "data/coloured-pattern-non-square.png"
    When logged in user crops temporary avatar with 400
      | x | 384 |
      | y | 256 |
      | w | 192 |
      | h | 128 |



  Scenario: delete user avatar
    Given Logging in using web as "user0"
    And logged in user posts temporary avatar from file "data/coloured-pattern-non-square.png"
    And logged in user crops temporary avatar
      | x | 384 |
      | y | 256 |
      | w | 128 |
      | h | 128 |
    And user "user0" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#FF0000" color
    And user "anonymous" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#FF0000" color
    When logged in user deletes the user avatar
    Then user "user0" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 0 |
    And last avatar is a square of size 512
    And last avatar is not a single color
    And user "anonymous" gets avatar for user "user0"
    And The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 0 |
    And last avatar is a square of size 512
    And last avatar is not a single color



  Scenario: get user avatar with a larger size than the original one
    Given Logging in using web as "user0"
    And logged in user posts temporary avatar from file "data/coloured-pattern-non-square.png"
    And logged in user crops temporary avatar
      | x | 384 |
      | y | 256 |
      | w | 128 |
      | h | 128 |
    When user "user0" gets avatar for user "user0" with size "192"
    Then The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#FF0000" color

  Scenario: get user avatar with a smaller size than the original one
    Given Logging in using web as "user0"
    And logged in user posts temporary avatar from file "data/coloured-pattern-non-square.png"
    And logged in user crops temporary avatar
      | x | 384 |
      | y | 256 |
      | w | 128 |
      | h | 128 |
    When user "user0" gets avatar for user "user0" with size "96"
    Then The following headers should be set
      | Content-Type | image/png |
      | X-NC-IsCustomAvatar | 1 |
    And last avatar is a square of size 512
    And last avatar is a single "#FF0000" color



  Scenario: get default guest avatar
    When user "user0" gets avatar for guest "guest0"
    Then The following headers should be set
      | Content-Type | image/png |
    And last avatar is a square of size 512
    And last avatar is not a single color

  Scenario: get default guest avatar as an anonymous user
    When user "anonymous" gets avatar for guest "guest0"
    Then The following headers should be set
      | Content-Type | image/png |
    And last avatar is a square of size 512
    And last avatar is not a single color
