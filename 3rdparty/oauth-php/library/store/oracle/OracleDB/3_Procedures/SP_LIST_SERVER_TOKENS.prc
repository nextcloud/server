CREATE OR REPLACE PROCEDURE SP_LIST_SERVER_TOKENS
(
P_USER_ID                   IN        NUMBER,
P_ROWS                      OUT       TYPES.REF_CURSOR,
P_RESULT                    OUT       NUMBER
)
AS

 -- PROCEDURE TO Find the server details that might be used for a request
BEGIN
P_RESULT := 0;

OPEN P_ROWS FOR
  SELECT	OCR_CONSUMER_KEY		"consumer_key",
  OCR_CONSUMER_SECRET		"consumer_secret",
  OCT_ID					"token_id",
  OCT_TOKEN				"token",
  OCT_TOKEN_SECRET		"token_secret",
  OCT_USA_ID_REF			"user_id",
  OCR_SIGNATURE_METHODS	"signature_methods",
  OCR_SERVER_URI			"server_uri",
  OCR_SERVER_URI_HOST		"server_uri_host",
  OCR_SERVER_URI_PATH		"server_uri_path",
  OCR_REQUEST_TOKEN_URI	"request_token_uri",
  OCR_AUTHORIZE_URI		"authorize_uri",
  OCR_ACCESS_TOKEN_URI	"access_token_uri",
  OCT_TIMESTAMP			"timestamp"
  FROM OAUTH_CONSUMER_REGISTRY
  JOIN OAUTH_CONSUMER_TOKEN
  ON OCT_OCR_ID_REF = OCR_ID
  WHERE OCT_USA_ID_REF = P_USER_ID
  AND OCT_TOKEN_TYPE = 'ACCESS'
  AND OCT_TOKEN_TTL  >= SYSDATE
  ORDER BY OCR_SERVER_URI_HOST, OCR_SERVER_URI_PATH;
              
              
        
              
EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
