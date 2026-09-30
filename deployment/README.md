<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Scalable Nextcloud demo stack

This Compose stack demonstrates the group's accepted topology: HAProxy routes requests to multiple Nextcloud app containers, which share the Nextcloud web root and use one PostgreSQL database and one Redis cache/session store. PostgreSQL and Nextcloud state use persistent named volumes; Redis is intentionally ephemeral.

This is a class/demo stack, not a production deployment. It binds plain HTTP to `127.0.0.1:8080`; it has no TLS termination, high availability for PostgreSQL or Redis, off-host backups, monitoring, or restore automation. Do not expose it publicly or use the example credentials outside a disposable local environment. Pin and review image versions and secrets before any real deployment.

## Run locally

From the repository root:

```sh
cp deployment/.env.example .env
docker compose --file deployment/compose.yaml up --detach --wait db redis app
docker compose --file deployment/compose.yaml up --detach --wait --scale app=3 load-balancer
curl --fail http://127.0.0.1:8080/status.php
```

Start the first app container before scaling it so initial setup completes and becomes healthy before additional instances join. Pass `--scale app=3` when starting the load balancer too; each Compose `up` reconciles the requested replica count, so omitting the scale option resets the service to one instance. The status endpoint should report `"installed": true` before treating the stack as ready.

The HAProxy frontend discovers up to three app containers through Docker's internal DNS. To add more instances, update the HAProxy `server-template` slot count and the scale command together.

## Create a local backup

With the stack running, execute:

```sh
bash deployment/backup.sh
```

The script enables Nextcloud maintenance mode, writes a custom-format PostgreSQL dump plus compressed data, config, and custom-app archives under `deployment/backups/`, writes SHA-256 checksums, and turns maintenance mode off. The backup directory is ignored by Git. Config backups contain credentials; protect them. Copy backups to separate protected storage and rehearse restores before relying on them.

Stop the demo stack with `docker compose --file deployment/compose.yaml down`. Named volumes are retained. To remove the demo data as well, use `docker compose --file deployment/compose.yaml down --volumes`.

## CI

The `Deployment architecture` GitHub Actions workflow runs on pull requests and pushes affecting `deployment/**` or the workflow itself. It validates Compose, starts the services, scales Nextcloud to three app containers, checks the app through HAProxy, creates a backup, and verifies the database dump, tar archives, and checksums. It always tears down the ephemeral CI stack and volumes.

The workflow consumes the published `nextcloud:apache` image; it tests the deployment topology, not a container image built from this repository's source. Existing PHPUnit, Node, integration, lint, and static-analysis workflows continue to validate application changes separately. The topology smoke test does not prove production capacity, node failover, TLS, or disaster recovery.

See [CI/CD and the deployment architecture](../.github/CI-CD.md) for the complete pipeline map, coverage limits, and design decisions.
