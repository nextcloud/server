<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\FullTextSearch\Model;

use JsonSerializable;
use OCP\FullTextSearch\Exceptions\FullTextSearchIndexNotAvailableException;
use OCP\FullTextSearch\Model\IDocumentAccess;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IIndexDocument;

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
class IndexDocument implements IIndexDocument, JsonSerializable {
	protected string $id = '';

	protected DocumentAccess $access;

	protected ?IIndex $index = null;

	protected int $modifiedTime = 0;

	protected string $source = '';

	protected array $tags = [];

	protected array $metaTags = [];

	protected array $subTags = [];

	protected string $title = '';

	protected string $content = '';

	protected string $hash = '';

	protected array $parts = [];

	protected string $link = '';

	protected array $more = [];

	protected array $excerpts = [];

	protected string $score = '';

	protected array $info = [];

	protected int $contentEncoded = 0;


	/**
	 * IIndexDocument constructor.
	 *
	 * On creation, we assure the uniqueness of the object using the providerId
	 * and the Id of the original document.
	 *
	 * @since 15.0.0
	 */
	public function __construct(
		protected string $providerId,
		string $documentId,
	) {
		$this->id = $documentId;
	}


	/**
	 * Returns the Id of the original document.
	 *
	 * @since 15.0.0
	 */
	final public function getId(): string {
		return $this->id;
	}


	/**
	 * Returns the Id of the provider.
	 *
	 * @since 15.0.0
	 */
	final public function getProviderId(): string {
		return $this->providerId;
	}


	/**
	 * Set the Index related to the IIndexDocument.
	 *
	 * @see IIndex
	 *
	 * @since 15.0.0
	 */
	final public function setIndex(IIndex $index): IIndexDocument {
		$this->index = $index;

		return $this;
	}

	/**
	 * Get the Index.
	 *
	 * @throws FullTextSearchIndexNotAvailableException
	 * @since 15.0.0
	 */
	final public function getIndex(): IIndex {
		if ($this->index === null) {
			throw new FullTextSearchIndexNotAvailableException('No IIndex generated');
		}

		return $this->index;
	}

	/**
	 * return if Index is defined.
	 *
	 * @since 16.0.0
	 */
	final public function hasIndex(): bool {
		return $this->index !== null;
	}

	/**
	 * Set the modified time of the original document.
	 *
	 * @since 15.0.0
	 */
	final public function setModifiedTime(int $modifiedTime): IIndexDocument {
		$this->modifiedTime = $modifiedTime;

		return $this;
	}

	/**
	 * Get the modified time of the original document.
	 *
	 * @since 15.0.0
	 */
	final public function getModifiedTime(): int {
		return $this->modifiedTime;
	}

	/**
	 * Check if the original document of the IIndexDocument is older than $time.
	 *
	 * @since 15.0.0
	 */
	final public function isOlderThan(int $time): bool {
		return ($this->modifiedTime < $time);
	}


	/**
	 * Set the read rights of the original document using a IDocumentAccess.
	 *
	 * @see IDocumentAccess
	 *
	 * @since 15.0.0
	 */
	final public function setAccess(IDocumentAccess $access): IIndexDocument {
		$this->access = $access;

		return $this;
	}

	/**
	 * Get the IDocumentAccess related to the original document.
	 *
	 * @since 15.0.0
	 */
	final public function getAccess(): IDocumentAccess {
		return $this->access;
	}


	/**
	 * Add a tag to the list.
	 *
	 * @since 15.0.0
	 */
	final public function addTag(string $tag): IIndexDocument {
		$this->tags[] = $tag;

		return $this;
	}

	/**
	 * Set the list of tags assigned to the original document.
	 *
	 * @since 15.0.0
	 */
	final public function setTags(array $tags): IIndexDocument {
		$this->tags = $tags;

		return $this;
	}

	/**
	 * Get the list of tags assigned to the original document.
	 *
	 * @since 15.0.0
	 */
	final public function getTags(): array {
		return $this->tags;
	}


	/**
	 * Add a meta tag to the list.
	 *
	 * @since 15.0.0
	 */
	final public function addMetaTag(string $tag): IIndexDocument {
		$this->metaTags[] = $tag;

		return $this;
	}

	/**
	 * Set the list of meta tags assigned to the original document.
	 *
	 * @since 15.0.0
	 */
	final public function setMetaTags(array $tags): IIndexDocument {
		$this->metaTags = $tags;

		return $this;
	}

	/**
	 * Get the list of meta tags assigned to the original document.
	 *
	 * @since 15.0.0
	 */
	final public function getMetaTags(): array {
		return $this->metaTags;
	}


	/**
	 * Add a sub tag to the list.
	 *
	 * @since 15.0.0
	 */
	final public function addSubTag(string $sub, string $tag): IIndexDocument {
		if (!array_key_exists($sub, $this->subTags)) {
			$this->subTags[$sub] = [];
		}

		$this->subTags[$sub][] = $tag;

		return $this;
	}


	/**
	 * Set the list of sub tags assigned to the original document.
	 *
	 * @since 15.0.0
	 */
	final public function setSubTags(array $tags): IIndexDocument {
		$this->subTags = $tags;

		return $this;
	}

	/**
	 * Get the list of sub tags assigned to the original document.
	 * If $formatted is true, the result will be formatted in a one
	 * dimensional array.
	 *
	 * @since 15.0.0
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
	 */
	final public function setSource(string $source): IIndexDocument {
		$this->source = $source;

		return $this;
	}

	/**
	 * Get the source of the original document.
	 *
	 * @since 15.0.0
	 */
	final public function getSource(): string {
		return $this->source;
	}


	/**
	 * Set the title of the original document.
	 *
	 * @since 15.0.0
	 */
	final public function setTitle(string $title): IIndexDocument {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get the title of the original document.
	 *
	 * @since 15.0.0
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
	 */
	final public function setContent(string $content, int $encoded = 0): IIndexDocument {
		$this->content = $content;
		$this->contentEncoded = $encoded;

		return $this;
	}

	/**
	 * Get the content of the original document.
	 *
	 * @since 15.0.0
	 */
	final public function getContent(): string {
		return $this->content;
	}

	/**
	 * Returns the type of the encoding on the content.
	 *
	 * @since 15.0.0
	 */
	final public function isContentEncoded(): int {
		return $this->contentEncoded;
	}

	/**
	 * Return the size of the content.
	 *
	 * @since 15.0.0
	 */
	final public function getContentSize(): int {
		return strlen($this->getContent());
	}


	/**
	 * Generate a hash, based on the content of the original document.
	 *
	 * @since 15.0.0
	 */
	final public function initHash(): IIndexDocument {
		if ($this->getContent() === '' || is_null($this->getContent())) {
			return $this;
		}

		$this->hash = hash('md5', $this->getContent());

		return $this;
	}

	/**
	 * Set the hash of the original document.
	 *
	 * @since 15.0.0
	 */
	final public function setHash(string $hash): IIndexDocument {
		$this->hash = $hash;

		return $this;
	}

	/**
	 * Get the hash of the original document.
	 *
	 * @since 15.0.0
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
	 */
	final public function addPart(string $part, string $content): IIndexDocument {
		$this->parts[$part] = $content;

		return $this;
	}

	/**
	 * Set all parts and their content.
	 *
	 * @since 15.0.0
	 */
	final public function setParts(array $parts): IIndexDocument {
		$this->parts = $parts;

		return $this;
	}

	/**
	 * Get all parts of the IIndexDocument.
	 *
	 * @since 15.0.0
	 */
	final public function getParts(): array {
		return $this->parts;
	}


	/**
	 * Add a link, usable by the frontend.
	 *
	 * @since 15.0.0
	 */
	final public function setLink(string $link): IIndexDocument {
		$this->link = $link;

		return $this;
	}

	/**
	 * Get the link.
	 *
	 * @since 15.0.0
	 */
	final public function getLink(): string {
		return $this->link;
	}


	/**
	 * Set more information that couldn't be set using other method.
	 *
	 * @since 15.0.0
	 */
	final public function setMore(array $more): IIndexDocument {
		$this->more = $more;

		return $this;
	}

	/**
	 * Get more information.
	 *
	 * @since 15.0.0
	 */
	final public function getMore(): array {
		return $this->more;
	}


	/**
	 * Add some excerpt of the content of the original document, usually based
	 * on the search request.
	 *
	 * @since 16.0.0
	 */
	final public function addExcerpt(string $source, string $excerpt): IIndexDocument {
		$this->excerpts[] =
			[
				'source' => $source,
				'excerpt' => $this->cleanExcerpt($excerpt)
			];

		return $this;
	}


	/**
	 * Set all excerpts of the content of the original document.
	 *
	 * @since 16.0.0
	 */
	final public function setExcerpts(array $excerpts): IIndexDocument {
		$new = [];
		foreach ($excerpts as $entry) {
			$new[] = [
				'source' => $entry['source'],
				'excerpt' => $this->cleanExcerpt($entry['excerpt'])
			];
		}

		$this->excerpts = $new;

		return $this;
	}

	/**
	 * Get all excerpts of the content of the original document.
	 *
	 * @since 15.0.0
	 */
	final public function getExcerpts(): array {
		return $this->excerpts;
	}

	/**
	 * Clean excerpt.
	 *
	 * @since 16.0.0
	 */
	private function cleanExcerpt(string $excerpt): string {
		$excerpt = str_replace('\\n', ' ', $excerpt);
		$excerpt = str_replace('\\r', ' ', $excerpt);
		$excerpt = str_replace('\\t', ' ', $excerpt);
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
	 */
	final public function setScore(string $score): IIndexDocument {
		$this->score = $score;

		return $this;
	}

	/**
	 * Get the score.
	 *
	 * @since 15.0.0
	 */
	final public function getScore(): string {
		return $this->score;
	}


	/**
	 * Set some information about the original document that will be available
	 * to the front-end when displaying search result. (as string)
	 * Because this information will not be indexed, this method can also be
	 * used to manage some data while filling the IIndexDocument before its
	 * indexing.
	 *
	 * @since 15.0.0
	 */
	final public function setInfo(string $info, string $value): IIndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	/**
	 * Get an information about a document. (string)
	 *
	 * @since 15.0.0
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
	 * used to manage some data while filling the IIndexDocument before its
	 * indexing.
	 *
	 * @since 15.0.0
	 */
	final public function setInfoArray(string $info, array $value): IIndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	/**
	 * Get an information about a document. (array)
	 *
	 * @since 15.0.0
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
	 * used to manage some data while filling the IIndexDocument before its
	 * indexing.
	 *
	 * @since 15.0.0
	 */
	final public function setInfoInt(string $info, int $value): IIndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	/**
	 * Get an information about a document. (int)
	 *
	 * @since 15.0.0
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
	 * used to manage some data while filling the IIndexDocument before its
	 * indexing.
	 *
	 * @since 15.0.0
	 */
	final public function setInfoBool(string $info, bool $value): IIndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	/**
	 * Get an information about a document. (bool)
	 *
	 * @since 15.0.0
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
	 */
	final public function getInfoAll(): array {
		$info = [];
		foreach ($this->info as $k => $v) {
			if (str_starts_with($k, '_')) {
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
	 * And during the index, the number of generated IIndexDocument can be
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
	 */
	public function jsonSerialize(): array {
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
