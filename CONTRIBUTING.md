# Contributing

## Development setup

```bash
composer install
tests/run.sh                                             # harness + unit tests
DB_HOST=127.0.0.1 DB_USER=root DB_PASSWORD=… tests/run.sh # + integration tests (creates and drops oc_extension_test)
```

Requirements: PHP 7.3+ with `mysqli`, `mbstring` and `dom`; Composer; `curl`, `tar` and `zip`; and, for integration tests, a MySQL 5.7+/8.x server and the `mysql` client.

## Rules for changes

1. **Every `<search>` must match all supported OpenCart versions.** `tests/run.sh` fails otherwise. Use `error="skip"` only for operations that are optional by design (for example theme-specific markup), and say so in a comment.
2. **SQL:** escape strings with `$this->db->escape()`, cast numbers with `(int)` / `(float)`. Add an integration test for new queries in `tests/integration/*-suite.php`, with fixtures in `fixtures.sql` (IDs ≥ 9000, synthetic data only).
3. **Output:** JSON for `<script>` goes through `Schemaorg\Builder::encode()`; HTML follows OpenCart's Twig conventions.
4. Keep PHP 7.3 compatible syntax (no arrow functions, typed properties, `match`, `str_contains`, …).
5. Bump the extension's `<version>`, update its README and `CHANGELOG.md`.

## Adding an extension

```
extensions/<kebab-name>/
  install.xml   (<code> in snake_case, <link> to this repository)
  upload/       (only installer-allowed directories; the harness checks this)
  README.md     (follow an existing extension's structure)
```
