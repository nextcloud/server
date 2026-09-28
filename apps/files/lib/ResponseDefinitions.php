<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files;

/**
 * @psalm-type FilesTemplateFile = array{
 *     basename: string,
 *     etag: string,
 *     fileid: int,
 *     filename: ?string,
 *     lastmod: int,
 *     mime: string,
 *     size: int,
 *     type: string,
 *     hasPreview: bool,
 * }
 *
 * @psalm-type FilesTemplateField = array{
 *     index: string,
 *     type: string,
 *     alias: ?string,
 *     tag: ?string,
 *     id: ?int,
 *     content?: string,
 *     checked?: bool,
 * }
 *
 * @psalm-type FilesTemplate = array{
 *      templateType: string,
 *      templateId: string,
 *      basename: string,
 *      etag: string,
 *      fileid: int,
 *      filename: string,
 *      lastmod: int,
 *      mime: string,
 *      size: int|float,
 *      type: string,
 *      hasPreview: bool,
 *      previewUrl: ?string,
 *      fields: list<FilesTemplateField>,
 *  }
 *
 * @psalm-type FilesTemplateFileCreator = array{
 *     app: string,
 *     label: string,
 *     extension: string,
 *     iconClass: ?string,
 *     iconSvgInline: ?string,
 *     mimetypes: list<string>,
 *     ratio: ?float,
 *     actionLabel: string,
 * }
 *
 * @psalm-type FilesTemplateFileCreatorWithTemplates = FilesTemplateFileCreator&array{
 *     templates: list<FilesTemplate>,
 * }
 *
 * @psalm-type FilesFolderTree = list<array{
 *     id: int,
 *     basename: string,
 *     displayName?: string,
 *     children: list<array{}>,
 * }>
 *
 */
class ResponseDefinitions {
}
