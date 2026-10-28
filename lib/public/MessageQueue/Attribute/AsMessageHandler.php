<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\MessageQueue\Attribute;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Declares the handler of a message. The handled message class is taken from
 * the type of the first parameter. Each message class has exactly one handler.
 *
 * Handler classes are listed in appinfo/info.xml under <message-handlers> and
 * are resolved through the app container, so constructor injection works.
 *
 * Can be used either on an invokable class:
 *
 * ```
 * #[AsMessageHandler]
 * final class GeneratePreviewHandler {
 *     public function __construct(private IPreview $preview) {
 *     }
 *
 *     public function __invoke(GeneratePreview $message): void {
 *         // ...
 *     }
 * }
 * ```
 *
 * Or on methods:
 *
 * ```
 * final class FileTasks {
 *     #[AsMessageHandler]
 *     public function preview(GeneratePreview $message): void {
 *     }
 *
 *     #[AsMessageHandler]
 *     public function scan(ScanFolder $message): void {
 *     }
 * }
 * ```
 *
 * Throw an UnrecoverableMessageException to fail the message without retrying.
 * Any other exception triggers a retry according to the message's retry settings.
 *
 * @since 36.0.0
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
#[Consumable(since: '36.0.0')]
final class AsMessageHandler {
}
