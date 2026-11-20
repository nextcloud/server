# seed N users with email straight into SQLite (users + settings/email prefs, lowercase, indexed)
N=${1:-50000}
DB=data/nextcloud.db
sqlite3 "$DB" <<SQL
WITH RECURSIVE n(i) AS (SELECT 1 UNION ALL SELECT i+1 FROM n WHERE i < $N)
INSERT INTO oc_users (uid, displayname, password, uid_lower) SELECT 'seed'||i, 'Seed '||i, '', 'seed'||i FROM n;
WITH RECURSIVE n(i) AS (SELECT 1 UNION ALL SELECT i+1 FROM n WHERE i < $N)
INSERT INTO oc_preferences (userid, appid, configkey, configvalue, lazy, type, flags, indexed)
SELECT 'seed'||i, 'settings', 'email', 'seed'||i||'@example.com', 0, 2, 2, 'seed'||i||'@example.com' FROM n;
ANALYZE;
SQL
