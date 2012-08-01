CREATE OR REPLACE PROCEDURE SP_EXCH_CONS_REQ_FOR_ACC_TOKEN
(
P_TOKEN_TTL                    IN        NUMBER, -- IN SECOND
P_NEW_TOKEN                    IN        VARCHAR2,
P_TOKEN                        IN        VARCHAR2,
P_TOKEN_SECRET                 IN        VARCHAR2,
P_VERIFIER                     IN        VARCHAR2,
P_OUT_TOKEN_TTL                OUT       NUMBER,
P_RESULT                       OUT       NUMBER
)
AS
 
 -- PROCEDURE TO Add an unautorized request token to our server.
 
V_TOKEN_EXIST                               NUMBER;


V_EXC_NO_TOKEN_EXIST                       EXCEPTION;
BEGIN

  P_RESULT := 0;
  
  IF P_VERIFIER IS NOT NULL THEN
     
      BEGIN 
      SELECT 1 INTO V_TOKEN_EXIST FROM DUAL WHERE EXISTS
       (SELECT OST_TOKEN FROM OAUTH_SERVER_TOKEN 
               WHERE OST_TOKEN      = P_TOKEN
            AND OST_TOKEN_TYPE = 'REQUEST'
            AND OST_AUTHORIZED = 1
            AND OST_TOKEN_TTL  >= SYSDATE
            AND OST_VERIFIER = P_VERIFIER);
       EXCEPTION
       WHEN NO_DATA_FOUND THEN
            RAISE V_EXC_NO_TOKEN_EXIST;
       END;
         
        UPDATE OAUTH_SERVER_TOKEN
          SET OST_TOKEN			= P_NEW_TOKEN,
          OST_TOKEN_SECRET	= P_TOKEN_SECRET,
          OST_TOKEN_TYPE		= 'ACCESS',
          OST_TIMESTAMP		= SYSDATE,
          OST_TOKEN_TTL       = NVL(SYSDATE + (P_TOKEN_TTL/(24*60*60)), TO_DATE('9999.12.31', 'yyyy.mm.dd'))
          WHERE OST_TOKEN      = P_TOKEN
          AND OST_TOKEN_TYPE = 'REQUEST'
          AND OST_AUTHORIZED = 1
          AND OST_TOKEN_TTL  >= SYSDATE
          AND OST_VERIFIER = P_VERIFIER;
  
  ELSE
      BEGIN 
        SELECT 1 INTO V_TOKEN_EXIST FROM DUAL WHERE EXISTS
        (SELECT OST_TOKEN FROM OAUTH_SERVER_TOKEN 
             WHERE OST_TOKEN      = P_TOKEN
          AND OST_TOKEN_TYPE = 'REQUEST'
          AND OST_AUTHORIZED = 1
          AND OST_TOKEN_TTL  >= SYSDATE);
      EXCEPTION
      WHEN NO_DATA_FOUND THEN
         RAISE V_EXC_NO_TOKEN_EXIST;
      END;
        
      UPDATE OAUTH_SERVER_TOKEN
      SET OST_TOKEN			= P_NEW_TOKEN,
      OST_TOKEN_SECRET	= P_TOKEN_SECRET,
      OST_TOKEN_TYPE		= 'ACCESS',
      OST_TIMESTAMP		= SYSDATE,
      OST_TOKEN_TTL       = NVL(SYSDATE + (P_TOKEN_TTL/(24*60*60)), TO_DATE('9999.12.31', 'yyyy.mm.dd'))
      WHERE OST_TOKEN      = P_TOKEN
      AND OST_TOKEN_TYPE = 'REQUEST'
      AND OST_AUTHORIZED = 1
      AND OST_TOKEN_TTL  >= SYSDATE;
              
  
  END IF;
  
    SELECT	CASE
            WHEN OST_TOKEN_TTL >= TO_DATE('9999.12.31', 'yyyy.mm.dd') THEN NULL ELSE (OST_TOKEN_TTL - SYSDATE)*24*60*60 
    END "TOKEN_TTL" INTO P_OUT_TOKEN_TTL
    FROM OAUTH_SERVER_TOKEN
    WHERE OST_TOKEN = P_NEW_TOKEN;
              
  
  
  
  

EXCEPTION
WHEN V_EXC_NO_TOKEN_EXIST THEN
P_RESULT := 2; -- NO_TOKEN_EXIST
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
