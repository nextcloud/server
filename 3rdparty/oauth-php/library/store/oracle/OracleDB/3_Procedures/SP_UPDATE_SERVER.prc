CREATE OR REPLACE PROCEDURE SP_UPDATE_SERVER
(
P_CONSUMER_KEY                          IN        VARCHAR2,
P_USER_ID                               IN        NUMBER,
P_OCR_ID                                IN        NUMBER,
P_USER_IS_ADMIN                         IN        NUMBER, -- 0:NO; 1:YES;
P_OCR_CONSUMER_SECRET                   IN        VARCHAR2,
P_OCR_SERVER_URI                        IN        VARCHAR2,
P_OCR_SERVER_URI_HOST                   IN        VARCHAR2,
P_OCR_SERVER_URI_PATH                   IN        VARCHAR2,
P_OCR_REQUEST_TOKEN_URI                 IN        VARCHAR2,
P_OCR_AUTHORIZE_URI                     IN        VARCHAR2,
P_OCR_ACCESS_TOKEN_URI                  IN        VARCHAR2,
P_OCR_SIGNATURE_METHODS                 IN        VARCHAR2,
P_OCR_USA_ID_REF                        IN        NUMBER,
P_UPDATE_P_OCR_USA_ID_REF_FLAG          IN        NUMBER, -- 1:TRUE; 0:FALSE
P_RESULT                                OUT       NUMBER
)
AS

 -- Add a request token we obtained from a server.
V_OCR_ID_EXIST                                  NUMBER;
V_OCR_USA_ID_REF                                NUMBER;

V_EXC_DUPLICATE_CONSUMER_KEY              EXCEPTION;
V_EXC_UNAUTHORISED_USER_ID                EXCEPTION;
BEGIN
P_RESULT := 0;

V_OCR_USA_ID_REF := P_OCR_USA_ID_REF;

      IF P_OCR_ID IS NOT NULL THEN
        BEGIN
          SELECT 1 INTO V_OCR_ID_EXIST FROM DUAL WHERE EXISTS 
          (SELECT OCR_ID FROM OAUTH_CONSUMER_REGISTRY 
  						WHERE OCR_CONSUMER_KEY = P_CONSUMER_KEY
  						  AND OCR_ID != P_OCR_ID
  						  AND (OCR_USA_ID_REF = P_USER_ID OR OCR_USA_ID_REF IS NULL));
           
        EXCEPTION
        WHEN NO_DATA_FOUND THEN
             V_OCR_ID_EXIST :=0;
        END;
      ELSE
         BEGIN
          SELECT 1 INTO V_OCR_ID_EXIST FROM DUAL WHERE EXISTS 
          (SELECT OCR_ID FROM OAUTH_CONSUMER_REGISTRY 
  						WHERE OCR_CONSUMER_KEY = P_CONSUMER_KEY
  						  AND (OCR_USA_ID_REF = P_USER_ID OR OCR_USA_ID_REF IS NULL));
           
        EXCEPTION
        WHEN NO_DATA_FOUND THEN
             V_OCR_ID_EXIST :=0;
        END;
      END IF;
      
      IF V_OCR_ID_EXIST = 1 THEN
         RAISE V_EXC_DUPLICATE_CONSUMER_KEY;
      END IF;
      
      
      IF P_OCR_ID IS NOT NULL THEN
         IF P_USER_IS_ADMIN != 1 THEN
            BEGIN
              SELECT OCR_USA_ID_REF INTO V_OCR_USA_ID_REF
  									FROM OAUTH_CONSUMER_REGISTRY
  									WHERE OCR_ID = P_OCR_ID;
               
            EXCEPTION
            WHEN NO_DATA_FOUND THEN
                 NULL;
            END;
            
            IF V_OCR_USA_ID_REF != P_USER_ID THEN
               RAISE V_EXC_UNAUTHORISED_USER_ID;
            END IF;
         END IF; 
         
         IF P_UPDATE_P_OCR_USA_ID_REF_FLAG = 0 THEN
         
            UPDATE OAUTH_CONSUMER_REGISTRY
              SET OCR_CONSUMER_KEY  = P_CONSUMER_KEY,
              OCR_CONSUMER_SECRET 	= P_OCR_CONSUMER_SECRET,
              OCR_SERVER_URI	    	= P_OCR_SERVER_URI,
              OCR_SERVER_URI_HOST 	= P_OCR_SERVER_URI_HOST,
              OCR_SERVER_URI_PATH 	= P_OCR_SERVER_URI_PATH,
              OCR_TIMESTAMP       	= SYSDATE,
              OCR_REQUEST_TOKEN_URI	= P_OCR_REQUEST_TOKEN_URI,
              OCR_AUTHORIZE_URI		  = P_OCR_AUTHORIZE_URI,
              OCR_ACCESS_TOKEN_URI	= P_OCR_ACCESS_TOKEN_URI,
              OCR_SIGNATURE_METHODS	= P_OCR_SIGNATURE_METHODS
              WHERE OCR_ID = P_OCR_ID;
                  
         ELSIF P_UPDATE_P_OCR_USA_ID_REF_FLAG = 1 THEN
             UPDATE OAUTH_CONSUMER_REGISTRY
              SET OCR_CONSUMER_KEY  = P_CONSUMER_KEY,
              OCR_CONSUMER_SECRET 	= P_OCR_CONSUMER_SECRET,
              OCR_SERVER_URI	    	= P_OCR_SERVER_URI,
              OCR_SERVER_URI_HOST 	= P_OCR_SERVER_URI_HOST,
              OCR_SERVER_URI_PATH 	= P_OCR_SERVER_URI_PATH,
              OCR_TIMESTAMP       	= SYSDATE,
              OCR_REQUEST_TOKEN_URI	= P_OCR_REQUEST_TOKEN_URI,
              OCR_AUTHORIZE_URI		  = P_OCR_AUTHORIZE_URI,
              OCR_ACCESS_TOKEN_URI	= P_OCR_ACCESS_TOKEN_URI,
              OCR_SIGNATURE_METHODS	= P_OCR_SIGNATURE_METHODS,
              OCR_USA_ID_REF        = P_OCR_USA_ID_REF
              WHERE OCR_ID = P_OCR_ID;
                    
         END IF;
      
      ELSE
          IF P_UPDATE_P_OCR_USA_ID_REF_FLAG = 0 THEN
             V_OCR_USA_ID_REF := P_USER_ID;
          END IF;
          
          INSERT INTO OAUTH_CONSUMER_REGISTRY
          (OCR_ID, OCR_CONSUMER_KEY ,OCR_CONSUMER_SECRET, OCR_SERVER_URI, OCR_SERVER_URI_HOST, OCR_SERVER_URI_PATH,
						OCR_TIMESTAMP, OCR_REQUEST_TOKEN_URI, OCR_AUTHORIZE_URI, OCR_ACCESS_TOKEN_URI, OCR_SIGNATURE_METHODS,
						OCR_USA_ID_REF)
          VALUES
          (SEQ_OCR_ID.NEXTVAL, P_CONSUMER_KEY, P_OCR_CONSUMER_SECRET, P_OCR_SERVER_URI, P_OCR_SERVER_URI_HOST, P_OCR_SERVER_URI_PATH,
						SYSDATE, P_OCR_REQUEST_TOKEN_URI, P_OCR_AUTHORIZE_URI, P_OCR_ACCESS_TOKEN_URI, P_OCR_SIGNATURE_METHODS,
						V_OCR_USA_ID_REF);
              
      END IF;
      
      
EXCEPTION
WHEN V_EXC_DUPLICATE_CONSUMER_KEY THEN
P_RESULT := 2; -- DUPLICATE_CONSUMER_KEY
WHEN V_EXC_UNAUTHORISED_USER_ID THEN
P_RESULT := 3; -- UNAUTHORISED_USER_ID

WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
