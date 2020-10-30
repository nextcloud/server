# Running Psalm

Once you've set up your config file, you can run Psalm from your project's root directory with
```bash
./vendor/bin/psalm
```

and Psalm will scan all files in the project referenced by `<projectFiles>`.

If you want to run on specific files, use
```bash
./vendor/bin/psalm file1.php [file2.php...]
```

## Command-line options

Run with `--help` to see a list of options that Psalm supports.

## Shepherd

Psalm currently offers some GitHub integration with public projects.

Add `--shepherd` to send information about your build to https://shepherd.dev.

Currently, Shepherd tracks type coverage (the percentage of types Psalm can infer) on `master` branches.

## Running Psalm faster

Psalm has a couple of command-line options that will result in faster builds:

- `--threads=[n]` to run Psalm’s analysis in a number of threads
- `--diff` which only checks files you’ve updated since the last run (and their dependents).

In Psalm 4 `--diff` is turned on by default (you can disable it with `--no-diff`).

Data from the last run is stored in the *cache directory*, which may be set in [configuration](./configuration.md).
If you are running Psalm on a build server, you may want to configure the server to ensure that the cache directory
is preserved between runs.

Running them together (e.g. `--threads=8 --diff`) will result in the fastest possible Psalm run.
