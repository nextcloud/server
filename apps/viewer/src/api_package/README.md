<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Nextcloud Viewer integration

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/viewer)](https://api.reuse.software/info/github.com/nextcloud/viewer)


## Usage
### 🔍 Add you own file view

If you want to make your app compatible with this app, you can use the methods provided by the [`@nextcloud/viewer`](https://www.npmjs.com/package/@nextcloud/viewer) npm.js package:
1. Create a vue component which use the `path` and `mime` props (they will be automatically passed by the viewer)
2. Register your mime viewer with the following:
   ``` js
    import { registerHandler } from '@nextcloud/viewer'
    import VideoView from 'VideoView.vue'

    registerHandler({
        // unique id
        id: 'video',

       // optional, it will group every view of this group and
       // use the proper view when building the file list
       // of the slideshow.
       // e.g. you open an image/jpeg that have the `media` group
       // you will be able to see the video/mpeg from the `video` handler
       // files that also have the `media` group set.
       group: 'media',

       // the list of mimes your component is able to display
       mimes: [
            'video/mpeg',
            'video/ogg',
            'video/webm',
            'video/mp4'
        ],

        // your vue component view
        component: VideoView
    })
   ```
3. Make sure your script is loaded with `\OCP\Util::addInitScript` so that the handler is registered **before** the viewer is loaded.

> [!TIP]
> If you feel like your mime should be integrated on this repo, you can also create a pull request with your object on the `models` directory and the view on the `components` directory. Please have a look at what's already here and take example of it. 🙇‍♀️
