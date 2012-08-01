CREATE OR REPLACE PROCEDURE SP_ADD_LOG
(
P_RECEIVED                  IN        VARCHAR2,
P_SENT                      IN        VARCHAR2,
P_BASE_STRING               IN        VARCHAR2,
P_NOTES                     IN        VARCHAR2,
P_USA_ID_REF                IN        NUMBER,
P_REMOTE_IP                 IN        VARCHAR2,
P_RESULT                    OUT       NUMBER
)
AS

 -- PROCEDURE TO Add an entry to the log table
 
BEGIN

  P_RESULT := 0;
  
  INSERT INTO oauth_log
  (OLG_ID, olg_received, olg_sent, olg_base_string, olg_notes, olg_usa_id_ref, olg_remote_ip)
  VALUES
  (SEQ_OLG_ID.NEXTVAL, P_RECEIVED, P_SENT, P_BASE_STRING, P_NOTES, NVL(P_USA_ID_REF, 0), P_REMOTE_IP);
            

EXCEPTION
WHEN OTHERS THEN
-- CALL THE FUNCTION TO LOG ERRORS
ROLLBACK;
P_RESULT := 1; -- ERROR
END;
/
