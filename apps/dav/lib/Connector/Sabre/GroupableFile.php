<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Connector\Sabre;

use OC\Files\View;
use OCA\DAV\Connector\Sabre\File;
use OCP\Files\FileInfo;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Share\IManager;

class GroupableFile extends File {

    public function __construct(
        View $view,
        FileInfo $info,
        ?IManager $shareManager = null,
        ?IRequest $request = null,
        ?IL10N $l10n = null,
        protected ?int $group = null,
    ) {
        parent::__construct($view, $info, $shareManager, $request, $l10n);
    }

    public function getGroup(): ?int {
        return $this->group;
    }

    public function setGroup(int $group): void {
        $this->group = $group;
    }
}
