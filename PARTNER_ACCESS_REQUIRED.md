# Hospes API — Accès partenaires requis

Ce document liste toutes les intégrations qui nécessitent un accès partenaire/API externe.
Les interfaces et le code d'intégration sont prêts (placeholders). Il suffit d'implémenter les appels API réels quand l'accès est obtenu.

---

## 1. Channel Manager — Airbnb

| | |
|---|---|
| **Quoi** | Sync bidirectionnelle : push dispo, pull réservations, sync prix |
| **Fichiers prêts** | `src/Channel/AirbnbChannel.php`, `src/Channel/ChannelManagerInterface.php` |
| **Comment obtenir l'accès** | Candidature [Airbnb Software Partner](https://www.airbnb.com/partner) |
| **Prérequis** | Produit fonctionnel, ~50-100 propriétés gérées, entité légale, assurance |
| **Délai** | 3-6 mois (certification technique + sandbox + prod) |
| **API docs** | Fournies après acceptation dans le programme |
| **Priorité** | Haute — c'est le canal #1 pour les hôtes indépendants |

---

## 2. Channel Manager — Booking.com

| | |
|---|---|
| **Quoi** | Sync bidirectionnelle : push dispo, pull réservations, sync prix |
| **Fichiers prêts** | `src/Channel/BookingComChannel.php`, `src/Channel/ChannelManagerInterface.php` |
| **Comment obtenir l'accès** | Candidature [Booking.com Connectivity Partner](https://connectivity.booking.com) |
| **Prérequis** | Volume minimum de propriétés, certification XML/JSON, audit technique |
| **Délai** | 2-4 mois (certification par phases) |
| **API docs** | [connectivity.booking.com/docs](https://connectivity.booking.com) (après acceptation) |
| **Priorité** | Haute — canal #2 en Europe |

---

## 3. PriceLabs (Dynamic Pricing)

| | |
|---|---|
| **Quoi** | PriceLabs pousse des prix optimisés via webhook, Hospes crée/MAJ des PriceOverride |
| **Fichiers prêts** | `src/Controller/PricingWebhookController.php` (webhook inbound prêt) |
| **Comment obtenir l'accès** | Créer un compte sur [pricelabs.co](https://pricelabs.co), plan à partir de $20/mois/logement |
| **Prérequis** | Compte PriceLabs, configurer le webhook URL vers `/api/lodgings/{id}/pricing-webhook` |
| **Délai** | Immédiat (self-service) |
| **API docs** | [pricelabs.co/integrations](https://pricelabs.co) — format webhook à vérifier |
| **Format webhook attendu** | `{"rates": [{"date": "2026-05-10", "price": 8500, "label": "dynamic"}]}` — à adapter au format réel |
| **Priorité** | Moyenne — les hôtes tech-savvy l'utilisent déjà |

**Alternative** : Beyond Pricing ([beyondpricing.com](https://beyondpricing.com)) — même principe, webhook/API similaire.

---

## 4. Pennylane (Comptabilité française)

| | |
|---|---|
| **Quoi** | Push automatique des écritures comptables (paiements, remboursements, commissions) |
| **Fichiers prêts** | `src/Service/AccountingService.php` (transactions prêtes avec mapping plan comptable FR) |
| **Comment obtenir l'accès** | Demander un accès API via [pennylane.com/api](https://pennylane.com) ou contacter leur support |
| **Prérequis** | Compte Pennylane (à partir de 24€/mois), clé API |
| **Délai** | 1-2 semaines (validation manuelle) |
| **API docs** | [pennylane.com/docs/api](https://pennylane.com/docs/api) |
| **À implémenter** | Service `PennylaneClient` : POST /api/v1/journal_entries avec le mapping depuis AccountingTransaction |
| **Priorité** | Moyenne — pertinent pour les hôtes français déclarant en BIC/micro-BIC |

---

## 5. QuickBooks / Xero (Comptabilité internationale)

| | |
|---|---|
| **Quoi** | Push des écritures ou export compatible pour import |
| **Fichiers prêts** | `src/Service/AccountingService.php` + `src/Controller/AccountingExportController.php` (CSV export prêt) |
| **Comment obtenir l'accès** | **QuickBooks** : [developer.intuit.com](https://developer.intuit.com) — **Xero** : [developer.xero.com](https://developer.xero.com) |
| **Prérequis** | Compte développeur (gratuit), OAuth2 app registration |
| **Délai** | Immédiat (self-service pour le sandbox) |
| **API docs** | QuickBooks : [developer.intuit.com/docs](https://developer.intuit.com) — Xero : [developer.xero.com/documentation](https://developer.xero.com) |
| **À implémenter** | OAuth2 flow + POST journal entries / invoices |
| **Priorité** | Basse — l'export CSV couvre 90% du besoin pour l'instant |

---

## 6. Nuki (Smart Lock)

| | |
|---|---|
| **Quoi** | Génération/révocation automatique de codes d'accès liés aux réservations |
| **Fichiers prêts** | `src/SmartLock/NukiProvider.php`, `src/SmartLock/SmartLockProviderInterface.php` |
| **Comment obtenir l'accès** | Créer un compte sur [web.nuki.io](https://web.nuki.io), activer l'API dans les settings |
| **Prérequis** | Serrure Nuki physique, compte Nuki Web, API token |
| **Délai** | Immédiat (self-service) |
| **API docs** | [developer.nuki.io](https://developer.nuki.io/page/nuki-web-api-1-4/3) |
| **À implémenter** | `POST /smartlock/{id}/auth` pour créer un code, `DELETE` pour révoquer |
| **Priorité** | Basse — niche, mais gros wow factor pour les hôtes équipés |

---

## 7. Igloohome (Smart Lock)

| | |
|---|---|
| **Quoi** | Même chose que Nuki — codes d'accès automatiques |
| **Fichiers prêts** | `src/SmartLock/IgloohomeProvider.php`, `src/SmartLock/SmartLockProviderInterface.php` |
| **Comment obtenir l'accès** | Contacter [igloohome.co/api](https://igloohome.co) — programme partenaire |
| **Prérequis** | Serrure Igloohome, accès API (sur demande) |
| **Délai** | 1-4 semaines |
| **API docs** | Fournis après acceptation dans le programme |
| **Priorité** | Basse |

---

## 8. Swikly (Assurance / Caution en ligne)

| | |
|---|---|
| **Quoi** | Caution dématérialisée, protection voyageur, gestion réclamations |
| **Fichiers prêts** | `src/Insurance/SwiklyProvider.php`, `src/Insurance/InsuranceProviderInterface.php` |
| **Comment obtenir l'accès** | Contacter [swikly.com/pro](https://www.swikly.com/pro) — programme partenaire PMS |
| **Prérequis** | Entité légale, volume minimum (à négocier) |
| **Délai** | 2-4 semaines |
| **API docs** | Fournis après signature du contrat partenaire |
| **Priorité** | Moyenne — différenciant fort pour les hôtes qui veulent remplacer le chèque de caution |

---

## 9. Superhog (Vérification identité + Assurance)

| | |
|---|---|
| **Quoi** | Screening guests, protection dommages, assurance intégrée |
| **Fichiers prêts** | `src/Insurance/SuperhogProvider.php`, `src/Insurance/InsuranceProviderInterface.php` |
| **Comment obtenir l'accès** | Contacter [superhog.com/partners](https://www.superhog.com) — programme partenaire |
| **Prérequis** | Volume minimum de réservations |
| **Délai** | 2-6 semaines |
| **API docs** | Fournis après acceptation |
| **Priorité** | Basse — marché anglophone principalement |

---

## 10. SMS (Twilio / OVH SMS)

| | |
|---|---|
| **Quoi** | Envoi de messages automatisés par SMS (canal `sms` dans MessageTemplate) |
| **Fichiers prêts** | `src/Service/AutomatedMessageDispatcher.php` (canal SMS loggé, prêt à brancher) |
| **Comment obtenir l'accès** | **Twilio** : [twilio.com](https://www.twilio.com) — **OVH SMS** : [ovhcloud.com/fr/sms](https://www.ovhcloud.com/fr/sms/) |
| **Prérequis** | Compte + crédit SMS (Twilio ~0.05€/SMS, OVH ~0.06€/SMS) |
| **Délai** | Immédiat (self-service) |
| **API docs** | Twilio : [twilio.com/docs/sms](https://www.twilio.com/docs/sms) — OVH : [docs.ovh.com/fr/sms](https://docs.ovh.com/fr/sms/) |
| **À implémenter** | Ajouter un `SmsChannel` dans le `match` de `AutomatedMessageDispatcher::deliverMessage` |
| **Priorité** | Moyenne — les SMS ont un taux d'ouverture de 98% vs 20% pour l'email |

---

## Ordre de priorité recommandé

1. **PriceLabs** — accès immédiat, webhook déjà prêt, juste valider le format
2. **SMS (Twilio)** — accès immédiat, fort impact sur l'engagement
3. **Swikly** — différenciant fort pour le marché français
4. **Pennylane** — pertinent si tes premiers users sont français
5. **Airbnb** — quand tu atteins ~50 propriétés gérées
6. **Booking.com** — idem, après Airbnb
7. **Nuki** — quand un hôte le demande
8. **QuickBooks/Xero** — l'export CSV suffit pour l'instant
9. **Igloohome/Superhog** — niche
