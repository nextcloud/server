<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Nextcloud Viewer integration

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/viewer)](https://api.reuse.software/info/github.com/nextcloud/viewer)


## Usage
### 🔍 Add your own file view

If you want to make your app compatible with this app, you can register your own
handler with the methods provided by the
[`@nextcloud/viewer`](https://www.npmjs.com/package/@nextcloud/viewer) npm package.

Handlers are rendered as **native custom elements**, not as Vue components passed
directly to the viewer. You register a custom element with the browser, then
reference it from your handler by its `tagname`.

#### 1. Create your view component

Write a Vue component that consumes the `ViewerProps` props and can emit the
`ViewerEmits` events. The viewer passes the props automatically and reacts to the
emitted events.

```vue
<!-- MyView.vue -->
<template>
	<div>
		<img
			:src="src"
			:style="{ maxHeight: maxHeight + 'px', maxWidth: maxWidth + 'px' }"
			@load="emit('loaded')"
			@error="emit('errored', new Error('Could not load file'))">
	</div>
</template>

<script setup lang="ts">
import type { ViewerEmits, ViewerProps } from '@nextcloud/viewer'
import { computed } from 'vue'

const props = defineProps<ViewerProps>()
const emit = defineEmits<ViewerEmits>()

const src = computed(() => props.file.encodedSource)
</script>
```

`ViewerProps` provides:

| Prop             | Type      | Description                                     |
| ---------------- | --------- | ----------------------------------------------- |
| `file`           | `File`    | The file currently displayed                    |
| `files`          | `File[]`  | The list of files currently opened in the viewer |
| `maxHeight`      | `number`  | Max height of the viewer container              |
| `maxWidth`       | `number`  | Max width of the viewer container               |
| `editing`        | `boolean` | Whether the viewer is in editing mode           |
| `isSidebarShown` | `boolean` | Whether the sidebar is shown                    |

`ViewerEmits` lets you emit:

| Event              | Payload   | Description                                                    |
| ------------------ | --------- | ------------------------------------------------------------- |
| `loaded`           | –         | Notify the viewer your component is done loading              |
| `errored`          | `[Error]` | Notify the viewer an error occurred (custom message shown)    |
| `update:canSwipe`  | `[boolean]` | Enable/disable the swipe gesture (e.g. for custom controls) |
| `update:editing`   | `[boolean]` | Notify the viewer the editing mode changed                  |

#### 2. Define the custom element and register the handler

Turn your component into a custom element with Vue's `defineCustomElement`, define
it on `window.customElements`, then register a handler that points at it via
`tagname`:

```ts
import MyIconSvg from '@mdi/svg/svg/file-image.svg?raw'
import { t } from '@nextcloud/l10n'
import { registerHandler } from '@nextcloud/viewer'
import { defineCustomElement } from 'vue'
import MyView from './MyView.vue'

// A valid custom element tag name: lowercase, must contain a hyphen,
// no consecutive hyphens, no leading/trailing hyphen.
const tagname = 'my-app-viewer'

// Define the custom element. `shadowRoot: false` keeps the element in the
// light DOM so Nextcloud's global styles and CSS variables apply.
const MyElement = defineCustomElement(MyView, { shadowRoot: false })
window.customElements.define(tagname, MyElement)

// Register the handler.
registerHandler({
	// Unique identifier for the handler.
	id: 'my-app',

	// Translated, human-readable name shown in the "Open with …" menu.
	displayName: t('myapp', 'My viewer'),

	// The custom element tag name registered above.
	tagname,

	// Optional inline SVG icon for the "Open with …" menu entry.
	iconSvgInline: MyIconSvg,

	// Optional group. When opening a folder, files are collected across all
	// enabled handlers sharing this group, so e.g. an image handler and a
	// video handler in the 'media' group build one combined slideshow.
	group: 'media',

	// Return true when this handler can display the given files.
	enabled: (nodes) => nodes.every((node) => node.mime === 'image/png'),

	// Optional: preload data for the previous/next files so navigation is
	// snappier. Called for neighbouring nodes when a file is opened.
	preload: async (node) => {
		await fetch(node.encodedSource)
	},

	// Optional viewer modal theme: 'dark', 'light' or 'default'.
	theme: 'default',
})
```

The full handler shape (see the `IHandler` interface):

| Field           | Type                                  | Required | Description                                                        |
| --------------- | ------------------------------------- | -------- | ------------------------------------------------------------------ |
| `id`            | `string`                              | yes      | Unique, non-empty handler identifier                               |
| `displayName`   | `string`                              | yes      | Translated name shown in the "Open with …" menu                    |
| `tagname`       | `string`                              | yes      | Registered custom element tag name (must contain a hyphen)         |
| `enabled`       | `(nodes: File[]) => boolean`          | yes      | Whether the handler can open the given files                       |
| `iconSvgInline` | `string`                              | no       | Inline SVG icon for the menu entry                                 |
| `group`         | `string`                              | no       | Group used to combine handlers when opening a folder               |
| `preload`       | `(node: File) => Promise<void>`       | no       | Preload data for neighbouring files                                |
| `theme`         | `'dark' \| 'light' \| 'default'`      | no       | Viewer modal theme                                                 |

#### 3. Load your registration before the viewer

The handler must be registered **before** the viewer initializes. Load your
registration script from the server side with `\OCP\Util::addInitScript` so it runs
early enough:

```php
\OCP\Util::addInitScript('myapp', 'myapp-viewer-register');
```

### 🚀 Open the viewer programmatically

Use the public `getViewer()` API to open the viewer from your own code. It returns
a shared `Viewer` instance:

```ts
import { getViewer } from '@nextcloud/viewer'

const viewer = getViewer()

// Open a list of files, optionally starting on a specific file and forcing a
// specific handler by its id.
await viewer.open(files, files[0], options, 'my-app')

// Open every viewable file of a folder.
await viewer.openFolder(folder, file, options, 'my-app')

// Open two files side by side for comparison.
await viewer.compare(file1, file2, 'my-app')
```

Signatures:

- `open(nodes: File[], file?: File, options?: ViewerOptions, handlerId?: string): Promise<void>`
- `openFolder(folder: Folder, file?: File, options?: ViewerOptions, handlerId?: string): Promise<void>`
- `compare(node1: File, node2: File, handlerId?: string): Promise<void>`

`ViewerOptions` lets you hook into navigation and paging:

| Option     | Type                      | Description                                                     |
| ---------- | ------------------------- | --------------------------------------------------------------- |
| `loadMore` | `() => Promise<File[]>`   | Called to append more files when reaching the end of the list   |
| `onPrev`   | `() => void`              | Called when navigating to the previous item                     |
| `onNext`   | `() => void`              | Called when navigating to the next item                         |
| `onClose`  | `() => void`              | Called when the viewer is closed                                |
| `canLoop`  | `boolean`                 | Whether navigation loops from last to first item and vice versa |

> [!TIP]
> If you feel like your mime should be integrated in this repo, you can also create
> a pull request with your handler in the `models` directory and its view in the
> `components` directory. Please have a look at what's already here and take example
> of it (e.g. `models/videos.ts` + `components/Videos.vue`). 🙇‍♀️
