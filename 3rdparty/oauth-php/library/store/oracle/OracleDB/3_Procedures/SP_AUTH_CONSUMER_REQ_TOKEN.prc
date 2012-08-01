CREATE OR REPLACE PROCEDURE SP_AUTH_CONSUMER_REQ_TOKEN
(
P_USER_ID                   IN        NUMBER,    
P_REFERRER_HOST             IN        VARCHAR2,                     
P_VERIFIER                  IN        VARCHAR2,
P_TOKEN                     IN        VARCHAR2,
P_RESULT                    OUT       NUMBER
)
AS

 -- PROCEDURE TO Fetch the consumer request token, by request token.
BEGIN
P_RESULT := 0;


UPDATE OAUTH_SERVER_TOKEN
  SET OST_AUTHORIZED    = 1,
  OST_USA_ID_REF    =  P_USER_ID,
  OST_TIMESTAMP     = SYSDATE,
  OST_REFERRER_HOST = P_REFERRER_HOST,
  OST_VERIFIER      = P_VERIFIER
  WHERE OST_TOKEN      = P_TOKEN
  AND OST_TOKEN_TYPE = 'REQUEST';
    
              
EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
