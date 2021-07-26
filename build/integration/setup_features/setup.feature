Feature: setup

	Scenario: setup page is shown properly
		When requesting "/index.php" with "GET"
		Then the HTTP status code should be "200"
