Feature: capabilities
	Background:
		Given using api version "1"

	Scenario: getting capabilities with admin user
		Given As an "admin"
		When sending "GET" to "/cloud/capabilities"
		Then the HTTP status code should be "200"
		And fields of capabilities match with
			| capability | feature | value_or_subfeature | value |
			| core | pollinterval | 60 | |
			| core | webdav-root | remote.php/webdav | |
			| files_sharing | api_enabled | 1 | |
			| files_sharing | public | enabled | 1 |
			| files_sharing | public | upload | 1 |
			| files_sharing | resharing | 1 | |
			| files_sharing | federation | outgoing | 1 |
			| files_sharing | federation | incoming | 1 |
			| files | bigfilechunking | 1 | |
			| files | undelete | 1 | |
			| files | versioning | 1 | |










