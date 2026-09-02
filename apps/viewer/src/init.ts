/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import plyrIcons from '../img/plyr.svg?raw'
import { createApp } from 'vue'
import Viewer from './views/Viewer.vue'
import { getViewer } from './api_package/viewer.ts'
import { registerAudioCustomElement, registerAudioHandler } from './models/audios.ts'
import { registerImageCustomElement, registerImageHandler } from './models/images.ts'
import { registerVideoCustomElement, registerVideoHandler } from './models/videos.ts'
import { logger } from './services/logger.ts'

const ViewerService = getViewer()

// Register the custom elements and handlers FIRST, before mounting the Vue app.
// This registers the viewer file actions as early as possible so they are part
// of the Files app's initial actions snapshot — otherwise the default
// "open in viewer" action can be missed and clicking a file navigates away
// instead of opening the viewer.
registerAudioCustomElement()
registerAudioHandler()
registerVideoCustomElement()
registerVideoHandler()
registerImageCustomElement()
registerImageHandler()

const ViewerApp = createApp(Viewer)

// Create top wrapper element
const ViewerRoot = document.createElement('div')
ViewerRoot.id = 'viewer'
document.body.appendChild(ViewerRoot)

// Put controls for video viewer
// Needed as Firefox CSP blocks the loading of the svg through the normal plyr system
const VideoControls = document.createElement('div')
VideoControls.innerHTML = plyrIcons
VideoControls.style.display = 'none'
document.body.appendChild(VideoControls)

// Mount and set the viewer instance
const ViewerInstance = ViewerApp.mount(ViewerRoot)
ViewerService._setViewer(ViewerInstance as InstanceType<typeof Viewer>)
logger.info('Viewer initialized', { ViewerInstance })
