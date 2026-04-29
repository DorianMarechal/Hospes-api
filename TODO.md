# Hospes API — Roadmap

## Legende

- [x] Fait
- [ ] A faire

---

# V1 — COMPLETE

<details>
<summary>Phase 0 — Correctifs critiques (12/12)</summary>

- [x] Fix 5 erreurs mappedBy Doctrine
- [x] Fix Lodging.name default 'null' → null
- [x] Fix NPE LodgingProcessor
- [x] Fix NPE SeasonProcessor
- [x] Fix $isNew inversé dans HostProfileProcessor
- [x] Retirer orphanRemoval sur Lodging→bookings et HostProfile→lodgings
- [x] Ajouter operations: [] sur entites internes
- [x] Reactiver IS_AUTHENTICATED_FULLY + whitelist
- [x] Fix ownership check SeasonCollectionProvider
- [x] Fix variable $perciste LodgingProcessor
- [x] Fix casing HostLegalIdentifier
- [x] Fix ValidPhoneNumberValidator return type

</details>

<details>
<summary>Phase 1 — Fondations DB (7/7)</summary>

- [x] UUID v7 sur tous les PKs
- [x] Contrainte EXCLUDE USING gist (btree_gist)
- [x] Index manquants
- [x] Contraintes uniques manquantes
- [x] Timestamps → TIMESTAMPTZ
- [x] onDelete strategies sur FK
- [x] Fix cascade strategies

</details>

<details>
<summary>Phase 2 — Securite et validation (7/8)</summary>

- [x] UserChecker (verifier isActive)
- [x] Assert constraints (prix, capacity, min/maxStay)
- [x] Rate limiting login
- [x] JWT TTL explicite
- [x] Fix role hierarchy LodgingVoter
- [x] Swagger/docs desactive en prod
- [x] Catch UniqueConstraintViolationException RegisterProcessor
- [ ] Rotater secrets (APP_SECRET, JWT passphrase) + nettoyer git history

</details>

<details>
<summary>Phase 3 — Endpoints (78 endpoints, 8 services metier)</summary>

- [x] Auth (8 endpoints)
- [x] Logements (12 endpoints + images + amenities + isActive filter)
- [x] Saisons / Tarification (4 endpoints + PriceOverride CRUD)
- [x] Disponibilite (3 endpoints + AvailabilityResolver)
- [x] Blocage de dates (3 endpoints)
- [x] Sync iCal (5 endpoints + IcalSyncService)
- [x] Reservations (11 endpoints + BookingReferenceGenerator + PendingBookingCleaner)
- [x] Paiements (4 endpoints + PaymentProvider config)
- [x] Cautions (3 endpoints + DepositManager)
- [x] Staff (7 endpoints + StaffVoter)
- [x] Messagerie (5 endpoints)
- [x] Favoris (3 endpoints)
- [x] Avis (5 endpoints + ReviewEligibilityValidator)
- [x] Notifications (3 endpoints + NotificationDispatcher)
- [x] Recherche (1 endpoint)
- [x] Statistiques (2 endpoints + StatisticsCalculator)
- [x] Admin (10 endpoints)
- [x] Services metier : CancellationPolicyResolver, DepositManager, NotificationDispatcher

</details>

<details>
<summary>Phase 4 — Tests (82 unit + 29 functional + 7 integration)</summary>

- [x] Factories Foundry (User, HostProfile, Lodging, Season, Booking, BlockedDate)
- [x] Trait ApiTestHelper
- [x] Tests unitaires (LodgingVoter, AvailabilityResolver, PriceCalculator, OrphanProtection, StaffVoter, BookingAccessVoter, SeasonOverlapValidator, CancellationPolicyResolver, DepositManager)
- [x] Tests integration PostgreSQL (EXCLUDE gist, unique constraints, cascades)
- [x] Tests fonctionnels (Auth, Lodging, Season, Booking, BlockedDate, Staff)
- [x] GitHub Actions CI (lint → phpstan → unit → functional)

</details>

---

# V2 — A FAIRE

## Phase 5 — Securite & ops

- [ ] Rotater secrets (APP_SECRET, JWT passphrase) + nettoyer git history
- [x] CORS configuration pour frontend
- [x] Helmet-style security headers (Content-Security-Policy, X-Frame-Options)
- [x] Audit log des actions admin (qui a fait quoi, quand)
- [x] Rate limiting global par IP (pas seulement login)
- [x] Monitoring : health check endpoint (/api/health)

## Phase 6 — Integration paiement Stripe Connect

- [ ] Installer stripe/stripe-php
- [ ] Implementer le flux OAuth Stripe Connect (redirect + callback)
- [ ] Stocker stripe_account_id sur HostProfile (remplacer les placeholders)
- [ ] Creer PaymentIntent via Stripe API dans PaymentCreateProcessor
- [ ] Webhook Stripe : payment_intent.succeeded → mettre Payment.status = succeeded
- [ ] Webhook Stripe : payment_intent.payment_failed → mettre Payment.status = failed
- [ ] Remboursement automatique via Stripe Refund API dans PaymentRefundProcessor
- [ ] Remboursement auto sur annulation selon CancellationPolicyResolver
- [ ] Dashboard paiements Stripe Connect pour les hotes (lien redirect)

## Phase 7 — Double validation des modifications de reservation

- [ ] Entite BookingModificationRequest (proposed dates, proposed price, status)
- [ ] POST /api/bookings/{id}/modification-request (proposer modif)
- [ ] POST /api/booking-modifications/{id}/accept
- [ ] POST /api/booking-modifications/{id}/reject
- [ ] Expiration auto des propositions (TTL configurable)
- [ ] Notifications aux deux parties (propose, accepte, refuse, expire)
- [ ] Recalcul prix uniquement apres acceptation des deux parties

## Phase 8 — Notifications temps reel

- [ ] Installer Mercure (symfony/mercure-bundle)
- [ ] Hub Mercure (Docker ou managed)
- [ ] Publier les notifications sur un topic user-specific
- [ ] Publier les messages de conversation en temps reel
- [ ] SSE/WebSocket endpoint pour le frontend
- [ ] Fallback : garder le modele pull pour les clients sans WebSocket

## Phase 9 — Recherche avancee

- [ ] Recherche geospatiale (PostGIS extension, ST_DWithin)
- [ ] Filtres composes : prix min/max, equipements, note minimale
- [ ] Tri par pertinence (distance + note + prix)
- [ ] Pagination cursor-based pour les gros resultats
- [ ] Cache des resultats de recherche (Redis/Varnish)

## Phase 10 — RGPD & conformite

- [ ] GET /api/me/data-export (export donnees personnelles JSON)
- [ ] DELETE /api/me/account (anonymisation : pseudonymiser les donnees liees aux resas passees)
- [ ] Consentement explicite a l'inscription (champ + date)
- [ ] Politique de retention des donnees (cron suppression comptes inactifs > 3 ans)
- [ ] Log des consentements

## Phase 11 — Performance & scalabilite

- [ ] Cache HTTP (Varnish/CDN) pour endpoints publics (lodgings, availability, reviews)
- [ ] Redis pour rate limiting et sessions
- [ ] Optimisation queries N+1 (Doctrine eager loading sur endpoints critiques)
- [ ] Pagination keyset sur collections volumineuses (bookings, notifications)
- [ ] Async : Symfony Messenger pour NotificationDispatcher + emails
- [ ] Cron iCal sync periodique (toutes les 15 min)

## Phase 12 — Emails transactionnels

- [ ] Template emails (Symfony Mailer + Twig)
- [ ] Email confirmation de reservation (customer)
- [ ] Email notification nouvelle reservation (hote)
- [ ] Email annulation (customer + hote)
- [ ] Email invitation staff
- [ ] Email reinitialisation mot de passe (deja le flow, ajouter le vrai envoi)
- [ ] Email rappel check-in J-1
- [ ] Email demande d'avis apres checkout J+1
- [ ] Provider : Resend, Postmark ou SES

## Phase 13 — Tests supplementaires

- [ ] Augmenter couverture tests fonctionnels (~110 cibles selon le cahier des charges)
- [ ] Tests Stripe webhooks (mock)
- [ ] Tests Mercure (mock hub)
- [ ] Tests de charge (k6 ou Artillery)
- [ ] Tests de securite automatises (OWASP ZAP)

## Phase 14 — Deploiement

- [ ] Dockerfile production (multi-stage, PHP-FPM + Caddy)
- [ ] Docker Compose production (PostgreSQL, Redis, Mercure)
- [ ] Terraform / IaC pour infra cloud (Railway, Fly.io ou AWS)
- [ ] Secrets management (Vault ou AWS SSM)
- [ ] CD pipeline (deploy on merge to main)
- [ ] Backup PostgreSQL automatise
- [ ] Monitoring (Sentry PHP SDK + uptime)
