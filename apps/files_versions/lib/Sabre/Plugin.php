<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_Versions\Sabre;

use OC\AppFramework\Http\Request;
use OCA\DAV\Connector\Sabre\FilesPlugin;
use OCP\IPreview;
use OCP\IRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {
	private Server $server;

	public const VERSION_LABEL = '{http://nextcloud.org/ns}version-label';

	public function __construct(
		private IRequest $request,
		private IPreview $previewManager,
	) {
		$this->request = $request;
	}

	public function initialize(Server $server) {
		$this->server = $server;

		$server->on('afterMethod:GET', [$this, 'afterGet']);
		$server->on('propFind', [$this, 'propFind']);
		$server->on('propPatch', [$this, 'propPatch']);
	}

	public function afterGet(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();
		if (!str_starts_with($path, 'versions')) {
			return;
		}

		try {
			$node = $this->server->tree->getNodeForPath($path);
		} catch (NotFound $e) {
			return;
		}

		if (!($node instanceof VersionFile)) {
			return;
		}

		$filename = $node->getVersion()->getSourceFileName();

		if ($this->request->isUserAgent(
			[
				Request::USER_AGENT_IE,
				Request::USER_AGENT_ANDROID_MOBILE_CHROME,
				Request::USER_AGENT_FREEBOX,
			])) {
			$response->addHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($filename) . '"');
		} else {
			$response->addHeader('Content-Disposition', 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename)
				. '; filename="' . rawurlencode($filename) . '"');
		}
	}

	public function propFind(PropFind $propFind, INode $node): void {
		if ($node instanceof VersionFile) {
			$propFind->handle(self::VERSION_LABEL, fn () => $node->getLabel());
			$propFind->handle(FilesPlugin::HAS_PREVIEW_PROPERTYNAME, fn () => $this->previewManager->isMimeSupported($node->getContentType()));
		}
	}

	public function propPatch($path, PropPatch $propPatch): void {
		$node = $this->server->tree->getNodeForPath($path);

		if ($node instanceof VersionFile) {
			$propPatch->handle(self::VERSION_LABEL, fn ($label) => $node->setLabel($label));
		}
	}
}
