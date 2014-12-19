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

/**
 * Class OC_Connector_Sabre_Server
 *
 * This class reimplements some methods from @see \Sabre\DAV\Server.
 *
 * Basically we add handling of depth: infinity.
 *
 * The right way to handle this would have been to submit a patch to the upstream project
 * and grab the corresponding version one merged.
 *
 * Due to time constrains and the limitations where we don't want to upgrade 3rdparty code in
 * this stage of the release cycle we did choose this approach.
 *
 * For ownCloud 7 we will upgrade SabreDAV and submit the patch - if needed.
 *
 * @see \Sabre\DAV\Server
 */
class OC_Connector_Sabre_Server extends Sabre\DAV\Server {

	/**
	 * @var string
	 */
	private $overLoadedUri = null;

	/**
	 * @var boolean
	 */
	private $ignoreRangeHeader = false;

	/**
	 * @see \Sabre\DAV\Server
	 */
	public function __construct($treeOrNode = null) {
		parent::__construct($treeOrNode);
		self::$exposeVersion = false;
	}

	public function getRequestUri() {

		if (!is_null($this->overLoadedUri)) {
			return $this->overLoadedUri;
		}

		return parent::getRequestUri();
	}

	public function checkPreconditions($handleAsGET = false) {
		// chunked upload handling
		if (isset($_SERVER['HTTP_OC_CHUNKED'])) {
			$filePath = parent::getRequestUri();
			list($path, $name) = \Sabre\DAV\URLUtil::splitPath($filePath);
			$info = OC_FileChunking::decodeName($name);
			if (!empty($info)) {
				$filePath = $path . '/' . $info['name'];
				$this->overLoadedUri = $filePath;
			}
		}

		$result = parent::checkPreconditions($handleAsGET);
		$this->overLoadedUri = null;
		return $result;
	}

	public function getHTTPRange() {
		if ($this->ignoreRangeHeader) {
			return null;
		}
		return parent::getHTTPRange();
	}

	protected function httpGet($uri) {
		$range = $this->getHTTPRange();

		if (OC_App::isEnabled('files_encryption') && $range) {
			// encryption does not support range requests
			$this->ignoreRangeHeader = true;	
		}
		return parent::httpGet($uri);
	}

	/**
	 * @see \Sabre\DAV\Server
	 */
	protected function httpPropfind($uri) {

		// $xml = new \Sabre\DAV\XMLReader(file_get_contents('php://input'));
		$requestedProperties = $this->parsePropFindRequest($this->httpRequest->getBody(true));

		$depth = $this->getHTTPDepth(1);
		// The only two options for the depth of a propfind is 0 or 1
		// if ($depth!=0) $depth = 1;

		$newProperties = $this->getPropertiesForPath($uri,$requestedProperties,$depth);

		// This is a multi-status response
		$this->httpResponse->sendStatus(207);
		$this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
		$this->httpResponse->setHeader('Vary','Brief,Prefer');

		// Normally this header is only needed for OPTIONS responses, however..
		// iCal seems to also depend on these being set for PROPFIND. Since
		// this is not harmful, we'll add it.
		$features = array('1','3', 'extended-mkcol');
		foreach($this->plugins as $plugin) {
			$features = array_merge($features,$plugin->getFeatures());
		}

		$this->httpResponse->setHeader('DAV',implode(', ',$features));

		$prefer = $this->getHTTPPrefer();
		$minimal = $prefer['return-minimal'];

		$data = $this->generateMultiStatus($newProperties, $minimal);
		$this->httpResponse->sendBody($data);

	}

	/**
	 * Small helper to support PROPFIND with DEPTH_INFINITY.
	 * @param string $path
	 */
	private function addPathNodesRecursively(&$nodes, $path) {
		foreach($this->tree->getChildren($path) as $childNode) {
			$nodes[$path . '/' . $childNode->getName()] = $childNode;
			if ($childNode instanceof \Sabre\DAV\ICollection)
				$this->addPathNodesRecursively($nodes, $path . '/' . $childNode->getName());
		}
	}

	public function getPropertiesForPath($path, $propertyNames = array(), $depth = 0) {

		//	if ($depth!=0) $depth = 1;

		$path = rtrim($path,'/');

		// This event allows people to intercept these requests early on in the
		// process.
		//
		// We're not doing anything with the result, but this can be helpful to
		// pre-fetch certain expensive live properties.
		$this->broadCastEvent('beforeGetPropertiesForPath', array($path, $propertyNames, $depth));

		$returnPropertyList = array();

		$parentNode = $this->tree->getNodeForPath($path);
		$nodes = array(
			$path => $parentNode
		);
		if ($depth==1 && $parentNode instanceof \Sabre\DAV\ICollection) {
			foreach($this->tree->getChildren($path) as $childNode)
				$nodes[$path . '/' . $childNode->getName()] = $childNode;
		} else if ($depth == self::DEPTH_INFINITY && $parentNode instanceof \Sabre\DAV\ICollection) {
			$this->addPathNodesRecursively($nodes, $path);
		}

		// If the propertyNames array is empty, it means all properties are requested.
		// We shouldn't actually return everything we know though, and only return a
		// sensible list.
		$allProperties = count($propertyNames)==0;

		foreach($nodes as $myPath=>$node) {

			$currentPropertyNames = $propertyNames;

			$newProperties = array(
				'200' => array(),
				'404' => array(),
			);

			if ($allProperties) {
				// Default list of propertyNames, when all properties were requested.
				$currentPropertyNames = array(
					'{DAV:}getlastmodified',
					'{DAV:}getcontentlength',
					'{DAV:}resourcetype',
					'{DAV:}quota-used-bytes',
					'{DAV:}quota-available-bytes',
					'{DAV:}getetag',
					'{DAV:}getcontenttype',
				);
			}

			// If the resourceType was not part of the list, we manually add it
			// and mark it for removal. We need to know the resourcetype in order
			// to make certain decisions about the entry.
			// WebDAV dictates we should add a / and the end of href's for collections
			$removeRT = false;
			if (!in_array('{DAV:}resourcetype',$currentPropertyNames)) {
				$currentPropertyNames[] = '{DAV:}resourcetype';
				$removeRT = true;
			}

			$result = $this->broadcastEvent('beforeGetProperties',array($myPath, $node, &$currentPropertyNames, &$newProperties));
			// If this method explicitly returned false, we must ignore this
			// node as it is inaccessible.
			if ($result===false) continue;

			if (count($currentPropertyNames) > 0) {

				if ($node instanceof \Sabre\DAV\IProperties) {
					$nodeProperties = $node->getProperties($currentPropertyNames);

					// The getProperties method may give us too much,
					// properties, in case the implementor was lazy.
					//
					// So as we loop through this list, we will only take the
					// properties that were actually requested and discard the
					// rest.
					foreach($currentPropertyNames as $k=>$currentPropertyName) {
						if (isset($nodeProperties[$currentPropertyName])) {
							unset($currentPropertyNames[$k]);
							$newProperties[200][$currentPropertyName] = $nodeProperties[$currentPropertyName];
						}
					}

				}

			}

			foreach($currentPropertyNames as $prop) {

				if (isset($newProperties[200][$prop])) continue;

				switch($prop) {
					case '{DAV:}getlastmodified'       : if ($node->getLastModified()) $newProperties[200][$prop] = new \Sabre\DAV\Property\GetLastModified($node->getLastModified()); break;
					case '{DAV:}getcontentlength'      :
						if ($node instanceof \Sabre\DAV\IFile) {
							$size = $node->getSize();
							if (!is_null($size)) {
								$newProperties[200][$prop] = 0 + $size;
							}
						}
						break;
					case '{DAV:}quota-used-bytes'      :
						if ($node instanceof \Sabre\DAV\IQuota) {
							$quotaInfo = $node->getQuotaInfo();
							$newProperties[200][$prop] = $quotaInfo[0];
						}
						break;
					case '{DAV:}quota-available-bytes' :
						if ($node instanceof \Sabre\DAV\IQuota) {
							$quotaInfo = $node->getQuotaInfo();
							$newProperties[200][$prop] = $quotaInfo[1];
						}
						break;
					case '{DAV:}getetag'               : if ($node instanceof \Sabre\DAV\IFile && $etag = $node->getETag())  $newProperties[200][$prop] = $etag; break;
					case '{DAV:}getcontenttype'        : if ($node instanceof \Sabre\DAV\IFile && $ct = $node->getContentType())  $newProperties[200][$prop] = $ct; break;
					case '{DAV:}supported-report-set'  :
						$reports = array();
						foreach($this->plugins as $plugin) {
							$reports = array_merge($reports, $plugin->getSupportedReportSet($myPath));
						}
						$newProperties[200][$prop] = new \Sabre\DAV\Property\SupportedReportSet($reports);
						break;
					case '{DAV:}resourcetype' :
						$newProperties[200]['{DAV:}resourcetype'] = new \Sabre\DAV\Property\ResourceType();
						foreach($this->resourceTypeMapping as $className => $resourceType) {
							if ($node instanceof $className) $newProperties[200]['{DAV:}resourcetype']->add($resourceType);
						}
						break;

				}

				// If we were unable to find the property, we will list it as 404.
				if (!$allProperties && !isset($newProperties[200][$prop])) $newProperties[404][$prop] = null;

			}

			$this->broadcastEvent('afterGetProperties',array(trim($myPath,'/'),&$newProperties, $node));

			$newProperties['href'] = trim($myPath,'/');

			// Its is a WebDAV recommendation to add a trailing slash to collectionnames.
			// Apple's iCal also requires a trailing slash for principals (rfc 3744), though this is non-standard.
			if ($myPath!='' && isset($newProperties[200]['{DAV:}resourcetype'])) {
				$rt = $newProperties[200]['{DAV:}resourcetype'];
				if ($rt->is('{DAV:}collection') || $rt->is('{DAV:}principal')) {
					$newProperties['href'] .='/';
				}
			}

			// If the resourcetype property was manually added to the requested property list,
			// we will remove it again.
			if ($removeRT) unset($newProperties[200]['{DAV:}resourcetype']);

			$returnPropertyList[] = $newProperties;

		}

		return $returnPropertyList;

	}
}
