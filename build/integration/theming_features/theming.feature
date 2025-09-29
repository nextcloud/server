# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
Feature: theming

	Background:
		Given user "user0" exists

	Scenario: themed stylesheets are available for users
		Given As an "user0"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/default.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/light.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/dark.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/light-highcontrast.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/dark-highcontrast.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/opendyslexic.css"
		Then the HTTP status code should be "200"

	Scenario: themed stylesheets are available for guests
		Given As an "anonymous"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/default.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/light.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/dark.css"
		Then the HTTP status code should be "200"
		# Themes that can not be explicitly set by a guest could have been
		# globally set too through "enforce_theme".
		When sending "GET" with exact url to "/index.php/apps/theming/theme/light-highcontrast.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/dark-highcontrast.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/opendyslexic.css"
		Then the HTTP status code should be "200"

	Scenario: themed stylesheets are available for disabled users
		Given As an "admin"
		And assure user "user0" is disabled
		And As an "user0"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/default.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/light.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/dark.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/light-highcontrast.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/dark-highcontrast.css"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/theme/opendyslexic.css"
		Then the HTTP status code should be "200"

	Scenario: themed images are available for users
		Given Logging in using web as "admin"
		And logged in admin uploads theming image for "background" from file "data/clouds.jpg"
		And logged in admin uploads theming image for "logo" from file "data/coloured-pattern-non-square.png"
		And logged in admin uploads theming image for "logoheader" from file "data/coloured-pattern-non-square.png"
		And As an "user0"
		When sending "GET" with exact url to "/index.php/apps/theming/image/background"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/image/logo"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/image/logoheader"
		Then the HTTP status code should be "200"

	Scenario: themed images are available for guests
		Given Logging in using web as "admin"
		And logged in admin uploads theming image for "background" from file "data/clouds.jpg"
		And logged in admin uploads theming image for "logo" from file "data/coloured-pattern-non-square.png"
		And logged in admin uploads theming image for "logoheader" from file "data/coloured-pattern-non-square.png"
		And As an "anonymous"
		When sending "GET" with exact url to "/index.php/apps/theming/image/background"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/image/logo"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/image/logoheader"
		Then the HTTP status code should be "200"

	Scenario: themed images are available for disabled users
		Given Logging in using web as "admin"
		And logged in admin uploads theming image for "background" from file "data/clouds.jpg"
		And logged in admin uploads theming image for "logo" from file "data/coloured-pattern-non-square.png"
		And logged in admin uploads theming image for "logoheader" from file "data/coloured-pattern-non-square.png"
		And As an "admin"
		And assure user "user0" is disabled
		And As an "user0"
		When sending "GET" with exact url to "/index.php/apps/theming/image/background"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/image/logo"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/image/logoheader"
		Then the HTTP status code should be "200"

	Scenario: themed icons are available for users
		Given As an "user0"
		When sending "GET" with exact url to "/index.php/apps/theming/favicon"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/icon"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/favicon/dashboard"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/icon/dashboard"
		Then the HTTP status code should be "200"

	Scenario: themed icons are available for guests
		Given As an "anonymous"
		When sending "GET" with exact url to "/index.php/apps/theming/favicon"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/icon"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/favicon/dashboard"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/icon/dashboard"
		Then the HTTP status code should be "200"

	Scenario: themed icons are available for disabled users
		Given As an "admin"
		And assure user "user0" is disabled
		And As an "user0"
		When sending "GET" with exact url to "/index.php/apps/theming/favicon"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/icon"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/favicon/dashboard"
		Then the HTTP status code should be "200"
		When sending "GET" with exact url to "/index.php/apps/theming/icon/dashboard"
		Then the HTTP status code should be "200"
