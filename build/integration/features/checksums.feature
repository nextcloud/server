Feature: checksums

  Scenario: Uploading a file with checksum should work
    Given using old dav path
    And user "user0" exists
    When user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    Then The webdav response should have a status code "201"

  Scenario: Uploading a file with checksum should return the checksum in the propfind
    Given using old dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When user "user0" request the checksum of "/myChecksumFile.txt" via propfind
    Then The webdav checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f MD5:d70b40f177b14b470d1756a3c12b963a ADLER32:8ae90960"

  Scenario: Uploading a file with checksum should return the checksum in the download header
    Given using old dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When user "user0" downloads the file "/myChecksumFile.txt"
    Then The header checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f"

  Scenario: Moving a file with checksum should return the checksum in the propfind
    Given using old dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" moves file "/myChecksumFile.txt" to "/myMovedChecksumFile.txt"
    And user "user0" request the checksum of "/myMovedChecksumFile.txt" via propfind
    Then The webdav checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f MD5:d70b40f177b14b470d1756a3c12b963a ADLER32:8ae90960"

  Scenario: Moving file with checksum should return the checksum in the download header
    Given using old dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" moves file "/myChecksumFile.txt" to "/myMovedChecksumFile.txt"
    And user "user0" downloads the file "/myMovedChecksumFile.txt"
    Then The header checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f"

  Scenario: Uploading a chunked file with checksum should return the checksum in the propfind
    Given using old dav path
    And user "user0" exists
    And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    When user "user0" request the checksum of "/myChecksumFile.txt" via propfind
    Then The webdav checksum should match "SHA1:acfa6b1565f9710d4d497c6035d5c069bd35a8e8 MD5:45a72715acdd5019c5be30bdbb75233e ADLER32:1ecd03df"

  Scenario: Uploading a chunked file with checksum should return the checksum in the download header
    Given using old dav path
    And user "user0" exists
    And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    When user "user0" downloads the file "/myChecksumFile.txt"
    Then The header checksum should match "SHA1:acfa6b1565f9710d4d497c6035d5c069bd35a8e8"

  @local_storage
  Scenario: Downloading a file from local storage has correct checksum
    Given using old dav path
    And user "user0" exists
    And file "prueba_cksum.txt" with text "Test file for checksums" is created in local storage
    When user "user0" downloads the file "/local_storage/prueba_cksum.txt"
    When user "user0" downloads the file "/local_storage/prueba_cksum.txt"
    Then The header checksum should match "SHA1:a35b7605c8f586d735435535c337adc066c2ccb6"

  Scenario: Uploading a file with checksum should work using new dav path
    Given using new dav path
    And user "user0" exists
    When user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    Then The webdav response should have a status code "201"

  Scenario: Uploading a file with checksum should return the checksum in the propfind using new dav path
    Given using new dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When user "user0" request the checksum of "/myChecksumFile.txt" via propfind
    Then The webdav checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f MD5:d70b40f177b14b470d1756a3c12b963a ADLER32:8ae90960"

  Scenario: Uploading a file with checksum should return the checksum in the download header using new dav path
    Given using new dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When user "user0" downloads the file "/myChecksumFile.txt"
    Then The header checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f"

  Scenario: Moving a file with checksum should return the checksum in the propfind using new dav path
    Given using new dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" moved file "/myChecksumFile.txt" to "/myMovedChecksumFile.txt"
    And user "user0" request the checksum of "/myMovedChecksumFile.txt" via propfind
    Then The webdav checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f MD5:d70b40f177b14b470d1756a3c12b963a ADLER32:8ae90960"

  Scenario: Moving file with checksum should return the checksum in the download header using new dav path
    Given using new dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" moved file "/myChecksumFile.txt" to "/myMovedChecksumFile.txt"
    And user "user0" downloads the file "/myMovedChecksumFile.txt"
    Then The header checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f"

  Scenario: Copying a file with checksum should return the checksum in the propfind using new dav path
    Given using new dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" copies file "/myChecksumFile.txt" to "/myChecksumFileCopy.txt"
    And user "user0" request the checksum of "/myChecksumFileCopy.txt" via propfind
    Then The webdav checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f MD5:d70b40f177b14b470d1756a3c12b963a ADLER32:8ae90960"

  Scenario: Copying file with checksum should return the checksum in the download header using new dav path
    Given using new dav path
    And user "user0" exists
    And user "user0" uploads file "data/textfile.txt" to "/myChecksumFile.txt" with checksum "MD5:d70b40f177b14b470d1756a3c12b963a"
    When User "user0" copies file "/myChecksumFile.txt" to "/myChecksumFileCopy.txt"
    And user "user0" downloads the file "/myChecksumFileCopy.txt"
    Then The header checksum should match "SHA1:3ee962b839762adb0ad8ba6023a4690be478de6f"

  Scenario: Uploading a chunked file with checksum should return the checksum in the propfind using new dav path
    Given using new dav path
    And user "user0" exists
    And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    When user "user0" request the checksum of "/myChecksumFile.txt" via propfind
    Then The webdav checksum should match "SHA1:acfa6b1565f9710d4d497c6035d5c069bd35a8e8 MD5:45a72715acdd5019c5be30bdbb75233e ADLER32:1ecd03df"

  Scenario: Uploading a chunked file with checksum should return the checksum in the download header using new dav path
    Given using new dav path
    And user "user0" exists
    And user "user0" uploads chunk file "1" of "3" with "AAAAA" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    And user "user0" uploads chunk file "2" of "3" with "BBBBB" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    And user "user0" uploads chunk file "3" of "3" with "CCCCC" to "/myChecksumFile.txt" with checksum "MD5:45a72715acdd5019c5be30bdbb75233e"
    When user "user0" downloads the file "/myChecksumFile.txt"
    Then The header checksum should match "SHA1:acfa6b1565f9710d4d497c6035d5c069bd35a8e8"

  @local_storage
  Scenario: Downloading a file from local storage has correct checksum using new dav path
    Given using new dav path
    And user "user0" exists
    And file "prueba_cksum.txt" with text "Test file for checksums" is created in local storage
    When user "user0" downloads the file "/local_storage/prueba_cksum.txt"
    When user "user0" downloads the file "/local_storage/prueba_cksum.txt"
    Then The header checksum should match "SHA1:a35b7605c8f586d735435535c337adc066c2ccb6"

  Scenario: Upload chunked file where checksum does not match
    Given using new dav path
    And user "user0" exists
    And user "user0" creates a new chunking upload with id "chunking-42"
    And user "user0" uploads new chunk file "2" with "BBBBB" to id "chunking-42"
    And user "user0" uploads new chunk file "3" with "CCCCC" to id "chunking-42" with checksum "SHA1:f005ba11"
    And user "user0" uploads new chunk file "1" with "AAAAA" to id "chunking-42"
    And user "user0" moves new chunk file with id "chunking-42" to "/myChunkedFile.txt"
    Then the HTTP status code should be "400"

  Scenario: Upload a file where checksum does not match
    Given using old dav path
    Given user "user0" exists
    And file "/chksumtst.txt"  does not exist for user "user0"
    And user "user0" uploads file with checksum "SHA1:f005ba11" and content "Some Text" to "/chksumtst.txt"
    Then the HTTP status code should be "400"

  Scenario: Upload a file where checksum does match
    Given using old dav path
    Given user "user0" exists
    And file "/chksumtst.txt"  does not exist for user "user0"
    And user "user0" uploads file with checksum "SHA1:ce5582148c6f0c1282335b87df5ed4be4b781399" and content "Some Text" to "/chksumtst.txt"
    Then the HTTP status code should be "201"

  Scenario: Uploaded file should have the same checksum when downloaded
    Given using old dav path
    Given user "user0" exists
    And file "/chksumtst.txt"  does not exist for user "user0"
    And user "user0" uploads file with checksum "SHA1:ce5582148c6f0c1282335b87df5ed4be4b781399" and content "Some Text" to "/chksumtst.txt"
    When Downloading file "/chksumtst.txt" as "user0"
    Then The following headers should be set
            | OC-Checksum | SHA1:ce5582148c6f0c1282335b87df5ed4be4b781399 |

  @local_storage
  Scenario: Uploaded file to external storage should have the same checksum when downloaded
    Given using old dav path
    Given user "user0" exists
    And file "/local_storage/chksumtst.txt"  does not exist for user "user0"
    And user "user0" uploads file with checksum "SHA1:ce5582148c6f0c1282335b87df5ed4be4b781399" and content "Some Text" to "/local_storage/chksumtst.txt"
    When Downloading file "/local_storage/chksumtst.txt" as "user0"
    Then The following headers should be set
            | OC-Checksum | SHA1:ce5582148c6f0c1282335b87df5ed4be4b781399 |

