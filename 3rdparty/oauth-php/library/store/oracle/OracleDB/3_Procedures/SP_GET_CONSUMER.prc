CREATE OR REPLACE PROCEDURE SP_GET_CONSUMER
(
P_CONSUMER_KEY              IN        STRING,
P_ROWS                      OUT       TYPES.REF_CURSOR,
P_RESULT                    OUT       NUMBER
)
AS

 -- PROCEDURE TO Fetch a consumer of this server, by consumer_key.
BEGIN
P_RESULT := 0;

OPEN P_ROWS FOR
  SELECT	OSR_ID "osr_id", 
	OSR_USA_ID_REF "osr_usa_id_ref", 
	OSR_CONSUMER_KEY "osr_consumer_key", 
	OSR_CONSUMER_SECRET "osr_consumer_secret", 
	OSR_ENABLED "osr_enabled", 
	OSR_STATUS "osr_status", 
	OSR_REQUESTER_NAME "osr_requester_name", 
	OSR_REQUESTER_EMAIL "osr_requester_email",
	OSR_CALLBACK_URI "osr_callback_uri",
	OSR_APPLICATION_URI "osr_application_uri",
	OSR_APPLICATION_TITLE "osr_application_title",
	OSR_APPLICATION_DESCR "osr_application_descr",
	OSR_APPLICATION_NOTES "osr_application_notes",
	OSR_APPLICATION_TYPE "osr_application_type",
	OSR_APPLICATION_COMMERCIAL "osr_application_commercial",
	OSR_ISSUE_DATE "osr_issue_date",
	OSR_TIMESTAMP "osr_timestamp"
	FROM OAUTH_SERVER_REGISTRY
	WHERE OSR_CONSUMER_KEY = P_CONSUMER_KEY;
              
              
EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
