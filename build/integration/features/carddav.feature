Feature: carddav
  Scenario: Accessing a not existing addressbook of another user
    Given user "user0" exists
    When "admin" requests addressbook "user0/MyAddressbook" with statuscode "404"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "Addressbook with name 'MyAddressbook' could not be found"

  Scenario: Accessing a not shared addressbook of another user
    Given user "user0" exists
    Given "admin" creates an addressbook named "MyAddressbook" with statuscode "201"
    When "user0" requests addressbook "admin/MyAddressbook" with statuscode "404"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "Addressbook with name 'MyAddressbook' could not be found"

  Scenario: Accessing a not existing addressbook of myself
    Given user "user0" exists
    When "user0" requests addressbook "admin/MyAddressbook" with statuscode "404"
    And The CardDAV exception is "Sabre\DAV\Exception\NotFound"
    And The CardDAV error message is "Addressbook with name 'MyAddressbook' could not be found"

  Scenario: Creating a new addressbook
    When "admin" creates an addressbook named "MyAddressbook" with statuscode "201"
    Then "admin" requests addressbook "admin/MyAddressbook" with statuscode "200"
