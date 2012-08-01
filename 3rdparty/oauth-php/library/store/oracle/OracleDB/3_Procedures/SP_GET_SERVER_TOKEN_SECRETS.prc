CREATE OR REPLACE PROCEDURE SP_GET_SERVER_TOKEN_SECRETS
(
P_CONSUMER_KEY      IN        VARCHAR2,
P_TOKEN             IN        VARCHAR2,
P_TOKEN_TYPE        IN        VARCHAR2,
P_USER_ID           IN        NUMBER,
P_ROWS              OUT       TYPES.REF_CURSOR,
P_RESULT            OUT       NUMBER
)
AS

 --  Get the token and token secret we obtained from a server.
 
BEGIN
P_RESULT := 0;


   OPEN P_ROWS FOR
   SELECT	OCR.OCR_CONSUMER_KEY		"consumer_key",
      OCR.OCR_CONSUMER_SECRET		"consumer_secret",
      OCT.OCT_TOKEN				"token",
      OCT.OCT_TOKEN_SECRET		"token_secret",
      OCT.OCT_NAME				"token_name",
      OCR.OCR_SIGNATURE_METHODS	"signature_methods",
      OCR.OCR_SERVER_URI			"server_uri",
      OCR.OCR_REQUEST_TOKEN_URI	"request_token_uri",
      OCR.OCR_AUTHORIZE_URI		"authorize_uri",
      OCR.OCR_ACCESS_TOKEN_URI	"access_token_uri",
      CASE WHEN OCT.OCT_TOKEN_TTL >= TO_DATE('9999.12.31', 'yyyy.mm.dd') THEN NULL 
                 ELSE OCT.OCT_TOKEN_TTL - SYSDATE 
            END "token_ttl"
      FROM OAUTH_CONSUMER_REGISTRY OCR, OAUTH_CONSUMER_TOKEN OCT
      WHERE OCT.OCT_OCR_ID_REF = OCR_ID
      AND OCR.OCR_CONSUMER_KEY = P_CONSUMER_KEY
      AND upper(OCT.OCT_TOKEN_TYPE)   = upper(P_TOKEN_TYPE)
      AND OCT.OCT_TOKEN        = P_TOKEN
      AND OCT.OCT_USA_ID_REF   = P_USER_ID
      AND OCT.OCT_TOKEN_TTL    >= SYSDATE;


EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
