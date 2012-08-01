CREATE OR REPLACE PROCEDURE SP_LIST_CONSUMER_TOKENS
(
P_USER_ID                      IN        NUMBER,
P_ROWS                         OUT       TYPES.REF_CURSOR,
P_RESULT                       OUT       NUMBER
)
AS

 -- PROCEDURE TO Fetch a list of all consumer tokens accessing the account of the given user.
 
BEGIN

  P_RESULT := 0;
  
  OPEN P_ROWS FOR
    SELECT	OSR_CONSUMER_KEY 		"consumer_key",
    OSR_CONSUMER_SECRET		"consumer_secret",
    OSR_ENABLED				"enabled",
    OSR_STATUS 				"status",
    OSR_APPLICATION_URI		"application_uri",
    OSR_APPLICATION_TITLE	"application_title",
    OSR_APPLICATION_DESCR	"application_descr",
    OST_TIMESTAMP			"timestamp",
    OST_TOKEN				"token",
    OST_TOKEN_SECRET		"token_secret",
    OST_REFERRER_HOST		"token_referrer_host",
    OSR_CALLBACK_URI		"callback_uri"
    FROM OAUTH_SERVER_REGISTRY
    JOIN OAUTH_SERVER_TOKEN
    ON OST_OSR_ID_REF = OSR_ID
    WHERE OST_USA_ID_REF = P_USER_ID
    AND OST_TOKEN_TYPE = 'ACCESS'
    AND OST_TOKEN_TTL  >= SYSDATE
    ORDER BY OSR_APPLICATION_TITLE;
            

EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
