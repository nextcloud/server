/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface BulkDeleteItem {
   path: string
   fileId: number
}

export interface BulkDeleteResult extends BulkDeleteItem {
   status: number
   attempted: boolean
}

export interface BulkDeleteResponse {
   results: BulkDeleteResult[]
   stopped: boolean
}

export interface BulkDeleteOptions {
   batchSize: number
   concurrency?: number
   request: (files: BulkDeleteItem[]) => Promise<unknown>
   onDeleted: (index: number) => void
   onError: (error: unknown) => void
}

/** Both sides validate the same raw UTF-8, user-relative paths. */
export function isBulkDeleteItem(value: BulkDeleteItem): boolean {
   return Number.isSafeInteger(value.fileId) && value.fileId > 0
      && typeof value.path === 'string' && value.path.startsWith('/')
      && new TextEncoder().encode(value.path).length <= 4096
      && !/[\u0000-\u001f\u007f\\]/.test(value.path)
      && value.path.slice(1).split('/').every((part) => part !== '' && part !== '.' && part !== '..')
}

/** Reject malformed or mismatched responses before emitting any success event. */
export function validateBulkDeleteResponse(value: unknown, files: BulkDeleteItem[]): BulkDeleteResponse {
   if (!value || typeof value !== 'object' || !('results' in value) || !Array.isArray(value.results)
      || !('stopped' in value) || typeof value.stopped !== 'boolean' || value.results.length !== files.length) {
      throw new Error('Invalid bulk delete response; refresh the file list before retrying')
   }
   var stopped = false
   for (var index = 0; index < files.length; index++) {
      var result = value.results[index]
      var file = files[index]!
      if (!result || result.path !== file.path || result.fileId !== file.fileId
         || !Number.isInteger(result.status) || typeof result.attempted !== 'boolean') {
         throw new Error('Bulk delete response does not match the requested files')
      }
      if (stopped) {
         if (result.attempted !== false || result.status !== 424) {
            throw new Error('Invalid bulk delete stopped-batch response')
         }
      } else {
         if (result.attempted !== true || (result.status !== 204 && (result.status < 400 || result.status > 599))) {
            throw new Error('Invalid bulk delete item status')
         }
         stopped = result.status !== 204
      }
   }
   if (value.stopped !== stopped) {
      throw new Error('Inconsistent bulk delete response')
   }
   return value as BulkDeleteResponse
}

/**
 * Bounded workers preserve input-order results even when requests finish out of
 * order. Never retry a POST or fall back to DELETE after dispatching a batch.
 */
export async function runBulkDelete(files: BulkDeleteItem[], options: BulkDeleteOptions): Promise<boolean[]> {
   var results = files.map(() => false)
   var batchSize = options.batchSize
   var concurrency = options.concurrency ?? 5
   if (!Number.isInteger(batchSize) || batchSize < 1 || batchSize > 100
      || !Number.isInteger(concurrency) || concurrency < 1 || concurrency > 5
      || !files.every(isBulkDeleteItem)
      || new Set(files.map((file) => file.path)).size !== files.length
      || new Set(files.map((file) => file.fileId)).size !== files.length) {
      options.onError(new Error('Invalid bulk delete selection or limits'))
      return results
   }

   var nextIndex = 0
   var stopped = false
   async function worker(): Promise<void> {
      while (!stopped && nextIndex < files.length) {
         // Reserve the slice synchronously, before yielding to another worker.
         var start = nextIndex
         nextIndex += batchSize
         var batch = files.slice(start, start + batchSize)
         try {
            var response = validateBulkDeleteResponse(await options.request(batch), batch)
            if (response.stopped) {
               stopped = true
            }
            for (var offset = 0; offset < response.results.length; offset++) {
               if (response.results[offset]!.status === 204) {
                  results[start + offset] = true
                  options.onDeleted(start + offset)
               }
            }
            if (response.stopped) {
               options.onError(new Error('Bulk deletion stopped after a failed item; refresh before retrying'))
            }
         } catch (error) {
            stopped = true
            options.onError(error)
         }
      }
   }
   // Already in-flight batches may finish after another worker reports failure.
   await Promise.all(Array.from({ length: Math.min(concurrency, Math.ceil(files.length / batchSize)) }, () => worker()))
   return results
}
