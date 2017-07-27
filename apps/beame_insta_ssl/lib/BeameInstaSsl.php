<?php
/**
 * @copyright Copyright (c) 2017 Beame.io LTD <support@beame.io>
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

namespace OCA\BeameInstaSsl;

class CommandRunError extends \Exception {
	public function __construct($command, $exit_code, $stderr) {
		$this->command = $command;
		$this->exit_code = $exit_code;
		$this->stderr = $stderr;
	}
}

function getHome() {
	$home = \OC::$server->getConfig()->getSystemValue('datadirectory') . '/beame-insta-ssl';
	// TODO: move it somewhere so it runs once, on installation.
	mkdir($home);
	return $home;
}


function runCommand($cmd) {
	$tmp = tempnam(sys_get_temp_dir(), 'beame-insta-ssl-stderr');
	$cmd = "env HOME=".escapeshellarg(getHome()). " beame-insta-ssl $cmd --format json 2>".escapeshellarg($tmp);
	exec($cmd, $output, $exit_code);
	$stderr = file_get_contents($tmp);
	unlink($tmp);
	if($exit_code !== 0) {
		error_log("[BeameInstaSsl] Failed to run beame-insta-ssl: $exit_code, $stderr");
		return new CommandRunError($cmd, $exit_code, $stderr);
	}
	return json_decode(join('', $output));
}

function startTunnel($fqdn) {
	$config = \OC::$server->getConfig();
	$trusted_domains = $config->getSystemValue('trusted_domains', []);
	if(!in_array($fqdn, $trusted_domains)) {
		$j = json_encode($trusted_domains);
		error_log("[BeameInstaSsl] adding to trusted domains: $fqdn (currently trusted domains are: $j)");
		$trusted_domains[] = $fqdn;
		$config->setSystemValue('trusted_domains', $trusted_domains);
	}

	$_cmd .= ' (beame-insta-ssl tunnel make --fqdn "$1" --dst 80 --proto http & echo $! >"$2") > >(logger -t beame-insta-ssl-stdout) 2> >(logger -t beame-insta-ssl-stderr)';
	$_fqdn = escapeshellarg($fqdn);
	$_pid_file = getHome() . '/run.pid';
	exec("env HOME=".escapeshellarg(getHome())." nohup bash -c '$_cmd' -- $_fqdn $_pid_file >/dev/null 2>&1");
	// putFile('run.pid', $pid);
	putFile('run.fqdn', $fqdn);
}

function stopTunnel() {
	$state = checkTunnelRunning();
	if($state['mode'] !== 'running') {
		return false;
	}
	exec("kill -9 ".escapeshellarg($state['run_pid']));
	cleanupRunFiles();
}

function checkTunnelRunning() {
	$ret = [];
	$run_pid = getFile('run.pid');
	if($run_pid === null) {
		$creds = listCredentials();
		error_log('p0'.json_encode($creds->exit_code));
		if(($creds instanceof CommandRunError) && ($creds->exit_code === 127)) {
			error_log('p1');
			$ret['mode'] = 'not-installed';
		} else {
			if($creds) {
				$ret['mode'] = 'stopped';
				$ret['creds'] = $creds;
			} else {
				$ret['mode'] = 'nocreds';
			}
		}
	} else {
		$ret['run_pid'] = $run_pid;
		$ret['run_fqdn'] = getFile('run.fqdn');
		$_run_pid = (int) $run_pid;
		exec("ps aux | grep '\<$_run_pid\>.*beame-insta-ssl tunnel make' | grep -v grep", $output, $code);
		if($code === 0) {
			$ret['mode'] = 'running';
			// Without trailing slash , there is redirect to HTTP
			$l = 'https://'.$ret['run_fqdn'].\OC::$WEBROOT;
			if(substr($l, -1) !== '/') {
				$l .= '/';
			}
			$ret['run_link'] = $l;
		} else {
			$ret['mode'] = 'stale';
		}
	}
	return $ret;
}

function cleanupRunFiles() {
	foreach(['run.pid', 'run.fqdn'] as $fname) {
		$f = getHome() . '/' . $fname;
		if(file_exists($f)) {
			unlink($f);
		}
	}
}

function listCredentials() {
	return runCommand("creds signers");
}

function getFile($fname) {
	$f = getHome() . '/' . $fname;
	if(!file_exists($f)) {
		return null;
	}
	$ret = file_get_contents($f);
	if($ret === false) {
		return null;
	}
	// In case someone decides to "echo something >run.fqdn"
	return trim($ret);
}

function putFile($fname, $data) {
	$f = getHome() . '/' . $fname;
	file_put_contents($f, $data);
}

