<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# External ffmpeg preview service

Nextcloud can generate video previews on a separate host or container, so that
neither `ffmpeg` nor the CPU load of decoding videos is needed on the Nextcloud
server itself. This works the same way as [Imaginary](https://github.com/h2non/imaginary)
does for images: the `OC\Preview\MovieRemote` provider sends the video to the
service and gets a single JPEG still back.

This directory contains a minimal implementation of that service: a small
HTTP wrapper ([server.py](server.py)) around the `ffmpeg` package of Alpine Linux.

## Running the service

Build and start the container:

```sh
docker build -t nextcloud-ffmpeg-preview build/ffmpeg-preview-service
docker run -d --name ffmpeg-preview --restart unless-stopped \
    -p 8080:8080 \
    -e API_KEY=change-me \
    nextcloud-ffmpeg-preview
```

Or with Docker Compose next to Nextcloud:

```yaml
services:
  ffmpeg-preview:
    build: ./build/ffmpeg-preview-service
    restart: unless-stopped
    environment:
      API_KEY: change-me
```

Do not expose the service to the internet. It only needs to be reachable from
the Nextcloud server.

### Environment variables

| Variable          | Default            | Description                                                                 |
|-------------------|--------------------|-----------------------------------------------------------------------------|
| `PORT`            | `8080`             | Port the service listens on.                                                |
| `API_KEY`         | *(empty)*          | If set, requests must send `Authorization: Bearer <API_KEY>`.               |
| `MAX_UPLOAD_SIZE` | `0`                | Maximum accepted video size in bytes, `0` means unlimited.                  |
| `MAX_JOBS`        | number of CPUs     | Maximum number of concurrently running ffmpeg processes.                    |
| `FFMPEG_TIMEOUT`  | `60`               | Timeout in seconds for a single ffmpeg/ffprobe call.                        |
| `ACCESS_LOG`      | *(empty)*          | Set to any value to log every request.                                      |

Uploaded videos are buffered in a temporary file (`/tmp` in the container) while
the still is extracted, so make sure there is enough space for the largest video.

## Configuring Nextcloud

Add the provider and the service url to `config/config.php`:

```php
'enabledPreviewProviders' => [
	// ... the providers you already use ...
	'OC\Preview\MovieRemote',
],
'preview_ffmpeg_url' => 'http://ffmpeg-preview:8080',
'preview_ffmpeg_key' => 'change-me',
```

Or with `occ`:

```sh
occ config:system:set enabledPreviewProviders 20 --value='OC\Preview\MovieRemote'
occ config:system:set preview_ffmpeg_url --value='http://ffmpeg-preview:8080'
occ config:system:set preview_ffmpeg_key --value='change-me'
```

Use an index for `enabledPreviewProviders` that is not taken yet. Do not enable
`OC\Preview\Movie` at the same time, as the local provider would be tried as well.

## HTTP API

```
POST /thumbnail?width=<int>&height=<int>&time=<seconds>[,<seconds>...]
Authorization: Bearer <key>
Content-Length: <size>
<video>
```

- `width`/`height`: bounding box of the still; the aspect ratio is kept and the
  frame is never upscaled.
- `time`: comma separated list of timestamps that are tried in order until a
  frame could be extracted (Nextcloud sends `5,1,0`).

Responses: `200` with an `image/jpeg` body, `401` for a wrong key, `411` without
`Content-Length`, `413` if `MAX_UPLOAD_SIZE` is exceeded and `422` if no frame
could be extracted. `GET /health` returns `200` for health checks.

HDR videos (`smpte2084`/`arib-std-b67`) are tonemapped to SDR, like the local
`OC\Preview\Movie` provider does.
