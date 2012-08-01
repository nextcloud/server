CREATE OR REPLACE PROCEDURE SP_GET_CONSUMER_REQUEST_TOKEN
(
P_TOKEN                     IN        VARCHAR2,
P_ROWS                      OUT       TYPES.REF_CURSOR,
P_RESULT                    OUT       NUMBER
)
AS

 -- PROCEDURE TO Fetch the consumer request token, by request token.
BEGIN
P_RESULT := 0;

OPEN P_ROWS FOR
 
SELECT	OST_TOKEN			"token",
    OST_TOKEN_SECRET	"token_secret",
    OSR_CONSUMER_KEY	"consumer_key",
    OSR_CONSUMER_SECRET	"consumer_secret",
    OST_TOKEN_TYPE		"token_type",
    OST_CALLBACK_URL    "callback_url",
    OSR_APPLICATION_TITLE "application_title",
    OSR_APPLICATION_DESCR "application_descr",
    OSR_APPLICATION_URI   "application_uri"
    FROM OAUTH_SERVER_TOKEN
    JOIN OAUTH_SERVER_REGISTRY
    ON OST_OSR_ID_REF = OSR_ID
    WHERE OST_TOKEN_TYPE = 'REQUEST'
    AND OST_TOKEN      = P_TOKEN
    AND OST_TOKEN_TTL  >= SYSDATE;
              
              
              
        
              
EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
