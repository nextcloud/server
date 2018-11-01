<?php
declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
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


namespace OCP\FullTextSearch\Model;


use JsonSerializable;

/**
 * Class IndexDocument
 *
 * This is one of the main class of the FullTextSearch, used as a data transfer
 * object. An IndexDocument is created to manage documents around FullTextSearch,
 * during an index and during a search.
 * The uniqueness of an IndexDocument is made by the Id of the Content Provider
 * and the Id of the original document within the Content Provider.
 *
 * We will call original document the source from which the IndexDocument is
 * generated. As an example, an original document can be a file, a mail, ...
 *
 * @since 15.0.0
 *
 * @package OC\FullTextSearch\Model
 */
class IndexDocument implements JsonSerializable {


	const NOT_ENCODED = 0;
	const ENCODED_BASE64 = 1;


	/** @var string */
	protected $id = '';

	/** @var string */
	protected $providerId = '';

	/** @var DocumentAccess */
	protected $access;

	/** @var IIndex */
	protected $index;

	/** @var int */
	protected $modifiedTime = 0;

	/** @var string */
	protected $source = '';

	/** @var array */
	protected $tags = [];

	/** @var array */
	protected $metaTags = [];

	/** @var array */
	protected $subTags = [];

	/** @var string */
	protected $title = '';

	/** @var string */
	protected $content = '';

	/** @var string */
	protected $hash = '';

	/** @var array */
	protected $parts = [];

	/** @var string */
	protected $link = '';

	/** @var array */
	protected $more = [];

	/** @var array */
	protected $excerpts = [];

	/** @var string */
	protected $score = '';

	/** @var array */
	protected $info = [];

	/** @var int */
	protected $contentEncoded = 0;


	/**
	 * IndexDocument constructor.
	 *
	 * On creation, we assure the uniqueness of the object using the providerId
	 * and the Id of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @param string $providerId
	 * @param string $documentId
	 */
	public function __construct(string $providerId, string $documentId) {
		$this->providerId = $providerId;
		$this->id = $documentId;
	}


	/**
	 * Returns the Id of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	final public function getId(): string {
		return $this->id;
	}


	/**
	 * Returns the Id of the provider.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	final public function getProviderId(): string {
		return $this->providerId;
	}


	/**
	 * Set the Index related to the IndexDocument.
	 *
	 * @see IIndex
	 *
	 * @since 15.0.0
	 *
	 * @param IIndex $index
	 *
	 * @return IndexDocument
	 */
	final public function setIndex(IIndex $index): IndexDocument {
		$this->index = $index;

		return $this;
	}

	/**
	 * Get the Index.
	 *
	 * @since 15.0.0
	 *
	 * @return IIndex
	 */
	final public function getIndex(): IIndex {
		return $this->index;
	}


	/**
	 * Set the modified time of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @param int $modifiedTime
	 *
	 * @return IndexDocument
	 */
	final public function setModifiedTime(int $modifiedTime): IndexDocument {
		$this->modifiedTime = $modifiedTime;

		return $this;
	}

	/**
	 * Get the modified time of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return int
	 */
	final public function getModifiedTime(): int {
		return $this->modifiedTime;
	}

	/**
	 * Check if the original document of the IndexDocument is older than $time.
	 *
	 * @since 15.0.0
	 *
	 * @param int $time
	 *
	 * @return bool
	 */
	final public function isOlderThan(int $time): bool {
		return ($this->modifiedTime < $time);
	}


	/**
	 * Set the read rights of the original document using a DocumentAccess.
	 *
	 * @see DocumentAccess
	 *
	 * @since 15.0.0
	 *
	 * @param DocumentAccess $access
	 *
	 * @return $this
	 */
	final public function setAccess(DocumentAccess $access) {
		$this->access = $access;

		return $this;
	}

	/**
	 * Get the DocumentAccess related to the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return DocumentAccess
	 */
	final public function getAccess(): DocumentAccess {
		return $this->access;
	}


	/**
	 * Add a tag to the list.
	 *
	 * @since 15.0.0
	 *
	 * @param string $tag
	 *
	 * @return IndexDocument
	 */
	final public function addTag(string $tag): IndexDocument {
		$this->tags[] = $tag;

		return $this;
	}

	/**
	 * Set the list of tags assigned to the original document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $tags
	 *
	 * @return IndexDocument
	 */
	final public function setTags(array $tags): IndexDocument {
		$this->tags = $tags;

		return $this;
	}

	/**
	 * Get the list of tags assigned to the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	final public function getTags(): array {
		return $this->tags;
	}


	/**
	 * Add a meta tag to the list.
	 *
	 * @since 15.0.0
	 *
	 * @param string $tag
	 *
	 * @return IndexDocument
	 */
	final public function addMetaTag(string $tag): IndexDocument {
		$this->metaTags[] = $tag;

		return $this;
	}

	/**
	 * Set the list of meta tags assigned to the original document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $tags
	 *
	 * @return IndexDocument
	 */
	final public function setMetaTags(array $tags): IndexDocument {
		$this->metaTags = $tags;

		return $this;
	}

	/**
	 * Get the list of meta tags assigned to the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	final public function getMetaTags(): array {
		return $this->metaTags;
	}


	/**
	 * Add a sub tag to the list.
	 *
	 * @since 15.0.0
	 *
	 * @param string $sub
	 * @param string $tag
	 *
	 * @return IndexDocument
	 */
	final public function addSubTag(string $sub, string $tag): IndexDocument {
		$this->subTags[$sub] = $tag;

		return $this;
	}

	/**
	 * Set the list of sub tags assigned to the original document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $tags
	 *
	 * @return IndexDocument
	 */
	final public function setSubTags(array $tags): IndexDocument {
		$this->subTags = $tags;

		return $this;
	}

	/**
	 * Get the list of sub tags assigned to the original document.
	 * If $formatted is true, the result will be formatted in a one
	 * dimensional array.
	 *
	 * @since 15.0.0
	 *
	 * @param bool $formatted
	 *
	 * @return array
	 */
	final public function getSubTags(bool $formatted = false): array {
		if ($formatted === false) {
			return $this->subTags;
		}

		$subTags = [];
		$ak = array_keys($this->subTags);
		foreach ($ak as $source) {
			$tags = $this->subTags[$source];
			foreach ($tags as $tag) {
				$subTags[] = $source . '_' . $tag;
			}
		}

		return $subTags;
	}


	/**
	 * Set the source of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @param string $source
	 *
	 * @return IndexDocument
	 */
	final public function setSource(string $source): IndexDocument {
		$this->source = $source;

		return $this;
	}

	/**
	 * Get the source of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	final public function getSource(): string {
		return $this->source;
	}


	/**
	 * Set the title of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @param string $title
	 *
	 * @return IndexDocument
	 */
	final public function setTitle(string $title): IndexDocument {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get the title of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	final public function getTitle(): string {
		return $this->title;
	}


	/**
	 * Set the content of the document.
	 * $encoded can be NOT_ENCODED or ENCODED_BASE64 if the content is raw or
	 * encoded in base64.
	 *
	 * @since 15.0.0
	 *
	 * @param string $content
	 * @param int $encoded
	 *
	 * @return IndexDocument
	 */
	final public function setContent(string $content, int $encoded = 0): IndexDocument {
		$this->content = $content;
		$this->contentEncoded = $encoded;

		return $this;
	}

	/**
	 * Get the content of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	final public function getContent(): string {
		return $this->content;
	}

	/**
	 * Returns the type of the encoding on the content.
	 *
	 * @since 15.0.0
	 *
	 * @return int
	 */
	final public function isContentEncoded(): int {
		return $this->contentEncoded;
	}

	/**
	 * Return the size of the content.
	 *
	 * @since 15.0.0
	 *
	 * @return int
	 */
	final public function getContentSize(): int {
		return strlen($this->getContent());
	}


	/**
	 * Generate an hash, based on the content of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return IndexDocument
	 */
	final public function initHash(): IndexDocument {
		if ($this->getContent() === '' || is_null($this->getContent())) {
			return $this;
		}

		$this->hash = hash("md5", $this->getContent());

		return $this;
	}

	/**
	 * Set the hash of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @param string $hash
	 *
	 * @return IndexDocument
	 */
	final public function setHash(string $hash): IndexDocument {
		$this->hash = $hash;

		return $this;
	}

	/**
	 * Get the hash of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	final public function getHash(): string {
		return $this->hash;
	}


	/**
	 * Add a part, identified by a string, and its content.
	 *
	 * It is strongly advised to use alphanumerical chars with no space in the
	 * $part string.
	 *
	 * @since 15.0.0
	 *
	 * @param string $part
	 * @param string $content
	 *
	 * @return IndexDocument
	 */
	final public function addPart(string $part, string $content): IndexDocument {
		$this->parts[$part] = $content;

		return $this;
	}

	/**
	 * Set all parts and their content.
	 *
	 * @since 15.0.0
	 *
	 * @param array $parts
	 *
	 * @return IndexDocument
	 */
	final public function setParts(array $parts): IndexDocument {
		$this->parts = $parts;

		return $this;
	}

	/**
	 * Get all parts of the IndexDocument.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	final public function getParts(): array {
		return $this->parts;
	}


	/**
	 * Add a link, usable by the frontend.
	 *
	 * @since 15.0.0
	 *
	 * @param string $link
	 *
	 * @return IndexDocument
	 */
	final public function setLink(string $link): IndexDocument {
		$this->link = $link;

		return $this;
	}

	/**
	 * Get the link.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	final public function getLink(): string {
		return $this->link;
	}


	/**
	 * Set more information that couldn't be set using other method.
	 *
	 * @since 15.0.0
	 *
	 * @param array $more
	 *
	 * @return IndexDocument
	 */
	final public function setMore(array $more): IndexDocument {
		$this->more = $more;

		return $this;
	}

	/**
	 * Get more information.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	final public function getMore(): array {
		return $this->more;
	}


	/**
	 * Add some excerpt of the content of the original document, usually based
	 * on the search request.
	 *
	 * @since 15.0.0
	 *
	 * @param string $excerpt
	 *
	 * @return IndexDocument
	 */
	final public function addExcerpt(string $excerpt): IndexDocument {
		$excerpt = $this->cleanExcerpt($excerpt);

		$this->excerpts[] = $excerpt;

		return $this;
	}

	/**
	 * Set all excerpts of the content of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @param array $excerpts
	 *
	 * @return IndexDocument
	 */
	final public function setExcerpts(array $excerpts): IndexDocument {
		$excerpts = array_map([$this, 'cleanExcerpt'], $excerpts);

		$this->excerpts = $excerpts;

		return $this;
	}

	/**
	 * Get all excerpts of the content of the original document.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	final public function getExcerpts(): array {
		return $this->excerpts;
	}

	/**
	 * Clean excerpt.
	 *
	 * @since 15.0.0
	 *
	 * @param string $excerpt
	 *
	 * @return string
	 */
	final public function cleanExcerpt(string $excerpt): string {
		$excerpt = str_replace("\\n", ' ', $excerpt);
		$excerpt = str_replace("\\r", ' ', $excerpt);
		$excerpt = str_replace("\\t", ' ', $excerpt);
		$excerpt = str_replace("\n", ' ', $excerpt);
		$excerpt = str_replace("\r", ' ', $excerpt);
		$excerpt = str_replace("\t", ' ', $excerpt);

		return $excerpt;
	}

	/**
	 * Set the score to the result assigned to this document during a search
	 * request.
	 *
	 * @since 15.0.0
	 *
	 * @param string $score
	 *
	 * @return IndexDocument
	 */
	final public function setScore(string $score): IndexDocument {
		$this->score = $score;

		return $this;
	}

	/**
	 * Get the score.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	final public function getScore(): string {
		return $this->score;
	}


	/**
	 * Set some information about the original document that will be available
	 * to the front-end when displaying search result. (as string)
	 * Because this information will not be indexed, this method can also be
	 * used to manage some data while filling the IndexDocument before its
	 * indexing.
	 *
	 * @since 15.0.0
	 *
	 * @param string $info
	 * @param string $value
	 *
	 * @return IndexDocument
	 */
	final public function setInfo(string $info, string $value): IndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	/**
	 * Get an information about a document. (string)
	 *
	 * @since 15.0.0
	 *
	 * @param string $info
	 * @param string $default
	 *
	 * @return string
	 */
	final public function getInfo(string $info, string $default = ''): string {
		if (!key_exists($info, $this->info)) {
			return $default;
		}

		return $this->info[$info];
	}

	/**
	 * Set some information about the original document that will be available
	 * to the front-end when displaying search result. (as array)
	 * Because this information will not be indexed, this method can also be
	 * used to manage some data while filling the IndexDocument before its
	 * indexing.
	 *
	 * @since 15.0.0
	 *
	 * @param string $info
	 * @param array $value
	 *
	 * @return IndexDocument
	 */
	final public function setInfoArray(string $info, array $value): IndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	/**
	 * Get an information about a document. (array)
	 *
	 * @since 15.0.0
	 *
	 * @param string $info
	 * @param array $default
	 *
	 * @return array
	 */
	final public function getInfoArray(string $info, array $default = []): array {
		if (!key_exists($info, $this->info)) {
			return $default;
		}

		return $this->info[$info];
	}

	/**
	 * Set some information about the original document that will be available
	 * to the front-end when displaying search result. (as int)
	 * Because this information will not be indexed, this method can also be
	 * used to manage some data while filling the IndexDocument before its
	 * indexing.
	 *
	 * @since 15.0.0
	 *
	 * @param string $info
	 * @param int $value
	 *
	 * @return IndexDocument
	 */
	final public function setInfoInt(string $info, int $value): IndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	/**
	 * Get an information about a document. (int)
	 *
	 * @since 15.0.0
	 *
	 * @param string $info
	 * @param int $default
	 *
	 * @return int
	 */
	final public function getInfoInt(string $info, int $default = 0): int {
		if (!key_exists($info, $this->info)) {
			return $default;
		}

		return $this->info[$info];
	}

	/**
	 * Set some information about the original document that will be available
	 * to the front-end when displaying search result. (as bool)
	 * Because this information will not be indexed, this method can also be
	 * used to manage some data while filling the IndexDocument before its
	 * indexing.
	 *
	 * @since 15.0.0
	 *
	 * @param string $info
	 * @param bool $value
	 *
	 * @return IndexDocument
	 */
	final public function setInfoBool(string $info, bool $value): IndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	/**
	 * Get an information about a document. (bool)
	 *
	 * @since 15.0.0
	 *
	 * @param string $info
	 * @param bool $default
	 *
	 * @return bool
	 */
	final public function getInfoBool(string $info, bool $default = false): bool {
		if (!key_exists($info, $this->info)) {
			return $default;
		}

		return $this->info[$info];
	}

	/**
	 * Get all info.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	final public function getInfoAll(): array {

		$info = [];
		foreach ($this->info as $k => $v) {
			if (substr($k, 0, 1) === '_') {
				continue;
			}

			$info[$k] = $v;
		}

		return $info;
	}


	/**
	 * @since 15.0.0
	 *
	 * On some version of PHP, it is better to force destruct the object.
	 * And during the index, the number of generated IndexDocument can be
	 * _huge_.
	 */
	public function __destruct() {
		unset($this->id);
		unset($this->providerId);
		unset($this->access);
		unset($this->modifiedTime);
		unset($this->title);
		unset($this->content);
		unset($this->hash);
		unset($this->link);
		unset($this->source);
		unset($this->tags);
		unset($this->metaTags);
		unset($this->subTags);
		unset($this->more);
		unset($this->excerpts);
		unset($this->score);
		unset($this->info);
		unset($this->contentEncoded);
	}

	/**
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'providerId' => $this->getProviderId(),
			'access' => $this->access,
			'modifiedTime' => $this->getModifiedTime(),
			'title' => $this->getTitle(),
			'link' => $this->getLink(),
			'index' => $this->index,
			'source' => $this->getSource(),
			'info' => $this->getInfoAll(),
			'hash' => $this->getHash(),
			'contentSize' => $this->getContentSize(),
			'tags' => $this->getTags(),
			'metatags' => $this->getMetaTags(),
			'subtags' => $this->getSubTags(),
			'more' => $this->getMore(),
			'excerpts' => $this->getExcerpts(),
			'score' => $this->getScore()
		];
	}

}

