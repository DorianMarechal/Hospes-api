# Hospes API — Roadmap V1

## Legende

- [x] Fait
- [ ] A faire

---

## Phase 0 — Correctifs critiques (bloquants)

- [x] Fix 5 erreurs mappedBy Doctrine (Lodging→seasons/blockedDates/priceOverrides, User→bookings, HostProfile→hostLegalIdentifiers)
- [x] Fix Lodging.name default 'null' (string) → null
- [x] Fix NPE LodgingProcessor ($hostProfile->getId() sur null)
- [x] Fix NPE SeasonProcessor ($lodging->getHost()->getId() sur null)
- [x] Fix $isNew inversé dans HostProfileProcessor
- [x] Retirer orphanRemoval: true sur Lodging→bookings et HostProfile→lodgings
- [x] Ajouter operations: [] sur Booking, BlockedDate, BookingNight, PriceOverride (bloquer CRUD par defaut)
- [x] Reactiver IS_AUTHENTICATED_FULLY dans security.yaml (+ whitelist register/login)
- [x] Fix ownership check dans SeasonCollectionProvider
- [x] Fix variable $perciste dans LodgingProcessor
- [x] Fix gethostProfile/sethostProfile casing dans HostLegalIdentifier
- [x] Fix ValidPhoneNumberValidator return type void

## Phase 1 — Fondations DB (migration majeure)

- [x] Migrer tous les PKs INT → UUID v7
- [x] Ajouter contrainte EXCLUDE USING gist sur booking (double-booking prevention)
- [x] Ajouter index manquants (booking pending/expiry, season lodging/dates, blocked_date lodging/dates)
- [x] Ajouter contraintes uniques manquantes (price_override lodging+date, booking.reference, host_legal_identifier profile+type+country)
- [x] Migrer timestamps → TIMESTAMPTZ
- [x] Ajouter onDelete strategies sur les FK
- [x] Fix cascade strategies (Lodging→Season, Lodging→PriceOverride)

## Phase 2 — Securite et validation

- [ ] Implementer UserChecker (verifier isActive)
- [ ] Ajouter Assert constraints sur Lodging et Season (prix positifs, capacity, min/maxStay)
- [ ] Ajouter rate limiting sur login (symfony/rate-limiter)
- [ ] Configurer JWT TTL explicite dans lexik_jwt_authentication.yaml
- [ ] Rotater secrets (APP_SECRET, JWT passphrase) + nettoyer git history
- [ ] Fix role hierarchy dans LodgingVoter (utiliser AuthorizationChecker au lieu de in_array)
- [ ] Desactiver Swagger/docs en prod
- [ ] Catch UniqueConstraintViolationException dans RegisterProcessor

## Phase 3 — Reprendre le developpement (roadmap V1)

_(voir sections numerotees ci-dessous)_

## Phase 4 — Tests et CI

- [ ] Creer factories Foundry pour toutes les entites
- [ ] Tests unitaires manquants (PriceCalculator, OrphanProtection, StaffVoter, BookingAccessVoter, CancellationPolicyResolver)
- [ ] Tests integration PostgreSQL
- [ ] Tests fonctionnels API (auth, lodging, season, booking)
- [ ] GitHub Actions CI pipeline (lint → phpstan → unit → integration → functional)

---

## 1. Authentification (8 endpoints)

- [x] POST /api/auth/register
- [x] POST /api/auth/login (JWT LexikJWT)
- [x] GET /api/auth/me
- [x] PUT /api/auth/me/host-profile
- [ ] POST /api/auth/refresh
- [ ] PUT /api/auth/me (modifier profil)
- [ ] PUT /api/auth/me/password
- [ ] POST /api/auth/forgot-password
- [ ] POST /api/auth/reset-password

## 2. Logements (12 endpoints)

- [x] POST /api/lodgings
- [x] GET /api/lodgings (collection)
- [x] GET /api/lodgings/{id}
- [x] PATCH /api/lodgings/{id}
- [x] DELETE /api/lodgings/{id}
- [x] LodgingVoter (VIEW, EDIT, DELETE)
- [ ] GET /api/me/lodgings (mes logements)
- [ ] POST /api/lodgings/{id}/images
- [ ] PUT /api/lodging-images/{id}
- [ ] DELETE /api/lodging-images/{id}
- [ ] Doctrine Extension filtrage isActive (public ne voit que les actifs)

### Equipements

- [ ] Entite Amenity + LodgingAmenity
- [ ] GET /api/amenities
- [ ] POST /api/lodgings/{id}/amenities
- [ ] DELETE /api/lodgings/{id}/amenities/{amenityId}

## 3. Saisons / Tarification (4 endpoints)

- [x] Entite Season
- [x] Entite PriceOverride
- [ ] POST /api/lodgings/{id}/seasons (+ validation non-chevauchement)
- [ ] GET /api/lodgings/{id}/seasons
- [ ] PUT /api/seasons/{id}
- [ ] DELETE /api/seasons/{id}
- [ ] SeasonOverlapValidator
- [ ] Endpoints PriceOverride (CRUD)

## 4. Disponibilite (3 endpoints)

- [x] AvailabilityResolver (isAvailable + validateStayDuration)
- [ ] GET /api/availability (recherche agregee)
- [ ] GET /api/lodgings/{id}/availability?checkin=&checkout=
- [ ] GET /api/lodgings/{id}/calendar?month=YYYY-MM

## 5. Blocage de dates (3 endpoints)

- [x] Entite BlockedDate
- [ ] POST /api/lodgings/{id}/blocked-dates (+ validation pas de resa sur ces dates)
- [ ] GET /api/lodgings/{id}/blocked-dates
- [ ] DELETE /api/blocked-dates/{id}

## 6. Sync iCal (5 endpoints)

- [ ] Entite IcalFeed
- [ ] POST /api/lodgings/{id}/ical-feeds
- [ ] GET /api/lodgings/{id}/ical-feeds
- [ ] DELETE /api/ical-feeds/{id}
- [ ] POST /api/ical-feeds/{id}/sync
- [ ] GET /api/lodgings/{id}/ical-export.ics
- [ ] IcalSyncService

## 7. Reservations (11 endpoints)

- [x] Entite Booking + BookingNight
- [x] Enum BookingStatus
- [ ] POST /api/bookings (pending + TTL 15 min)
- [ ] GET /api/bookings/{id}
- [ ] GET /api/bookings?reference={ref}
- [ ] PUT /api/bookings/{id}/dates (modif dates + recalcul prix)
- [ ] POST /api/bookings/{id}/confirm
- [ ] POST /api/bookings/{id}/cancel (+ politique annulation)
- [ ] GET /api/me/bookings
- [ ] GET /api/lodgings/{id}/bookings
- [ ] GET /api/bookings/{id}/nights
- [ ] GET /api/bookings/{id}/history
- [ ] POST /api/lodgings/{id}/quote (devis)
- [ ] BookingReferenceGenerator
- [ ] PendingBookingCleaner (cron + lazy check)
- [ ] BookingAccessVoter

## 8. Services metier

- [x] AvailabilityResolver
- [x] LegalIdentifierValidator
- [ ] PriceCalculator (nuit par nuit, saisons, weekend)
- [ ] OrphanProtectionChecker (AR-14 a AR-17)
- [ ] CancellationPolicyResolver
- [ ] DepositManager
- [ ] NotificationDispatcher
- [ ] StatisticsCalculator

## 9. Paiements (4 endpoints)

- [ ] Entite Payment
- [ ] POST /api/bookings/{id}/payments
- [ ] GET /api/bookings/{id}/payments
- [ ] GET /api/me/payments
- [ ] POST /api/payments/{id}/refund

## 10. Cautions (3 endpoints)

- [ ] Entite Deposit
- [ ] GET /api/bookings/{id}/deposit
- [ ] POST /api/bookings/{id}/deposit/retain
- [ ] POST /api/bookings/{id}/deposit/release

## 11. Staff (7 endpoints)

- [ ] Entites StaffAssignment, StaffPermission, StaffLodging
- [ ] POST /api/me/staff (invitation)
- [ ] GET /api/me/staff
- [ ] POST /api/staff-invitations/{token}/accept
- [ ] PUT /api/staff-assignments/{id}/permissions
- [ ] PUT /api/staff-assignments/{id}/lodgings
- [ ] POST /api/staff-assignments/{id}/revoke
- [ ] GET /api/me/permissions
- [ ] StaffVoter

## 12. Messagerie (5 endpoints)

- [ ] Entites Conversation, Message
- [ ] POST /api/lodgings/{id}/conversations
- [ ] GET /api/me/conversations
- [ ] GET /api/conversations/{id}/messages
- [ ] POST /api/conversations/{id}/messages
- [ ] POST /api/conversations/{id}/read

## 13. Favoris (3 endpoints)

- [ ] Entite Favorite
- [ ] POST /api/me/favorites
- [ ] GET /api/me/favorites
- [ ] DELETE /api/me/favorites/{lodgingId}

## 14. Avis (5 endpoints)

- [ ] Entite Review
- [ ] POST /api/bookings/{id}/review (+ validation apres sejour)
- [ ] GET /api/lodgings/{id}/reviews
- [ ] GET /api/me/reviews
- [ ] POST /api/reviews/{id}/response
- [ ] DELETE /api/reviews/{id} (admin)
- [ ] ReviewEligibilityValidator

## 15. Notifications (3 endpoints)

- [ ] Entite Notification
- [ ] GET /api/me/notifications
- [ ] POST /api/notifications/{id}/read
- [ ] POST /api/me/notifications/read-all

## 16. Recherche (1 endpoint)

- [ ] GET /api/search/lodgings (filtres: ville, dates, type, capacite, prix, equipements, geo)

## 17. Statistiques (2 endpoints)

- [ ] GET /api/me/stats (CA, taux occupation, RevPAR)
- [ ] GET /api/me/lodgings/{id}/stats

## 18. Admin (8 endpoints)

- [ ] GET /api/admin/users
- [ ] GET /api/admin/users/{id}
- [ ] POST /api/admin/users/{id}/deactivate
- [ ] POST /api/admin/users/{id}/reactivate
- [ ] GET /api/admin/lodgings
- [ ] PUT /api/admin/lodgings/{id}
- [ ] DELETE /api/admin/lodgings/{id}
- [ ] GET /api/admin/bookings
- [ ] GET /api/admin/payments
- [ ] GET /api/admin/reviews
- [ ] GET /api/admin/stats

## 19. Tests

- [x] PHPUnit configure (phpunit.dist.xml)
- [x] Tests unitaires LodgingVoter (9 tests)
- [x] Tests unitaires AvailabilityResolver (12 tests)
- [ ] Tests unitaires PriceCalculator
- [ ] Tests unitaires OrphanProtectionChecker
- [ ] Tests unitaires StaffVoter
- [ ] Tests unitaires BookingAccessVoter
- [ ] Tests unitaires SeasonOverlapValidator
- [ ] Tests unitaires CancellationPolicyResolver
- [ ] Tests unitaires DepositManager
- [ ] Tests integration (PostgreSQL, btree_gist)
- [ ] Tests fonctionnels Auth
- [ ] Tests fonctionnels Lodging
- [ ] Tests fonctionnels Season
- [ ] Tests fonctionnels Booking
- [ ] Tests fonctionnels BlockedDate
- [ ] Tests fonctionnels Staff
- [ ] Trait ApiTestHelper (loginAs, assertJsonResponse, createBookingFixture)

## 20. Infra / CI

- [x] Docker (PostgreSQL, PHP-FPM, Caddy)
- [x] PHPStan niveau 6
- [x] PHP-CS-Fixer
- [x] Makefile
- [ ] GitHub Actions CI (lint -> phpstan -> unit -> integration -> functional)
- [ ] Contrainte EXCLUDE USING gist (btree_gist) sur booking
- [ ] Cron PendingBookingCleaner
