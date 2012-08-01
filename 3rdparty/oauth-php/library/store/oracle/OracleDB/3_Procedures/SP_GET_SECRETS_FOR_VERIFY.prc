CREATE OR REPLACE PROCEDURE SP_GET_SECRETS_FOR_VERIFY
(
P_CONSUMER_KEY      IN        VARCHAR2,
P_TOKEN             IN        VARCHAR2,
P_TOKEN_TYPE        IN        VARCHAR2,
P_ROWS          OUT       TYPES.REF_CURSOR,
P_RESULT          OUT       NUMBER
)
AS

 -- PROCEDURE to Find stored credentials for the consumer key and token. Used by an OAuth server
 -- when verifying an OAuth request.
 
BEGIN
P_RESULT := 0;

IF P_TOKEN_TYPE IS NULL THEN
   OPEN P_ROWS FOR
   SELECT	OSR.OSR_ID "osr_id",
          OSR.OSR_CONSUMER_KEY		"consumer_key",
          OSR.OSR_CONSUMER_SECRET		"consumer_secret"
          FROM OAUTH_SERVER_REGISTRY OSR
          WHERE OSR.OSR_CONSUMER_KEY	= P_CONSUMER_KEY
          AND OSR.OSR_ENABLED		= 1;
ELSE
    OPEN P_ROWS FOR
    SELECT OSR.OSR_ID "osr_id",
           OST.OST_ID "ost_id",
           OST.OST_USA_ID_REF			"user_id",
           OSR.OSR_CONSUMER_KEY		"consumer_key",
           OSR.OSR_CONSUMER_SECRET		"consumer_secret",
           OST.OST_TOKEN				"token",
           OST.OST_TOKEN_SECRET		"token_secret"
           FROM OAUTH_SERVER_REGISTRY OSR, OAUTH_SERVER_TOKEN OST
           WHERE OST.OST_OSR_ID_REF = OSR.OSR_ID
           AND upper(OST.OST_TOKEN_TYPE)	= upper(P_TOKEN_TYPE)
           AND OSR.OSR_CONSUMER_KEY	= P_CONSUMER_KEY
           AND OST.OST_TOKEN			= P_TOKEN
           AND OSR.OSR_ENABLED		= 1
           AND OST.OST_TOKEN_TTL  >= SYSDATE;
    
END IF;



EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
