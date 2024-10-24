<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DirectEditing;

use Doctrine\DBAL\FetchMode;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Constants;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DirectEditing\ACreateFromTemplate;
use OCP\DirectEditing\IEditor;
use OCP\DirectEditing\IManager;
use OCP\DirectEditing\IToken;
use OCP\Encryption\IManager as EncryptionManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\Share\IShare;
use Throwable;
use function array_key_exists;
use function in_array;

class Manager implements IManager {
	private const TOKEN_CLEANUP_TIME = 12 * 60 * 60 ;

	public const TABLE_TOKENS = 'direct_edit';

	/** @var IEditor[] */
	private $editors = [];
	/** @var string|null */
	private $userId;
	/** @var IL10N */
	private $l10n;

	public function __construct(
		private ISecureRandom $random,
		private IDBConnection $connection,
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
		private IFactory $l10nFactory,
		private EncryptionManager $encryptionManager,
	) {
		$this->userId = $userSession->getUser() ? $userSession->getUser()->getUID() : null;
		$this->l10n = $l10nFactory->get('lib');
	}

	public function registerDirectEditor(IEditor $directEditor): void {
		$this->editors[$directEditor->getId()] = $directEditor;
	}

	public function getEditors(): array {
		return $this->editors;
	}

	public function getTemplates(string $editor, string $type): array {
		if (!array_key_exists($editor, $this->editors)) {
			throw new \RuntimeException('No matching editor found');
		}
		$templates = [];
		foreach ($this->editors[$editor]->getCreators() as $creator) {
			if ($creator->getId() === $type) {
				$templates = [
					'empty' => [
						'id' => 'empty',
						'title' => $this->l10n->t('Empty file'),
						'preview' => null
					]
				];

				if ($creator instanceof ACreateFromTemplate) {
					$templates = $creator->getTemplates();
				}

				$templates = array_map(function ($template) use ($creator) {
					$template['extension'] = $creator->getExtension();
					$template['mimetype'] = $creator->getMimetype();
					return $template;
				}, $templates);
			}
		}
		$return = [];
		$return['templates'] = $templates;
		return $return;
	}

	public function create(string $path, string $editorId, string $creatorId, $templateId = null): string {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		if ($userFolder->nodeExists($path)) {
			throw new \RuntimeException('File already exists');
		} else {
			if (!$userFolder->nodeExists(dirname($path))) {
				throw new \RuntimeException('Invalid path');
			}
			/** @var Folder $folder */
			$folder = $userFolder->get(dirname($path));
			$file = $folder->newFile(basename($path));
			$editor = $this->getEditor($editorId);
			$creators = $editor->getCreators();
			foreach ($creators as $creator) {
				if ($creator->getId() === $creatorId) {
					$creator->create($file, $creatorId, $templateId);
					return $this->createToken($editorId, $file, $path);
				}
			}
		}

		throw new \RuntimeException('No creator found');
	}

	public function open(string $filePath, ?string $editorId = null, ?int $fileId = null): string {
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$file = $userFolder->get($filePath);
		if ($fileId !== null && $file instanceof Folder) {
			$files = $file->getById($fileId);

			// Workaround to always open files with edit permissions if multiple occurences of
			// the same file id are in the user home, ideally we should also track the path of the file when opening
			usort($files, function (Node $a, Node $b) {
				return ($b->getPermissions() & Constants::PERMISSION_UPDATE) <=> ($a->getPermissions() & Constants::PERMISSION_UPDATE);
			});
			$file = array_shift($files);
		}

		if (!$file instanceof File) {
			throw new NotFoundException();
		}

		$filePath = $userFolder->getRelativePath($file->getPath());

		if ($editorId === null) {
			$editorId = $this->findEditorForFile($file);
		}
		if (!array_key_exists($editorId, $this->editors)) {
			throw new \RuntimeException("Editor $editorId is unknown");
		}

		return $this->createToken($editorId, $file, $filePath);
	}

	private function findEditorForFile(File $file) {
		foreach ($this->editors as $editor) {
			if (in_array($file->getMimeType(), $editor->getMimetypes())) {
				return $editor->getId();
			}
		}
		throw new \RuntimeException('No default editor found for files mimetype');
	}

	public function edit(string $token): Response {
		try {
			/** @var IEditor $editor */
			$tokenObject = $this->getToken($token);
			if ($tokenObject->hasBeenAccessed()) {
				throw new \RuntimeException('Token has already been used and can only be used for followup requests');
			}
			$editor = $this->getEditor($tokenObject->getEditor());
			$this->accessToken($token);
		} catch (Throwable $throwable) {
			$this->invalidateToken($token);
			return new NotFoundResponse();
		}

		try {
			$this->invokeTokenScope($tokenObject->getUser());
			return $editor->open($tokenObject);
		} finally {
			$this->revertTokenScope();
		}
	}

	public function editSecure(File $file, string $editorId): TemplateResponse {
		// TODO: Implementation in follow up
	}

	private function getEditor($editorId): IEditor {
		if (!array_key_exists($editorId, $this->editors)) {
			throw new \RuntimeException('No editor found');
		}
		return $this->editors[$editorId];
	}

	public function getToken(string $token): IToken {
		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from(self::TABLE_TOKENS)
			->where($query->expr()->eq('token', $query->createNamedParameter($token, IQueryBuilder::PARAM_STR)));
		$result = $query->executeQuery();
		if ($tokenRow = $result->fetch(FetchMode::ASSOCIATIVE)) {
			return new Token($this, $tokenRow);
		}
		throw new \RuntimeException('Failed to validate the token');
	}

	public function cleanup(): int {
		$query = $this->connection->getQueryBuilder();
		$query->delete(self::TABLE_TOKENS)
			->where($query->expr()->lt('timestamp', $query->createNamedParameter(time() - self::TOKEN_CLEANUP_TIME)));
		return $query->executeStatement();
	}

	public function refreshToken(string $token): bool {
		$query = $this->connection->getQueryBuilder();
		$query->update(self::TABLE_TOKENS)
			->set('timestamp', $query->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('token', $query->createNamedParameter($token, IQueryBuilder::PARAM_STR)));
		$result = $query->executeStatement();
		return $result !== 0;
	}


	public function invalidateToken(string $token): bool {
		$query = $this->connection->getQueryBuilder();
		$query->delete(self::TABLE_TOKENS)
			->where($query->expr()->eq('token', $query->createNamedParameter($token, IQueryBuilder::PARAM_STR)));
		$result = $query->executeStatement();
		return $result !== 0;
	}

	public function accessToken(string $token): bool {
		$query = $this->connection->getQueryBuilder();
		$query->update(self::TABLE_TOKENS)
			->set('accessed', $query->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
			->set('timestamp', $query->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('token', $query->createNamedParameter($token, IQueryBuilder::PARAM_STR)));
		$result = $query->executeStatement();
		return $result !== 0;
	}

	public function invokeTokenScope($userId): void {
		\OC_User::setUserId($userId);
	}

	public function revertTokenScope(): void {
		$this->userSession->setUser(null);
	}

	public function createToken($editorId, File $file, string $filePath, ?IShare $share = null): string {
		$token = $this->random->generate(64, ISecureRandom::CHAR_HUMAN_READABLE);
		$query = $this->connection->getQueryBuilder();
		$query->insert(self::TABLE_TOKENS)
			->values([
				'token' => $query->createNamedParameter($token),
				'editor_id' => $query->createNamedParameter($editorId),
				'file_id' => $query->createNamedParameter($file->getId()),
				'file_path' => $query->createNamedParameter($filePath),
				'user_id' => $query->createNamedParameter($this->userId),
				'share_id' => $query->createNamedParameter($share !== null ? $share->getId(): null),
				'timestamp' => $query->createNamedParameter(time())
			]);
		$query->executeStatement();
		return $token;
	}

	/**
	 * @param string $userId
	 * @param int $fileId
	 * @param ?string $filePath
	 * @throws NotFoundException
	 */
	public function getFileForToken($userId, $fileId, $filePath = null): Node {
		$userFolder = $this->rootFolder->getUserFolder($userId);
		if ($filePath !== null) {
			return $userFolder->get($filePath);
		}
		$file = $userFolder->getFirstNodeById($fileId);
		if (!$file) {
			throw new NotFoundException('File nound found by id ' . $fileId);
		}
		return $file;
	}

	public function isEnabled(): bool {
		if (!$this->encryptionManager->isEnabled()) {
			return true;
		}

		try {
			$moduleId = $this->encryptionManager->getDefaultEncryptionModuleId();
			$module = $this->encryptionManager->getEncryptionModule($moduleId);
			/** @var \OCA\Encryption\Util $util */
			$util = \OCP\Server::get(\OCA\Encryption\Util::class);
			if ($module->isReadyForUser($this->userId) && $util->isMasterKeyEnabled()) {
				return true;
			}
		} catch (Throwable $e) {
		}
		return false;
	}
}
