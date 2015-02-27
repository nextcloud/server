<?php
/**
 * ownCloud / SabreDAV
 *
 * @author Markus Goetz
 *
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

namespace OC\Connector\Sabre;

/**
 * Class \OC\Connector\Sabre\Server
 *
 * This class overrides some methods from @see \Sabre\DAV\Server.
 *
 * @see \Sabre\DAV\Server
 */
class Server extends \Sabre\DAV\Server {

	/**
	 * @see \Sabre\DAV\Server
	 */
	public function __construct($treeOrNode = null) {
		parent::__construct($treeOrNode);
		self::$exposeVersion = false;
		$this->enablePropfindDepthInfinity = true;
	}
}
