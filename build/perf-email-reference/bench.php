<?php
// usage: php bench.php <reps> ; times getByEmail on a missing and an existing address
require '/srv/lib/base.php';
$m = \OCP\Server::get(\OCP\IUserManager::class);
$reps = (int)($argv[1] ?? 50);
foreach (['seed49999@example.com', 'nobody@example.com'] as $mail) {
	$m->getByEmail($mail);
	$t = [];
	for ($i = 0; $i < $reps; $i++) {
		$s = hrtime(true);
		$m->getByEmail($mail);
		$t[] = (hrtime(true) - $s) / 1e6;
	}
	sort($t);
	printf("%-24s median %.3f ms  p90 %.3f ms\n", $mail, $t[intdiv($reps, 2)], $t[intdiv($reps * 9, 10)]);
}
