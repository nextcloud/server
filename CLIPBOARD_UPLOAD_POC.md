# Clipboard Image Upload – Proof of Concept

**Related issue:** [Nextcloud #37830](https://github.com/nextcloud/server/issues/37830)

## 1. Objective

The goal is to allow users to paste copied images directly into the currently open folder in Nextcloud Files using Ctrl+V or Cmd+V, without having to save the image locally and upload it manually.

My approach was to make clipboard upload an extension of the existing upload functionality rather than introducing a separate upload mechanism.

## 2. Scope and assumptions

- Support single and multiple images copied to the clipboard.
- Preserve the original filename, image content, MIME type and modification timestamp provided by the browser.
- Generate a valid fallback filename when the original filename is missing or invalid.
- Handle duplicate filenames using Nextcloud's existing conflict resolution dialog.
- Use existing upload permissions and storage quota restrictions.
- Preserve normal paste behaviour when the clipboard does not contain images.

The feature only processes image files exposed by the browser's clipboard event. Fetching images from copied URLs, processing HTML content and uploading non-image files are outside the scope of this PoC.

## 3. Supported views

| View | Clipboard upload |
|---|---|
| All files / Personal files | Supported in writable folders |
| Nested folders | Supported |
| Internal shared folders | Supported with upload permission |
| Public writable folders | Supported |
| Read-only folders | Not supported |
| File Drop shares | Not supported |
| Overview views without a valid upload destination | Not supported |
| Folders with exhausted storage quota | Not supported |

Rather than relying on a hardcoded list of view names, the implementation uses the current folder, view context and existing permission checks.

File Drop is intentionally excluded because visitors cannot read the destination contents required by the clipboard conflict resolution logic.

## 4. Special cases and expected behaviour

| Scenario | Expected behaviour |
|---|---|
| Pasting text, HTML or a URL without image files | Preserve normal paste behaviour |
| Pasting while editing a filename or using an input field | Ignore clipboard upload |
| Pasting while a modal dialog is open | Ignore clipboard upload |
| Duplicate filenames within a batch | Generate unique filenames |
| Conflict with an existing file | Use existing conflict resolution options |
| Cancelling conflict resolution | Cancel the batch |
| Navigating away during an upload | Retain the original destination |
| Consecutive accepted paste events | Process batches sequentially |
| Upload or destination-fetch failure | Handle the error and allow subsequent uploads |

## 5. Implementation approach

The implementation is divided into two main parts:

- **FilesList.vue:** Registers and removes the paste event listener, validates the current view and destination, and checks upload permissions and quota.
- **ClipboardUploadService.ts:** Extracts clipboard images, prepares filenames, handles conflicts and passes the files to Nextcloud's existing uploader.

I chose this separation to keep the view responsible for application context and the service responsible for clipboard-specific logic.

Reusing the existing uploader and conflict picker avoids duplicating upload functionality and keeps the behaviour consistent with standard file uploads.

No new dependencies or backend changes were introduced.

## 6. Testing

The implementation was validated using three levels of testing:

| Testing approach | Result |
|---|---|
| Vitest unit tests | 13/13 passed |
| Playwright end-to-end tests | 10/10 passed |
| Manual testing | Completed |

Manual testing included native clipboard interactions, nested folders, filename conflicts, sharing permissions, storage quotas, network interruptions and navigation during uploads.

## 7. Known limitations

- File Drop support would require a different approach to conflict handling.
- The local development environment displays incomplete metadata in the existing conflict dialog. This behaviour was reproduced on the original baseline without the clipboard implementation and was therefore left unchanged.

## 8. AI-assisted development

I used ChatGPT and Cursor for exploring implementation approaches, discussing edge cases, refining code, designing tests and reviewing changes.

I evaluated the suggestions against Nextcloud's existing architecture and conventions, reviewed the implementation, executed the automated tests and manually validated the feature in the development environment.

## 9. Running the tests

The implementation was developed using Nextcloud's official Docker development environment.

**Unit tests:**

```bash
npm test -- apps/files/src/services/ClipboardUploadService.spec.ts
```

**End-to-end tests:**

```bash
npx playwright test clipboard-upload.spec.ts --workers=1
```

To manually reproduce the feature, open a writable folder in Nextcloud Files, copy an image and press Ctrl+V or Cmd+V.