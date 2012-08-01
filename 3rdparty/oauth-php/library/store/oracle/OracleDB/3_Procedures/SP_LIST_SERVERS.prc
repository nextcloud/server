CREATE OR REPLACE PROCEDURE SP_LIST_SERVERS
(
P_Q                         IN        VARCHAR2,
P_USER_ID                   IN        NUMBER,
P_ROWS                      OUT       TYPES.REF_CURSOR,
P_RESULT                    OUT       NUMBER
)
AS

 -- PROCEDURE TO Get a list of all consumers from the consumer registry.
BEGIN
P_RESULT := 0;

IF P_Q IS NOT NULL THEN

   OPEN P_ROWS FOR
    SELECT	OCR_ID					"id",
    	OCR_USA_ID_REF			"user_id",
    	OCR_CONSUMER_KEY 		"consumer_key",
    	OCR_CONSUMER_SECRET 	"consumer_secret",
    	OCR_SIGNATURE_METHODS	"signature_methods",
    	OCR_SERVER_URI			"server_uri",
    	OCR_SERVER_URI_HOST		"server_uri_host",
    	OCR_SERVER_URI_PATH		"server_uri_path",
    	OCR_REQUEST_TOKEN_URI	"request_token_uri",
    	OCR_AUTHORIZE_URI		"authorize_uri",
    	OCR_ACCESS_TOKEN_URI	"access_token_uri"
    FROM OAUTH_CONSUMER_REGISTRY
    WHERE (	OCR_CONSUMER_KEY LIKE '%'|| P_Q ||'%'
						  	 OR OCR_SERVER_URI LIKE '%'|| P_Q ||'%'
						  	 OR OCR_SERVER_URI_HOST LIKE '%'|| P_Q ||'%'
						  	 OR OCR_SERVER_URI_PATH LIKE '%'|| P_Q ||'%')
						 AND (OCR_USA_ID_REF = P_USER_ID OR OCR_USA_ID_REF IS NULL)
    ORDER BY OCR_SERVER_URI_HOST, OCR_SERVER_URI_PATH;
        
ELSE

   OPEN P_ROWS FOR
    SELECT	OCR_ID					"id",
    	OCR_USA_ID_REF			"user_id",
    	OCR_CONSUMER_KEY 		"consumer_key",
    	OCR_CONSUMER_SECRET 	"consumer_secret",
    	OCR_SIGNATURE_METHODS	"signature_methods",
    	OCR_SERVER_URI			"server_uri",
    	OCR_SERVER_URI_HOST		"server_uri_host",
    	OCR_SERVER_URI_PATH		"server_uri_path",
    	OCR_REQUEST_TOKEN_URI	"request_token_uri",
    	OCR_AUTHORIZE_URI		"authorize_uri",
    	OCR_ACCESS_TOKEN_URI	"access_token_uri"
    FROM OAUTH_CONSUMER_REGISTRY
    WHERE OCR_USA_ID_REF = P_USER_ID OR OCR_USA_ID_REF IS NULL
    ORDER BY OCR_SERVER_URI_HOST, OCR_SERVER_URI_PATH;
                 
END IF;


  
   
              
EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
