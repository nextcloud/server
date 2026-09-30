<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Nextcloud AIO deployment

This Compose file starts the official [Nextcloud All-in-One (AIO) mastercontainer](https://github.com/nextcloud/all-in-one). AIO creates and manages the Nextcloud and supporting containers, including its database and cache. The Compose file deliberately does not define or scale those child containers.

The mastercontainer setup interface is bound to `127.0.0.1:8080`; access it locally or through an SSH tunnel. The AIO Apache endpoint is configured on `127.0.0.1:11000` for a host-based reverse proxy. Configure and secure that proxy separately before exposing the service to users. The Docker socket mount gives AIO control over the host's Docker daemon, so run this only on a trusted host.

This AIO configuration is not a horizontally scaled deployment: AIO does not provide the separate load balancer, replicated Nextcloud app servers, or independently managed PostgreSQL and Redis services shown in the architecture diagram. Deployments requiring those components must use a separately designed architecture rather than scaling AIO-managed containers.

The `Deployment configuration` workflow validates the Compose file on pull requests and pushes to `master`; it does not deploy or start containers.
