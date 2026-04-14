# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

## Project overview

Hospes is a REST API for independent lodging hosts (hotels, gites, cabins, B&Bs, apartments). It handles multi-property management, seasonal pricing, booking with concurrency protection, iCal sync, team permissions, messaging, reviews, and platform administration.

## Stack

- PHP 8.4 / Symfony 7.4 LTS
- API Platform (resource-driven REST endpoints)
- Doctrine ORM / PostgreSQL (requires `btree_gist` extension for EXCLUDE USING gist constraints)
- JWT auth via LexikJWTAuthenticationBundle
- PHPUnit 11, PHPStan level 6, PHP-CS-Fixer

## Setup

```bash
composer install
docker compose up -d          # PostgreSQL
php bin/console doctrine:migrations:migrate
symfony server:start
```

## Commands

```bash
# Static analysis
vendor/bin/phpstan analyse -l 6 src

# Code formatting
vendor/bin/php-cs-fixer fix

# Tests (PostgreSQL required, not SQLite — btree_gist constraints)
vendor/bin/phpunit --testsuite=unit
vendor/bin/phpunit --testsuite=integration
vendor/bin/phpunit --testsuite=functional
vendor/bin/phpunit --filter=TestClassName::test_method_name  # single test

# Symfony console
php bin/console doctrine:migrations:migrate
php bin/console debug:router
```

## Architecture

Follow standard Symfony + API Platform conventions as spec'd in the documentation files.

- **Entities** (Doctrine ORM): 20+ entities with UUID PKs. Key entities: User, Lodging, Booking, Season, BlockedDate, StaffAssignment, Conversation, Review, Notification.
- **API Platform resources**: endpoints exposed via PHP attributes on entities/DTOs, not manual controllers. ~78 endpoints across 17 sections.
- **Error responses**: RFC 7807 (Problem Details for HTTP APIs). API Platform handles this natively.
- **Security**: Symfony Voters for authorization (StaffVoter, LodgingOwnerVoter, BookingAccessVoter). Staff permissions are stored in DB and checked per-request (not in JWT). Multi-tenant isolation is mandatory: a host's staff can never access another host's resources.
- **Services**: domain logic in dedicated services: `AvailabilityResolver`, `PriceCalculator`, `OrphanProtectionChecker`, `PendingBookingCleaner`, `IcalSyncService`, `CancellationPolicyResolver`, `DepositManager`, `StatisticsCalculator`.

## Key business rules

- **Monetary amounts**: always stored as integers in euro cents. Never use float for money.
- **Dates and times**: ISO 8601 format, UTC storage. Dates without time (check-in/check-out) are `Y-m-d` strings.
- **Night convention**: checkout day J and checkin day J are compatible (a booking from 10th to 13th occupies nights 10, 11, 12).
- **Booking TTL**: new bookings are `pending` for 15 min, then auto-cancelled. Cleanup via lazy check + cron.
- **Concurrency**: PostgreSQL EXCLUDE USING gist constraint prevents double bookings at DB level, backed by application-level check (defense in depth).
- **Pricing**: night-by-night resolution. Each night checks for a covering season, falls back to base rate. Weekend = Friday and Saturday nights. Rounding only on final total. Amounts snapshotted at booking creation.
- **Orphan protection**: configurable per lodging. Rejects bookings that would create a gap shorter than min_stay.
- **Staff permissions**: granular (can_view_bookings, can_edit_bookings, can_block_dates, can_view_revenue, can_manage_lodgings) + lodging scope. Revenue filtering (not 403) when can_view_bookings without can_view_revenue.

## Do NOT

- Use SQLite for tests (btree_gist requires PostgreSQL)
- Use float for monetary values (always integer cents)
- Create manual controllers (use API Platform attributes on entities/DTOs)
- Put business logic in controllers (use dedicated services)
- Store permissions in JWT (DB-only, checked per request)
- Return 403 for filtered data (use response filtering instead, e.g. hide revenue fields)
- Allow season date overlap (validate and reject at creation)
- Skip application-level availability check (DB constraint is a safety net, not the primary check)

## Testing conventions

- Test DB must be PostgreSQL (btree_gist constraints)
- Each functional test starts from a clean DB (Foundry ResetDatabase trait)
- Factories via Foundry for all entities
- Naming: `test_[action]_[context]_[expected_result]`
- No database mocking
- Shared trait `ApiTestHelper` with: `loginAs($user)`, `assertJsonResponse($code)`, `createBookingFixture()`
- CI order: lint -> phpstan -> unit -> integration -> functional (fail-fast)

## Documentation

Detailed specs:
- `hospes-cahier-des-charges.md` — full product spec (user stories, business rules, data model)
- `hospes-endpoints.md` — endpoint-by-endpoint API reference
- `hospes-tests.md` — test strategy with per-test matrix and non-trivial case mapping
- `hospes-dbml.dbml` — database schema in DBML format