# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-only
Feature: live photos
  Background:
    Given using new dav path
    And user "user0" exists

  Scenario: Deleting a live photo deletes its video
    Given User "user0" uploads file with content "photo" to "/IMG.jpg"
    And User "user0" uploads file with content "video" to "/IMG.mov"
    And User "user0" links "/IMG.jpg" as live photo to "/IMG.mov"
    And User "user0" links "/IMG.mov" as live photo to "/IMG.jpg"
    When User "user0" deletes file "/IMG.jpg"
    Then the HTTP status code should be "204"
    And as "user0" the file "/IMG.mov" does not exist

  Scenario: Deleting a live photo keeps a linked video in another folder
    Given User "user0" created a folder "/photos"
    And User "user0" created a folder "/videos"
    And User "user0" uploads file with content "photo" to "/photos/IMG.jpg"
    And User "user0" uploads file with content "video" to "/videos/IMG.mov"
    And User "user0" links "/photos/IMG.jpg" as live photo to "/videos/IMG.mov"
    And User "user0" links "/videos/IMG.mov" as live photo to "/photos/IMG.jpg"
    When User "user0" deletes file "/photos/IMG.jpg"
    Then the HTTP status code should be "204"
    And as "user0" the file "/videos/IMG.mov" exists

  Scenario: Renaming a live photo keeps a linked video in another folder
    Given User "user0" created a folder "/photos"
    And User "user0" created a folder "/videos"
    And User "user0" uploads file with content "photo" to "/photos/IMG.jpg"
    And User "user0" uploads file with content "video" to "/videos/IMG.mov"
    And User "user0" links "/photos/IMG.jpg" as live photo to "/videos/IMG.mov"
    And User "user0" links "/videos/IMG.mov" as live photo to "/photos/IMG.jpg"
    When User "user0" moves file "/photos/IMG.jpg" to "/photos/renamed.jpg"
    Then the HTTP status code should be "201"
    And as "user0" the file "/videos/IMG.mov" exists
    And as "user0" the file "/photos/renamed.mov" does not exist

  Scenario: Copying a live photo does not copy a linked video in another folder
    Given User "user0" created a folder "/photos"
    And User "user0" created a folder "/videos"
    And User "user0" uploads file with content "photo" to "/photos/IMG.jpg"
    And User "user0" uploads file with content "video" to "/videos/IMG.mov"
    And User "user0" links "/photos/IMG.jpg" as live photo to "/videos/IMG.mov"
    And User "user0" links "/videos/IMG.mov" as live photo to "/photos/IMG.jpg"
    When User "user0" copies file "/photos/IMG.jpg" to "/copy.jpg"
    Then the HTTP status code should be "201"
    And as "user0" the file "/copy.mov" does not exist

  Scenario: Deleting a photo keeps a video that does not link back to it
    Given User "user0" uploads file with content "photo" to "/IMG.jpg"
    And User "user0" uploads file with content "video" to "/IMG.mov"
    And User "user0" links "/IMG.jpg" as live photo to "/IMG.mov"
    When User "user0" deletes file "/IMG.jpg"
    Then the HTTP status code should be "204"
    And as "user0" the file "/IMG.mov" exists

  Scenario: Deleting a video is allowed when the photo does not link back to it
    Given User "user0" uploads file with content "photo" to "/IMG.jpg"
    And User "user0" uploads file with content "video" to "/IMG.mov"
    And User "user0" links "/IMG.mov" as live photo to "/IMG.jpg"
    When User "user0" deletes file "/IMG.mov"
    Then the HTTP status code should be "204"
    And as "user0" the file "/IMG.jpg" exists

  Scenario: Moving a live photo into another storage moves its video along
    Given User "user0" uploads file with content "photo" to "/IMG.jpg"
    And User "user0" uploads file with content "video" to "/IMG.mov"
    And User "user0" links "/IMG.jpg" as live photo to "/IMG.mov"
    And User "user0" links "/IMG.mov" as live photo to "/IMG.jpg"
    And User "user0" created a folder "/local_storage/live-photos"
    When User "user0" moves file "/IMG.jpg" to "/local_storage/live-photos/IMG.jpg"
    Then the HTTP status code should be "201"
    And as "user0" the file "/local_storage/live-photos/IMG.jpg" exists
    And as "user0" the file "/local_storage/live-photos/IMG.mov" exists
    And as "user0" the file "/IMG.mov" does not exist
    And User "user0" deletes folder "/local_storage/live-photos"

  Scenario: Moving a live photo video into another storage moves its photo along
    Given User "user0" uploads file with content "photo" to "/IMG.jpg"
    And User "user0" uploads file with content "video" to "/IMG.mov"
    And User "user0" links "/IMG.jpg" as live photo to "/IMG.mov"
    And User "user0" links "/IMG.mov" as live photo to "/IMG.jpg"
    And User "user0" created a folder "/local_storage/live-photos"
    When User "user0" moves file "/IMG.mov" to "/local_storage/live-photos/IMG.mov"
    Then the HTTP status code should be "201"
    And as "user0" the file "/local_storage/live-photos/IMG.mov" exists
    And as "user0" the file "/local_storage/live-photos/IMG.jpg" exists
    And as "user0" the file "/IMG.jpg" does not exist
    And User "user0" deletes folder "/local_storage/live-photos"

  Scenario: Renaming a live photo also renames its peer
    Given User "user0" uploads file with content "photo" to "/photo.jpg"
    And User "user0" uploads file with content "video" to "/photo.mov"
    And User "user0" links "/photo.jpg" as live photo to "/photo.mov"
    And User "user0" links "/photo.mov" as live photo to "/photo.jpg"
    When User "user0" moves file "/photo.jpg" to "/renamed.jpg"
    Then as "user0" the file "/renamed.jpg" exists
    And as "user0" the file "/renamed.mov" exists
    And as "user0" the file "/photo.mov" does not exist

  Scenario: Renaming a live photo does not move its peer out of a read-only share
    Given user "user1" exists
    And User "user0" created a folder "/handbook"
    And User "user0" uploads file with content "chapter one" to "/handbook/chapter.txt"
    And folder "/handbook" of user "user0" is shared with user "user1" with permissions 1
    And User "user1" uploads file with content "photo" to "/photo.jpg"
    And User "user1" links "/photo.jpg" as live photo to "/handbook/chapter.txt"
    When User "user1" moves file "/photo.jpg" to "/mine.jpg"
    Then as "user0" the file "/handbook/chapter.txt" exists
    And as "user1" the file "/handbook/chapter.txt" exists
    And as "user1" the file "/mine.txt" does not exist
