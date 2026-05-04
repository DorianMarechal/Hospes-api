# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

## Project overview

Hospes is a REST API for independent lodging hosts (hotels, gites, cabins, B&Bs, apartments). It handles multi-property management, seasonal pricing, booking with concurrency protection, iCal sync, channel management, team permissions, messaging, reviews, Stripe Connect + PayPal payments, real-time notifications (Mercure), and platform administration.

**Positioning**: open-source PMS API for independent hosts who want control over their bookings, zero OTA commissions, and full data ownership. Target: tech-savvy European hosts (gites, chambres d'hotes).

**Competitors**: Lodgify, Guesty, Hostaway, Beds24, Smoobu, Hospitable. Hospes differentiates with DB-level double-booking protection (btree_gist EXCLUDE), orphan night protection, night-by-night pricing transparency, booking modification double-validation, and API-first open-source model.

## Stack

- PHP 8.4 / Symfony 7.4 LTS
- API Platform 4 (resource-driven REST endpoints)
- Doctrine ORM / PostgreSQL (requires `btree_gist` + `postgis` extensions)
- JWT auth via LexikJWTAuthenticationBundle + gesdinet refresh tokens
- Mercure for real-time SSE notifications
- Symfony Messenger (async: notifications, iCal sync)
- Symfony Mailer (transactional emails via Twig templates)
- Redis (cache, rate limiting)
- PHPUnit 13, PHPStan level 6, PHP-CS-Fixer

## Setup

```bash
composer install
docker compose up -d          # PostgreSQL + Redis + Mercure
php bin/console doctrine:migrations:migrate
symfony server:start
```

## Commands

```bash
# Static analysis
vendor/bin/phpstan analyse -l 6 --memory-limit=512M src

# Code formatting
vendor/bin/php-cs-fixer fix

# Tests (PostgreSQL required, not SQLite — btree_gist constraints)
vendor/bin/phpunit --testsuite=unit
vendor/bin/phpunit --testsuite=integration
vendor/bin/phpunit --testsuite=functional
vendor/bin/phpunit --filter=TestClassName::testMethodName  # single test

# Symfony console
php bin/console doctrine:migrations:migrate
php bin/console debug:router
php bin/console messenger:consume async     # worker Messenger

# Cron jobs
php bin/console app:sync-ical-feeds         # iCal sync (every 15 min)
php bin/console app:send-checkin-reminders   # J-1 reminders (daily)
php bin/console app:send-review-requests     # J+1 review requests (daily)
php bin/console app:purge-inactive-accounts  # GDPR retention (weekly)
```

## Architecture

Follow standard Symfony + API Platform conventions as spec'd in the documentation files.

- **Entities** (Doctrine ORM): 25+ entities with UUID PKs. Key entities: User, Lodging, Booking, Season, BlockedDate, StaffAssignment, Conversation, Review, Notification, BookingModificationRequest, Payment, Deposit.
- **API Platform resources**: endpoints exposed via PHP attributes on entities/DTOs, not manual controllers. Only exceptions: WebhookController, IcalExportController, PaymentProviderCallbackController, HealthCheckController.
- **State layer**: Processors handle writes (BookingCreateProcessor, PaymentCreateProcessor, etc.), Providers handle reads (AvailabilitySearchProvider, MyBookingsProvider, etc.).
- **Error responses**: RFC 7807 (Problem Details for HTTP APIs). API Platform handles this natively.
- **Security**: Symfony Voters for authorization (StaffVoter, LodgingOwnerVoter, BookingAccessVoter, ModificationRequestVoter). Staff permissions are stored in DB and checked per-request (not in JWT). Multi-tenant isolation is mandatory.
- **Services**: domain logic in dedicated services: `AvailabilityResolver`, `PriceCalculator`, `OrphanProtectionChecker`, `PendingBookingCleaner`, `IcalSyncService`, `CancellationPolicyResolver`, `DepositManager`, `StatisticsCalculator`, `NotificationDispatcher`, `MercurePublisher`, `EmailSender`.
- **Async**: Symfony Messenger with Doctrine transport. Messages: `SendNotificationMessage`, `SyncIcalFeedsMessage`.
- **Payments**: `PaymentGatewayInterface` with `StripeGateway`, `PayPalGateway`, `StubPaymentGateway` (test). Factory pattern via `PaymentGatewayFactory`.

## Key business rules

- **Monetary amounts**: always stored as integers in euro cents. Never use float for money.
- **Dates and times**: ISO 8601 format, UTC storage. Dates without time (check-in/check-out) are `Y-m-d` strings.
- **Night convention**: checkout day J and checkin day J are compatible (a booking from 10th to 13th occupies nights 10, 11, 12).
- **Booking TTL**: new bookings are `pending` for 15 min, then auto-cancelled. Cleanup via lazy check + cron.
- **Concurrency**: PostgreSQL EXCLUDE USING gist constraint prevents double bookings at DB level, backed by application-level check (defense in depth).
- **Pricing**: night-by-night resolution. Each night checks for a covering season, falls back to base rate. Weekend = Friday and Saturday nights. Rounding only on final total. Amounts snapshotted at booking creation.
- **Orphan protection**: configurable per lodging. Rejects bookings that would create a gap shorter than min_stay.
- **Staff permissions**: granular (can_view_bookings, can_edit_bookings, can_block_dates, can_view_revenue, can_manage_lodgings) + lodging scope. Revenue filtering (not 403) when can_view_bookings without can_view_revenue.
- **Modification requests**: double validation — both parties must agree. TTL 48h, price recalculated at acceptance.

## Known issues (from audit V3)

These are documented bugs/security issues to fix in V3. Be aware when working near these areas:

- **Null safety**: `getExpiresAt()` returns `?DateTimeImmutable` — `null < now()` is `true` in PHP. Always null-check before comparing.
- **N+1 queries**: `findByLodging()` loads ALL bookings/blocked dates without date filter. Use targeted queries with date range + status filter.
- **Mercure sync**: `NotificationDispatcher` publishes to Mercure before DB flush. Should use Messenger instead.
- **Payment amount**: currently client-controlled in `CreatePaymentRequest`. Must be computed server-side.

## Do NOT

- Use SQLite for tests (btree_gist requires PostgreSQL)
- Use float for monetary values (always integer cents)
- Create manual controllers (use API Platform attributes on entities/DTOs) — except for webhooks, iCal export, health check
- Put business logic in controllers or processors (use dedicated services)
- Store permissions in JWT (DB-only, checked per request)
- Return 403 for filtered data (use response filtering instead, e.g. hide revenue fields)
- Allow season date overlap (validate and reject at creation)
- Skip application-level availability check (DB constraint is a safety net, not the primary check)
- Use `assert()` for runtime type guards in processors (disabled in prod, use if/throw)
- Compare nullable DateTimeImmutable directly without null check
- Pass client-supplied amounts to payment gateways without server-side validation
- Publish to Mercure before entityManager flush (notification could be for uncommitted data)

## Testing conventions

- Test DB must be PostgreSQL (btree_gist constraints)
- Each functional test starts from a clean DB (Foundry ResetDatabase trait)
- Factories via Foundry for all entities (tests/Factory/)
- Naming: `test_[action]_[context]_[expected_result]` or camelCase `testActionContextResult`
- No database mocking
- Shared trait `ApiTestHelper` with: `loginAs($user)`, `authClient($user)`, `assertJsonResponse($code)`, `createBookingFixture()`
- CI order: lint -> phpstan -> unit -> integration -> functional (fail-fast)
- Payment tests use `StubPaymentGateway` (configured in services.yaml when@test)

## Workflow rules

- **ALWAYS read TODO.md** before starting any work session. On "continue" or "passe à la suite", read TODO.md first to find the next unchecked phase and start working on it immediately.
- **Enchainer les phases** : quand l'utilisateur dit "continue", finir la phase en cours → passer immédiatement à la suivante. Ne pas s'arrêter entre les phases, ne pas demander confirmation, ne pas lancer les tests de régression sauf si quelque chose casse.
- **Direct coding** : coder directement, ne pas expliquer les patterns, ne pas guider pas-à-pas, ne pas demander "par quel fichier commencer". Implémenter end-to-end sans poser de questions sauf ambiguité architecturale réelle.
- **Push back** : contredire l'utilisateur quand il est sur le point de prendre une mauvaise décision. Ne jamais acquiescer silencieusement à quelque chose de mauvais. Dire clairement pourquoi c'est une mauvaise idée. Si l'utilisateur insiste après explication, obtempérer.

## Commit conventions

- Messages en **anglais**, une seule ligne courte, format Conventional Commits (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`)
- **Pas de Co-Authored-By** pour Claude
- **Vérifier git status** avant chaque commit — ne pas supposer que seuls les fichiers attendus ont été modifiés. php-cs-fixer ou des hooks peuvent avoir touché d'autres fichiers.

## PostgreSQL setup (dev)

- **Toujours stopper Homebrew PostgreSQL** (`brew services stop postgresql@18`) avant de démarrer Docker PostgreSQL pour ce projet. Ils sont en conflit sur le port 5432.
- Le projet utilise l'instance Docker avec config spécifique (user: hospes, db: hospes, password: hospes).
- Avant toute opération DB, vérifier si brew postgres tourne et le stopper si nécessaire.

## API Platform 4 patterns (lessons learned)

1. **uriVariables obligatoires** : toute opération avec `{lodgingId}`, `{bookingId}`, `{conversationId}` dans le uriTemplate DOIT avoir `uriVariables: ['param' => new Link(fromClass: Entity::class, toProperty: 'relation')]`
2. **denormalizationContext: [] sur DTOs d'input** : quand une opération utilise `input: SomeDto::class` et que l'entité a un denormalizationContext avec groups, les propriétés du DTO sont filtrées (toutes null). Fix : `denormalizationContext: []` sur l'opération.
3. **read: false sur DTOs admin** : les opérations POST/DELETE sur un DTO (pas Doctrine) avec custom processor doivent avoir `read: false`
4. **read: false sur POST sub-resource** : les POST avec `uriVariables + Link(toProperty)` chargent silencieusement une entité existante au lieu d'en créer une nouvelle. Toujours ajouter `read: false` sur les POST sub-resource.
5. **Format collections** : les réponses GetCollection utilisent `member` (pas `hydra:member`) dans les tests.
6. **Boolean is* getters** : `isActive()`, `isRead()` ne sont PAS sérialisés de manière fiable — ne pas asserter sur ces clés dans les tests fonctionnels.

## Documentation

Detailed specs:
- `docs/hospes-cahier-des-charges.md` — full product spec (user stories, business rules, data model)
- `docs/hospes-endpoints.md` — endpoint-by-endpoint API reference
- `docs/hospes-tests.md` — test strategy with per-test matrix and non-trivial case mapping
- `docs/hospes-dbml.dbml` — database schema in DBML format
