<!--
SPDX-FileCopyrightText: 2026 Nextcloud contributors
SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Crypto key-derivation benchmark

The Passwords app decrypts six fields consecutively with the same key material.
This benchmark measures that workload using only synthetic keys and fields.
It verifies every decrypted value, counts `AES::setPassword()` calls and reports
three runs for 1, 100 and 344 revisions.

The script loads the source autoloaders, not `lib/base.php` or instance
configuration. Its configuration stub never calls the parent constructor and
returns only the supplied defaults. It does not access a database, vault or
network, and prints no keys, ciphertexts or plaintexts. Fixture creation is
outside the measured section; each run uses a fresh reader.

From a checkout with its pinned `3rdparty` submodule initialized:

```sh
php tests/benchmarks/crypto.php
```

To compare an earlier implementation while keeping dependencies identical,
extract its public source and run each implementation in a separate process:

```sh
git show 25900f62b68ae912780116ba8879d014ba2602ba:lib/private/Security/Crypto.php > /tmp/Crypto.before.php
php tests/benchmarks/crypto.php . /tmp/Crypto.before.php
php tests/benchmarks/crypto.php
```

Only pass trusted PHP source to the optional second argument. Neither command
changes the checkout's runtime files. JSON includes the loaded source's SHA-256
so results can be matched to the implementation.

For 344 revisions with six fields, the original derives 2,064 keys; the proposed
implementation derives 344. Durations depend on PHP, CPU and system load. This
benchmark does not measure HTTP, SQL or browser rendering; there is no timing
threshold in the unit tests.
