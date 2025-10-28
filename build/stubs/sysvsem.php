<?php

// SPDX-FileCopyrightText: The PHP Group
// SPDX-License-Identifier: PHP-3.01
// https://github.com/php/php-src/blob/master/ext/sysvsem/sysvsem.stub.php

/** @generate-class-entries */

/**
 * @strict-properties
 * @not-serializable
 */
final class SysvSemaphore
{
}

function sem_get(int $key, int $max_acquire = 1, int $permissions = 0666, bool $auto_release = true): SysvSemaphore|false {}

function sem_acquire(SysvSemaphore $semaphore, bool $non_blocking = false): bool {}

function sem_release(SysvSemaphore $semaphore): bool {}

function sem_remove(SysvSemaphore $semaphore): bool {}
