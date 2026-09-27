# elephentity-sqlite

Experimental SQLite storage for Elephentity's PHP runtime, extracted from Clog's
standalone backend. It uses PDO and implements `StorageAdaptor`; generated PHP
entities keep using `elephentity/runtime`. WordPress is not a dependency.

This is an initial working-tree import, not a release. Clog is still developing the
implementation. [Source provenance](docs/clog-source.json) records the exact file
hashes: the recorded Git HEAD alone does not contain this uncommitted work.
Original source licensing is preserved in [CLOG-LICENSE](CLOG-LICENSE).

## Package boundary

- `Database`: PDO SQLite connection, parameter binding, transactions and savepoints.
- `SQLiteAdaptor`: reads, queries, counts, writes and relationship storage.
- `Manifest/StorageManifest`: tables, columns, indexes and edge placements.
- `Sql`: query compilation and schema metadata.

PHP 8.3+, PDO SQLite, mbstring and `elephentity/runtime ^0.10` are required.
The Composer package name is `elephentity/sqlite`; no published release is assumed.
Use a Composer path or VCS repository while developing it.

The generic GraphQL PHP integration is a separate sibling repository,
`elephentity-graphql-php`, with Composer name `elephentity/graphql`.
Both packages consume the PHP runtime. SQLite is a storage choice alongside the
existing WordPress adapter; it does not replace the PHP entity generator.

## Validation and following Clog

With both sibling repositories checked out and Clog's PHP image and client build
available:

```sh
python3 tools/clog-status.py ~/Projects/clog
python3 tools/test-clog.py ~/Projects/clog
```

The status command reads both source packages and reports added, changed or deleted
files against their recorded imports. Exit 0 means unchanged, 1 means changes and 2
means invalid input. It never imports changes automatically. Review a changed file,
copy the accepted version, update its source hash, then rerun the tests.

The test command copies Clog's standalone fixture to a temporary directory,
replaces its two integration packages with these checkouts, and runs its integration
and HTTP suites in disposable containers. The live Clog checkout is never modified.
Tests require Podman and the `localhost/clog-php:8.3-rust` image (`--image` overrides
it). They use Clog's bundled runtime and webonyx dependencies; Composer dependency
resolution and compatibility against other versions are separate release checks.

Session monitoring is performed by the active agent. No background service or
scheduled job is installed by this repository.

See [the extraction notes](docs/extraction.md) before using this prototype.
