<!--
SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Playwright end-to-end tests

Playwright tests for the Nextcloud server core and bundled apps.
The test runner starts a Nextcloud instance inside Docker automatically — no manual setup is needed.

## Running the tests

```bash
# Install the browser binary once
npm run playwright:install

# Run all tests (starts the server automatically)
npm run playwright

# Run a single spec file
npx playwright test tests/playwright/e2e/files/files-sidebar.spec.ts

# Run with the interactive UI (recommended for local development)
npx playwright test --ui
```

The dev server is reused between runs locally (`reuseExistingServer: true`) so subsequent runs start faster.

## Viewing test traces

Traces are captured on the first retry of a failing test (`trace: 'on-first-retry'` in `playwright.config.ts`). After a run that produced trace files, open the Playwright trace viewer:

```bash
# Open the HTML report — includes a "Traces" link for each failing test
npx playwright show-report

# Open a specific trace archive directly
npx playwright show-trace test-results/<test-name>/trace.zip
```

The trace viewer shows a timeline of every action, a DOM snapshot at each step, network requests, and console output. Use it to pinpoint exactly where a test diverged from expected behavior.

For an even faster loop while writing tests, run in **headed mode** so you can watch the browser live:

```bash
npx playwright test --headed --project=chrome tests/playwright/e2e/files/files-sidebar.spec.ts
```

### Viewing test traces from CI

When a test failed on the CI it is also possible to review the full test run locally.
For this download the "HTML report" archive from the CI summary of the Playwright tests.
Then extract it and use `playwright show-trace` as described above.

## Directory layout

```
tests/playwright/
├── e2e/                 # Test specs, one directory per feature area
│   ├── dav/
│   ├── files/
│   ├── systemtags/
│   └── theming/
└── support/
    ├── fixtures/        # Playwright fixture extensions (auth, page objects)
    ├── matchers.ts      # Custom expect matchers
    ├── sections/        # Page Object Model classes
    └── utils/           # Shared helpers (DAV, theming, …)
```

## Adding a new test

We use Page Object Models to abstract the Nextcloud UI and make tests reusable to easier create new tests and ease maintenance.
You can find more general information here:
- [General Playwright documentation](https://playwright.dev/docs/writing-tests)
- [Page Object Models](https://playwright.dev/docs/pom)

### 1. Pick or create a fixture

Fixtures in `support/fixtures/` extend Playwright's `test` with auth and page objects. Use an existing one when the test area is already covered:

| Fixture file | When to use |
|---|---|
| `files-page.ts` | Tests that need a random user with `filesListPage` and `filesSidebar` |
| `random-user-session.ts` | Any test needing a fresh random user, no page objects |
| `admin-session.ts` | Admin-only tests |
| `admin-theming-page.ts` | Theming admin settings |
| `admin-appstore-page.ts` | Appstore admin settings |

If no existing fixture fits, extend the closest one:

```typescript
import { test as baseTest } from './random-user-session.ts'
import { MyPage } from '../sections/MyPage.ts'

export const test = baseTest.extend<{ myPage: MyPage }>({
    myPage: async ({ page }, use) => {
        await use(new MyPage(page))
    },
})
export { expect } from '../matchers.ts'
```

### 2. Write the spec

Create `e2e/<area>/my-feature.spec.ts`. Import `test` and `expect` from the fixture:

```typescript
import { test, expect } from '../../support/fixtures/files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'

test.describe('Files: my feature', () => {
    test.beforeEach(async ({ user, page, filesListPage }) => {
        await uploadContent(page.request, user, Buffer.from('hello'), 'text/plain', '/hello.txt')
        await filesListPage.open()
    })

    test('does something', async ({ filesListPage }) => {
        await expect(filesListPage.getRowForFile('hello.txt')).toBeVisible()
    })
})
```

**Always set up `waitForResponse` before the action that triggers the request**, otherwise there is a race condition:

```typescript
const saved = page.waitForResponse(r => r.url().includes('/endpoint'))
await page.getByRole('button', { name: 'Save' }).click()
await saved
```

### 3. Page Object Models

Page objects live in `support/sections/`. Each class wraps a `Page` or a scoped `Locator` and exposes named locators and action methods. This keeps selectors out of the specs and makes them easy to update when the UI changes.

A minimal page object:

```typescript
import type { Locator, Page } from '@playwright/test'

export class MyFeaturePage {
    constructor(private readonly page: Page) {}

    // Locators — return Locator, never await
    container(): Locator {
        return this.page.locator('[data-cy-my-feature]')
    }

    submitButton(): Locator {
        return this.container().getByRole('button', { name: 'Submit' })
    }

    // Actions — async, orchestrate one user interaction
    async open(): Promise<void> {
        await this.page.goto('apps/myapp')
        await this.container().waitFor({ state: 'visible' })
    }
}
```

Guidelines:
- **Locator methods** are synchronous and return `Locator`. Only actions are `async`.
- Scope child locators to `this.container()` so they stay inside the component boundary.
- Prefer accessible selectors (`getByRole`, `getByLabel`) over CSS classes. Fall back to `data-cy-*` attributes for elements that have no stable accessible name.
- Add the page object to the appropriate fixture so tests receive it as a parameter — do not instantiate page objects inside specs.
