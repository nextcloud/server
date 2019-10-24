@apache
Feature: apps

  Scenario: enable an installed app
    Given I act as Jane
    And I am logged in as the admin
    And I open the Apps management
    When I enable the "QA testing" app
    Then I see that the "QA testing" app has been enabled

  Scenario: disable a installed app
    Given I act as Jane
    And I am logged in as the admin
    And I open the Apps management
    When I disable the "Update notification" app
    Then I see that the "Update notification" app has been disabled

  Scenario: Browse enabled apps
    Given I act as Jane
    And I am logged in as the admin
    And I open the Apps management
    When I open the "Active apps" section
    Then I see that the current section is "Active apps"
    And I see that there are only enabled apps

  Scenario: Browse disabled apps
    Given I act as Jane
    And I am logged in as the admin
    And I open the Apps management
    When I open the "Disabled apps" section
    Then I see that the current section is "Disabled apps"
    And I see that there are only disabled apps

  Scenario: Browse app bundles
    Given I act as Jane
    And I am logged in as the admin
    And I open the Apps management
    When I open the "App bundles" section
    Then I see that the current section is "App bundles"
    And I see the app bundles
    And I see that the "Enterprise bundle" is disabled

# Enabling an app bundle fails when not all apps have a matching version available
#  Scenario: Enable an app bundle
#    Given I act as Jane
#    And I am logged in as the admin
#    And I open the Apps management
#    And I open the "App bundles" section
#    When I enable all apps from the "Enterprise bundle"
#    Then I see that the "Auditing / Logging" app has been enabled
#    And I see that the "LDAP user and group backend" app has been enabled

  Scenario: View app details
    Given I act as Jane
    And I am logged in as the admin
    And I open the Apps management
    When I click on the "QA testing" app
    Then I see that the app details are shown

  # TODO: Improve testing with app store as external API
  # The following scenarios require the files_antivirus and calendar app
  # being present in the app store with support for the current server version
  # Ideally we would have either a dummy app store endpoint with some test apps
  # or even an app store instance running somewhere to properly test this.
  # This is also a requirement to properly test updates of apps

  Scenario: Show section from app store
    Given I act as Jane
    And I am logged in as the admin
    And I open the Apps management
    And I see that the current section is "Your apps"
    #When I open the "Files" section
    #Then I see that there some apps listed from the app store
    #And I see that the current section is "Files"

#  Scenario: View app details for app store apps
#    Given I act as Jane
#    And I am logged in as the admin
#    And I open the Apps management
#    And I open the "Tools" section
#    When I click on the "Antivirus for files" app
#    Then I see that the app details are shown

#  Scenario: Install an app from the app store
#    Given I act as Jane
#    And I am logged in as the admin
#    And I open the Apps management
#    And I open the "Tools" section
#    And I click on the "Antivirus for files" app
#    And I see that the app details are shown
#    Then I download and enable the "Antivirus for files" app
#    And I see that the "Antivirus for files" app has been enabled
