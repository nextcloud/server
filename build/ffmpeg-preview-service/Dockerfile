# syntax=docker/dockerfile:latest
# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
FROM alpine:3.24.2

RUN set -ex; \
    apk upgrade --no-cache -a; \
    apk add --no-cache \
        ffmpeg \
        python3 \
        tzdata \
        ca-certificates

COPY --chmod=755 server.py /usr/local/bin/ffmpeg-preview-service

ENV PORT=8080
EXPOSE 8080
USER nobody:nobody

ENTRYPOINT ["/usr/local/bin/ffmpeg-preview-service"]
HEALTHCHECK CMD ["wget", "-qO-", "http://127.0.0.1:8080/health"]
