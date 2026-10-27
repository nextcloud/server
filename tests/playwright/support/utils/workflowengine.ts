/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext, APIResponse } from '@playwright/test'

interface OcsEnvelope<T> {
	ocs: {
		meta: { statuscode: number, message: string }
		data: T
	}
}

/** Which set of flows a request addresses: the instance-wide ones or the caller's own. */
export type WorkflowScope = 'global' | 'user'

export interface WorkflowCheck {
	class: string
	operator: string
	value: string
}

export interface WorkflowRule {
	id: number
	class: string
	name: string
	entity: string
	events: string[]
	operation: string
	checks: WorkflowCheck[]
}

/**
 * The OCS endpoint of a flow scope, optionally addressing a single flow.
 *
 * @param scope - Which set of flows to address
 * @param id - Id of a single flow
 */
function workflowsUrl(scope: WorkflowScope, id?: number): string {
	const base = `/ocs/v2.php/apps/workflowengine/api/v1/workflows/${scope}`
	return `${id === undefined ? base : `${base}/${id}`}?format=json`
}

/**
 * Unwrap an OCS response, turning a non-200 OCS status into an error.
 *
 * @param response - The response to unwrap
 * @param action - What the request attempted, for the error message
 */
async function ocsData<T>(response: APIResponse, action: string): Promise<T> {
	const { ocs } = await response.json() as OcsEnvelope<T>
	if (ocs?.meta?.statuscode !== 200) {
		throw new Error(`${action} failed: ${ocs?.meta?.statuscode} ${ocs?.meta?.message}`)
	}
	return ocs.data as T
}

/**
 * List the configured flows of a scope.
 *
 * The response groups the flows by operation class, which is an implementation
 * detail of the storage — callers only ever want the flat list.
 *
 * @param request - Request context authenticated as the owner of the flows
 * @param scope - Which set of flows to list
 */
export async function listRules(request: APIRequestContext, scope: WorkflowScope): Promise<WorkflowRule[]> {
	const response = await request.get(workflowsUrl(scope), {
		headers: { 'OCS-APIRequest': 'true' },
	})
	const byOperation = await ocsData<Record<string, WorkflowRule[]>>(response, `Listing ${scope} flows`)
	return Object.values(byOperation).flat()
}

/**
 * Delete a single flow.
 *
 * @param request - Request context authenticated as the owner of the flow
 * @param scope - Which set of flows the flow belongs to
 * @param id - Id of the flow to delete
 */
export async function deleteRule(request: APIRequestContext, scope: WorkflowScope, id: number): Promise<void> {
	const response = await request.delete(workflowsUrl(scope, id), {
		headers: { 'OCS-APIRequest': 'true' },
	})
	await ocsData(response, `Deleting ${scope} flow ${id}`)
}

/**
 * Delete every configured flow of a scope.
 *
 * @param request - Request context authenticated as the owner of the flows
 * @param scope - Which set of flows to clear
 */
export async function clearRules(request: APIRequestContext, scope: WorkflowScope): Promise<void> {
	for (const rule of await listRules(request, scope)) {
		await deleteRule(request, scope, rule.id)
	}
}
