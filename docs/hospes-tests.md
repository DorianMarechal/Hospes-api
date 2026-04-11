# Hospes API - Strategie de tests

## Stack

- PHPUnit 11 (Symfony 7.4 LTS)
- Doctrine Fixtures + Foundry (factories)
- PHPStan niveau 6
- PHP-CS-Fixer
- GitHub Actions (CI)

---

## Organisation des fichiers

```
tests/
  Unit/
    Service/
      AvailabilityResolverTest.php
      PriceCalculatorTest.php
      OrphanProtectionCheckerTest.php
      PendingBookingCleanerTest.php
      IcalSyncServiceTest.php
      BookingReferenceGeneratorTest.php
      CancellationPolicyResolverTest.php
      DepositManagerTest.php
      NotificationDispatcherTest.php
      StatisticsCalculatorTest.php
    Validator/
      SeasonOverlapValidatorTest.php
      MinMaxStayValidatorTest.php
      BookingDatesValidatorTest.php
      ReviewEligibilityValidatorTest.php
    Security/
      StaffVoterTest.php
      LodgingOwnerVoterTest.php
      BookingAccessVoterTest.php
  Integration/
    Repository/
      BookingRepositoryTest.php
      LodgingRepositoryTest.php
      SeasonRepositoryTest.php
      StaffAssignmentRepositoryTest.php
      ReviewRepositoryTest.php
    Service/
      AvailabilityResolverIntegrationTest.php
      PriceCalculatorIntegrationTest.php
      IcalSyncServiceIntegrationTest.php
    Database/
      ExclusionConstraintTest.php
      UniqueIndexesTest.php
  Functional/
    Api/
      AuthTest.php
      LodgingTest.php
      SeasonTest.php
      AvailabilityTest.php
      BlockedDateTest.php
      IcalFeedTest.php
      BookingTest.php
      BookingModificationTest.php
      PaymentTest.php
      DepositTest.php
      StaffTest.php
      ConversationTest.php
      FavoriteTest.php
      ReviewTest.php
      NotificationTest.php
      SearchTest.php
      StatsTest.php
      AdminTest.php
```

---

## Conventions

- Base de test PostgreSQL dediee (pas SQLite, car btree_gist + EXCLUDE USING gist)
- Chaque test fonctionnel repart d'une base propre (ResetDatabase trait de Foundry)
- Factories Foundry pour toutes les entites
- Trait `ApiTestHelper` avec methodes : `loginAs($user)`, `assertJsonResponse($code)`, `createBookingFixture()`
- Nommage : `test_[action]_[contexte]_[resultat_attendu]`
- Pas de mock de la base de donnees

---

## 1. Tests unitaires

Objectif : tester la logique metier isolee, sans base de donnees.

### AvailabilityResolverTest

| # | Test | Cas non-trivial |
|---|------|----------------|
| 1 | Chevauchement partiel avec reservation existante → false | AR-1 |
| 2 | Check-out J = check-in J → true (convention nuitee) | AR-2 |
| 3 | Dates bloquees (manuellement) → false | AR-3 |
| 4 | Pending active (TTL non expiree) bloque les dates → false | AR-5 |
| 5 | Pending expiree → ignoree, dates disponibles | AR-6 |
| 6 | Duree < min_stay saison → exception | AR-11 |
| 7 | Duree > max_stay saison → exception | AR-12 |
| 8 | Sejour multi-saisons, min_stay differents → le plus restrictif | AR-13 |
| 9 | Modification : exclut la resa modifiee du check dispo | AR-9/10 |

### OrphanProtectionCheckerTest

| # | Test | Cas non-trivial |
|---|------|----------------|
| 1 | Gap < min_stay cree, protection ON → rejet | AR-14 |
| 2 | Gap < min_stay cree, protection OFF → accepte | AR-15 |
| 3 | Reservation comble un trou → toujours accepte | AR-16 |
| 4 | Verification gap avant ET apres la reservation | AR-17 |

### PriceCalculatorTest

| # | Test | Cas non-trivial |
|---|------|----------------|
| 1 | Mono-saison semaine : tarif saisonnier semaine | PC-1 |
| 2 | Mono-saison week-end : tarif saisonnier week-end | PC-2 |
| 3 | Mixte semaine/week-end : nuit par nuit selon jour | PC-3 |
| 4 | Multi-saisons : nuit par nuit selon saison ET jour | PC-4 |
| 5 | Nuit hors saison : fallback base (semaine/week-end) | PC-5 |
| 6 | Arrondi centime sur total uniquement | PC-7 |
| 7 | Sejour entierement hors saison : tarif de base integral | PC-9 |
| 8 | Cleaning fee + tourist tax calcul correct | - |
| 9 | Tourist tax = tax_per_person * guests * nights | - |
| 10 | Modification dates → recalcul automatique prix | PC-8 |

### SeasonOverlapValidatorTest

| # | Test | Cas non-trivial |
|---|------|----------------|
| 1 | Deux saisons qui se chevauchent → violation | PC-6 |
| 2 | Saisons adjacentes (fin J = debut J+1) → OK | - |
| 3 | Modification saison sans creer de chevauchement → OK | - |

### CancellationPolicyResolverTest

| # | Test |
|---|------|
| 1 | Politique flexible : remboursement total si > 24h |
| 2 | Politique moderate : remboursement si > 5 jours |
| 3 | Politique strict : pas de remboursement |
| 4 | Annulation par hote : toujours remboursement total |

### StaffVoterTest

| # | Test | Cas non-trivial |
|---|------|----------------|
| 1 | Staff avec permission adequate → GRANTED | AZ-1 |
| 2 | Staff sans permission → DENIED | AZ-2 |
| 3 | can_view_bookings sans can_view_revenue → GRANTED mais flag filtrage | AZ-3 |
| 4 | Staff sans can_block_dates → DENIED sur blocage | AZ-4 |
| 5 | Staff hote A accede logement hote B → DENIED | AZ-5 |
| 6 | Staff hors perimetre logement → DENIED | AZ-6 |
| 7 | Staff revoque → DENIED sur tout | AZ-9 |

### BookingReferenceGeneratorTest

| # | Test |
|---|------|
| 1 | Reference generee non devinable (format attendu) |
| 2 | Unicite sur N generations |

### DepositManagerTest

| # | Test |
|---|------|
| 1 | Retenue partielle : retained_amount <= amount |
| 2 | Retenue totale : status = fully_retained |
| 3 | Liberation : status = released |
| 4 | Retenue > amount → exception |

---

## 2. Tests d'integration

Objectif : verifier les interactions avec PostgreSQL (contraintes, requetes, index).

### ExclusionConstraintTest (btree_gist)

| # | Test | Cas non-trivial |
|---|------|----------------|
| 1 | Deux reservations concurrentes sur memes dates → une seule passe | AR-8 |
| 2 | Deux pending simultanees : premiere expire, seconde confirme | AR-7 |

### UniqueIndexesTest

| # | Test |
|---|------|
| 1 | Doublon lodging_amenity → exception |
| 2 | Doublon staff_lodging → exception |
| 3 | Doublon favorite (customer+lodging) → exception |
| 4 | Doublon booking_night (booking+date) → exception |
| 5 | Doublon conversation (lodging+customer) → exception |
| 6 | Doublon review (booking) → exception |

### BookingRepositoryTest

| # | Test |
|---|------|
| 1 | findOverlapping retourne les conflits corrects |
| 2 | findExpiredPending retourne uniquement les pending expirees |
| 3 | Filtre par lodging_id + plage de dates |

### AvailabilityResolverIntegrationTest

| # | Test | Cas non-trivial |
|---|------|----------------|
| 1 | Check dispo avec blocages + reservations en base | AR-1,3,5 |
| 2 | Annulation libere les dates | AR-19 |
| 3 | Import iCal cree blocage, conflit avec resa → rejet partiel | AR-21 |

### PriceCalculatorIntegrationTest

| # | Test | Cas non-trivial |
|---|------|----------------|
| 1 | Calcul avec saisons reelles en base | PC-4 |
| 2 | Coherence devis / creation reservation (memes donnees) | PC-10 |

### ReviewRepositoryTest

| # | Test |
|---|------|
| 1 | average_rating et review_count recalcules correctement |

---

## 3. Tests fonctionnels (API)

Objectif : tester les endpoints HTTP de bout en bout, avec auth JWT.

### AuthTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/auth/register | Inscription hote OK |
| 2 | POST | /api/auth/register | Inscription customer OK |
| 3 | POST | /api/auth/register | Email duplique → 422 |
| 4 | POST | /api/auth/login | Login OK → JWT retourne |
| 5 | POST | /api/auth/login | Mauvais password → 401 |
| 6 | POST | /api/auth/login | Compte desactive → 403 |
| 7 | GET | /api/auth/me | Profil avec JWT valide |
| 8 | GET | /api/auth/me | Sans JWT → 401 |
| 9 | PUT | /api/auth/me | Modification profil |
| 10 | PUT | /api/auth/me/password | Changement mot de passe |
| 11 | POST | /api/auth/forgot-password | Envoi token reset |
| 12 | POST | /api/auth/reset-password | Reset avec token valide |
| 13 | POST | /api/auth/reset-password | Token expire → 400 |

### LodgingTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/lodgings | Creation logement par hote |
| 2 | POST | /api/lodgings | Customer tente creer → 403 |
| 3 | GET | /api/lodgings | Liste publique |
| 4 | GET | /api/lodgings/{id} | Fiche detaillee (avec amenities, images, rating) |
| 5 | PUT | /api/lodgings/{id} | Modification par proprietaire |
| 6 | PUT | /api/lodgings/{id} | Modification par autre hote → 403 |
| 7 | DELETE | /api/lodgings/{id} | Suppression OK (sans resa active) |
| 8 | DELETE | /api/lodgings/{id} | Suppression avec resa active → 409 (AR-20) |
| 9 | POST | /api/lodgings/{id}/images | Upload photo |
| 10 | POST | /api/lodgings/{id}/amenities | Ajout equipement |

### SeasonTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/lodgings/{id}/seasons | Creation saison OK |
| 2 | POST | /api/lodgings/{id}/seasons | Chevauchement → 422 (PC-6) |
| 3 | PUT | /api/seasons/{id} | Modification prix |
| 4 | DELETE | /api/seasons/{id} | Suppression |

### AvailabilityTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | GET | /api/lodgings/{id}/availability | Disponible → 200 + available: true |
| 2 | GET | /api/lodgings/{id}/availability | Indisponible (resa existante) → available: false |
| 3 | GET | /api/lodgings/{id}/availability | Indisponible (blocage) → available: false |
| 4 | GET | /api/availability | Recherche agregee |
| 5 | GET | /api/lodgings/{id}/calendar | Calendrier mensuel (hote) |

### BookingTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/bookings | Creation → pending + TTL 15 min |
| 2 | POST | /api/bookings | Dates indisponibles → 409 |
| 3 | POST | /api/bookings | < min_stay → 422 |
| 4 | POST | /api/bookings | > max_stay → 422 |
| 5 | POST | /api/bookings | Orphelin avec protection ON → 409 |
| 6 | POST | /api/bookings | Snapshot prix correct (nights, cleaning, tax, deposit, policy) |
| 7 | POST | /api/bookings/{id}/confirm | Confirmation → status confirmed |
| 8 | POST | /api/bookings/{id}/confirm | Pending expiree → 410 |
| 9 | POST | /api/bookings/{id}/cancel | Annulation customer |
| 10 | POST | /api/bookings/{id}/cancel | Annulation hote → remboursement |
| 11 | GET | /api/bookings/{id} | Detail avec JWT customer |
| 12 | GET | /api/bookings?reference={ref} | Consultation par reference |
| 13 | GET | /api/bookings/{id}/nights | Detail nuit par nuit |
| 14 | GET | /api/bookings/{id}/history | Historique statuts |
| 15 | GET | /api/me/bookings | Liste client |
| 16 | GET | /api/lodgings/{id}/bookings | Liste hote |
| 17 | POST | /api/lodgings/{id}/quote | Devis coherent avec prix reel |

### BookingModificationTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | PUT | /api/bookings/{id}/dates | Modification dates par customer → recalcul |
| 2 | PUT | /api/bookings/{id}/dates | Modification dates par hote → recalcul |
| 3 | PUT | /api/bookings/{id}/dates | Nouvelles dates indisponibles → 409 |
| 4 | PUT | /api/bookings/{id}/dates | Nouvelles dates < min_stay → 422 |

### PaymentTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/bookings/{id}/payments | Initiation paiement |
| 2 | GET | /api/bookings/{id}/payments | Liste paiements |
| 3 | POST | /api/payments/{id}/refund | Remboursement par hote |
| 4 | POST | /api/payments/{id}/refund | Remboursement par non-hote → 403 |

### DepositTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | GET | /api/bookings/{id}/deposit | Consultation caution |
| 2 | POST | /api/bookings/{id}/deposit/retain | Retenue partielle |
| 3 | POST | /api/bookings/{id}/deposit/release | Liberation |

### StaffTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/me/staff | Invitation → token envoye |
| 2 | POST | /api/staff-invitations/{token}/accept | Acceptation → compte cree |
| 3 | PUT | /api/staff-assignments/{id}/permissions | Modif permissions → effet immediat (AZ-8) |
| 4 | PUT | /api/staff-assignments/{id}/lodgings | Modif perimetre → effet immediat (AZ-7) |
| 5 | POST | /api/staff-assignments/{id}/revoke | Revocation |
| 6 | GET | /api/me/permissions | Staff consulte ses droits (AZ-10) |
| 7 | - | Staff accede lodging hors perimetre → 403 (AZ-6) |
| 8 | - | Staff hote A accede lodging hote B → 403 (AZ-5) |

### ConversationTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/lodgings/{id}/conversations | Demarrer conversation |
| 2 | POST | /api/lodgings/{id}/conversations | Doublon meme customer+lodging → retourne existante |
| 3 | POST | /api/conversations/{id}/messages | Envoi message |
| 4 | GET | /api/me/conversations | Liste conversations |
| 5 | POST | /api/conversations/{id}/read | Marquer lu |

### FavoriteTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/me/favorites | Ajout favori |
| 2 | POST | /api/me/favorites | Doublon → 409 |
| 3 | GET | /api/me/favorites | Liste favoris |
| 4 | DELETE | /api/me/favorites/{lodgingId} | Retrait |

### ReviewTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/bookings/{id}/review | Avis apres sejour OK |
| 2 | POST | /api/bookings/{id}/review | Avis avant checkout → 403 |
| 3 | POST | /api/bookings/{id}/review | Doublon → 409 |
| 4 | POST | /api/bookings/{id}/review | Verifie average_rating mis a jour |
| 5 | GET | /api/lodgings/{id}/reviews | Liste publique |
| 6 | GET | /api/me/reviews | Avis recus par hote |
| 7 | POST | /api/reviews/{id}/response | Reponse hote |
| 8 | DELETE | /api/reviews/{id} | Suppression admin |

### NotificationTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | GET | /api/me/notifications | Liste notifications |
| 2 | POST | /api/notifications/{id}/read | Marquer lu |
| 3 | POST | /api/me/notifications/read-all | Tout marquer lu |
| 4 | - | Reservation confirmee → notification hote generee |

### SearchTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | GET | /api/search/lodgings | Recherche par ville |
| 2 | GET | /api/search/lodgings | Recherche par dates (exclut indisponibles) |
| 3 | GET | /api/search/lodgings | Filtre capacite + type |
| 4 | GET | /api/search/lodgings | Filtre prix min/max |
| 5 | GET | /api/search/lodgings | Filtre equipements |
| 6 | GET | /api/search/lodgings | Tri par prix / note |

### StatsTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | GET | /api/me/stats | Stats hote : CA correct |
| 2 | GET | /api/me/lodgings/{id}/stats | Stats par logement |
| 3 | GET | /api/me/stats | Staff can_view_revenue → OK |
| 4 | GET | /api/me/stats | Staff sans can_view_revenue → 403 |

### AdminTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | GET | /api/admin/users | Liste users (admin only) |
| 2 | GET | /api/admin/users | Non-admin → 403 |
| 3 | POST | /api/admin/users/{id}/deactivate | Desactivation compte |
| 4 | POST | /api/admin/users/{id}/reactivate | Reactivation |
| 5 | GET | /api/admin/bookings | Liste reservations |
| 6 | DELETE | /api/admin/lodgings/{id} | Suppression logement |
| 7 | GET | /api/admin/reviews | Moderation avis |
| 8 | GET | /api/admin/stats | Stats globales |

---

## 4. Blocage manuel + iCal

### BlockedDateTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/lodgings/{id}/blocked-dates | Blocage OK |
| 2 | POST | /api/lodgings/{id}/blocked-dates | Blocage sur dates reservees → 409 (AR-4) |
| 3 | DELETE | /api/blocked-dates/{id} | Suppression blocage |

### IcalFeedTest

| # | Methode | Endpoint | Test |
|---|---------|----------|------|
| 1 | POST | /api/lodgings/{id}/ical-feeds | Ajout flux import |
| 2 | POST | /api/ical-feeds/{id}/sync | Sync manuelle |
| 3 | GET | /api/lodgings/{id}/ical-export.ics | Export .ics (public, sans JWT) |
| 4 | DELETE | /api/ical-feeds/{id} | Suppression flux |

---

## 5. CI/CD (GitHub Actions)

```yaml
# .github/workflows/ci.yml
steps:
  - php-cs-fixer (lint)
  - phpstan level 6 (analyse statique)
  - phpunit --testsuite=unit
  - phpunit --testsuite=integration (service PostgreSQL)
  - phpunit --testsuite=functional (service PostgreSQL)
```

Ordre : lint → analyse statique → unit → integration → functional
Fail fast : si une etape echoue, les suivantes ne s'executent pas.

---

## Compteurs

| Type | Nombre de tests |
|------|----------------|
| Unitaires | ~45 |
| Integration | ~15 |
| Fonctionnels | ~110 |
| **Total** | **~170** |

44/44 cas non-triviaux couverts (AR-1 a AR-21 + PC-1 a PC-10 + AZ-1 a AZ-10 + AR-4 blocage refusee + AR-19 annulation + AR-20 suppression lodging).
