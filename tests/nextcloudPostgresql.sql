SELECT table_name FROM information_schema.tables WHERE table_schema='public';

CREATE USER nextcloud CREATEDB;
CREATE DATABASE nextcloud OWNER nextcloud;



select * from oc_users;

update oc_users set uid ='sagis2', uid_lower='sagis2' where uid='sagis'