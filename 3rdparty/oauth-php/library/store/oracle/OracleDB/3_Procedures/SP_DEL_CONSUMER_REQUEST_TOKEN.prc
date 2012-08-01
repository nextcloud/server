CREATE OR REPLACE PROCEDURE SP_DEL_CONSUMER_REQUEST_TOKEN
(
P_TOKEN                     IN        VARCHAR2,
P_RESULT                    OUT       NUMBER
)
AS

 -- PROCEDURE TO Delete a consumer token.  The token must be a request or authorized token.

BEGIN

  P_RESULT := 0;
  
  DELETE FROM OAUTH_SERVER_TOKEN
  WHERE OST_TOKEN 	 = P_TOKEN
  AND OST_TOKEN_TYPE = 'REQUEST';
              
  
EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
