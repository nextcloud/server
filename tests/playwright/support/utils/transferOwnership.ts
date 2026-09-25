/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'

import { runExec } from '@nextcloud/e2e-test-server/docker'

const TRANSFER_JOB_CLASS = 'OCA\\Files\\BackgroundJob\\TransferOwnership'

/**
 * Carry out the ownership transfer a user requested: accept it as the new owner
 * and run the background job that moves the files.
 *
 * Returns once the files have been moved, so callers can assert on the result
 * without polling.
 *
 * @param request - A request context authenticated as `target`
 * @param source - The user who requested the transfer
 * @param target - The user receiving the ownership
 */
export async function completeOwnershipTransfer(
	request: APIRequestContext,
	source: User,
	target: User,
): Promise<void> {
	const transferId = await getPendingTransferId(source, target)
	await acceptOwnershipTransfer(request, transferId)
	await runTransferJob(transferId)
}

/**
 * The name of the folder an ownership transfer creates in the new owners root,
 * as a pattern — the folder is suffixed with the time of the transfer.
 *
 * @param source - The user who transferred the files
 */
export function transferFolderPattern(source: User): RegExp {
	return new RegExp(`^Transferred from ${source.userId} on `)
}

/**
 * Read the id of the ownership transfer pending between two users.
 *
 * A transfer is normally accepted from the notification it creates, but the
 * notifications app is not installed on the test instance, so there is no API
 * to look the id up with and it is read from the database instead.
 *
 * @param source - The user who requested the transfer
 * @param target - The user receiving the ownership
 */
async function getPendingTransferId(source: User, target: User): Promise<number> {
	const script = `
		require_once "/var/www/html/lib/base.php";
		$query = \\OCP\\Server::get(\\OCP\\IDBConnection::class)->getQueryBuilder();
		$query->select("id")
			->from("user_transfer_owner")
			->where($query->expr()->eq("source_user", $query->createNamedParameter($argv[1])))
			->andWhere($query->expr()->eq("target_user", $query->createNamedParameter($argv[2])));
		echo json_encode(array_column($query->executeQuery()->fetchAll(), "id"));
	`
	const stdout = await run(['php', '-r', script, '--', source.userId, target.userId], { retry: true })

	const ids = JSON.parse(stdout.trim()) as number[]
	if (ids.length !== 1) {
		throw new Error(`Expected one pending transfer from ${source.userId} to ${target.userId}, found ${ids.length}`)
	}
	return Number(ids[0])
}

/**
 * Accept an ownership transfer, which queues the job doing the actual transfer.
 *
 * @param request - A request context authenticated as the receiving user
 * @param transferId - The id of the transfer to accept
 */
async function acceptOwnershipTransfer(request: APIRequestContext, transferId: number): Promise<void> {
	const response = await request.post(`/ocs/v2.php/apps/files/api/v1/transferownership/${transferId}`, {
		headers: {
			Accept: 'application/json',
			'OCS-APIRequest': 'true',
		},
	})
	const meta = (await response.json()).ocs?.meta
	if (meta?.statuscode !== 200) {
		throw new Error(`Accepting transfer ${transferId} failed: ${meta?.statuscode} ${meta?.message}`)
	}
}

/**
 * Run the queued background job of a transfer.
 *
 * The job is addressed by its id instead of running a worker for the job class,
 * so that parallel tests never execute each others transfers.
 *
 * @param transferId - The id of the accepted transfer
 */
async function runTransferJob(transferId: number): Promise<void> {
	const stdout = await run(['php', 'occ', 'background-job:list', '--class', TRANSFER_JOB_CLASS, '--output', 'json'], { retry: true })
	const jobs = JSON.parse(stdout) as { id: number, argument: string }[]
	const job = jobs.find(({ argument }) => JSON.parse(argument).id === transferId)
	if (!job) {
		throw new Error(`No background job queued for transfer ${transferId}`)
	}

	await run(['php', 'occ', 'background-job:execute', String(job.id), '--force-execute'])
}

/**
 * Run a command in the Nextcloud container, reporting its output on failure.
 *
 * @param command - The command to run
 * @param options - Whether to run the command a second time if it failed. Only
 *   for commands that read, they can fail spuriously when the SQLite database
 *   of the test instance is busy with the transfers of parallel tests.
 */
async function run(command: string[], { retry = false } = {}): Promise<string> {
	const { stdout, stderr, exitCode } = await runExec(command, { failOnError: false })
	if (exitCode === 0) {
		return stdout
	}
	if (retry) {
		return await run(command)
	}
	throw new Error(`"${command.join(' ')}" exited with ${exitCode}: ${stderr || stdout}`)
}
