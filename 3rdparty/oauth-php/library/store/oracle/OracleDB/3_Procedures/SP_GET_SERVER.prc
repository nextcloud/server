CREATE OR REPLACE PROCEDURE SP_GET_SERVER
(
P_CONSUMER_KEY              IN        VARCHAR2,
P_USER_ID                   IN        NUMBER,
P_ROWS                      OUT       TYPES.REF_CURSOR,
P_RESULT                    OUT       NUMBER
)
AS

 -- PROCEDURE TO Get a server from the consumer registry using the consumer key
BEGIN
P_RESULT := 0;

OPEN P_ROWS FOR
  SELECT	OCR_ID	"id",
  OCR_USA_ID_REF			"user_id",
  OCR_CONSUMER_KEY 		"consumer_key",
  OCR_CONSUMER_SECRET 	"consumer_secret",
  OCR_SIGNATURE_METHODS	"signature_methods",
  OCR_SERVER_URI			"server_uri",
  OCR_REQUEST_TOKEN_URI	"request_token_uri",
  OCR_AUTHORIZE_URI		"authorize_uri",
  OCR_ACCESS_TOKEN_URI	"access_token_uri"
  FROM OAUTH_CONSUMER_REGISTRY
  WHERE OCR_CONSUMER_KEY = P_CONSUMER_KEY
  AND (OCR_USA_ID_REF = P_USER_ID OR OCR_USA_ID_REF IS NULL);
        
              
EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
