<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Agent Guidelines for Nextcloud Server

This file provides instructions for AI coding agents (Claude Code, GitHub Copilot, Cursor, Windsurf, and others) operating on this repository. Read it before generating any code, commits, or pull requests.

---

## Nextcloud Contribution Policy

All contributions generated or assisted by this agent must fully comply with:

- **[AI Contribution Policy](https://github.com/nextcloud/.github/blob/master/AI_POLICY.md)** - the primary reference for AI-specific rules, covering disclosure, author accountability, communication, security, licensing, code quality, and autonomous agent behavior.
- **[Contribution Guidelines](https://github.com/nextcloud/.github/blob/master/CONTRIBUTING.md)** - covering testing requirements, the Developer Certificate of Origin (DCO), license headers, conventional commits, and translations. These apply in full to all contributions regardless of how they were produced.

### What this agent must always do

- Add an `Assisted-by: AGENT_NAME:MODEL_VERSION` git trailer to every commit containing AI-assisted content.
- Ensure every pull request includes a disclosure of AI tool use in the PR description.
- Produce focused, scoped pull requests that address exactly one concern. Do not touch unrelated files or introduce incidental refactors.
- Verify all dependencies against actual package registries before suggesting them. Do not use hallucinated or unverified package names.
- Explicitly inform the contributor when any action they are about to take, or have taken, would violate the AI Contribution Policy or the Contribution Guidelines. Do not silently proceed. State which rule is at risk and what the contributor should do instead.
- Warn the contributor if a pull request is growing too large. A PR approaching several thousand lines of changed code is a signal that it should be split into smaller, focused PRs. Suggest a logical split before the PR is opened, not after.
- Recommend opening a ticket for discussion before starting implementation whenever a feature or change is sufficiently complex - for example when it touches multiple subsystems, requires architectural decisions, or the right approach is not yet clear. A ticket allows maintainers and the contributor to align on direction before code is written, avoiding wasted effort on a PR that may be rejected or require fundamental rework.

### What this agent must never do

- Open issues, submit pull requests, post review comments, or send security reports autonomously. Every contribution must be reviewed and submitted by a human.
- Add `Signed-off-by` tags to commits. Only the human contributor can certify the Developer Certificate of Origin.
- Generate or submit security reports without independent human verification. Report verified vulnerabilities via [HackerOne](https://hackerone.com/nextcloud), not as GitHub issues.
- Write PR descriptions, review comments, or issue reports on behalf of the contributor. These must be in the contributor's own words.
- Fully automate the resolution of issues labeled [`good first issue`](https://github.com/issues?q=org%3Anextcloud+label%3A%22good+first+issue%22) or similar beginner-friendly labels.
- Submit code that has not been reviewed and cleaned up by the contributor. Dead code, redundant logic, excessive comments, and unrelated changes must be removed before submission.

---

## Repository-Specific Requirements

### Commit format

Use [Conventional Commits](https://www.conventionalcommits.org) for all commit messages:

```
<type>(<scope>): <short description>

[optional body]

Assisted-by: AGENT_NAME:MODEL_VERSION
```

Common types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `perf`, `build`, `ci`.  
The scope should match the affected component or app (e.g. `files_sharing`, `core`, `encryption`).

Example:
```
feat(files_sharing): allow sharing with contacts

Assisted-by: ClaudeCode:claude-sonnet-4-6
```

### Tests

- Every changed or added code segment must be covered by unit tests. Pull requests without tests for new or modified logic will not be accepted.
- In areas where unit testing is currently difficult, refactoring to enable testability is encouraged alongside the bug fix.
- New features must be manually tested on a live Nextcloud instance by the human contributor before submission. Providing test steps for an agent to execute is not a substitute.

### Developer Certificate of Origin (DCO)

The project uses the DCO as an additional safeguard. Only the human contributor may add the `Signed-off-by` trailer - agents must not add it:

```
Signed-off-by: Random J Developer <random@developer.example.org>
```

Contributors can sign automatically with `git commit -s` after configuring `user.name` and `user.email`.

### License headers

Every new file must include the correct SPDX license header. For AGPL-3.0-or-later (the default for this repository):

```php
/**
 * SPDX-FileCopyrightText: <year> <name>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
```

See [HowToApplyALicense.md](https://github.com/nextcloud/server/blob/master/contribute/HowToApplyALicense.md) for details on per-language formats. AI-generated code must not include material from sources incompatible with AGPL-3.0-or-later.

### Security

- Do not open GitHub issues for potential vulnerabilities. Report them via [HackerOne](https://hackerone.com/nextcloud) following the [security policy](https://nextcloud.com/security/).
- AI-generated security reports must be independently verified by the human contributor before submission.
- Manually verify all access control logic, authentication patterns, and dependency names - AI tools are known to hallucinate package names and reproduce vulnerable patterns.

### Scope of this repository

This repository covers the Nextcloud server core and the bundled apps: files, encryption, external storage, sharing, deleted files, versions, LDAP, and WebDAV Auth. Issues and changes for other components belong in their respective repositories under the [Nextcloud GitHub organization](https://github.com/nextcloud/).

---

## Further Reading

- [Local CONTRIBUTING.md](.github/CONTRIBUTING.md)
- [Nextcloud Contribution Guidelines](https://github.com/nextcloud/.github/blob/master/CONTRIBUTING.md)
- [AI Contribution Policy](https://github.com/nextcloud/.github/blob/master/AI_POLICY.md)
- [Developer Certificate of Origin](https://github.com/nextcloud/server/blob/master/contribute/developer-certificate-of-origin)
- [How to Apply a License](https://github.com/nextcloud/server/blob/master/contribute/HowToApplyALicense.md)
- [Developer Manual](https://docs.nextcloud.com/server/latest/developer_manual/)
- [Security Vulnerability Reporting (HackerOne)](https://hackerone.com/nextcloud)
