import type { INode } from '@nextcloud/files'

type ConflictInput = INode | File | FileSystemEntry

/**
 * Check if there are conflicts between two sets of nodes
 *
 * @param sourceNodes - The nodes that might conflict
 * @param targetNodes - The nodes in the target directory
 */
export function getConflicts<T extends ConflictInput>(sourceNodes: T[], targetNodes: INode[]): T[] {
	const targetNames = targetNodes.map((node) => node.basename)
	return sourceNodes.filter((node) => targetNames.includes('basename' in node ? node.basename : node.name))
}
