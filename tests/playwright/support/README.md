<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# @nextcloud/playwright-poms

Playwright Page Object Models, fixtures and helpers for the Nextcloud server UI
and its bundled apps. Apps that integrate into server can reuse them instead of
re-describing the files list, the sidebar or the sharing dialog themselves.

These are the same sources the server repository runs its own end-to-end suite
against, so a UI change and the matching POM change always land in one pull
request.

## Installation

The package is published as a nightly, versioned after the server release it
describes. Pick the dist-tag for the branch you test against:

```bash
npm i -D @nextcloud/playwright-poms@nightly           # master
npm i -D @nextcloud/playwright-poms@nightly-stable35
npm i -D @nextcloud/playwright-poms@nightly-stable34
```

`@playwright/test` is a peer dependency and must resolve to a single copy —
fixtures that cross a package boundary break if two versions are installed.

## Usage

Page Object Models come from the package root:

```typescript
import { FilesListPage, FilesSidebarPage } from '@nextcloud/playwright-poms'
```

Helpers and fixtures have their own subpaths, because every fixture module
exports a symbol named `test`:

```typescript
import { uploadContent } from '@nextcloud/playwright-poms/utils/dav'
import { createShare } from '@nextcloud/playwright-poms/utils/sharing'
import { test } from '@nextcloud/playwright-poms/fixtures/files-page'
import { expect } from '@nextcloud/playwright-poms/matchers'
```

To combine a server fixture with your app's own, use Playwright's `mergeTests`
rather than extending the server chain:

```typescript
import { mergeTests } from '@playwright/test'
import { test as filesTest } from '@nextcloud/playwright-poms/fixtures/files-page'
import { test as myAppTest } from './fixtures/my-app.ts'

export const test = mergeTests(filesTest, myAppTest)
```

## Exports

| Subpath | Contents |
|---|---|
| `.` | All Page Object Model classes and the types they expose |
| `./matchers` | `expect` extended with the custom matchers |
| `./sections/*` | A single Page Object Model, e.g. `./sections/FilesListPage` |
| `./utils/*` | Helpers, e.g. `./utils/dav`, `./utils/sharing`, `./utils/systemtags` |
| `./fixtures/*` | Fixture modules, e.g. `./fixtures/files-page` |

## Versioning

`X.Y.Z` mirrors `$OC_Version` of the branch the package was built from, so
`35.0.0-nightly.20260917` describes the stable35 UI.

The date is a prerelease identifier rather than `+` build metadata on purpose:
semver ignores build metadata when ordering versions, so two nightlies of the
same base version would compare equal and could not be resolved to the newer
one.

There is no stable release channel yet. Pin an exact version if you need a
reproducible CI run.

## Development

The package is built with `tsc` and published as compiled JavaScript with type
declarations — Playwright does not transpile TypeScript inside `node_modules`,
so shipping the sources would not work for consumers.

```bash
cd tests/playwright/support
npm install
npm run build
```

The server's own specs import these files by relative path and are unaffected
by the build output.
