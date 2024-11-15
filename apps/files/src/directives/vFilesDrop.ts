import type { IFolder, INode } from '@nextcloud/files'
import type { Upload } from '@nextcloud/upload'
import type { DirectiveHook } from 'vue'
import type { VNode } from 'vue/types/umd'
import {
	onDropExternalFiles,
	onDropInternalFiles,
} from '../services/DropService'
import { useDragAndDropStore } from '../store/dragging'
import { useFilesStore } from '../store/files'

interface OnFileDropProperties {
	disabled?: boolean,
	/** Folder where to upload */
	targetFolder: IFolder
	/** Optional callback called after uploading files - even if disabled */
	callback?: (uploads: INode[]|Upload[]) => void
}

/**
 * Vue directive to handle uploading files on drop events.
 *
 * @param el The element where to bound to
 * @param bindings Directive bindings
 * @param bindings.modifiers Modifiers used on the component - e.g. ".stop"
 * @param bindings.value The value passed through the component
 */
const onFileDrop: DirectiveHook<HTMLElement, VNode | null, OnFileDropProperties> = function(
	el,
	{
		modifiers,
		value: options,
	},
) {
	// We need to use `ondrop` instead of addEventListener as we have no reference to previous
	// event listener to remove it from the component
	el.ondrop = async (event: DragEvent) => {
		// Stop the event if called with "v-on-file-drop.stop"
		if (modifiers.stop) {
			event.stopPropagation()
		}
		// Prevent default drop behavior if called with "v-on-file-drop.prevent"
		if (modifiers.prevent) {
			event.preventDefault()
		}
		// Skip any drop handling if disabled or aborted (right click)
		if (options.disabled || event.button > 0) {
			return options.callback?.([])
		}

		let result: INode[]|Upload[] = []
		const draggingStore = useDragAndDropStore()
		if (draggingStore.isDragging) {
			// Internal files are being dragged
			const filesStore = useFilesStore()
			const nodes = filesStore.getNodes(draggingStore.dragging)
			await onDropInternalFiles(
				nodes,
				options.targetFolder,
				event.ctrlKey,
			)
			result = nodes
		} else if (event.dataTransfer) {
			const uploads = await onDropExternalFiles(
				event.dataTransfer,
				options.targetFolder,
			)
			result = uploads
		}

		return options.callback?.(result)
	}
}

export default onFileDrop
