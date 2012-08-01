CREATE OR REPLACE PROCEDURE SP_LIST_CONSUMERS
(
P_USER_ID                      IN        NUMBER,
P_ROWS                         OUT       TYPES.REF_CURSOR,
P_RESULT                       OUT       NUMBER
)
AS

 -- PROCEDURE TO Fetch a list of all consumer keys, secrets etc.
 -- Returns the public (user_id is null) and the keys owned by the user
 
BEGIN

  P_RESULT := 0;
  
  OPEN P_ROWS FOR
    SELECT	OSR_ID					"id",
    OSR_USA_ID_REF			"user_id",
    OSR_CONSUMER_KEY 		"consumer_key",
    OSR_CONSUMER_SECRET		"consumer_secret",
    OSR_ENABLED				"enabled",
    OSR_STATUS 				"status",
    OSR_ISSUE_DATE			"issue_date",
    OSR_APPLICATION_URI		"application_uri",
    OSR_APPLICATION_TITLE	"application_title",
    OSR_APPLICATION_DESCR	"application_descr",
    OSR_REQUESTER_NAME		"requester_name",
    OSR_REQUESTER_EMAIL		"requester_email",
    OSR_CALLBACK_URI		"callback_uri"
    FROM OAUTH_SERVER_REGISTRY
    WHERE (OSR_USA_ID_REF = P_USER_ID OR OSR_USA_ID_REF IS NULL)
    ORDER BY OSR_APPLICATION_TITLE;
              

EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
