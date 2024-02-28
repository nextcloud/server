<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Connector\Sabre;

use OC\Files\View;
use OCP\App\IAppManager;
use OCP\Files\Folder;
use OCP\Files\Node as INode;
use OCP\IGroupManager;
use OCP\ITagManager;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\PropFind;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Tree;
use Sabre\DAV\Xml\Element\Response;
use Sabre\DAV\Xml\Response\MultiStatus;

class FilesReportPlugin extends ServerPlugin {
	// namespace
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';
	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';
	public const REPORT_NAME = '{http://owncloud.org/ns}filter-files';
	public const SYSTEMTAG_PROPERTYNAME = '{http://owncloud.org/ns}systemtag';
	public const CIRCLE_PROPERTYNAME = '{http://owncloud.org/ns}circle';

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var Tree
	 */
	private $tree;

	/**
	 * @var View
	 */
	private $fileView;

	/**
	 * @var ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var ISystemTagObjectMapper
	 */
	private $tagMapper;

	/**
	 * Manager for private tags
	 *
	 * @var ITagManager
	 */
	private $fileTagger;

	/**
	 * @var IUserSession
	 */
	private $userSession;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var Folder
	 */
	private $userFolder;

	/**
	 * @var IAppManager
	 */
	private $appManager;

	/**
	 * @param Tree $tree
	 * @param View $view
	 * @param ISystemTagManager $tagManager
	 * @param ISystemTagObjectMapper $tagMapper
	 * @param ITagManager $fileTagger manager for private tags
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 * @param Folder $userFolder
	 * @param IAppManager $appManager
	 */
	public function __construct(Tree $tree,
		View $view,
		ISystemTagManager $tagManager,
		ISystemTagObjectMapper $tagMapper,
		ITagManager $fileTagger,
		IUserSession $userSession,
		IGroupManager $groupManager,
		Folder $userFolder,
		IAppManager $appManager
	) {
		$this->tree = $tree;
		$this->fileView = $view;
		$this->tagManager = $tagManager;
		$this->tagMapper = $tagMapper;
		$this->fileTagger = $fileTagger;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->userFolder = $userFolder;
		$this->appManager = $appManager;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$server->xml->namespaceMap[self::NS_OWNCLOUD] = 'oc';

		$this->server = $server;
		$this->server->on('report', [$this, 'onReport']);
	}

	/**
	 * Returns a list of reports this plugin supports.
	 *
	 * This will be used in the {DAV:}supported-report-set property.
	 *
	 * @param string $uri
	 * @return array
	 */
	public function getSupportedReportSet($uri) {
		return [self::REPORT_NAME];
	}

	/**
	 * REPORT operations to look for files
	 *
	 * @param string $reportName
	 * @param $report
	 * @param string $uri
	 * @return bool
	 * @throws BadRequest
	 * @throws PreconditionFailed
	 * @internal param $ [] $report
	 */
	public function onReport($reportName, $report, $uri) {
		$reportTargetNode = $this->server->tree->getNodeForPath($uri);
		if (!$reportTargetNode instanceof Directory || $reportName !== self::REPORT_NAME) {
			return;
		}

		$ns = '{' . $this::NS_OWNCLOUD . '}';
		$ncns = '{' . $this::NS_NEXTCLOUD . '}';
		$requestedProps = [];
		$filterRules = [];

		// parse report properties and gather filter info
		foreach ($report as $reportProps) {
			$name = $reportProps['name'];
			if ($name === $ns . 'filter-rules') {
				$filterRules = $reportProps['value'];
			} elseif ($name === '{DAV:}prop') {
				// propfind properties
				foreach ($reportProps['value'] as $propVal) {
					$requestedProps[] = $propVal['name'];
				}
			} elseif ($name === '{DAV:}limit') {
				foreach ($reportProps['value'] as $propVal) {
					if ($propVal['name'] === '{DAV:}nresults') {
						$limit = (int)$propVal['value'];
					} elseif ($propVal['name'] === $ncns . 'firstresult') {
						$offset = (int)$propVal['value'];
					}
				}
			}
		}

		if (empty($filterRules)) {
			// an empty filter would return all existing files which would be slow
			throw new BadRequest('Missing filter-rule block in request');
		}

		// gather all file ids matching filter
		try {
			$resultFileIds = $this->processFilterRulesForFileIDs($filterRules);
			// no logic in circles and favorites for paging, we always have all results, and slice later on
			$resultFileIds = array_slice($resultFileIds, $offset ?? 0, $limit ?? null);
			// fetching nodes has paging on DB level – therefore we cannot mix and slice the results, similar
			// to user backends. I.e. the final result may return more results than requested.
			$resultNodes = $this->processFilterRulesForFileNodes($filterRules, $limit ?? null, $offset ?? null);
		} catch (TagNotFoundException $e) {
			throw new PreconditionFailed('Cannot filter by non-existing tag', 0, $e);
		}

		$results = [];
		foreach ($resultNodes as $entry) {
			if ($entry) {
				$results[] = $this->wrapNode($entry);
			}
		}

		// find sabre nodes by file id, restricted to the root node path
		$additionalNodes = $this->findNodesByFileIds($reportTargetNode, $resultFileIds);
		if ($additionalNodes && $results) {
			$results = array_uintersect($results, $additionalNodes, function (Node $a, Node $b): int {
				return $a->getId() - $b->getId();
			});
		} elseif (!$results && $additionalNodes) {
			$results = $additionalNodes;
		}

		$filesUri = $this->getFilesBaseUri($uri, $reportTargetNode->getPath());
		$responses = $this->prepareResponses($filesUri, $requestedProps, $results);

		$xml = $this->server->xml->write(
			'{DAV:}multistatus',
			new MultiStatus($responses)
		);

		$this->server->httpResponse->setStatus(207);
		$this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
		$this->server->httpResponse->setBody($xml);

		return false;
	}

	/**
	 * Returns the base uri of the files root by removing
	 * the subpath from the URI
	 *
	 * @param string $uri URI from this request
	 * @param string $subPath subpath to remove from the URI
	 *
	 * @return string files base uri
	 */
	private function getFilesBaseUri(string $uri, string $subPath): string {
		$uri = trim($uri, '/');
		$subPath = trim($subPath, '/');
		if (empty($subPath)) {
			$filesUri = $uri;
		} else {
			$filesUri = substr($uri, 0, strlen($uri) - strlen($subPath));
		}
		$filesUri = trim($filesUri, '/');
		if (empty($filesUri)) {
			return '';
		}
		return '/' . $filesUri;
	}

	/**
	 * Find file ids matching the given filter rules
	 *
	 * @param array $filterRules
	 * @return array array of unique file id results
	 */
	protected function processFilterRulesForFileIDs(array $filterRules): array {
		$ns = '{' . $this::NS_OWNCLOUD . '}';
		$resultFileIds = [];
		$circlesIds = [];
		$favoriteFilter = null;
		foreach ($filterRules as $filterRule) {
			if ($filterRule['name'] === self::CIRCLE_PROPERTYNAME) {
				$circlesIds[] = $filterRule['value'];
			}
			if ($filterRule['name'] === $ns . 'favorite') {
				$favoriteFilter = true;
			}
		}

		if ($favoriteFilter !== null) {
			$resultFileIds = $this->fileTagger->load('files')->getFavorites();
			if (empty($resultFileIds)) {
				return [];
			}
		}

		if (!empty($circlesIds)) {
			$fileIds = $this->getCirclesFileIds($circlesIds);
			if (empty($resultFileIds)) {
				$resultFileIds = $fileIds;
			} else {
				$resultFileIds = array_intersect($fileIds, $resultFileIds);
			}
		}

		return $resultFileIds;
	}

	protected function processFilterRulesForFileNodes(array $filterRules, ?int $limit, ?int $offset): array {
		$systemTagIds = [];
		foreach ($filterRules as $filterRule) {
			if ($filterRule['name'] === self::SYSTEMTAG_PROPERTYNAME) {
				$systemTagIds[] = $filterRule['value'];
			}
		}

		$nodes = [];

		if (!empty($systemTagIds)) {
			$tags = $this->tagManager->getTagsByIds($systemTagIds, $this->userSession->getUser());

			// For we run DB queries per tag and require intersection, we cannot apply limit and offset for DB queries on multi tag search.
			$oneTagSearch = count($tags) === 1;
			$dbLimit = $oneTagSearch ? $limit ?? 0 : 0;
			$dbOffset = $oneTagSearch ? $offset ?? 0 : 0;

			foreach ($tags as $tag) {
				$tagName = $tag->getName();
				$tmpNodes = $this->userFolder->searchBySystemTag($tagName, $this->userSession->getUser()->getUID(), $dbLimit, $dbOffset);
				if (count($nodes) === 0) {
					$nodes = $tmpNodes;
				} else {
					$nodes = array_uintersect($nodes, $tmpNodes, function (INode $a, INode $b): int {
						return $a->getId() - $b->getId();
					});
				}
				if ($nodes === []) {
					// there cannot be a common match when nodes are empty early.
					return $nodes;
				}
			}

			if (!$oneTagSearch && ($limit !== null || $offset !== null)) {
				$nodes = array_slice($nodes, $offset, $limit);
			}
		}

		return $nodes;
	}

	/**
	 * @suppress PhanUndeclaredClassMethod
	 * @param array $circlesIds
	 * @return array
	 */
	private function getCirclesFileIds(array $circlesIds) {
		if (!$this->appManager->isEnabledForUser('circles') || !class_exists('\OCA\Circles\Api\v1\Circles')) {
			return [];
		}
		return \OCA\Circles\Api\v1\Circles::getFilesForCircles($circlesIds);
	}


	/**
	 * Prepare propfind response for the given nodes
	 *
	 * @param string $filesUri $filesUri URI leading to root of the files URI,
	 * with a leading slash but no trailing slash
	 * @param string[] $requestedProps requested properties
	 * @param Node[] nodes nodes for which to fetch and prepare responses
	 * @return Response[]
	 */
	public function prepareResponses($filesUri, $requestedProps, $nodes) {
		$responses = [];
		foreach ($nodes as $node) {
			$propFind = new PropFind($filesUri . $node->getPath(), $requestedProps);

			$this->server->getPropertiesByNode($propFind, $node);
			// copied from Sabre Server's getPropertiesForPath
			$result = $propFind->getResultForMultiStatus();
			$result['href'] = $propFind->getPath();

			$resourceType = $this->server->getResourceTypeForNode($node);
			if (in_array('{DAV:}collection', $resourceType) || in_array('{DAV:}principal', $resourceType)) {
				$result['href'] .= '/';
			}

			$responses[] = new Response(
				rtrim($this->server->getBaseUri(), '/') . $filesUri . $node->getPath(),
				$result,
			);
		}
		return $responses;
	}

	/**
	 * Find Sabre nodes by file ids
	 *
	 * @param Node $rootNode root node for search
	 * @param array $fileIds file ids
	 * @return Node[] array of Sabre nodes
	 */
	public function findNodesByFileIds(Node $rootNode, array $fileIds): array {
		if (empty($fileIds)) {
			return [];
		}
		$folder = $this->userFolder;
		if (trim($rootNode->getPath(), '/') !== '') {
			$folder = $folder->get($rootNode->getPath());
		}

		$results = [];
		foreach ($fileIds as $fileId) {
			$entry = $folder->getById($fileId);
			if ($entry) {
				$entry = current($entry);
				$results[] = $this->wrapNode($entry);
			}
		}

		return $results;
	}

	protected function wrapNode(\OCP\Files\File|\OCP\Files\Folder $node): File|Directory {
		if ($node instanceof \OCP\Files\File) {
			return new File($this->fileView, $node);
		} else {
			return new Directory($this->fileView, $node);
		}
	}

	/**
	 * Returns whether the currently logged in user is an administrator
	 */
	private function isAdmin() {
		$user = $this->userSession->getUser();
		if ($user !== null) {
			return $this->groupManager->isAdmin($user->getUID());
		}
		return false;
	}
}
