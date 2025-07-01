<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Files;

use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OC\Files\Storage\Wrapper\Jail;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\CachingTree;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCA\DAV\Connector\Sabre\Server;
use OCA\DAV\Connector\Sabre\TagsPlugin;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Search\ISearchQuery;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\IMetadataQuery;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;
use OCP\IUser;
use OCP\Share\IManager;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use SearchDAV\Backend\ISearchBackend;
use SearchDAV\Backend\SearchPropertyDefinition;
use SearchDAV\Backend\SearchResult;
use SearchDAV\Query\Literal;
use SearchDAV\Query\Operator;
use SearchDAV\Query\Order;
use SearchDAV\Query\Query;

class FileSearchBackend implements ISearchBackend {
	public const OPERATOR_LIMIT = 100;

	public function __construct(
		private Server $server,
		private CachingTree $tree,
		private IUser $user,
		private IRootFolder $rootFolder,
		private IManager $shareManager,
		private View $view,
		private IFilesMetadataManager $filesMetadataManager,
	) {
	}

	/**
	 * Search endpoint will be remote.php/dav
	 */
	public function getArbiterPath(): string {
		return '';
	}

	public function isValidScope(string $href, $depth, ?string $path): bool {
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

	public function getPropertyDefinitionsForScope(string $href, ?string $path): array {
		// all valid scopes support the same schema

		//todo dynamically load all propfind properties that are supported
		$props = [
			// queryable properties
			new SearchPropertyDefinition('{DAV:}displayname', true, true, true),
			new SearchPropertyDefinition('{DAV:}getcontenttype', true, true, true),
			new SearchPropertyDefinition('{DAV:}getlastmodified', true, true, true, SearchPropertyDefinition::DATATYPE_DATETIME),
			new SearchPropertyDefinition(FilesPlugin::SIZE_PROPERTYNAME, true, true, true, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
			new SearchPropertyDefinition(TagsPlugin::FAVORITE_PROPERTYNAME, true, true, true, SearchPropertyDefinition::DATATYPE_BOOLEAN),
			new SearchPropertyDefinition(FilesPlugin::INTERNAL_FILEID_PROPERTYNAME, true, true, false, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
			new SearchPropertyDefinition(FilesPlugin::OWNER_ID_PROPERTYNAME, true, true, false),

			// select only properties
			new SearchPropertyDefinition('{DAV:}resourcetype', true, false, false),
			new SearchPropertyDefinition('{DAV:}getcontentlength', true, false, false),
			new SearchPropertyDefinition(FilesPlugin::CHECKSUMS_PROPERTYNAME, true, false, false),
			new SearchPropertyDefinition(FilesPlugin::PERMISSIONS_PROPERTYNAME, true, false, false),
			new SearchPropertyDefinition(FilesPlugin::GETETAG_PROPERTYNAME, true, false, false),
			new SearchPropertyDefinition(FilesPlugin::OWNER_DISPLAY_NAME_PROPERTYNAME, true, false, false),
			new SearchPropertyDefinition(FilesPlugin::DATA_FINGERPRINT_PROPERTYNAME, true, false, false),
			new SearchPropertyDefinition(FilesPlugin::HAS_PREVIEW_PROPERTYNAME, true, false, false, SearchPropertyDefinition::DATATYPE_BOOLEAN),
			new SearchPropertyDefinition(FilesPlugin::FILEID_PROPERTYNAME, true, false, false, SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER),
		];

		return array_merge($props, $this->getPropertyDefinitionsForMetadata());
	}


	private function getPropertyDefinitionsForMetadata(): array {
		$metadataProps = [];
		$metadata = $this->filesMetadataManager->getKnownMetadata();
		$indexes = $metadata->getIndexes();
		foreach ($metadata->getKeys() as $key) {
			$isIndex = in_array($key, $indexes);
			$type = match ($metadata->getType($key)) {
				IMetadataValueWrapper::TYPE_INT => SearchPropertyDefinition::DATATYPE_INTEGER,
				IMetadataValueWrapper::TYPE_FLOAT => SearchPropertyDefinition::DATATYPE_DECIMAL,
				IMetadataValueWrapper::TYPE_BOOL => SearchPropertyDefinition::DATATYPE_BOOLEAN,
				default => SearchPropertyDefinition::DATATYPE_STRING
			};
			$metadataProps[] = new SearchPropertyDefinition(
				FilesPlugin::FILE_METADATA_PREFIX . $key,
				true,
				$isIndex,
				$isIndex,
				$type
			);
		}

		return $metadataProps;
	}

	/**
	 * @param INode[] $nodes
	 * @param string[] $requestProperties
	 */
	public function preloadPropertyFor(array $nodes, array $requestProperties): void {
		$this->server->emit('preloadProperties', [$nodes, $requestProperties]);
	}

	private function getFolderForPath(?string $path = null): Folder {
		if ($path === null) {
			throw new \InvalidArgumentException('Using uri\'s as scope is not supported, please use a path relative to the search arbiter instead');
		}

		$node = $this->tree->getNodeForPath($path);

		if (!$node instanceof Directory) {
			throw new \InvalidArgumentException('Search is only supported on directories');
		}

		$fileInfo = $node->getFileInfo();

		/** @var Folder */
		return $this->rootFolder->get($fileInfo->getPath());
	}

	/**
	 * @param Query $search
	 * @return SearchResult[]
	 */
	public function search(Query $search): array {
		switch (count($search->from)) {
			case 0:
				throw new \InvalidArgumentException('You need to specify a scope for the search.');
				break;
			case 1:
				$scope = $search->from[0];
				$folder = $this->getFolderForPath($scope->path);
				$query = $this->transformQuery($search);
				$results = $folder->search($query);
				break;
			default:
				$scopes = [];
				foreach ($search->from as $scope) {
					$folder = $this->getFolderForPath($scope->path);
					$folderStorage = $folder->getStorage();
					if ($folderStorage->instanceOfStorage(Jail::class)) {
						/** @var Jail $folderStorage */
						$internalPath = $folderStorage->getUnjailedPath($folder->getInternalPath());
					} else {
						$internalPath = $folder->getInternalPath();
					}

					$scopes[] = new SearchBinaryOperator(
						ISearchBinaryOperator::OPERATOR_AND,
						[
							new SearchComparison(
								ISearchComparison::COMPARE_EQUAL,
								'storage',
								$folderStorage->getCache()->getNumericStorageId(),
								''
							),
							new SearchComparison(
								ISearchComparison::COMPARE_LIKE,
								'path',
								$internalPath . '/%',
								''
							),
						]
					);
				}

				$scopeOperators = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $scopes);
				$query = $this->transformQuery($search, $scopeOperators);
				$userFolder = $this->rootFolder->getUserFolder($this->user->getUID());
				$results = $userFolder->search($query);
		}

		/** @var SearchResult[] $nodes */
		$nodes = array_map(function (Node $node) {
			if ($node instanceof Folder) {
				$davNode = new Directory($this->view, $node, $this->tree, $this->shareManager);
			} else {
				$davNode = new File($this->view, $node, $this->shareManager);
			}
			$path = $this->getHrefForNode($node);
			$this->tree->cacheNode($davNode, $path);
			return new SearchResult($davNode, $path);
		}, $results);

		if (!$query->limitToHome()) {
			// Sort again, since the result from multiple storages is appended and not sorted
			usort($nodes, function (SearchResult $a, SearchResult $b) use ($search) {
				return $this->sort($a, $b, $search->orderBy);
			});
		}

		// If a limit is provided use only return that number of files
		if ($search->limit->maxResults !== 0) {
			$nodes = \array_slice($nodes, 0, $search->limit->maxResults);
		}

		return $nodes;
	}

	private function sort(SearchResult $a, SearchResult $b, array $orders) {
		/** @var Order $order */
		foreach ($orders as $order) {
			$v1 = $this->getSearchResultProperty($a, $order->property);
			$v2 = $this->getSearchResultProperty($b, $order->property);


			if ($v1 === null && $v2 === null) {
				continue;
			}
			if ($v1 === null) {
				return $order->order === Order::ASC ? 1 : -1;
			}
			if ($v2 === null) {
				return $order->order === Order::ASC ? -1 : 1;
			}

			$s = $this->compareProperties($v1, $v2, $order);
			if ($s === 0) {
				continue;
			}

			if ($order->order === Order::DESC) {
				$s = -$s;
			}
			return $s;
		}

		return 0;
	}

	private function compareProperties($a, $b, Order $order) {
		switch ($order->property->dataType) {
			case SearchPropertyDefinition::DATATYPE_STRING:
				return strcmp($a, $b);
			case SearchPropertyDefinition::DATATYPE_BOOLEAN:
				if ($a === $b) {
					return 0;
				}
				if ($a === false) {
					return -1;
				}
				return 1;
			default:
				if ($a === $b) {
					return 0;
				}
				if ($a < $b) {
					return -1;
				}
				return 1;
		}
	}

	private function getSearchResultProperty(SearchResult $result, SearchPropertyDefinition $property) {
		/** @var \OCA\DAV\Connector\Sabre\Node $node */
		$node = $result->node;

		switch ($property->name) {
			case '{DAV:}displayname':
				return $node->getName();
			case '{DAV:}getlastmodified':
				return $node->getLastModified();
			case FilesPlugin::SIZE_PROPERTYNAME:
				return $node->getSize();
			case FilesPlugin::INTERNAL_FILEID_PROPERTYNAME:
				return $node->getInternalFileId();
			default:
				return null;
		}
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
	 * @param Query $query
	 *
	 * @return ISearchQuery
	 */
	private function transformQuery(Query $query, ?SearchBinaryOperator $scopeOperators = null): ISearchQuery {
		$orders = array_map(function (Order $order): ISearchOrder {
			$direction = $order->order === Order::ASC ? ISearchOrder::DIRECTION_ASCENDING : ISearchOrder::DIRECTION_DESCENDING;
			if (str_starts_with($order->property->name, FilesPlugin::FILE_METADATA_PREFIX)) {
				return new SearchOrder($direction, substr($order->property->name, strlen(FilesPlugin::FILE_METADATA_PREFIX)), IMetadataQuery::EXTRA);
			} else {
				return new SearchOrder($direction, $this->mapPropertyNameToColumn($order->property));
			}
		}, $query->orderBy);

		$limit = $query->limit;
		$offset = $limit->firstResult;

		$limitHome = false;
		$ownerProp = $this->extractWhereValue($query->where, FilesPlugin::OWNER_ID_PROPERTYNAME, Operator::OPERATION_EQUAL);
		if ($ownerProp !== null) {
			if ($ownerProp === $this->user->getUID()) {
				$limitHome = true;
			} else {
				throw new \InvalidArgumentException("Invalid search value for '{http://owncloud.org/ns}owner-id', only the current user id is allowed");
			}
		}

		$operatorCount = $this->countSearchOperators($query->where);
		if ($operatorCount > self::OPERATOR_LIMIT) {
			throw new \InvalidArgumentException('Invalid search query, maximum operator limit of ' . self::OPERATOR_LIMIT . ' exceeded, got ' . $operatorCount . ' operators');
		}

		/** @var SearchBinaryOperator|SearchComparison */
		$queryOperators = $this->transformSearchOperation($query->where);
		if ($scopeOperators === null) {
			$operators = $queryOperators;
		} else {
			$operators = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [$queryOperators, $scopeOperators]);
		}

		return new SearchQuery(
			$operators,
			(int)$limit->maxResults,
			$offset,
			$orders,
			$this->user,
			$limitHome
		);
	}

	private function countSearchOperators(Operator $operator): int {
		switch ($operator->type) {
			case Operator::OPERATION_AND:
			case Operator::OPERATION_OR:
			case Operator::OPERATION_NOT:
				/** @var Operator[] $arguments */
				$arguments = $operator->arguments;
				return array_sum(array_map([$this, 'countSearchOperators'], $arguments));
			case Operator::OPERATION_EQUAL:
			case Operator::OPERATION_GREATER_OR_EQUAL_THAN:
			case Operator::OPERATION_GREATER_THAN:
			case Operator::OPERATION_LESS_OR_EQUAL_THAN:
			case Operator::OPERATION_LESS_THAN:
			case Operator::OPERATION_IS_LIKE:
			case Operator::OPERATION_IS_COLLECTION:
			default:
				return 1;
		}
	}

	/**
	 * @param Operator $operator
	 * @return ISearchOperator
	 */
	private function transformSearchOperation(Operator $operator) {
		[, $trimmedType] = explode('}', $operator->type);
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
				if (!($operator->arguments[1] instanceof Literal)) {
					throw new \InvalidArgumentException('Invalid argument 2 for ' . $trimmedType . ' operation, expected literal');
				}
				$value = $operator->arguments[1]->value;
				// no break
			case Operator::OPERATION_IS_DEFINED:
				if (!($operator->arguments[0] instanceof SearchPropertyDefinition)) {
					throw new \InvalidArgumentException('Invalid argument 1 for ' . $trimmedType . ' operation, expected property');
				}
				$property = $operator->arguments[0];

				if (str_starts_with($property->name, FilesPlugin::FILE_METADATA_PREFIX)) {
					$field = substr($property->name, strlen(FilesPlugin::FILE_METADATA_PREFIX));
					$extra = IMetadataQuery::EXTRA;
				} else {
					$field = $this->mapPropertyNameToColumn($property);
				}

				try {
					$castedValue = $this->castValue($property, $value ?? '');
				} catch (\Error $e) {
					throw new \InvalidArgumentException('Invalid property value for ' . $property->name, previous: $e);
				}

				return new SearchComparison(
					$trimmedType,
					$field,
					$castedValue,
					$extra ?? ''
				);

			case Operator::OPERATION_IS_COLLECTION:
				return new SearchComparison('eq', 'mimetype', ICacheEntry::DIRECTORY_MIMETYPE);
			default:
				throw new \InvalidArgumentException('Unsupported operation ' . $trimmedType . ' (' . $operator->type . ')');
		}
	}

	/**
	 * @param SearchPropertyDefinition $property
	 * @return string
	 */
	private function mapPropertyNameToColumn(SearchPropertyDefinition $property) {
		switch ($property->name) {
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
				throw new \InvalidArgumentException('Unsupported property for search or order: ' . $property->name);
		}
	}

	private function castValue(SearchPropertyDefinition $property, $value) {
		if ($value === '') {
			return '';
		}

		switch ($property->dataType) {
			case SearchPropertyDefinition::DATATYPE_BOOLEAN:
				return $value === 'yes';
			case SearchPropertyDefinition::DATATYPE_DECIMAL:
			case SearchPropertyDefinition::DATATYPE_INTEGER:
			case SearchPropertyDefinition::DATATYPE_NONNEGATIVE_INTEGER:
				return 0 + $value;
			case SearchPropertyDefinition::DATATYPE_DATETIME:
				if (is_numeric($value)) {
					return max(0, 0 + $value);
				}
				$date = \DateTime::createFromFormat(\DateTimeInterface::ATOM, (string)$value);
				return ($date instanceof \DateTime && $date->getTimestamp() !== false) ? $date->getTimestamp() : 0;
			default:
				return $value;
		}
	}

	/**
	 * Get a specific property from the were clause
	 */
	private function extractWhereValue(Operator &$operator, string $propertyName, string $comparison, bool $acceptableLocation = true): ?string {
		switch ($operator->type) {
			case Operator::OPERATION_AND:
			case Operator::OPERATION_OR:
			case Operator::OPERATION_NOT:
				foreach ($operator->arguments as &$argument) {
					$value = $this->extractWhereValue($argument, $propertyName, $comparison, $acceptableLocation && $operator->type === Operator::OPERATION_AND);
					if ($value !== null) {
						return $value;
					}
				}
				return null;
			case Operator::OPERATION_EQUAL:
			case Operator::OPERATION_GREATER_OR_EQUAL_THAN:
			case Operator::OPERATION_GREATER_THAN:
			case Operator::OPERATION_LESS_OR_EQUAL_THAN:
			case Operator::OPERATION_LESS_THAN:
			case Operator::OPERATION_IS_LIKE:
				if ($operator->arguments[0]->name === $propertyName) {
					if ($operator->type === $comparison) {
						if ($acceptableLocation) {
							if ($operator->arguments[1] instanceof Literal) {
								$value = $operator->arguments[1]->value;

								// to remove the comparison from the query, we replace it with an empty AND
								$operator = new Operator(Operator::OPERATION_AND);

								return $value;
							} else {
								throw new \InvalidArgumentException("searching by '$propertyName' is only allowed with a literal value");
							}
						} else {
							throw new \InvalidArgumentException("searching by '$propertyName' is not allowed inside a '{DAV:}or' or '{DAV:}not'");
						}
					} else {
						throw new \InvalidArgumentException("searching by '$propertyName' is only allowed inside a '$comparison'");
					}
				} else {
					return null;
				}
				// no break
			default:
				return null;
		}
	}
}
