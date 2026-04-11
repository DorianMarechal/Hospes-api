# Hospes API - Cartographie des endpoints REST

## Convention

- Tous les endpoints retournent du JSON
- Auth : JWT Bearer token (sauf endpoints publics)
- Rôles : PUBLIC, CUSTOMER, HOTE, STAFF, ADMIN
- STAFF : accès conditionné par permissions + périmètre logements
- Pagination : ?page=1&itemsPerPage=30 (API Platform standard)

---

## 1. Authentification

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/auth/register | PUBLIC | Créer un compte (HOTE ou CUSTOMER) |
| POST | /api/auth/login | PUBLIC | Obtenir un JWT |
| POST | /api/auth/refresh | AUTH | Rafraîchir le JWT |
| GET | /api/auth/me | AUTH | Profil de l'utilisateur connecté |
| PUT | /api/auth/me | AUTH | Modifier son profil |
| PUT | /api/auth/me/password | AUTH | Changer son mot de passe |
| POST | /api/auth/forgot-password | PUBLIC | Demander un lien de réinitialisation par email |
| POST | /api/auth/reset-password | PUBLIC | Réinitialiser le mot de passe via token |

---

## 2. Logements

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/lodgings | HOTE | Créer un logement |
| GET | /api/lodgings | PUBLIC | Lister les logements (recherche publique) |
| GET | /api/lodgings/{id} | PUBLIC | Fiche détaillée d'un logement |
| PUT | /api/lodgings/{id} | HOTE, STAFF(can_manage_lodgings) | Modifier un logement |
| DELETE | /api/lodgings/{id} | HOTE | Supprimer un logement (si aucune résa active) |
| GET | /api/me/lodgings | HOTE | Lister mes logements |

### Photos

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/lodgings/{id}/images | HOTE, STAFF(can_manage_lodgings) | Ajouter une photo |
| PUT | /api/lodging-images/{id} | HOTE, STAFF(can_manage_lodgings) | Modifier position/alt_text |
| DELETE | /api/lodging-images/{id} | HOTE, STAFF(can_manage_lodgings) | Supprimer une photo |

### Équipements

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| GET | /api/amenities | PUBLIC | Lister tous les équipements disponibles |
| POST | /api/lodgings/{id}/amenities | HOTE, STAFF(can_manage_lodgings) | Ajouter un équipement au logement |
| DELETE | /api/lodgings/{id}/amenities/{amenityId} | HOTE, STAFF(can_manage_lodgings) | Retirer un équipement |

---

## 3. Saisons / Tarification

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/lodgings/{id}/seasons | HOTE, STAFF(can_manage_lodgings) | Créer une saison (valide chevauchement) |
| GET | /api/lodgings/{id}/seasons | HOTE, STAFF(can_manage_lodgings) | Lister les saisons d'un logement |
| PUT | /api/seasons/{id} | HOTE, STAFF(can_manage_lodgings) | Modifier une saison |
| DELETE | /api/seasons/{id} | HOTE, STAFF(can_manage_lodgings) | Supprimer une saison |

---

## 4. Disponibilité

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| GET | /api/availability?host={id}&type={type}&checkin={date}&checkout={date} | PUBLIC | Recherche de disponibilité agrégée par type/hôte/période |
| GET | /api/lodgings/{id}/availability?checkin={date}&checkout={date} | PUBLIC | Vérifier la disponibilité d'un logement précis |
| GET | /api/lodgings/{id}/calendar?month={YYYY-MM} | HOTE, STAFF(can_view_bookings) | Calendrier mensuel (résas + blocages) |

---

## 5. Blocage de dates

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/lodgings/{id}/blocked-dates | HOTE, STAFF(can_block_dates) | Bloquer des dates |
| GET | /api/lodgings/{id}/blocked-dates | HOTE, STAFF(can_block_dates) | Lister les blocages d'un logement |
| DELETE | /api/blocked-dates/{id} | HOTE, STAFF(can_block_dates) | Supprimer un blocage |

---

## 6. Sync iCal

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/lodgings/{id}/ical-feeds | HOTE | Ajouter un flux iCal (import ou export) |
| GET | /api/lodgings/{id}/ical-feeds | HOTE | Lister les flux iCal d'un logement |
| DELETE | /api/ical-feeds/{id} | HOTE | Supprimer un flux iCal |
| POST | /api/ical-feeds/{id}/sync | HOTE | Forcer une synchronisation manuelle |
| GET | /api/lodgings/{id}/ical-export.ics | PUBLIC | URL d'export iCal (format .ics, pas de JWT) |

---

## 7. Réservations

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/bookings | CUSTOMER | Créer une réservation (pending + TTL 15 min) |
| GET | /api/bookings/{id} | CUSTOMER, HOTE, STAFF(can_view_bookings), ADMIN | Détail d'une réservation |
| GET | /api/bookings?reference={ref} | PUBLIC | Consulter une réservation par référence |
| PUT | /api/bookings/{id}/dates | CUSTOMER, HOTE, STAFF(can_edit_bookings) | Modifier les dates (recalcul prix) |
| POST | /api/bookings/{id}/confirm | CUSTOMER | Confirmer une réservation pending |
| POST | /api/bookings/{id}/cancel | CUSTOMER, HOTE, STAFF(can_edit_bookings) | Annuler (applique politique d'annulation) |
| GET | /api/me/bookings | CUSTOMER | Historique de mes réservations |
| GET | /api/lodgings/{id}/bookings | HOTE, STAFF(can_view_bookings) | Réservations d'un logement |
| GET | /api/bookings/{id}/nights | CUSTOMER, HOTE, STAFF(can_view_revenue), ADMIN | Détail prix nuit par nuit |
| GET | /api/bookings/{id}/history | CUSTOMER, HOTE, STAFF(can_view_bookings), ADMIN | Historique des changements de statut |

### Devis

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/lodgings/{id}/quote | PUBLIC | Obtenir un devis (nuits + ménage + taxe + caution) |

---

## 8. Paiements

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/bookings/{id}/payments | CUSTOMER | Initier un paiement |
| GET | /api/bookings/{id}/payments | CUSTOMER, HOTE, STAFF(can_view_revenue), ADMIN | Lister les paiements d'une réservation |
| GET | /api/me/payments | HOTE | Lister tous mes paiements reçus |
| POST | /api/payments/{id}/refund | HOTE, ADMIN | Rembourser un paiement |

---

## 9. Cautions

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| GET | /api/bookings/{id}/deposit | CUSTOMER, HOTE, ADMIN | Consulter la caution d'une réservation |
| POST | /api/bookings/{id}/deposit/retain | HOTE | Retenir tout ou partie de la caution |
| POST | /api/bookings/{id}/deposit/release | HOTE | Libérer la caution |

---

## 10. Staff

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/me/staff | HOTE | Inviter un membre d'équipe (envoi email) |
| GET | /api/me/staff | HOTE | Lister mes membres d'équipe |
| POST | /api/staff-invitations/{token}/accept | PUBLIC | Accepter une invitation (créer compte staff) |
| PUT | /api/staff-assignments/{id}/permissions | HOTE | Modifier les permissions d'un staff |
| PUT | /api/staff-assignments/{id}/lodgings | HOTE | Modifier le périmètre logements d'un staff |
| POST | /api/staff-assignments/{id}/revoke | HOTE | Révoquer un staff |
| GET | /api/me/permissions | STAFF | Consulter mes permissions et mon périmètre |

---

## 11. Messagerie

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/lodgings/{id}/conversations | CUSTOMER | Démarrer une conversation avec l'hôte |
| GET | /api/me/conversations | CUSTOMER, HOTE, STAFF(can_view_bookings) | Lister mes conversations |
| GET | /api/conversations/{id}/messages | CUSTOMER, HOTE, STAFF(can_view_bookings) | Lister les messages d'une conversation |
| POST | /api/conversations/{id}/messages | CUSTOMER, HOTE, STAFF(can_edit_bookings) | Envoyer un message |
| POST | /api/conversations/{id}/read | CUSTOMER, HOTE, STAFF(can_view_bookings) | Marquer les messages comme lus |

---

## 12. Favoris

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/me/favorites | CUSTOMER | Ajouter un logement en favori |
| GET | /api/me/favorites | CUSTOMER | Lister mes favoris |
| DELETE | /api/me/favorites/{lodgingId} | CUSTOMER | Retirer un favori |

---

## 13. Avis

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| POST | /api/bookings/{id}/review | CUSTOMER | Laisser un avis (après séjour uniquement) |
| GET | /api/lodgings/{id}/reviews | PUBLIC | Lister les avis d'un logement |
| GET | /api/me/reviews | HOTE | Lister tous les avis reçus sur mes logements |
| POST | /api/reviews/{id}/response | HOTE | Répondre à un avis |
| DELETE | /api/reviews/{id} | ADMIN | Supprimer un avis (modération) |

---

## 14. Notifications

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| GET | /api/me/notifications | AUTH | Lister mes notifications |
| POST | /api/notifications/{id}/read | AUTH | Marquer une notification comme lue |
| POST | /api/me/notifications/read-all | AUTH | Marquer toutes mes notifications comme lues |

---

## 15. Recherche

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| GET | /api/search/lodgings?city={}&checkin={}&checkout={}&type={}&capacity={}&min_price={}&max_price={}&amenities[]={}&lat={}&lng={}&radius={}&sort={} | PUBLIC | Recherche avancée de logements |

---

## 16. Statistiques

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| GET | /api/me/stats?period={month/quarter/year}&date={YYYY-MM} | HOTE, STAFF(can_view_revenue) | Stats hôte : CA, taux occupation, RevPAR |
| GET | /api/me/lodgings/{id}/stats?period={}&date={} | HOTE, STAFF(can_view_revenue) | Stats par logement |

---

## 17. Admin

| Méthode | Endpoint | Rôle | Description |
|---------|----------|------|-------------|
| GET | /api/admin/users | ADMIN | Lister tous les utilisateurs |
| GET | /api/admin/users/{id} | ADMIN | Détail d'un utilisateur |
| POST | /api/admin/users/{id}/deactivate | ADMIN | Désactiver un compte |
| POST | /api/admin/users/{id}/reactivate | ADMIN | Réactiver un compte |
| GET | /api/admin/lodgings | ADMIN | Lister tous les logements |
| PUT | /api/admin/lodgings/{id} | ADMIN | Modifier un logement |
| DELETE | /api/admin/lodgings/{id} | ADMIN | Supprimer un logement |
| GET | /api/admin/bookings | ADMIN | Lister toutes les réservations |
| GET | /api/admin/payments | ADMIN | Lister tous les paiements |
| GET | /api/admin/reviews | ADMIN | Lister tous les avis (modération) |
| GET | /api/admin/stats?period={}&date={} | ADMIN | Stats globales plateforme |
