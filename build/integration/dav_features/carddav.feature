Feature: carddav
  Scenario: Accessing a not existing addressbook of another user
    Given user "user0" exists
    When "admin" requests addressbook "user0/MyAddressbook" with statuscode "404" on the endpoint "/remote.php/dav/addressbooks/users/"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "Addressbook with name 'MyAddressbook' could not be found"

  Scenario: Accessing a not shared addressbook of another user
    Given user "user0" exists
    Given "admin" creates an addressbook named "MyAddressbook" with statuscode "201"
    When "user0" requests addressbook "admin/MyAddressbook" with statuscode "404" on the endpoint "/remote.php/dav/addressbooks/users/"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "Addressbook with name 'MyAddressbook' could not be found"

  Scenario: Accessing a not existing addressbook of another user via legacy endpoint
    Given user "user0" exists
    When "admin" requests addressbook "user0/MyAddressbook" with statuscode "404" on the endpoint "/remote.php/carddav/addressbooks/"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "Addressbook with name 'MyAddressbook' could not be found"

  Scenario: Accessing a not shared addressbook of another user via legacy endpoint
    Given user "user0" exists
    Given "admin" creates an addressbook named "MyAddressbook" with statuscode "201"
    When "user0" requests addressbook "admin/MyAddressbook" with statuscode "404" on the endpoint "/remote.php/carddav/addressbooks/"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "Addressbook with name 'MyAddressbook' could not be found"

  Scenario: Accessing a not existing addressbook of myself
    Given user "user0" exists
    When "user0" requests addressbook "admin/MyAddressbook" with statuscode "404" on the endpoint "/remote.php/dav/addressbooks/users/"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "Addressbook with name 'MyAddressbook' could not be found"

  Scenario: Creating a new addressbook
    When "admin" creates an addressbook named "MyAddressbook" with statuscode "201"
    Then "admin" requests addressbook "admin/MyAddressbook" with statuscode "207" on the endpoint "/remote.php/dav/addressbooks/users/"

  Scenario: Accessing ones own contact
    Given "admin" creates an addressbook named "MyAddressbook" with statuscode "201"
    Given "admin" uploads the contact "bjoern.vcf" to the addressbook "MyAddressbook"
    When Downloading the contact "bjoern.vcf" from addressbook "MyAddressbook" as user "admin"
    Then The following HTTP headers should be set
        |Content-Disposition|attachment; filename*=UTF-8''bjoern.vcf; filename="bjoern.vcf"|
        |Content-Type|text/vcard; charset=utf-8|
        |Content-Security-Policy|default-src 'none';|
        |X-Content-Type-Options |nosniff|
        |X-Frame-Options|SAMEORIGIN|
        |X-Permitted-Cross-Domain-Policies|none|
        |X-Robots-Tag|noindex, nofollow|
        |X-XSS-Protection|1; mode=block|

  Scenario: Exporting the picture of ones own contact
    Given "admin" creates an addressbook named "MyAddressbook" with statuscode "201"
    Given "admin" uploads the contact "bjoern.vcf" to the addressbook "MyAddressbook"
    When Exporting the picture of contact "bjoern.vcf" from addressbook "MyAddressbook" as user "admin"
    Then The following HTTP headers should be set
      |Content-Disposition|attachment; filename=bjoern.vcf.jpg|
      |Content-Type|image/jpeg|
      |Content-Security-Policy|default-src 'none';|
      |X-Content-Type-Options |nosniff|
      |X-Frame-Options|SAMEORIGIN|
      |X-Permitted-Cross-Domain-Policies|none|
      |X-Robots-Tag|noindex, nofollow|
      |X-XSS-Protection|1; mode=block|

  Scenario: Create addressbook request for non-existing addressbook of another user
    Given user "user0" exists
    When "user0" sends a create addressbook request to "admin/MyAddressbook2" on the endpoint "/remote.php/dav/addressbooks/"
    Then The CardDAV HTTP status code should be "404"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "File not found: admin in 'addressbooks'"

  Scenario: Create addressbook request for existing addressbook of another user
    Given user "user0" exists
    When "admin" creates an addressbook named "MyAddressbook2" with statuscode "201"
    When "user0" sends a create addressbook request to "admin/MyAddressbook2" on the endpoint "/remote.php/dav/addressbooks/"
    Then The CardDAV HTTP status code should be "404"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "File not found: admin in 'addressbooks'"
