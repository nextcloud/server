<?php
###################################################################
# smb.php
# This class implements a SMB stream wrapper based on 'smbclient'
#
# Date: lun oct 22 10:35:35 CEST 2007
#
# Homepage: http://www.phpclasses.org/smb4php
#
# Copyright (c) 2007 Victor M. Varela <vmvarela@gmail.com>
# Copyright (c) 2012 Frank Karlitschek <frank@owncloud.org>
# Copyright (c) 2014 Robin McCorkell <rmccorkell@karoshi.org.uk>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# On the official website http://www.phpclasses.org/smb4php the
# license is listed as LGPL so we assume that this is
# dual-licensed GPL/LGPL
###################################################################

define ('SMB4PHP_VERSION', '0.8');

###################################################################
# CONFIGURATION SECTION - Change for your needs
###################################################################

define ('SMB4PHP_SMBCLIENT', 'smbclient');
define ('SMB4PHP_SMBOPTIONS', 'TCP_NODELAY IPTOS_LOWDELAY SO_KEEPALIVE SO_RCVBUF=8192 SO_SNDBUF=8192');
define ('SMB4PHP_AUTHMODE', 'arg'); # set to 'env' to use USER enviroment variable

###################################################################
# SMB - commands that does not need an instance
###################################################################

$GLOBALS['__smb_cache'] = array ('stat' => array (), 'dir' => array ());

class smb {

	private static $regexp = array (
		'^added interface ip=(.*) bcast=(.*) nmask=(.*)$' => 'skip',
		'Anonymous login successful' => 'skip',
		'^Domain=\[(.*)\] OS=\[(.*)\] Server=\[(.*)\]$' => 'skip',
		'^\tSharename[ ]+Type[ ]+Comment$' => 'shares',
		'^\t---------[ ]+----[ ]+-------$' => 'skip',
		'^\tServer   [ ]+Comment$' => 'servers',
		'^\t---------[ ]+-------$' => 'skip',
		'^\tWorkgroup[ ]+Master$' => 'workg',
		'^\t(.*)[ ]+(Disk|IPC)[ ]+IPC.*$' => 'skip',
		'^\tIPC\\\$(.*)[ ]+IPC' => 'skip',
		'^\t(.*)[ ]+(Disk)[ ]+(.*)$' => 'share',
		'^\t(.*)[ ]+(Printer)[ ]+(.*)$' => 'skip',
		'([0-9]+) blocks of size ([0-9]+)\. ([0-9]+) blocks available' => 'skip',
		'Got a positive name query response from ' => 'skip',
		'^(session setup failed): (.*)$' => 'error',
		'^(.*): ERRSRV - ERRbadpw' => 'error',
		'^Error returning browse list: (.*)$' => 'error',
		'^tree connect failed: (.*)$' => 'error',
		'^(Connection to .* failed)(.*)$' => 'error-connect',
		'^NT_STATUS_(.*) ' => 'error',
		'^NT_STATUS_(.*)\$' => 'error',
		'ERRDOS - ERRbadpath \((.*).\)' => 'error',
		'cd (.*): (.*)$' => 'error',
		'^cd (.*): NT_STATUS_(.*)' => 'error',
		'^\t(.*)$' => 'srvorwg',
		'^([0-9]+)[ ]+([0-9]+)[ ]+(.*)$' => 'skip',
		'^Job ([0-9]+) cancelled' => 'skip',
		'^[ ]+(.*)[ ]+([0-9]+)[ ]+(Mon|Tue|Wed|Thu|Fri|Sat|Sun)[ ](Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[ ]+([0-9]+)[ ]+([0-9]{2}:[0-9]{2}:[0-9]{2})[ ]([0-9]{4})$' => 'files',
		'^message start: ERRSRV - (ERRmsgoff)' => 'error'
	);

	function getRegexp() {
		return self::$regexp;
	}

	function parse_url ($url) {
		$pu = parse_url (trim($url));
		foreach (array ('domain', 'user', 'pass', 'host', 'port', 'path') as $i) {
			if (! isset($pu[$i])) {
				$pu[$i] = '';
			}
		}
		if (count ($userdomain = explode (';', urldecode ($pu['user']))) > 1) {
			@list ($pu['domain'], $pu['user']) = $userdomain;
		}
		$path = preg_replace (array ('/^\//', '/\/$/'), '', urldecode ($pu['path']));
		list ($pu['share'], $pu['path']) = (preg_match ('/^([^\/]+)\/(.*)/', $path, $regs))
			? array ($regs[1], preg_replace ('/\//', '\\', $regs[2]))
			: array ($path, '');
		$pu['type'] = $pu['path'] ? 'path' : ($pu['share'] ? 'share' : ($pu['host'] ? 'host' : '**error**'));
		if (! ($pu['port'] = intval(@$pu['port']))) {
			$pu['port'] = 139;
		}

		// decode user and password
		$pu['user'] = urldecode($pu['user']);
		$pu['pass'] = urldecode($pu['pass']);
		return $pu;
	}


	function look ($purl) {
		return smb::client ('-L ' . escapeshellarg ($purl['host']), $purl);
	}


	function execute ($command, $purl, $regexp = NULL) {
		return smb::client ('-d 0 '
				. escapeshellarg ('//' . $purl['host'] . '/' . $purl['share'])
				. ' -c ' . escapeshellarg ($command), $purl, $regexp
		);
	}

	function client ($params, $purl, $regexp = NULL) {

		if ($regexp === NULL) $regexp = smb::$regexp;

		if (SMB4PHP_AUTHMODE == 'env') {
			putenv("USER={$purl['user']}%{$purl['pass']}");
			$auth = '';
		} else {
			$auth = ($purl['user'] <> '' ? (' -U ' . escapeshellarg ($purl['user'] . '%' . $purl['pass'])) : '');
		}
		if ($purl['domain'] <> '') {
			$auth .= ' -W ' . escapeshellarg ($purl['domain']);
		}
		$port = ($purl['port'] <> 139 ? ' -p ' . escapeshellarg ($purl['port']) : '');
		$options = '-O ' . escapeshellarg(SMB4PHP_SMBOPTIONS);

		// this put env is necessary to read the output of smbclient correctly
		$old_locale = getenv('LC_ALL');
		putenv('LC_ALL=en_US.UTF-8');
		$output = popen ('TZ=UTC '.SMB4PHP_SMBCLIENT." -N {$auth} {$options} {$port} {$options} {$params} 2>/dev/null", 'r');
		$gotInfo = false;
		$info = array ();
		$info['info']= array ();
		$mode = '';
		while ($line = fgets ($output, 4096)) {
			list ($tag, $regs, $i) = array ('skip', array (), array ());
			reset ($regexp);
			foreach ($regexp as $r => $t) if (preg_match ('/'.$r.'/', $line, $regs)) {
				$tag = $t;
				break;
			}
			switch ($tag) {
				case 'skip':    continue;
				case 'shares':  $mode = 'shares';     break;
				case 'servers': $mode = 'servers';    break;
				case 'workg':   $mode = 'workgroups'; break;
				case 'share':
					list($name, $type) = array (
						trim(substr($line, 1, 15)),
						trim(strtolower(substr($line, 17, 10)))
					);
					$i = ($type <> 'disk' && preg_match('/^(.*) Disk/', $line, $regs))
						? array(trim($regs[1]), 'disk')
						: array($name, 'disk');
					break;
				case 'srvorwg':
					list ($name, $master) = array (
						strtolower(trim(substr($line,1,21))),
						strtolower(trim(substr($line, 22)))
					);
					$i = ($mode == 'servers') ? array ($name, "server") : array ($name, "workgroup", $master);
					break;
				case 'files':
					list ($attr, $name) = preg_match ("/^(.*)[ ]+([D|A|H|N|S|R]+)$/", trim ($regs[1]), $regs2)
						? array (trim ($regs2[2]), trim ($regs2[1]))
						: array ('', trim ($regs[1]));
					list ($his, $im) = array (
						explode(':', $regs[6]), 1 + strpos("JanFebMarAprMayJunJulAugSepOctNovDec", $regs[4]) / 3);
					$i = ($name <> '.' && $name <> '..')
						? array (
							$name,
							(strpos($attr,'D') === FALSE) ? 'file' : 'folder',
							'attr' => $attr,
							'size' => intval($regs[2]),
							'time' => mktime ($his[0], $his[1], $his[2], $im, $regs[5], $regs[7])
						)
						: array();
					break;
				case 'error':
					if(substr($regs[0],0,22)=='NT_STATUS_NO_SUCH_FILE'){
						return false;
					}elseif(substr($regs[0],0,31)=='NT_STATUS_OBJECT_NAME_COLLISION'){
						return false;
					}elseif(substr($regs[0],0,31)=='NT_STATUS_OBJECT_PATH_NOT_FOUND'){
						return false;
					}elseif(substr($regs[0],0,31)=='NT_STATUS_OBJECT_NAME_NOT_FOUND'){
						return false;
					}elseif(substr($regs[0],0,29)=='NT_STATUS_FILE_IS_A_DIRECTORY'){
						return false;
					}
					trigger_error($regs[0].' params('.$params.')', E_USER_ERROR);
				case 'error-connect':
					// connection error can happen after obtaining share list if
					// NetBIOS is disabled/blocked on the target server,
					// in which case we keep the info and continue
					if (!$gotInfo) {
						return false;
					}
			}
			if ($i) switch ($i[1]) {
				case 'file':
				case 'folder':    $info['info'][$i[0]] = $i;
				case 'disk':
				case 'server':
				case 'workgroup': $info[$i[1]][] = $i[0];
				$gotInfo = true;
			}
		}
		pclose($output);


		// restore previous locale
		if ($old_locale===false) {
			putenv('LC_ALL');
		} else {
			putenv('LC_ALL='.$old_locale);
		}

		return $info;
	}


	# stats

	function url_stat ($url, $flags = STREAM_URL_STAT_LINK) {
		if ($s = smb::getstatcache($url)) {
			return $s;
		}
		list ($stat, $pu) = array (false, smb::parse_url ($url));
		switch ($pu['type']) {
			case 'host':
				if ($o = smb::look ($pu))
					$stat = stat ("/tmp");
				else
					trigger_error ("url_stat(): list failed for host '{$pu['host']}'", E_USER_WARNING);
				break;
			case 'share':
				if (smb::execute("ls", $pu))
					$stat = stat ("/tmp");
				else
					trigger_error ("url_stat(): disk resource '{$pu['share']}' not found in '{$pu['host']}'", E_USER_WARNING);
				break;
			case 'path':
				if ($o = smb::execute ('dir "'.$pu['path'].'"', $pu)) {
					$p = explode('\\', $pu['path']);
					$name = $p[count($p)-1];
					if (isset ($o['info'][$name])) {
						$stat = smb::addstatcache ($url, $o['info'][$name]);
					} else {
						trigger_error ("url_stat(): path '{$pu['path']}' not found", E_USER_WARNING);
					}
				} else {
					return false;
// 					trigger_error ("url_stat(): dir failed for path '{$pu['path']}'", E_USER_WARNING);
				}
				break;
			default: trigger_error ('error in URL', E_USER_ERROR);
		}
		return $stat;
	}

	function addstatcache ($url, $info) {
		$url = str_replace('//', '/', $url);
		$url = rtrim($url, '/');
		global $__smb_cache;
		$is_file = (strpos ($info['attr'],'D') === FALSE);
		$s = ($is_file) ? stat ('/etc/passwd') : stat ('/tmp');
		$s[7] = $s['size'] = $info['size'];
		$s[8] = $s[9] = $s[10] = $s['atime'] = $s['mtime'] = $s['ctime'] = $info['time'];
		return $__smb_cache['stat'][$url] = $s;
	}

	function getstatcache ($url) {
		$url = str_replace('//', '/', $url);
		$url = rtrim($url, '/');
		global $__smb_cache;
		return isset ($__smb_cache['stat'][$url]) ? $__smb_cache['stat'][$url] : FALSE;
	}

	function clearstatcache ($url='') {
		$url = str_replace('//', '/', $url);
		$url = rtrim($url, '/');
		global $__smb_cache;
		if ($url == '') $__smb_cache['stat'] = array (); else unset ($__smb_cache['stat'][$url]);
	}


	# commands

	function unlink ($url) {
		$pu = smb::parse_url($url);
		if ($pu['type'] <> 'path') trigger_error('unlink(): error in URL', E_USER_ERROR);
		smb::clearstatcache ($url);
		smb_stream_wrapper::cleardircache (dirname($url));
		return smb::execute ('del "'.$pu['path'].'"', $pu);
	}

	function rename ($url_from, $url_to) {
		$replace = false;
		list ($from, $to) = array (smb::parse_url($url_from), smb::parse_url($url_to));
		if ($from['host'] <> $to['host'] ||
			$from['share'] <> $to['share'] ||
			$from['user'] <> $to['user'] ||
			$from['pass'] <> $to['pass'] ||
			$from['domain'] <> $to['domain']) {
			trigger_error('rename(): FROM & TO must be in same server-share-user-pass-domain', E_USER_ERROR);
		}
		if ($from['type'] <> 'path' || $to['type'] <> 'path') {
			trigger_error('rename(): error in URL', E_USER_ERROR);
		}
		smb::clearstatcache ($url_from);
		$cmd = '';
		// check if target file exists
		if (smb::url_stat($url_to)) {
			// delete target file first
			$cmd = 'del "' . $to['path'] . '"; ';
			$replace = true;
		}
		$cmd .= 'rename "' . $from['path'] . '" "' . $to['path'] . '"';
		$result = smb::execute($cmd, $to);
		if ($replace) {
			// clear again, else the cache will return the info
			// from the old file
			smb::clearstatcache ($url_to);
		}
		return $result !== false;
	}

	function mkdir ($url, $mode, $options) {
		$pu = smb::parse_url($url);
		if ($pu['type'] <> 'path') trigger_error('mkdir(): error in URL', E_USER_ERROR);
		return smb::execute ('mkdir "'.$pu['path'].'"', $pu)!==false;
	}

	function rmdir ($url) {
		$pu = smb::parse_url($url);
		if ($pu['type'] <> 'path') trigger_error('rmdir(): error in URL', E_USER_ERROR);
		smb::clearstatcache ($url);
		smb_stream_wrapper::cleardircache (dirname($url));
		return smb::execute ('rmdir "'.$pu['path'].'"', $pu)!==false;
	}

}

###################################################################
# SMB_STREAM_WRAPPER - class to be registered for smb:// URLs
###################################################################

class smb_stream_wrapper extends smb {

	# variables

	private $stream, $url, $parsed_url = array (), $mode, $tmpfile;
	private $need_flush = FALSE;
	private $dir = array (), $dir_index = -1;


	# directories

	function dir_opendir ($url, $options) {
		if ($d = $this->getdircache ($url)) {
			$this->dir = $d;
			$this->dir_index = 0;
			return TRUE;
		}
		$pu = smb::parse_url ($url);
		switch ($pu['type']) {
			case 'host':
				if ($o = smb::look ($pu)) {
					$this->dir = $o['disk'];
					$this->dir_index = 0;
				} else {
					trigger_error ("dir_opendir(): list failed for host '{$pu['host']}'", E_USER_WARNING);
					return false;
				}
				break;
			case 'share':
			case 'path':
				if (is_array($o = smb::execute ('dir "'.$pu['path'].'\*"', $pu))) {
					$this->dir = array_keys($o['info']);
					$this->dir_index = 0;
					$this->adddircache ($url, $this->dir);
					if(substr($url,-1,1)=='/'){
						$url=substr($url,0,-1);
					}
					foreach ($o['info'] as $name => $info) {
						smb::addstatcache($url . '/' . $name, $info);
					}
				} else {
					trigger_error ("dir_opendir(): dir failed for path '".$pu['path']."'", E_USER_WARNING);
					return false;
				}
				break;
			default:
				trigger_error ('dir_opendir(): error in URL', E_USER_ERROR);
				return false;
		}
		return TRUE;
	}

	function dir_readdir () {
		return ($this->dir_index < count($this->dir)) ? $this->dir[$this->dir_index++] : FALSE;
	}

	function dir_rewinddir () { $this->dir_index = 0; }

	function dir_closedir () { $this->dir = array(); $this->dir_index = -1; return TRUE; }


	# cache

	function adddircache ($url, $content) {
		$url = str_replace('//', '/', $url);
		$url = rtrim($url, '/');
		global $__smb_cache;
		return $__smb_cache['dir'][$url] = $content;
	}

	function getdircache ($url) {
		$url = str_replace('//', '/', $url);
		$url = rtrim($url, '/');
		global $__smb_cache;
		return isset ($__smb_cache['dir'][$url]) ? $__smb_cache['dir'][$url] : FALSE;
	}

	function cleardircache ($url='') {
		$url = str_replace('//', '/', $url);
		$url = rtrim($url, '/');
		global $__smb_cache;
		if ($url == ''){
			$__smb_cache['dir'] = array ();
		}else{
			unset ($__smb_cache['dir'][$url]);
		}
	}


	# streams

	function stream_open ($url, $mode, $options, $opened_path) {
		$this->url = $url;
		$this->mode = $mode;
		$this->parsed_url = $pu = smb::parse_url($url);
		if ($pu['type'] <> 'path') trigger_error('stream_open(): error in URL', E_USER_ERROR);
		switch ($mode) {
			case 'r':
			case 'r+':
			case 'rb':
			case 'a':
			case 'a+':  $this->tmpfile = tempnam('/tmp', 'smb.down.');
				$result = smb::execute ('get "'.$pu['path'].'" "'.$this->tmpfile.'"', $pu);
				if($result === false){
					return $result;
				}
				break;
			case 'w':
			case 'w+':
			case 'wb':
			case 'x':
			case 'x+':  $this->cleardircache();
				$this->tmpfile = tempnam('/tmp', 'smb.up.');
				$this->need_flush=true;
		}
		$this->stream = fopen ($this->tmpfile, $mode);
		return TRUE;
	}

	function stream_close () { return fclose($this->stream); }

	function stream_read ($count) { return fread($this->stream, $count); }

	function stream_write ($data) { $this->need_flush = TRUE; return fwrite($this->stream, $data); }

	function stream_eof () { return feof($this->stream); }

	function stream_tell () { return ftell($this->stream); }

	// PATCH: the wrapper must return true when fseek succeeded by returning 0.
	function stream_seek ($offset, $whence=null) { return fseek($this->stream, $offset, $whence) === 0; }

	function stream_flush () {
		if ($this->mode <> 'r' && $this->need_flush) {
			smb::clearstatcache ($this->url);
			smb::execute ('put "'.$this->tmpfile.'" "'.$this->parsed_url['path'].'"', $this->parsed_url);
			$this->need_flush = FALSE;
		}
	}

	function stream_stat () { return smb::url_stat ($this->url); }

	function __destruct () {
		if ($this->tmpfile <> '') {
			if ($this->need_flush) $this->stream_flush ();
			unlink ($this->tmpfile);

		}
	}

}

###################################################################
# Register 'smb' protocol !
###################################################################

stream_wrapper_register('smb', 'smb_stream_wrapper')
	or die ('Failed to register protocol');
