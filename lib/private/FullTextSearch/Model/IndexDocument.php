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


	final public function getId(): string {
		return $this->id;
	}


	final public function getProviderId(): string {
		return $this->providerId;
	}


	final public function setIndex(IIndex $index): IIndexDocument {
		$this->index = $index;

		return $this;
	}

	final public function getIndex(): IIndex {
		if ($this->index === null) {
			throw new FullTextSearchIndexNotAvailableException('No IIndex generated');
		}

		return $this->index;
	}

	final public function hasIndex(): bool {
		return $this->index !== null;
	}

	final public function setModifiedTime(int $modifiedTime): IIndexDocument {
		$this->modifiedTime = $modifiedTime;

		return $this;
	}

	final public function getModifiedTime(): int {
		return $this->modifiedTime;
	}

	final public function isOlderThan(int $time): bool {
		return ($this->modifiedTime < $time);
	}


	final public function setAccess(IDocumentAccess $access): IIndexDocument {
		$this->access = $access;

		return $this;
	}

	final public function getAccess(): IDocumentAccess {
		return $this->access;
	}


	final public function addTag(string $tag): IIndexDocument {
		$this->tags[] = $tag;

		return $this;
	}

	final public function setTags(array $tags): IIndexDocument {
		$this->tags = $tags;

		return $this;
	}

	final public function getTags(): array {
		return $this->tags;
	}


	final public function addMetaTag(string $tag): IIndexDocument {
		$this->metaTags[] = $tag;

		return $this;
	}

	final public function setMetaTags(array $tags): IIndexDocument {
		$this->metaTags = $tags;

		return $this;
	}

	final public function getMetaTags(): array {
		return $this->metaTags;
	}


	final public function addSubTag(string $sub, string $tag): IIndexDocument {
		if (!array_key_exists($sub, $this->subTags)) {
			$this->subTags[$sub] = [];
		}

		$this->subTags[$sub][] = $tag;

		return $this;
	}


	final public function setSubTags(array $tags): IIndexDocument {
		$this->subTags = $tags;

		return $this;
	}

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


	final public function setSource(string $source): IIndexDocument {
		$this->source = $source;

		return $this;
	}

	final public function getSource(): string {
		return $this->source;
	}


	final public function setTitle(string $title): IIndexDocument {
		$this->title = $title;

		return $this;
	}

	final public function getTitle(): string {
		return $this->title;
	}


	final public function setContent(string $content, int $encoded = 0): IIndexDocument {
		$this->content = $content;
		$this->contentEncoded = $encoded;

		return $this;
	}

	final public function getContent(): string {
		return $this->content;
	}

	final public function isContentEncoded(): int {
		return $this->contentEncoded;
	}

	final public function getContentSize(): int {
		return strlen($this->getContent());
	}


	final public function initHash(): IIndexDocument {
		if ($this->getContent() === '' || is_null($this->getContent())) {
			return $this;
		}

		$this->hash = hash('md5', $this->getContent());

		return $this;
	}

	final public function setHash(string $hash): IIndexDocument {
		$this->hash = $hash;

		return $this;
	}

	final public function getHash(): string {
		return $this->hash;
	}


	final public function addPart(string $part, string $content): IIndexDocument {
		$this->parts[$part] = $content;

		return $this;
	}

	final public function setParts(array $parts): IIndexDocument {
		$this->parts = $parts;

		return $this;
	}

	final public function getParts(): array {
		return $this->parts;
	}


	final public function setLink(string $link): IIndexDocument {
		$this->link = $link;

		return $this;
	}

	final public function getLink(): string {
		return $this->link;
	}


	final public function setMore(array $more): IIndexDocument {
		$this->more = $more;

		return $this;
	}

	final public function getMore(): array {
		return $this->more;
	}


	final public function addExcerpt(string $source, string $excerpt): IIndexDocument {
		$this->excerpts[] =
			[
				'source' => $source,
				'excerpt' => $this->cleanExcerpt($excerpt)
			];

		return $this;
	}


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


	final public function setScore(string $score): IIndexDocument {
		$this->score = $score;

		return $this;
	}

	final public function getScore(): string {
		return $this->score;
	}


	final public function setInfo(string $info, string $value): IIndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	final public function getInfo(string $info, string $default = ''): string {
		if (!key_exists($info, $this->info)) {
			return $default;
		}

		return $this->info[$info];
	}

	final public function setInfoArray(string $info, array $value): IIndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	final public function getInfoArray(string $info, array $default = []): array {
		if (!key_exists($info, $this->info)) {
			return $default;
		}

		return $this->info[$info];
	}

	final public function setInfoInt(string $info, int $value): IIndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	final public function getInfoInt(string $info, int $default = 0): int {
		if (!key_exists($info, $this->info)) {
			return $default;
		}

		return $this->info[$info];
	}

	final public function setInfoBool(string $info, bool $value): IIndexDocument {
		$this->info[$info] = $value;

		return $this;
	}

	final public function getInfoBool(string $info, bool $default = false): bool {
		if (!key_exists($info, $this->info)) {
			return $default;
		}

		return $this->info[$info];
	}

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
