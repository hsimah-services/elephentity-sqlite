# Runtime compatibility for 0.1.0-alpha.2

Validated on 2026-09-27 with Clog commit
`93ad3c9322d9fd49b58c413cf84078c8b4904a04`, including its generated PHP entities,
SQLite installation/storage manifest and GraphQL manifest.

Runtime tags `v0.10.0` and `v0.11.0` both point to
`f3190c28d60e3e2e5f463f59e7d0992d1f700efd` in `elephentity-runtime`.
No runtime API or integration source changes were needed. Both integration
packages now require `elephentity/runtime ^0.10 || ^0.11`.

## Validation

Run from a workspace containing the two integration checkouts:

```sh
python3 elephentity-sqlite/tools/test-clog.py ~/Projects/clog
```

The runner builds a disposable Composer project from Clog's production requirements
and autoload mappings, with exact runtime versions and local path repositories for
the candidate integrations. It performs `composer update --dry-run`, installs fresh
dependencies, verifies the lockfile versions and runs each suite without network
access. It never reuses Clog's vendor directory or aliases runtime versions.

| Check | Runtime 0.10.0 | Runtime 0.11.0 |
| --- | --- | --- |
| Composer resolution and installation | Pass | Pass |
| Adapter conformance: all four relationship kinds | Pass | Pass |
| Schema upgrade and recovery | Pass | Pass |
| SQLite CRUD, policies, GraphQL pagination/Node/mutations, cascades | Pass | Pass |
| HTTP authentication, CSRF, sessions, GraphQL, compiled deep links | Pass | Pass |

Environment: PHP 8.3.33, webonyx/graphql-php 15.37.2,
psr/container 2.0.2 and psr/log 3.0.2, using `localhost/clog-php:8.3-rust`.
The candidate packages are assigned `0.1.0-alpha.2` by Composer's path repository
metadata. This validates the candidates before publication; it does not establish
that the version is available from Packagist.

## Release validation

After publishing **both** integration packages as `v0.1.0-alpha.2`, run:

```sh
python3 elephentity-sqlite/tools/test-clog.py ~/Projects/clog --package-version 0.1.0-alpha.2
```

This disables the path repositories and repeats the same matrix with published
artifacts. Consumers can then update their lockfiles to runtime 0.11 and rerun their
application checks. Browser tests and package-owned standalone coverage remain
separate from these Clog integration checks.
