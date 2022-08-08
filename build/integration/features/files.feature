Feature: files

  # Counterpart scenarios for getting the folder size in webdav-related.feature

  Scenario: Retrieving storage stats after a file was uploaded
    Given using old dav path
    And As an "admin"
    And user "user0" exists
    And user "user0" adds a file of 108 bytes to "/test.txt"
    When Logging in using web as "user0"
    And logged in user gets storage stats of folder "/"
    Then the storage stats match with
      | used | 447 |

  Scenario: Retrieving storage stats after a file was uploaded to a folder
    Given using old dav path
    And As an "admin"
    And user "user0" exists
    And user "user0" adds a file of 108 bytes to "/FOLDER/test.txt"
    When Logging in using web as "user0"
    And logged in user gets storage stats of folder "/"
    Then the storage stats match with
      | used | 447 |

  Scenario: Retrieving storage stats for folder after a file was uploaded to that folder
    Given using old dav path
    And As an "admin"
    And user "user0" exists
    And user "user0" adds a file of 108 bytes to "/FOLDER/test.txt"
    When Logging in using web as "user0"
    And logged in user gets storage stats of folder "/FOLDER/"
    Then the storage stats match with
      | used | 108 |

  Scenario: Retrieving storage stats after a file was deleted from a folder
    Given using old dav path
    And As an "admin"
    And user "user0" exists
    And user "user0" adds a file of 23 bytes to "/FOLDER/test1.txt"
    And user "user0" adds a file of 42 bytes to "/FOLDER/test2.txt"
    And user "user0" adds a file of 108 bytes to "/FOLDER/test3.txt"
    And User "user0" deletes file "/FOLDER/test2.txt"
    When Logging in using web as "user0"
    And logged in user gets storage stats of folder "/"
    Then the storage stats match with
      | used | 470 |

  Scenario: Retrieving storage stats for folder after a file was deleted from that folder
    Given using old dav path
    And As an "admin"
    And user "user0" exists
    And user "user0" adds a file of 23 bytes to "/FOLDER/test1.txt"
    And user "user0" adds a file of 42 bytes to "/FOLDER/test2.txt"
    And user "user0" adds a file of 108 bytes to "/FOLDER/test3.txt"
    And User "user0" deletes file "/FOLDER/test2.txt"
    When Logging in using web as "user0"
    And logged in user gets storage stats of folder "/FOLDER/"
    Then the storage stats match with
      | used | 131 |

  Scenario: Retrieving storage stats after the last file was deleted from a folder
    Given using old dav path
    And As an "admin"
    And user "user0" exists
    And user "user0" adds a file of 108 bytes to "/FOLDER/test.txt"
    And Logging in using web as "user0"
    # Get the size after uploading the file to ensure that the size after the
    # deletion is not just a size cached before the upload.
    And logged in user gets storage stats of folder "/"
    And the storage stats match with
      | used | 447 |
    And User "user0" deletes file "/FOLDER/test.txt"
    When logged in user gets storage stats of folder "/"
    Then the storage stats match with
      | used | 339 |

  Scenario: Retrieving storage stats for folder after the last file was deleted from that folder
    Given using old dav path
    And As an "admin"
    And user "user0" exists
    And user "user0" adds a file of 108 bytes to "/FOLDER/test.txt"
    And Logging in using web as "user0"
    # Get the size after uploading the file to ensure that the size after the
    # deletion is not just a size cached before the upload.
    And logged in user gets storage stats of folder "/"
    And the storage stats match with
      | used | 447 |
    And User "user0" deletes file "/FOLDER/test.txt"
    When logged in user gets storage stats of folder "/FOLDER/"
    Then the storage stats match with
      | used | 0 |

  # End of counterpart scenarios

  Scenario: Retrieving storage stats after a file was uploaded when using APCu
    Given using old dav path
    And invoking occ with "config:system:set memcache.local --value \OC\Memcache\APCu --type string"
    And As an "admin"
    And user "user0" exists
    And user "user0" adds a file of 108 bytes to "/test.txt"
    When Logging in using web as "user0"
    And logged in user gets storage stats of folder "/"
    Then the storage stats match with
      | used | 447 |
