CREATE OR REPLACE PROCEDURE SP_GET_SERVER_FOR_URI
(
P_HOST                      IN        VARCHAR2,
P_PATH                      IN        VARCHAR2,
P_USER_ID                   IN        NUMBER,
P_ROWS                      OUT       TYPES.REF_CURSOR,
P_RESULT                    OUT       NUMBER
)
AS

 -- PROCEDURE TO Find the server details that might be used for a request
BEGIN
P_RESULT := 0;

OPEN P_ROWS FOR
SELECT * FROM (
  SELECT	OCR_ID					"id",
  OCR_USA_ID_REF			"user_id",
  OCR_CONSUMER_KEY		"consumer_key",
  OCR_CONSUMER_SECRET		"consumer_secret",
  OCR_SIGNATURE_METHODS	"signature_methods",
  OCR_SERVER_URI			"server_uri",
  OCR_REQUEST_TOKEN_URI	"request_token_uri",
  OCR_AUTHORIZE_URI		"authorize_uri",
  OCR_ACCESS_TOKEN_URI	"access_token_uri"
  FROM OAUTH_CONSUMER_REGISTRY
  WHERE OCR_SERVER_URI_HOST = P_HOST
  AND OCR_SERVER_URI_PATH = SUBSTR(P_PATH, 1, LENGTH(OCR_SERVER_URI_PATH))
  AND (OCR_USA_ID_REF = P_USER_ID OR OCR_USA_ID_REF IS NULL)
  ORDER BY ocr_usa_id_ref DESC, OCR_CONSUMER_KEY DESC, LENGTH(ocr_server_uri_path) DESC
) WHERE ROWNUM<=1;
              
        
              
EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
