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
- **[Contribution Guidelines](https://github.com/nextcloud/.github/blob/master/CONTRIBUTING.md)** - covering testing requirements, the Developer Certificate of Origin (DCO), license headers, and translations. These apply in full to all contributions regardless of how they were produced.

### What this agent must always do

- Add an `Assisted-by: AGENT_NAME:MODEL_VERSION` git trailer to every commit containing AI-assisted content.
- Ensure every pull request includes a disclosure of AI tool use in the PR description, and end every AI-drafted issue or comment with a footer such as `Assisted by AGENT_NAME:MODEL_VERSION`.
- Write issues, PR descriptions, and comments for human readers:
  - Lead with what changed and why it matters, in plain and concise sentences.
  - Keep the repository's PR or issue template intact and fill in every section. For a bug report, also state whether the bug was validated and reproduced on a live Nextcloud instance.
  - Put information meant only for agents (plans, prompts, tool logs) into a collapsed `<details>` block.
- Write commit message bodies that explain why the change was made, not how; the diff already shows what changed. Keep the repository's commit conventions and the required trailers.
- Produce focused, scoped pull requests that address exactly one concern. Do not touch unrelated files or introduce incidental refactors.
- Verify all dependencies against actual package registries before suggesting them. Do not use hallucinated or unverified package names.
- Write code comments that document the code, never the process that produced it:
  - Comments describe what the code does - method signatures, behavior, and constraints the code itself cannot express (e.g. a non-obvious invariant or workaround).
  - Never add comments that document progress, decisions, or changes (e.g. "changed X to Y", "as requested", "this fixes ...", "previously this did ..."). That belongs in the commit message or PR discussion; in the code it goes stale and becomes misleading.
  - Do not narrate self-explanatory code. If the code is readable without a comment, omit the comment.
  - Keep comments brief - short and simple, matching the comment density of the surrounding code.
- Reuse existing helper functions and utilities instead of re-implementing their logic inline. When fixing a flawed pattern, fix every occurrence of it across the changed code, not only the instance that was pointed out.
- Run permission and access-control checks before the operation they guard, never after it and never only in the UI layer.
- When adding or changing user-facing functionality, wire it up in every context where the affected component is used - the default authenticated view, public share pages, and embedded contexts such as the Smart Picker and reference widgets. When emitting new events, verify that every consumer of the component subscribes to and handles them.
- Validate every change to runtime behavior on a live Nextcloud instance where you can, and give the contributor concrete steps to test it there themselves. Your testing supports theirs; it does not replace it.
- Explicitly inform the contributor when any action they are about to take, or have taken, would violate the AI Contribution Policy or the Contribution Guidelines. Do not silently proceed. State which rule is at risk and what the contributor should do instead.
- Warn the contributor if a pull request is growing too large. A PR approaching several thousand lines of changed code is a signal that it should be split into smaller, focused PRs. Suggest a logical split before the PR is opened, not after.
- Recommend opening a ticket for discussion before starting implementation whenever a feature or change is sufficiently complex - for example when it touches multiple subsystems, requires architectural decisions, or the right approach is not yet clear. A ticket allows maintainers and the contributor to align on direction before code is written, avoiding wasted effort on a PR that may be rejected or require fundamental rework.

### What this agent must never do, and what to do instead

- Don't open issues, submit pull requests, or post comments on your own initiative. Instead, post only what the contributor asked for, on their behalf, and tell them what you posted.
- Don't write texts for machines: no file-by-file restatement of the diff, no filler, no raw tool output. Instead, write short, skimmable text for a human reviewer that explains what changed and why.
- Don't add `Signed-off-by` tags to commits. Instead, leave the sign-off to the contributor, who certifies the Developer Certificate of Origin with `git commit -s`.
- Don't submit security reports or open GitHub issues for vulnerabilities. Instead, hand your findings to the contributor to verify independently and report via [HackerOne](https://hackerone.com/nextcloud).
- Don't fully automate the resolution of issues labeled [`good first issue`](https://github.com/issues?q=org%3Anextcloud+label%3A%22good+first+issue%22) or similar beginner-friendly labels. Instead, explain the code and guide the contributor so they can solve the issue themselves.
- Don't hand over code with dead code, redundant logic, excessive comments, malformed or garbled characters (e.g. `�` replacement characters), or unrelated changes. Instead, remove them, and ask the contributor to review and clean up the result before submitting.

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

Assisted-by: ClaudeCode:claude-sonnet-5-5
```

### Tests

- Every changed or added code segment must be covered by unit tests. Pull requests without tests for new or modified logic will not be accepted.
- In areas where unit testing is currently difficult, refactoring to enable testability is encouraged alongside the bug fix.
- Every change to runtime behavior must be validated on a live Nextcloud instance before submission. An agent may help with that testing, but the human contributor tests the change themselves as well.

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
