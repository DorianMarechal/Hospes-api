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

# V2 — COMPLETE

<details>
<summary>Phase 5 — Securite & ops</summary>

- [ ] Rotater secrets (APP_SECRET, JWT passphrase) + nettoyer git history
- [x] CORS configuration pour frontend
- [x] Helmet-style security headers (Content-Security-Policy, X-Frame-Options)
- [x] Audit log des actions admin (qui a fait quoi, quand)
- [x] Rate limiting global par IP (pas seulement login)
- [x] Monitoring : health check endpoint (/api/health)

</details>

<details>
<summary>Phase 6 — Integration paiement Stripe Connect + PayPal</summary>

- [x] Stripe Connect (OAuth, PaymentIntent, webhooks, refund, dashboard)
- [x] PayPal (OAuth, Orders, webhooks, refund)
- [x] Interface PaymentProviderInterface + PaymentProviderFactory
- [x] Remboursement auto sur annulation + choix provider

</details>

<details>
<summary>Phase 7 — Double validation des modifications de reservation</summary>

- [x] Entite BookingModificationRequest + enum ModificationRequestStatus
- [x] POST /api/bookings/{id}/modification-request
- [x] POST /api/booking-modifications/{id}/accept + reject
- [x] Expiration auto 48h + notifications aux deux parties

</details>

<details>
<summary>Phase 8 — Notifications temps reel (Mercure)</summary>

- [x] symfony/mercure-bundle + hub Docker
- [x] MercurePublisher : notifications, messages, modification requests
- [x] Integration NotificationDispatcher + MessageProcessor

</details>

<details>
<summary>Phase 9 — Recherche avancee</summary>

- [x] PostGIS + ST_DWithin geospatial
- [x] Filtres composes + tri pertinence + pagination cursor-based

</details>

<details>
<summary>Phase 10 — RGPD & conformite</summary>

- [x] Data export, anonymisation, consentement, retention 3 ans

</details>

<details>
<summary>Phase 11 — Performance & scalabilite</summary>

- [x] Redis, Symfony Messenger async, cron iCal sync

</details>

<details>
<summary>Phase 12 — Emails transactionnels</summary>

- [x] Symfony Mailer + 7 templates Twig + commandes cron

</details>

<details>
<summary>Phase 13 — Tests supplementaires</summary>

- [x] Tests fonctionnels BookingModificationRequest, webhooks, k6, OWASP ZAP

</details>

<details>
<summary>Phase 14 — Deploiement</summary>

- [x] Dockerfile production + compose.prod.yaml
- [ ] Terraform / IaC, secrets management, CD pipeline, backup, monitoring

</details>

---

# V3 — CORRECTIONS AUDIT (securite, bugs, performance)

## Phase 15 — Securite critique

- [x] Montant paiement : calculer cote serveur, ignorer le montant client dans CreatePaymentRequest
- [x] GET /api/bookings : ajouter security + authorization dans BookingByReferenceProvider
- [x] PayPal webhook : rendre la verification de signature obligatoire (throw si absent)
- [x] Stripe OAuth callback : remplacer state=UUID par un token CSRF signe lie a la session
- [ ] Rotater JWT keypair + passphrase, stocker dans .env.local ou Vault uniquement
- [x] Hasher les reset tokens (SHA-256) avant stockage en base
- [x] Retirer paymentProviderAccountId du groupe host-profile:read
- [x] Configurer trusted_proxies dans framework.yaml pour rate limiter derriere Caddy
- [x] LodgingIsActiveExtension : filtrer par host_id pour les logements inactifs (cross-tenant leak)
- [x] Protection SSRF sur iCal URL : whitelist https, bloquer RFC1918/loopback, limiter taille reponse
- [x] Mercure : ajouter private: true sur tous les Update + configurer JWT subscriber claims
- [x] Webhooks Stripe/PayPal : exempter du rate limiter global
- [x] Invalider refresh tokens sur changement de mot de passe et suppression de compte
- [x] Rate limiters dedies pour forgot-password, register, accept-invitation
- [x] Remplacer assert() par des gardes explicites if/throw dans tous les processors
- [x] Cabler ForgotPasswordProcessor → EmailSender::sendPasswordReset()
- [x] Cabler StaffInviteProcessor → EmailSender::sendStaffInvitation()
- [x] iCal export : sanitizer le nom du fichier dans Content-Disposition

## Phase 16 — Bugs et null safety

- [x] AvailabilityResolver/OrphanProtectionChecker : fix null expiresAt (ne skip que si PENDING + expiresAt non null + expire)
- [x] BookingConfirmProcessor : fix null expiresAt + ajouter BookingStatusHistory a l'expiration
- [x] BookingModifyDatesProcessor + ModificationRequestCreateProcessor + AcceptProcessor : null guard sur getLodging()
- [x] Null-coalesce getGuestsCount() avant passage a PriceCalculator (fallback 1)
- [x] BookingCancelProcessor/PaymentRefundProcessor : null guard sur getMethod() avant setMethod()
- [x] SeasonProcessor : supprimer le check overlap duplique (deja dans NoSeasonOverlapValidator)
- [x] BookingCancelProcessor : void/release le Deposit a l'annulation
- [x] AcceptInvitationProcessor : verifier si email existe deja, rattacher le User existant
- [x] IcalSyncService : remplacer delete-all-then-recreate par diff-and-patch
- [x] PendingBookingCleaner : notifier le customer avant le bulk UPDATE (dispatch SendNotificationMessage)
- [x] PayPalGateway::completeOnboarding() : implementer l'echange code → merchant ID via API PayPal
- [x] NotificationDispatcher : publier Mercure APRES flush, pas avant (ou mieux : dispatcher via Messenger)

## Phase 17 — Performance

- [x] AvailabilitySearchProvider : batch queries (findActiveOverlappingForLodgings) au lieu de N+1
- [x] Geo filter : merger dans advancedSearch avant pagination, pas apres en PHP
- [x] StatisticsCalculator : remplacer par une seule query SQL agrégée (SUM, COUNT, GROUP BY)
- [x] Admin providers : ajouter pagination (setMaxResults/setFirstResult), supprimer findAll()
- [x] NotificationDispatcher : cabler sur Messenger (dispatcher SendNotificationMessage au lieu d'appeler MercurePublisher directement)
- [x] BookingCreateProcessor : eager load seasons + priceOverrides via JOIN FETCH
- [x] CalendarProvider : filtrer bookings/blockedDates par plage de dates, pas tout charger
- [x] BookingRepository::findByLodging : ajouter filtre status + date range (findActiveInPeriod)
- [x] MyConversationsProvider : une seule query DQL avec OR au lieu de merge PHP
- [x] Cache search results : utiliser le pool cache.search_results (deja configure mais jamais injecte)
- [x] Lodging : creer un groupe lodging:list sans images pour les collections
- [x] DataExportProvider : JOIN FETCH lodging sur les bookings pour eviter N lazy loads

## Phase 18 — Base de donnees

- [x] CHECK constraints : montants >= 0, dates start < end, rating 1-5, guests > 0, deposit retained <= amount
- [x] Index : idx_lodging_city_lower, idx_lodging_active, idx_booking_lodging_status, idx_booking_customer_created
- [x] Index : idx_review_lodging_created, idx_conversation_customer, idx_conversation_host, idx_message_conversation_created
- [x] PostGIS : colonne geography(Point,4326) + GiST index sur lodging au lieu de DECIMAL cast a la volee
- [x] UNIQUE sur staff_permission(staff_assignment_id, permission)
- [x] EXCLUDE sur blocked_date (no overlap par lodging)
- [x] Cascades manquantes : ON DELETE CASCADE sur blocked_date, season, price_override → lodging + host_legal_identifier → host_profile
- [x] VARCHAR(255) → length reel pour les colonnes enum (20-30 chars)
- [x] Ajouter review.host_response_at, review.moderated_at, review.moderated_by
- [x] Ajouter payment.original_payment_id (FK vers le paiement original pour les refunds)
- [x] Ajouter booking.source (direct, ical, channel_manager) pour tracking canal

---

# V4 — API DESIGN & CONFORMITE

## Phase 19 — API design

- [x] PUT /bookings/{id}/dates → PATCH (update partiel)
- [x] PUT /staff-assignments/{id}/permissions → PATCH
- [x] POST /notifications/{id}/read → PATCH /notifications/{id} avec {"isRead": true}
- [x] Idempotency-Key header sur POST /bookings/{id}/payments (deduplication paiement)
- [x] Webhook deduplication : stocker event ID Stripe/PayPal, skip si deja traite
- [x] Webhook : log des event types non geres (default => null silencieux actuellement)
- [x] Webhook : cascader le statut booking quand payment succeeds (pending → confirmed)
- [x] maximum_items_per_page: 100 dans api_platform.yaml
- [x] Rate limit headers (X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset)
- [x] Augmenter rate limit global de 60/min a 200/min, ajouter rate limit par user_id en plus de IP
- [x] Rate limits differencies read vs write
- [x] Montants monetaires : wrapper {amount, currency} au lieu de int brut (preparer multi-devise)
- [x] Notifications : type machine-readable + params au lieu de texte francais hardcode
- [x] OpenAPI : summaries/descriptions sur toutes les operations
- [x] OpenAPI : decorateurs manuels pour login_check, refresh, webhooks
- [x] Documenter et choisir une strategie de versioning (URI prefix /api/v1/)

## Phase 20 — Endpoints manquants

- [x] GET /api/bookings/{id}/invoice — generation facture PDF (obligation legale France)
- [x] POST /api/bookings/{id}/check-in + POST /api/bookings/{id}/check-out — tracking statut arrivee/depart
- [x] GET /api/me/unread-counts — compteurs badge (notifications + messages non lus en un appel)
- [x] PATCH /api/reviews/{id} — modification avis par le customer (fenetre de 14 jours)
- [x] GET /api/admin/users?search=&status=&role= — filtres admin
- [x] GET /api/admin/bookings?status=&from=&to=&lodging= — filtres admin
- [x] GET /api/me/bookings/export — export CSV reservations pour comptabilite
- [x] GET /api/me/revenue/export — export CSV revenus pour declaration fiscale
- [x] POST /api/auth/logout — invalidation refresh token cote serveur

---

# V5 — COMPETITIVITE (rattraper et depasser les concurrents)

## Phase 21 — Messagerie automatisee (pain point #1 des hotes)

- [x] Entite MessageTemplate (nom, trigger, sujet, corps, variables, delai)
- [x] Triggers : booking_created, booking_confirmed, checkin_minus_1d, checkin_minus_3h, checkout_plus_1d, review_received
- [x] Variables : {guest_name}, {lodging_name}, {checkin_date}, {checkout_date}, {reference}, {checkin_time}, {address}
- [x] Delai configurable par template (ex: envoyer 3h avant check-in)
- [x] CRUD complet : POST/GET/PATCH/DELETE /api/me/message-templates
- [x] Service AutomatedMessageDispatcher (evalue les triggers, substitue les variables, envoie)
- [x] Commande cron app:dispatch-automated-messages (toutes les 5 min)
- [x] Canal d'envoi : email + message in-app + SMS (extensible)

## Phase 22 — Integration dynamic pricing

- [x] Endpoint PUT /api/lodgings/{id}/rates (bulk update tarifs par date range)
- [x] Webhook inbound : POST /api/lodgings/{id}/pricing-webhook (PriceLabs/Beyond Pricing push des prix)
- [x] Authentification webhook par API key par lodging
- [x] Creer/MAJ PriceOverride automatiquement a la reception du webhook
- [ ] Documentation integration PriceLabs (guide de configuration) — **ACCES PARTENAIRE REQUIS**

## Phase 23 — Channel Manager (Airbnb + Booking.com API)

- [x] Interface ChannelManagerInterface (sync_listings, sync_availability, sync_bookings, sync_prices)
- [x] AirbnbChannel : OAuth2, API listings, API calendar, API reservations
- [x] BookingComChannel : OAuth2, API property, API availability, API reservations
- [x] ChannelSyncService : sync bidirectionnel (push dispo + pull resas)
- [x] Entite ChannelConnection (lodging_id, channel, external_listing_id, credentials, last_sync_at)
- [x] Entite ChannelBooking (booking_id, channel, external_reservation_id)
- [x] POST /api/lodgings/{id}/channels — connecter un canal
- [x] DELETE /api/lodgings/{id}/channels/{channel} — deconnecter
- [x] POST /api/channels/{id}/sync — sync manuelle
- [x] Commande cron app:sync-channels (toutes les 5 min, remplace iCal a terme)
- [x] Gestion conflits : si resa recue du canal, verifier dispo locale avant import
- [x] Mapping des statuts entre plateformes et Hospes

## Phase 24 — Multi-devise

- [x] Ajouter currency (CHAR 3) sur Lodging et Booking
- [x] Stocker devise dans chaque Payment, Deposit, BookingNight
- [x] PriceCalculator : retourner devise dans QuoteResult
- [x] API : wrapper montants en {amount: int, currency: string} dans les reponses
- [x] Conversion a l'affichage uniquement (pas de conversion a la creation)
- [x] Supporter EUR, USD, GBP, CHF minimum

## Phase 25 — Gestion des taches / housekeeping

- [x] Entite Task (lodging, booking, assignee, type, status, due_date, notes)
- [x] Types : cleaning, maintenance, inspection, key_handover
- [x] Status : pending, in_progress, completed
- [x] Auto-creation : tache menage a chaque checkout
- [x] Assignation automatique selon staff permissions + lodging scope
- [x] CRUD : /api/me/tasks, /api/tasks/{id}, PATCH statut
- [x] Notification au staff quand tache assignee
- [x] Vue calendrier : GET /api/me/tasks?from=&to=

## Phase 26 — Portail proprietaire (owner reporting)

- [x] Entite PropertyOwner (user_id, commission_rate, payment_details)
- [x] Relation Lodging → PropertyOwner (un owner peut avoir plusieurs logements geres par un host)
- [x] GET /api/owner/lodgings — liste des logements du proprietaire
- [x] GET /api/owner/lodgings/{id}/revenue — revenus du logement (apres commission)
- [x] GET /api/owner/statements — releves mensuels
- [x] Calcul commission automatique (pourcentage configurable par owner)
- [x] Export CSV releve mensuel

## Phase 27 — Integration comptable

- [x] GET /api/me/accounting/transactions — liste des transactions (paiements, refunds, commissions)
- [x] GET /api/me/accounting/export?format=csv&from=&to= — export standard
- [x] Mapping vers plan comptable francais (comptes 706, 411, 512)
- [ ] Integration Pennylane (API francaise) : push automatique des ecritures — **ACCES PARTENAIRE REQUIS** (voir docs/PARTNER_ACCESS_REQUIRED.md)
- [ ] Integration QuickBooks/Xero (webhook ou export) — **ACCES PARTENAIRE REQUIS**
- [x] Calcul TVA automatique selon pays du logement

## Phase 28 — Statistiques avancees et revenue management

- [x] RevPAR (Revenue Per Available Room) par logement et periode
- [x] Taux d'occupation par logement, mois, saison
- [x] ADR (Average Daily Rate) et evolution
- [x] Duree moyenne de sejour
- [ ] Taux de conversion devis → reservation
- [x] Comparaison annee N vs N-1
- [x] Prevision revenus (bookings confirmes futurs + tendance historique)
- [x] GET /api/me/analytics/dashboard — KPIs consolides
- [x] GET /api/me/analytics/lodgings/{id}/performance — performance individuelle
- [x] Benchmarking : comparaison anonymisee avec logements similaires (meme ville, meme type)

## Phase 29 — Guests et conformite reglementaire

- [x] Entite Guest (booking_id, first_name, last_name, nationality, birth_date, id_type, id_number)
- [x] POST /api/bookings/{id}/guests — enregistrement des voyageurs
- [x] Fiche de police automatique (obligation legale France/Espagne/Italie)
- [x] Export fiche de police CSV
- [x] Verification identite basique (format document)
- [x] Collecte consentement RGPD par guest

## Phase 30 — Smart locks et acces

- [x] Interface SmartLockProviderInterface (generate_code, revoke_code, list_codes)
- [x] Integration Nuki (API REST) — placeholder
- [x] Integration Igloohome (API REST) — placeholder
- [x] Auto-generation code d'acces a la confirmation de reservation
- [x] Envoi code via message automatise (Phase 21)
- [x] Revocation automatique du code au checkout
- [x] GET /api/bookings/{id}/access-code — consulter le code

## Phase 31 — Promotions et codes promo

- [x] Entite PromotionCode (code, type: percent/fixed, value, max_uses, valid_from, valid_to, lodging_scope)
- [x] POST /api/me/promotion-codes — creation par l'hote
- [x] Application du code a la reservation (BookingCreateProcessor)
- [x] Champ discount_amount sur Booking
- [x] Tracking utilisation (uses_count sur PromotionCode)

## Phase 32 — Multi-langue API

- [x] Accept-Language header pour les reponses (messages d'erreur, notifications)
- [x] Entite LodgingTranslation (lodging_id, locale, name, description)
- [x] Notifications : notification_type + params au lieu de texte hardcode
- [x] Emails : templates Twig multilingues (fr, en, de, es, it)
- [x] Preference de langue sur User entity

## Phase 33 — Assurance et protection

- [x] Integration protection voyageur (Swikly, Superhog) — placeholders
- [x] Interface InsuranceProviderInterface (create_policy, cancel_policy, file_claim)
- [x] Proposition automatique a la reservation (InsuranceClaim entity + insuranceProposed flag sur Booking)
- [x] Tracking statut reclamation (InsuranceClaim avec status: proposed/active/claimed/resolved)

## Phase 34 — Deploiement et ops

- [ ] Terraform / IaC pour infra cloud (Railway, Fly.io ou AWS)
- [ ] Secrets management (Vault ou AWS SSM)
- [ ] CD pipeline (deploy on merge to main)
- [ ] Backup PostgreSQL automatise (pg_dump cron + S3)
- [ ] Monitoring (Sentry PHP SDK + uptime check)
- [ ] Alerting (PagerDuty/OpsGenie) pour erreurs critiques et webhooks fails
- [ ] Log aggregation (Loki/ELK) pour debug production
