# Contributing to PHP Composables

Thank you for considering a contribution to PHP Composables.  
This project aims to provide a modular, reactive, versioned workflow framework for PHP applications.  
Contributions of all kinds—bug reports, fixes, features, documentation, discussions—are welcome.

Please take a moment to review the guidelines below to ensure a smooth and effective contribution process.

## How to Contribute

### 1. Reporting Bugs
Before opening an issue:

- Ensure the bug reproduces on the latest `main`.
- Include precise steps, expected behaviour, actual behaviour, error logs, and minimal code to reproduce.
- Tag the issue appropriately (e.g., `bug`, `regression`, `critical`).

### 2. Requesting Enhancements
Feature proposals should include:

- A clear problem statement.
- Expected behaviour.
- Example usage.
- Potential risks or breaking changes.

Well-defined proposals are much more likely to be accepted.

### 3. Submitting Pull Requests

#### Fork > Branch > PR model
1. Fork the repository.
2. Create a feature branch:
```bash
git checkout -b feature/my-new-feature
```

3. Commit changes with clear messages.
4. Ensure tests pass locally:
```bash
composer install
composer test
```

5. Ensure coding standards:
```bash
vendor/bin/phpcs --standard=PSR12 src tests
vendor/bin/php-cs-fixer fix --dry-run
```

6. Run static analysis:
```bash
vendor/bin/phpstan analyse src tests --level=max
```

7. Run mutation tests (optional, but preferred):
```bash
vendor/bin/infection
```

8. Push and submit a PR to `master`.

#### PR Requirements
- All tests must pass.
- PHPStan must report no errors.
- No new PHPCS violations.
- New features must include appropriate tests.
- Breaking changes require explicit discussion and MUST target a major version.

#### PR Reviews
All PRs undergo:
- Automated CI (GitHub Actions)
- Manual code review
- Mutation score evaluation (informational but encouraged)

## Testing Requirements
This library requires high test coverage and mutational robustness.

### Mandatory:
- PHPUnit tests
- Coverage for new code
- Deterministic, isolated tests (no external services)

### Strongly Recommended:
- Infection mutation tests
- Integration tests for modules/pipelines/events

## Dependency Guidelines
- Avoid unnecessary dependencies.
- Use PHP standard library where possible.
- For features requiring external libraries, open an issue first.

## Coding Standards
PHP Composables follows:
- **PSR-1**, **PSR-4**, **PSR-12**
- Strict types
- Immutable data structures where reasonable
- Purity for module operations unless explicitly documented

## Documentation
Contributors should update the README or docs when modifying behaviour, adding features, or introducing new components.

---

## Security Issues
Please **do not** open GitHub issues for security problems.  
Instead, refer to **SECURITY.md** for responsible disclosure instructions.

## Community
Every contribution improves the ecosystem. Thank you for helping make PHP Composables a robust, modular, and developer-friendly workflow framework.
