CREATE OR REPLACE PROCEDURE SP_SET_CONSUMER_ACC_TOKEN_TTL
(
P_TOKEN                        IN        VARCHAR2,
P_TOKEN_TTL                    IN        NUMBER,
P_RESULT                       OUT       NUMBER
)
AS

 -- PROCEDURE TO Set the ttl of a consumer access token.  This is done when the
 -- server receives a valid request with a xoauth_token_ttl parameter in it.
 
BEGIN

  P_RESULT := 0;
  
  UPDATE OAUTH_SERVER_TOKEN
  SET OST_TOKEN_TTL = SYSDATE + (P_TOKEN_TTL/(24*60*60))
  WHERE OST_TOKEN 	 = P_TOKEN
  AND OST_TOKEN_TYPE = 'ACCESS';
        
  
EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
