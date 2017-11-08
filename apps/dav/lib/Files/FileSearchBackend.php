<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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

namespace OCA\DAV\Files;

use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\CachingTree;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\TagsPlugin;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Search\ISearchQuery;
use OCP\IUser;
use OCP\Share\IManager;
use Sabre\DAV\Exception\NotFound;
use SearchDAV\Backend\ISearchBackend;
use SearchDAV\Backend\SearchPropertyDefinition;
use SearchDAV\Backend\SearchResult;
use SearchDAV\XML\BasicSearch;
use SearchDAV\XML\Literal;
use SearchDAV\XML\Operator;
use SearchDAV\XML\Order;

class FileSearchBackend implements ISearchBackend {
	/** @var CachingTree */
	private $tree;

	/** @var IUser */
	private $user;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IManager */
	private $shareManager;

	/** @var View */
	private $view;

	/**
	 * FileSearchBackend constructor.
	 *
	 * @param CachingTree $tree
	 * @param IUser $user
	 * @param IRootFolder $rootFolder
	 * @param IManager $shareManager
	 * @param View $view
	 * @internal param IRootFolder $rootFolder
	 */
	public function __construct(CachingTree $tree, IUser $user, IRootFolder $rootFolder, IManager $shareManager, View $view) {
		$this->tree = $tree;
		$this->user = $user;
		$this->rootFolder = $rootFolder;
		$this->shareManager = $shareManager;
		$this->view = $view;
	}

	/**
	 * Search endpoint will be remote.php/dav
	 *
	 * @return string
	 */
	public function getArbiterPath() {
		return '';
	}

	public function isValidScope($href, $depth, $path) {
		// only allow scopes inside the dav server
		if (is_null($path)) {
			return false;
		}

		try {
			$node = $this->tree->getNodeForPath($path);
			return $node instanceof Directory;
		} catch (NotFound $e) {
			return false;
		}
	}

	public function getPropertyDefinitionsForScope($href, $path) {
		// all valid scopes support the same schema

		//todo dynamically load all propfind properties that are supported
		return [
			// queryable properties
			new SearchPropertyDefinition('{DAV:}displayname', true, false, true),
			new SearchPropertyDefinition('{DAV:}getcontenttype', true, true, true),
			new SearchPropertyDefinition('{DAV:}getlastmodified', true, true, true, SearchPropertyDefinition::DATATYPE_DATETIME),
			new SearchPropertyDefinition(FilesPlugin::SIZE_PROPERTYNAME, true, true, true, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
			new SearchPropertyDefinition(TagsPlugin::FAVORITE_PROPERTYNAME, true, true, true, SearchPropertyDefinition::DATATYPE_BOOLEAN),
			new SearchPropertyDefinition(FilesPlugin::INTERNAL_FILEID_PROPERTYNAME, true, true, false, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),

			// select only properties
			new SearchPropertyDefinition('{DAV:}resourcetype', false, true, false),
			new SearchPropertyDefinition('{DAV:}getcontentlength', false, true, false),
			new SearchPropertyDefinition(FilesPlugin::CHECKSUMS_PROPERTYNAME, false, true, false),
			new SearchPropertyDefinition(FilesPlugin::PERMISSIONS_PROPERTYNAME, false, true, false),
			new SearchPropertyDefinition(FilesPlugin::GETETAG_PROPERTYNAME, false, true, false),
			new SearchPropertyDefinition(FilesPlugin::OWNER_ID_PROPERTYNAME, false, true, false),
			new SearchPropertyDefinition(FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME, false, true, false),
			new SearchPropertyDefinition(FilesPlugin::DATA_FINGERPRINT_PROPERTYNAME, false, true, false),
			new SearchPropertyDefinition(FilesPlugin::HAS_PREVIEW_PROPERTYNAME, false, true, false, SearchPropertyDefinition::DATATYPE_BOOLEAN),
			new SearchPropertyDefinition(FilesPlugin::FILEID_PROPERTYNAME, false, true, false, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
		];
	}

	/**
	 * @param BasicSearch $search
	 * @return SearchResult[]
	 */
	public function search(BasicSearch $search) {
		if (count($search->from) !== 1) {
			throw new \InvalidArgumentException('Searching more than one folder is not supported');
		}
		$query = $this->transformQuery($search);
		$scope = $search->from[0];
		if ($scope->path === null) {
			throw new \InvalidArgumentException('Using uri\'s as scope is not supported, please use a path relative to the search arbiter instead');
		}
		$node = $this->tree->getNodeForPath($scope->path);
		if (!$node instanceof Directory) {
			throw new \InvalidArgumentException('Search is only supported on directories');
		}

		$fileInfo = $node->getFileInfo();
		$folder = $this->rootFolder->get($fileInfo->getPath());
		/** @var Folder $folder $results */
		$results = $folder->search($query);

		return array_map(function (Node $node) {
			if ($node instanceof Folder) {
				$davNode = new \OCA\DAV\Connector\Sabre\Directory($this->view, $node, $this->tree, $this->shareManager);
			} else {
				$davNode = new \OCA\DAV\Connector\Sabre\File($this->view, $node, $this->shareManager);
			}
			$path = $this->getHrefForNode($node);
			$this->tree->cacheNode($davNode, $path);
			return new SearchResult($davNode, $path);
		}, $results);
	}

	/**
	 * @param Node $node
	 * @return string
	 */
	private function getHrefForNode(Node $node) {
		$base = '/files/' . $this->user->getUID();
		return $base . $this->view->getRelativePath($node->getPath());
	}

	/**
	 * @param BasicSearch $query
	 * @return ISearchQuery
	 */
	private function transformQuery(BasicSearch $query) {
		// TODO offset, limit
		$orders = array_map([$this, 'mapSearchOrder'], $query->orderBy);
		return new SearchQuery($this->transformSearchOperation($query->where), 0, 0, $orders, $this->user);
	}

	/**
	 * @param Order $order
	 * @return ISearchOrder
	 */
	private function mapSearchOrder(Order $order) {
		return new SearchOrder($order->order === Order::ASC ? ISearchOrder::DIRECTION_ASCENDING : ISearchOrder::DIRECTION_DESCENDING, $this->mapPropertyNameToColumn($order->property));
	}

	/**
	 * @param Operator $operator
	 * @return ISearchOperator
	 */
	private function transformSearchOperation(Operator $operator) {
		list(, $trimmedType) = explode('}', $operator->type);
		switch ($operator->type) {
			case Operator::OPERATION_AND:
			case Operator::OPERATION_OR:
			case Operator::OPERATION_NOT:
				$arguments = array_map([$this, 'transformSearchOperation'], $operator->arguments);
				return new SearchBinaryOperator($trimmedType, $arguments);
			case Operator::OPERATION_EQUAL:
			case Operator::OPERATION_GREATER_OR_EQUAL_THAN:
			case Operator::OPERATION_GREATER_THAN:
			case Operator::OPERATION_LESS_OR_EQUAL_THAN:
			case Operator::OPERATION_LESS_THAN:
			case Operator::OPERATION_IS_LIKE:
				if (count($operator->arguments) !== 2) {
					throw new \InvalidArgumentException('Invalid number of arguments for ' . $trimmedType . ' operation');
				}
				if (!is_string($operator->arguments[0])) {
					throw new \InvalidArgumentException('Invalid argument 1 for ' . $trimmedType . ' operation, expected property');
				}
				if (!($operator->arguments[1] instanceof Literal)) {
					throw new \InvalidArgumentException('Invalid argument 2 for ' . $trimmedType . ' operation, expected literal');
				}
				return new SearchComparison($trimmedType, $this->mapPropertyNameToColumn($operator->arguments[0]), $this->castValue($operator->arguments[0], $operator->arguments[1]->value));
			case Operator::OPERATION_IS_COLLECTION:
				return new SearchComparison('eq', 'mimetype', ICacheEntry::DIRECTORY_MIMETYPE);
			default:
				throw new \InvalidArgumentException('Unsupported operation ' . $trimmedType . ' (' . $operator->type . ')');
		}
	}

	/**
	 * @param string $propertyName
	 * @return string
	 */
	private function mapPropertyNameToColumn($propertyName) {
		switch ($propertyName) {
			case '{DAV:}displayname':
				return 'name';
			case '{DAV:}getcontenttype':
				return 'mimetype';
			case '{DAV:}getlastmodified':
				return 'mtime';
			case FilesPlugin::SIZE_PROPERTYNAME:
				return 'size';
			case TagsPlugin::FAVORITE_PROPERTYNAME:
				return 'favorite';
			case TagsPlugin::TAGS_PROPERTYNAME:
				return 'tagname';
			case FilesPlugin::INTERNAL_FILEID_PROPERTYNAME:
				return 'fileid';
			default:
				throw new \InvalidArgumentException('Unsupported property for search or order: ' . $propertyName);
		}
	}

	private function castValue($propertyName, $value) {
		$allProps = $this->getPropertyDefinitionsForScope('', '');
		foreach ($allProps as $prop) {
			if ($prop->name === $propertyName) {
				$dataType = $prop->dataType;
				switch ($dataType) {
					case SearchPropertyDefinition::DATATYPE_BOOLEAN:
						return $value === 'yes';
					case SearchPropertyDefinition::DATATYPE_DECIMAL:
					case SearchPropertyDefinition::DATATYPE_INTEGER:
					case SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER:
						return 0 + $value;
					case SearchPropertyDefinition::DATATYPE_DATETIME:
						if (is_numeric($value)) {
							return 0 + $value;
						}
						$date = \DateTime::createFromFormat(\DateTime::ATOM, $value);
						return ($date instanceof \DateTime) ? $date->getTimestamp() : 0;
					default:
						return $value;
				}
			}
		}
		return $value;
	}
}
