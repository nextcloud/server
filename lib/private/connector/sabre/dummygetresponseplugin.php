<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Connector\Sabre;

/**
 * Class DummyGetResponsePlugin is a plugin used to not show a "Not implemented"
 * error to clients that rely on verifying the functionality of the ownCloud
 * WebDAV backend using a simple GET to /.
 *
 * This is considered a legacy behaviour and implementers should consider sending
 * a PROPFIND request instead to verify whether the WebDAV component is working
 * properly.
 *
 * FIXME: Remove once clients are all compliant.
 *
 * @package OC\Connector\Sabre
 */
class DummyGetResponsePlugin extends \Sabre\DAV\ServerPlugin {
	/** @var \Sabre\DAV\Server */
	protected $server;

	/**
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	function initialize(\Sabre\DAV\Server  $server) {
		$this->server = $server;
		$this->server->on('method:GET', [$this,'httpGet'], 200);
	}

	/**
	 * @return false
	 */
	function httpGet() {
		return false;
	}
}
