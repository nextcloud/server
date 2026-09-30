<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: MIT
-->

# CI/CD and the deployment architecture

## Scope

This repository is the Nextcloud Server application. Its existing GitHub Actions workflows provide continuous integration for server code. A separate Compose smoke-test workflow starts a local demonstration of the group deployment topology and verifies service startup and backup artifact integrity. Neither workflow deploys to a live service or uses production credentials.

The deployment design has a load balancer and multiple Nextcloud app servers, with PostgreSQL and Redis remaining single instances. Backups are explicit, and optional/community apps remain extensible. `.github/workflows/deployment-architecture.yml` tests this topology using the published Nextcloud Apache image; it does not build an image from this server source checkout.

## Pipeline

The normal entry point is a pull request. Jobs use path filters where applicable to avoid running unrelated test suites. Scheduled runs provide additional coverage for selected test suites.

1. **PHP checks:** `.github/workflows/lint-php.yml` runs PHP linting on PHP 8.3 and 8.5. `.github/workflows/static-code-analysis.yml` runs Psalm and taint analysis for applicable changes.
2. **Server unit tests:** `.github/workflows/phpunit.yml` fans out to database and storage configurations, including PostgreSQL, MySQL/MariaDB, SQLite, and other supported service combinations. Its summary job reports failure if a required test job fails.
3. **Frontend unit tests:** `.github/workflows/node-test.yml` installs the repository's Node dependencies and runs `npm run test:coverage` when frontend source paths change.
4. **Feature integration tests:** `.github/workflows/integration-sqlite.yml` runs selected Behat suites with SQLite and supporting services such as Redis and OpenLDAP.
5. **Browser end-to-end tests:** `.github/workflows/playwright.yml` builds the frontend and runs sharded Playwright suites when its label or changed-test conditions allow them. It is not run for every ordinary pull request.
6. **Deployment architecture smoke test:** `.github/workflows/deployment-architecture.yml` validates Compose, starts PostgreSQL and Redis, bootstraps one app instance, scales the app service to three instances, starts HAProxy, checks Nextcloud's status endpoint through the load balancer, creates database/data/config/custom-app backups with maintenance mode enabled, restores the SQL dump into a scratch database, extracts the file archives, and checks checksums. The workflow tears down its ephemeral stack and volumes even on failure.
7. **Compliance:** `.github/workflows/reuse.yml` checks license and copyright metadata on pull requests.
8. **Merge gate:** Review the required checks on the pull request. Fix any failed job before merging; skipped path-filtered jobs mean their covered files were not changed.

To test a deployment change, push a branch or open a pull request that changes `deployment/**` or the workflow. The architecture smoke test also supports manual dispatch after the workflow is present on the default branch. Existing application tests remain responsible for validating server source changes. This is not a production deployment pipeline.

## Architecture coverage and gaps

| Architecture concern | What CI can check | What this CI does not prove |
| --- | --- | --- |
| Multiple app servers | The deployment smoke test runs three app containers behind HAProxy and checks the public status endpoint. App containers share the web-root volume; Redis is common to all instances and the official image configures Redis-backed PHP sessions. | Production load, balancing distribution, app-node failover, independent-host shared storage, TLS termination, and high availability. |
| PostgreSQL | PHPUnit includes a PostgreSQL test workflow; the deployment smoke test uses one PostgreSQL service. | Production database replication, capacity, recovery, or failover. |
| Redis | Existing test jobs exercise Redis-backed services; the smoke test uses one Redis instance. | Production Redis availability, persistence guarantees, clustering, capacity, or failover. |
| Explicit backups | `deployment/backup.sh` enables maintenance mode and writes PostgreSQL, data, config, and custom-app archives with checksums. CI restores the database dump into a scratch database and extracts the file archives. | Off-host backup retention, encryption/key management, scheduled execution, and a full service recovery drill. Keep backups on protected storage and test restores before relying on them. |
| Optional/community apps | Existing app and integration suites test the app code and selected integrations represented in those suites. | Compatibility of every optional/community app or its production container image. |
| HTTPS reverse proxy and load balancer | Not represented by these application test workflows. | TLS termination, proxy headers, network policy, and end-to-end traffic routing. |

This is a local/demo topology, not production-ready infrastructure. PostgreSQL and Redis remain single-instance failure points. The Compose endpoint uses HTTP bound to loopback, not public TLS. Backups are written to the local host and must be copied to protected off-host storage. A production rollout still needs a target environment, TLS, protected secrets, durable shared storage, backup retention and restore drills, monitoring, and operational review.

## AI-assisted design record

- **Initial user request:** “add or fix the current ci/cd to this new improved architecture”
- **Architecture review prompt supplied by the group:** “Review Your System (5 min) Work with your group Review your Module 1 architecture diagram. (You are always welcome to modify it) Identify what your pipeline needs to build and test, and eventually deploy. Identify anything in your architecture that the CI/CD workflow needs to account for. Use AI to Build the pipeline (15 min) You may ask AI to: Generate the pipeline configuration. Explain the configuration. Modify the workflow when your system requires something different. Document every step in details (AI prompts, what AI generated, and any decisions or changes your group made.)”
- **Group decisions shown in the architecture review:** Accept app-server scalability using a load balancer and multiple app servers; make backups explicit; retain the optional/community-app split. Modify the single-point-of-failure recommendation so app servers scale while PostgreSQL and Redis remain single instances. Reject the admin deployment-flow view in favor of a component architecture.
- **Scope clarifications:** First, “Build CI for this repo’s app tests and architecture documentation only (Recommended).” Later, “now make it work with our improved and accepted reccomendation and architecture”; the selected target was “Docker Compose for a class/demo deployment (Recommended).”
- **Generated outcome:** Retained and documented the existing application tests, then added Compose services for HAProxy, three scalable Nextcloud app instances, one PostgreSQL instance, one Redis instance, and persistent named volumes. Added a maintenance-mode backup script for the database, data, config, and custom apps.
- **Implementation decision:** CI starts and smoke-tests this local topology and validates backup artifacts; it does not deploy to a live host. PostgreSQL and Redis remain single instances per the group's modified recommendation. Production TLS, HA, off-host backup retention, and restore drills remain follow-up work.
