<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Script to verify that all commits have been signed-off, if a commit doesn't end
 * with a signed-off message the script is failing.
 */
$baseDir = __DIR__ . '/../';

$pullRequestNumber = getenv('DRONE_PULL_REQUEST');

if(!is_string($pullRequestNumber) || $pullRequestNumber === '') {
	echo("The environment variable DRONE_PULL_REQUEST has no proper value.\n");
	exit(1);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/nextcloud/server/pulls/'.$pullRequestNumber.'/commits');
curl_setopt($ch, CURLOPT_USERAGENT, 'CI for Nextcloud (https://github.com/nextcloud/server)');
$response = curl_exec($ch);
curl_close($ch);

shell_exec(
	sprintf(
		'cd %s && git fetch',
		escapeshellarg($baseDir),
		escapeshellarg($pullRequestNumber)
	)
);

$decodedResponse = json_decode($response, true);
if(!is_array($decodedResponse) || count($decodedResponse) === 0) {
	echo("Could not decode JSON response from GitHub API.\n");
	exit(1);
}

// Get all commits SHAs
$commits = [];

foreach($decodedResponse as $commit) {
	if(!isset($commit['sha'])) {
		echo("No SHA specified in $commit\n");
		exit(1);
	}
	$commits[] = $commit['sha'];
}


if(count($commits) < 1) {
	echo("Could not read commits.\n");
	exit(1);
}

$notSignedCommits = [];
foreach($commits as $commit) {
	if($commit === '') {
		continue;
	}

	$signOffMessage = false;
	$commitMessageLines =
		explode(
			"\n",
			shell_exec(
				sprintf(
					'cd %s && git rev-list --format=%%B --max-count=1 %s',
					$baseDir,
					$commit
				)
			)
		);

	foreach($commitMessageLines as $line) {
		if(preg_match('/^Signed-off-by: .* <.*@.*>$/', $line)) {
			echo "$commit is signed-off with \"$line\"\n";
			$signOffMessage = true;
			continue;
		}
	}
	if($signOffMessage === true) {
		continue;
	}

	$notSignedCommits[] = $commit;
}

if($notSignedCommits !== []) {
	echo("\n");
	echo("Some commits were not signed off!\n");
	echo("Missing signatures on:\n");
	foreach ($notSignedCommits as $commit) {
		echo("- " . $commit . "\n");
	}
	echo("Build has failed\n");
	exit(1);
} else {
	exit(0);
}

