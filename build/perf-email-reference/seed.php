<?php
// usage: php seed.php data/nextcloud.db 50000 ; bulk insert users + lowercase settings/email prefs
[$_, $db, $n] = $argv;
$pdo = new PDO("sqlite:$db");
$pdo->exec('BEGIN');
$u = $pdo->prepare("INSERT INTO oc_users (uid, displayname, password, uid_lower) VALUES (?, ?, '', ?)");
$p = $pdo->prepare("INSERT INTO oc_preferences (userid, appid, configkey, configvalue, lazy, type, flags, indexed) VALUES (?, 'settings', 'email', ?, 0, 2, 2, ?)");
for ($i = 1; $i <= $n; $i++) {
	$u->execute(["seed$i", "Seed $i", "seed$i"]);
	$m = "seed$i@example.com";
	$p->execute(["seed$i", $m, $m]);
}
$pdo->exec('COMMIT');
$pdo->exec('ANALYZE');
