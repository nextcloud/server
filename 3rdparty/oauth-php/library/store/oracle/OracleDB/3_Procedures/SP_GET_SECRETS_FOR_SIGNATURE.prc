CREATE OR REPLACE PROCEDURE SP_GET_SECRETS_FOR_SIGNATURE
(
P_HOST                IN        VARCHAR2,
P_PATH                IN        VARCHAR2,
P_USER_ID             IN        NUMBER,
P_NAME                IN        VARCHAR2,
P_ROWS                OUT       TYPES.REF_CURSOR,
P_RESULT              OUT       NUMBER
)
AS

 -- PROCEDURE TO Find the server details for signing a request, always looks for an access token.
 -- The returned credentials depend on which local user is making the request.
BEGIN
P_RESULT := 0;

   OPEN P_ROWS FOR
   SELECT * FROM (
   SELECT	OCR_CONSUMER_KEY		"consumer_key",
      OCR_CONSUMER_SECRET		"consumer_secret",
      OCT_TOKEN				"token",
      OCT_TOKEN_SECRET		"token_secret",
      OCR_SIGNATURE_METHODS	"signature_methods"
      FROM OAUTH_CONSUMER_REGISTRY
      JOIN OAUTH_CONSUMER_TOKEN ON OCT_OCR_ID_REF = OCR_ID
      WHERE OCR_SERVER_URI_HOST = P_HOST
      AND OCR_SERVER_URI_PATH = SUBSTR(P_PATH, 1, LENGTH(OCR_SERVER_URI_PATH))
      AND (OCR_USA_ID_REF = P_USER_ID OR OCR_USA_ID_REF IS NULL)
      AND OCT_USA_ID_REF	= P_USER_ID
      AND OCT_TOKEN_TYPE  = 'ACCESS'
      AND OCT_NAME			  = P_NAME
      AND OCT_TOKEN_TTL   >= SYSDATE
      ORDER BY OCR_USA_ID_REF DESC, OCR_CONSUMER_SECRET DESC, LENGTH(OCR_SERVER_URI_PATH) DESC
      ) WHERE ROWNUM<=1;


EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
