CREATE OR REPLACE PROCEDURE SP_LIST_LOG
(
P_OPTION_FLAG                  IN        NUMBER, -- 0:NULL; 1:OTHERWISE
P_USA_ID                       IN        NUMBER,
P_OSR_CONSUMER_KEY             IN        VARCHAR2,
P_OCR_CONSUMER_KEY             IN        VARCHAR2,
P_OST_TOKEN                    IN        VARCHAR2,
P_OCT_TOKEN                    IN        VARCHAR2,
P_ROWS                         OUT       TYPES.REF_CURSOR,
P_RESULT                       OUT       NUMBER
)
AS

 -- PROCEDURE TO Get a page of entries from the log.  Returns the last 100 records
 -- matching the options given.
 
BEGIN

  P_RESULT := 0;
  
  IF P_OPTION_FLAG IS NULL OR P_OPTION_FLAG = 0 THEN
      OPEN P_ROWS FOR
      SELECT * FROM (
         SELECT OLG_ID "olg_id",
    							OLG_OSR_CONSUMER_KEY 	"osr_consumer_key",
    							OLG_OST_TOKEN			"ost_token",
    							OLG_OCR_CONSUMER_KEY	"ocr_consumer_key",
    							OLG_OCT_TOKEN			"oct_token",
    							OLG_USA_ID_REF			"user_id",
    							OLG_RECEIVED			"received",
    							OLG_SENT				"sent",
    							OLG_BASE_STRING			"base_string",
    							OLG_NOTES				"notes",
    							OLG_TIMESTAMP			"timestamp",
    							-- INET_NTOA(OLG_REMOTE_IP) "remote_ip"
                  OLG_REMOTE_IP "remote_ip"
    					FROM OAUTH_LOG
    					WHERE  OLG_USA_ID_REF = P_USA_ID
    					ORDER BY OLG_ID DESC
       )  WHERE ROWNUM<=100; 
  ELSE
      OPEN P_ROWS FOR
      SELECT * FROM (
          SELECT OLG_ID "olg_id",
    							OLG_OSR_CONSUMER_KEY 	"osr_consumer_key",
    							OLG_OST_TOKEN			"ost_token",
    							OLG_OCR_CONSUMER_KEY	"ocr_consumer_key",
    							OLG_OCT_TOKEN			"oct_token",
    							OLG_USA_ID_REF			"user_id",
    							OLG_RECEIVED			"received",
    							OLG_SENT				"sent",
    							OLG_BASE_STRING			"base_string",
    							OLG_NOTES				"notes",
    							OLG_TIMESTAMP			"timestamp",
    							-- INET_NTOA(OLG_REMOTE_IP) "remote_ip"
                  OLG_REMOTE_IP "remote_ip"
    					FROM OAUTH_LOG
    					WHERE  OLG_OSR_CONSUMER_KEY = P_OSR_CONSUMER_KEY
              AND OLG_OCR_CONSUMER_KEY = P_OCR_CONSUMER_KEY
              AND OLG_OST_TOKEN = P_OST_TOKEN
              AND OLG_OCT_TOKEN = P_OCT_TOKEN
              AND (OLG_USA_ID_REF IS NULL OR OLG_USA_ID_REF = P_USA_ID)
    					ORDER BY OLG_ID DESC
       )  WHERE ROWNUM<=100; 
              
  END IF;
               

EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
