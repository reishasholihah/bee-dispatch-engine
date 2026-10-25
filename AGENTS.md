# AGENTS.md

Guidelines for AI coding agents working on BeeDispatch-Engine, a Laravel API that matches delivery drivers to orders by proximity.

## Stack
- PHP ^8.3, Laravel 13, PHPUnit 12
- Default database: SQLite (tests use in-memory SQLite)

## Code style
- PSR-12, enforced with `./vendor/bin/pint`
- `declare(strict_types=1);` in every PHP file
- Modern PHP: constructor promotion, typed properties, backed enums for statuses

## Architecture
- Thin API controllers; business logic lives in service classes (e.g. the driver matching service)
- Form Requests for validation, API Resources for JSON responses
- Feature tests for every endpoint, plus edge cases: no drivers available, driver already busy, distance ties

## Workflow
- Run `composer test` before considering any change done
- Keep changes small and focused; one concern per commit
- Do not commit `.env` or any credentials
