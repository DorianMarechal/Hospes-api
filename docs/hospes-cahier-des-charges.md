# Hospes - Cahier des charges

## 1. Presentation du projet

### 1.1 Contexte

Hospes est une API REST de gestion de reservations pour hebergeurs independants. Elle cible les proprietaires gerant un ou plusieurs logements de typologies variees : chambres d'hotel, gites, cabanes, chambres d'hotes, appartements.

Le marche actuel offre deux options aux hebergeurs independants : les grandes plateformes OTA (Airbnb, Booking.com) qui prelevent des commissions elevees (15-20%) et reduisent l'autonomie, ou des solutions proprietaires couteuses et rigides. Hospes se positionne comme une alternative en fournissant une API permettant aux hebergeurs de gerer directement leurs reservations, leur tarification et leur equipe.

### 1.2 Objectifs

- Permettre la gestion multi-logements avec tarification saisonniere avancee (semaine/week-end)
- Eliminer les doubles reservations via un systeme de disponibilite robuste
- Synchroniser les calendriers avec les OTA existantes via iCal
- Offrir une gestion d'equipe avec permissions granulaires
- Fournir des statistiques de performance (CA, taux d'occupation, RevPAR)
- Garantir la fiabilite des reservations face aux acces concurrents

### 1.3 Stack technique

- Symfony 7.4 LTS avec API Platform
- Doctrine ORM avec PostgreSQL (extension btree_gist requise)
- Authentification JWT (LexikJWTAuthenticationBundle)
- PHPUnit pour les tests
- PHPStan niveau 6 pour l'analyse statique
- PHP-CS-Fixer pour le formatage
- GitHub Actions pour l'integration continue

---

## 2. Acteurs et roles

### 2.1 Hote (ROLE_HOTE)

Proprietaire ou gestionnaire qui cree et administre un ou plusieurs logements. Il a le controle total sur ses logements, sa tarification, ses reservations et son equipe. Peut consulter ses statistiques de performance.

### 2.2 Customer (ROLE_CUSTOMER)

Voyageur qui recherche, reserve et paie un logement. Peut creer un compte librement (independamment d'une reservation), gerer ses favoris, laisser des avis apres un sejour, et communiquer avec les hotes.

### 2.3 Staff (ROLE_STAFF)

Collaborateur invite par un hote. Dispose de permissions granulaires et d'un perimetre restreint aux logements que l'hote lui attribue. Ne peut pas creer de logements ni inviter d'autres staff.

Permissions disponibles :
- can_view_bookings : consulter les reservations
- can_edit_bookings : modifier/annuler des reservations
- can_block_dates : bloquer des dates
- can_view_revenue : voir les montants et statistiques financieres
- can_manage_lodgings : modifier les fiches logements, photos, equipements, saisons

### 2.4 Admin (ROLE_ADMIN)

Administrateur de la plateforme. Gere les comptes utilisateurs (activation/desactivation), modere les avis, supervise les logements et reservations, consulte les statistiques globales.

---

## 3. User stories

### 3.1 Hote (23 stories)

**Compte et profil**

US-H01 : En tant qu'hote, je veux creer un compte pour acceder a la plateforme.
US-H02 : En tant qu'hote, je veux modifier mon profil (nom, telephone, email).
US-H03 : En tant qu'hote, je veux changer mon mot de passe.
US-H04 : En tant qu'hote, je veux reinitialiser mon mot de passe si je l'ai oublie.

**Logements**

US-H05 : En tant qu'hote, je veux creer un logement avec ses caracteristiques (type, capacite, adresse, horaires check-in/check-out).
US-H06 : En tant qu'hote, je veux modifier les informations d'un logement.
US-H07 : En tant qu'hote, je veux supprimer un logement qui n'a aucune reservation active.
US-H08 : En tant qu'hote, je veux ajouter, reordonner et supprimer des photos pour un logement.
US-H09 : En tant qu'hote, je veux associer des equipements (wifi, parking, piscine...) a un logement.
US-H10 : En tant qu'hote, je veux lister tous mes logements.

**Tarification**

US-H11 : En tant qu'hote, je veux definir un tarif de base (semaine et week-end) pour un logement.
US-H12 : En tant qu'hote, je veux creer des saisons avec des tarifs specifiques (semaine et week-end), un min_stay et un max_stay.
US-H13 : En tant qu'hote, je veux definir les frais de menage, la taxe de sejour par personne/nuit, et le montant de la caution.
US-H14 : En tant qu'hote, je veux choisir une politique d'annulation (flexible, moderate, strict) par logement.
US-H15 : En tant qu'hote, je veux activer ou desactiver la protection contre les nuits orphelines.

**Reservations et calendrier**

US-H16 : En tant qu'hote, je veux consulter le calendrier mensuel d'un logement (reservations + blocages).
US-H17 : En tant qu'hote, je veux bloquer manuellement des dates pour un logement.
US-H18 : En tant qu'hote, je veux consulter, modifier les dates ou annuler une reservation.
US-H19 : En tant qu'hote, je veux synchroniser mon calendrier avec des OTA via import/export iCal.

**Equipe**

US-H20 : En tant qu'hote, je veux inviter un collaborateur par email, lui attribuer des permissions et un perimetre de logements.
US-H21 : En tant qu'hote, je veux modifier les permissions ou le perimetre d'un staff a tout moment (effet immediat).
US-H22 : En tant qu'hote, je veux revoquer un staff.

**Statistiques et avis**

US-H23 : En tant qu'hote, je veux consulter mes statistiques (CA, taux d'occupation, RevPAR) globales et par logement, sur differentes periodes (mois, trimestre, annee).

### 3.2 Customer (16 stories)

**Compte et profil**

US-C01 : En tant que customer, je veux creer un compte librement (sans reservation en cours).
US-C02 : En tant que customer, je veux modifier mon profil.
US-C03 : En tant que customer, je veux changer mon mot de passe.
US-C04 : En tant que customer, je veux reinitialiser mon mot de passe si je l'ai oublie.

**Recherche et consultation**

US-C05 : En tant que customer, je veux rechercher des logements par ville, dates, type, capacite, prix, equipements.
US-C06 : En tant que customer, je veux consulter la fiche detaillee d'un logement (photos, equipements, avis, tarifs).
US-C07 : En tant que customer, je veux verifier la disponibilite d'un logement pour des dates precises.
US-C08 : En tant que customer, je veux obtenir un devis detaille (nuits, menage, taxe de sejour, caution).

**Reservation**

US-C09 : En tant que customer, je veux creer une reservation (statut pending, 15 min pour confirmer).
US-C10 : En tant que customer, je veux confirmer une reservation pending.
US-C11 : En tant que customer, je veux modifier les dates d'une reservation (recalcul automatique du prix).
US-C12 : En tant que customer, je veux annuler une reservation (application de la politique d'annulation).
US-C13 : En tant que customer, je veux consulter l'historique de mes reservations.
US-C14 : En tant que customer, je veux consulter une reservation par son code de reference.

**Interactions**

US-C15 : En tant que customer, je veux ajouter et retirer des logements de mes favoris.
US-C16 : En tant que customer, je veux laisser un avis sur un logement apres mon sejour.

### 3.3 Staff (3 stories)

US-S01 : En tant que staff, je veux accepter une invitation recue par email.
US-S02 : En tant que staff, je veux consulter mes permissions et mon perimetre de logements.
US-S03 : En tant que staff, je veux effectuer les actions autorisees par mes permissions sur les logements de mon perimetre.

### 3.4 Admin (7 stories)

US-A01 : En tant qu'admin, je veux lister et consulter tous les utilisateurs.
US-A02 : En tant qu'admin, je veux desactiver ou reactiver un compte utilisateur.
US-A03 : En tant qu'admin, je veux lister et gerer tous les logements.
US-A04 : En tant qu'admin, je veux lister toutes les reservations.
US-A05 : En tant qu'admin, je veux lister et gerer tous les paiements.
US-A06 : En tant qu'admin, je veux moderer les avis (consultation et suppression).
US-A07 : En tant qu'admin, je veux consulter les statistiques globales de la plateforme (reservations, CA, taux d'occupation).

### 3.5 Transverses

US-T01 : En tant qu'utilisateur connecte, je veux recevoir des notifications pour les evenements me concernant.
US-T02 : En tant qu'utilisateur connecte, je veux marquer mes notifications comme lues.
US-T03 : En tant qu'utilisateur connecte (customer ou hote), je veux echanger des messages dans une conversation liee a un logement.

---

## 4. Regles metier

### 4.1 Disponibilite (AvailabilityResolver)

**Convention de nuitee** : le check-out du jour J et le check-in du jour J sont compatibles. Une reservation du 10 au 13 occupe les nuits du 10, 11 et 12. Un check-in le 13 est possible.

**Blocage manuel** : un hote peut bloquer des dates manuellement (entretien, usage personnel). Un blocage a le meme poids qu'une reservation pour le calcul de disponibilite. Il est interdit de bloquer des dates deja reservees ; l'hote doit d'abord annuler la reservation.

**Reservation pending et TTL** : toute nouvelle reservation est creee avec le statut "pending" et un delai de 15 minutes pour confirmer. Pendant ce delai, les dates sont bloquees. Si le delai expire, la reservation est automatiquement annulee et les dates liberees. Le nettoyage se fait par verification paresseuse (a chaque consultation de disponibilite) et par un cron periodique.

**Concurrence** : pour eviter les doubles reservations en cas d'acces simultane, une contrainte d'exclusion PostgreSQL (EXCLUDE USING gist avec btree_gist) est mise en place au niveau base de donnees. Elle est doublee d'une verification au niveau applicatif (defense en profondeur).

**Duree de sejour** : chaque logement a un min_stay et un max_stay globaux, surchargeable par saison. Si un sejour couvre plusieurs saisons, le min_stay le plus restrictif s'applique.

**Nuits orphelines** : une nuit orpheline est un gap entre deux reservations dont la duree est inferieure au min_stay, rendant la periode inreservable. La protection est configurable par logement (on/off). Si activee, une reservation qui creerait un tel gap (avant ou apres elle) est refusee. Exception : une reservation qui comble exactement un trou existant est toujours acceptee.

**Annulation** : l'annulation d'une reservation (par le customer ou l'hote) libere immediatement les dates.

**Suppression de logement** : un logement ne peut pas etre supprime s'il a des reservations actives (pending ou confirmed).

### 4.2 Synchronisation iCal

L'hote peut configurer des flux iCal en import ou export pour chaque logement.

Import : la synchronisation recree des blocages a partir du flux externe. Si un evenement importe entre en conflit avec une reservation existante, il est rejete sur les dates concernees (pas de suppression de reservation existante). La synchronisation peut etre declenchee manuellement ou par cron.

Export : une URL publique (sans JWT) au format .ics expose le calendrier d'un logement pour etre consomme par des OTA ou d'autres systemes.

### 4.3 Tarification (PriceCalculator)

**Structure des tarifs** : chaque logement a un tarif de base compose de deux prix (semaine et week-end). Les saisons surchargent le tarif de base avec leurs propres prix semaine et week-end.

**Resolution nuit par nuit** : le prix de chaque nuit est determine individuellement. Pour une nuit donnee, le systeme verifie si une saison couvre cette date. Si oui, le tarif saisonnier (semaine ou week-end selon le jour) s'applique. Sinon, le tarif de base (semaine ou week-end selon le jour) s'applique. Le week-end correspond au vendredi et samedi soir.

**Chevauchement de saisons** : strictement interdit. Une validation a la creation/modification empeche toute superposition de periodes.

**Arrondi** : les prix individuels par nuit sont calcules en centimes d'euros (entiers). L'arrondi au centime s'applique uniquement sur le total final.

**Frais supplementaires** : le menage (cleaning_fee) est facture une fois par sejour. La taxe de sejour est calculee par personne et par nuit. La caution (deposit_amount) est un montant fixe par logement.

**Snapshot a la reservation** : au moment de la creation d'une reservation, les montants (nuits, menage, taxe, caution) et la politique d'annulation sont copies dans la reservation. Cela garantit que des modifications ulterieures des tarifs n'affectent pas les reservations existantes.

**Devis** : le devis utilise le meme calcul que la reservation. Il est indicatif : si les tarifs changent entre le devis et la reservation, le prix final sera different.

**Modification de dates** : tout changement de dates entraine un recalcul automatique du prix selon les tarifs en vigueur.

### 4.4 Politique d'annulation

Trois politiques possibles, configurees par logement :

- Flexible : remboursement total si l'annulation intervient plus de 24 heures avant le check-in
- Moderee : remboursement total si l'annulation intervient plus de 5 jours avant le check-in
- Stricte : aucun remboursement quelle que soit la date d'annulation

Exception : une annulation a l'initiative de l'hote entraine systematiquement un remboursement total au customer.

### 4.5 Autorisation et permissions

**JWT et roles** : le token JWT contient uniquement le role et l'identifiant de l'utilisateur. Les permissions detaillees sont stockees en base de donnees et verifiees a chaque requete. Cela garantit un effet immediat en cas de modification des permissions.

**Modele de permissions du staff** : chaque staff est lie a un hote via une assignation. Cette assignation porte des permissions (actions autorisees) et un perimetre (logements accessibles). Un Voter Symfony verifie a chaque requete : la permission requise, l'appartenance du logement au perimetre, et la chaine de propriete (le logement appartient bien a l'hote du staff).

**Isolation multi-tenant** : un staff d'un hote A ne peut jamais acceder aux ressources d'un hote B, meme s'il a toutes les permissions.

**Filtrage de reponse** : un staff avec can_view_bookings mais sans can_view_revenue voit les reservations sans les montants financiers (pas de 403, mais reponse filtree).

**Revocation** : un staff revoque perd immediatement tout acces.

### 4.6 Paiements

Le systeme prepare l'integration d'un prestataire de paiement (Stripe, PayPal ou autre). Un paiement est lie a une reservation et peut etre de type "booking" (paiement initial) ou "refund" (remboursement). Les statuts suivis sont : pending, succeeded, failed, refunded.

La reference du prestataire (provider_transaction_id) est stockee pour le rapprochement comptable.

### 4.7 Caution (deposit)

Chaque reservation peut avoir une caution (une seule par reservation). Le montant est defini par le logement. Apres le sejour, l'hote peut liberer la caution ou en retenir tout ou partie (avec motif obligatoire). Statuts : held, released, partially_retained, fully_retained.

### 4.8 Avis

Un customer peut laisser un seul avis par reservation, uniquement apres la date de check-out. L'avis comporte une note (1 a 5) et un commentaire optionnel. L'hote peut repondre a un avis. L'admin peut supprimer un avis (moderation).

Chaque avis declenche la mise a jour des champs denormalises du logement (average_rating et review_count).

### 4.9 Messagerie

Une conversation est liee a un logement et un customer. Il ne peut y avoir qu'une seule conversation par couple customer/logement. La conversation peut exister avant une reservation. Les messages peuvent etre marques comme lus.

### 4.10 Notifications

Les notifications sont generees par le systeme lors d'evenements : reservation confirmee, annulee, modifiee, expiree, invitation staff, avis recu, message recu, paiement recu, caution liberee. Elles sont consultables et marquables comme lues (individuellement ou en masse).

---

## 5. Modele de donnees

Le modele comporte 20 entites. Tous les identifiants sont des UUID. Les montants monetaires sont stockes en centimes d'euros (entiers).

### 5.1 User

Utilisateur de la plateforme. Champs : email (unique), mot de passe, role (HOTE, STAFF, ADMIN, CUSTOMER), prenom, nom, telephone (optionnel), token de reinitialisation de mot de passe, statut actif/inactif, dates de creation et modification.

### 5.2 Lodging (logement)

Logement rattache a un hote. Champs : nom, type (hotel_room, gite, cabin, bnb, apartment), description, capacite, tarifs de base semaine/week-end, frais de menage, taxe de sejour par personne, montant caution, politique d'annulation, min/max stay globaux, protection orpheline, horaires check-in/check-out, adresse complete (rue, ville, region, code postal, pays ISO), coordonnees GPS, note moyenne et nombre d'avis (denormalises), statut actif.

### 5.3 Lodging Image

Photo rattachee a un logement. Champs : URL, texte alternatif, position d'affichage (1 = photo principale).

### 5.4 Amenity (equipement)

Equipement disponible sur la plateforme. Champs : nom unique, icone.

### 5.5 Lodging Amenity

Association logement/equipement. Contrainte d'unicite sur le couple logement/equipement.

### 5.6 Season (saison)

Saison tarifaire rattachee a un logement. Champs : nom, date de debut, date de fin, prix semaine, prix week-end, min_stay et max_stay (optionnels, surchargent le logement). Contrainte metier : pas de chevauchement entre saisons d'un meme logement.

### 5.7 Booking (reservation)

Reservation rattachee a un logement et un customer. Champs : reference unique non devinable, dates check-in/check-out, nombre d'hotes, nombre de nuits (denormalise), montants snapshotes (total nuits, menage, taxe de sejour, caution, prix total), politique d'annulation snapshotee, statut (pending, confirmed, cancelled, completed), date d'expiration (pending), informations d'annulation (par qui, motif).

### 5.8 Booking Night

Detail du prix pour une nuit specifique d'une reservation. Champs : date, prix, source (ex: nom de la saison + jour type, ou tarif de base + jour type). Contrainte d'unicite sur le couple reservation/date.

### 5.9 Payment (paiement)

Paiement lie a une reservation. Champs : montant, type (booking, refund), methode (card, bank_transfer), statut (pending, succeeded, failed, refunded), prestataire et reference de transaction, motif de remboursement.

### 5.10 Deposit (caution)

Caution liee a une reservation (une seule par reservation). Champs : montant initial, statut (held, released, partially_retained, fully_retained), montant retenu, motif de retenue, date de liberation.

### 5.11 Blocked Date

Blocage de dates sur un logement. Champs : dates de debut et fin, motif, source (manual ou ical).

### 5.12 iCal Feed

Flux de synchronisation iCal rattache a un logement. Champs : URL, direction (import ou export), date de derniere synchronisation.

### 5.13 Staff Assignment

Lien entre un staff et un hote. Champs : statut revoque, token d'invitation, date d'expiration de l'invitation, date d'acceptation.

### 5.14 Staff Permission

Permission rattachee a une assignation staff. Champ : nom de la permission.

### 5.15 Staff Lodging

Logement rattache au perimetre d'un staff. Contrainte d'unicite sur le couple assignation/logement.

### 5.16 Conversation

Echange entre un customer et un hote autour d'un logement. Champs : logement, customer, hote, reservation liee (optionnel), statut (open, closed, archived). Contrainte d'unicite sur le couple logement/customer.

### 5.17 Message

Message dans une conversation. Champs : expediteur, contenu, statut lu, date de lecture.

### 5.18 Favorite

Logement mis en favori par un customer. Contrainte d'unicite sur le couple customer/logement.

### 5.19 Review (avis)

Avis lie a une reservation (un seul par reservation). Champs : customer, logement, note (1-5), commentaire, reponse de l'hote, dates de reponse et de moderation, moderateur.

### 5.20 Booking Status History

Historique des changements de statut d'une reservation. Champs : statut precedent (null pour la creation), nouveau statut, utilisateur declencheur, motif.

### 5.21 Notification

Notification destinee a un utilisateur. Champs : type d'evenement, titre, contenu, entite liee (type + ID), statut lu, date de lecture.

---

## 6. Cartographie API

L'API expose environ 78 endpoints repartis en 17 sections. Tous les endpoints retournent du JSON. L'authentification se fait par JWT Bearer token sauf les endpoints publics. La pagination suit le standard API Platform.

### 6.1 Authentification (8 endpoints)

Inscription (hote ou customer), connexion (obtention JWT), rafraichissement du token, consultation et modification du profil, changement de mot de passe, demande de reinitialisation par email, reinitialisation via token.

### 6.2 Logements (12 endpoints)

CRUD complet sur les logements (creation par hote, liste publique, fiche detaillee, modification, suppression sous conditions). Gestion des photos (ajout, modification position/alt_text, suppression). Gestion des equipements (liste, ajout, suppression).

### 6.3 Saisons (4 endpoints)

CRUD sur les saisons d'un logement. Validation du non-chevauchement a la creation et modification.

### 6.4 Disponibilite (3 endpoints)

Recherche de disponibilite agregee (par type/hote/periode). Verification de disponibilite d'un logement precis (dates donnees). Calendrier mensuel pour l'hote (reservations + blocages).

### 6.5 Blocage de dates (3 endpoints)

Creation, consultation et suppression de blocages manuels.

### 6.6 Sync iCal (5 endpoints)

Ajout d'un flux iCal (import ou export), liste des flux, suppression, synchronisation manuelle, URL d'export public au format .ics (accessible sans JWT).

### 6.7 Reservations (11 endpoints)

Creation (pending + TTL), consultation detail, consultation par reference, modification de dates, confirmation, annulation, historique customer, liste par logement (hote), detail nuit par nuit, historique des statuts, devis.

### 6.8 Paiements (4 endpoints)

Initiation d'un paiement, liste des paiements d'une reservation, liste des paiements recus (hote), remboursement.

### 6.9 Cautions (3 endpoints)

Consultation de la caution, retenue (totale ou partielle), liberation.

### 6.10 Staff (7 endpoints)

Invitation par email, liste des membres, acceptation d'invitation, modification des permissions, modification du perimetre logements, revocation, consultation de ses propres permissions.

### 6.11 Messagerie (5 endpoints)

Demarrer une conversation, lister ses conversations, lister les messages, envoyer un message, marquer comme lu.

### 6.12 Favoris (3 endpoints)

Ajout, liste, retrait.

### 6.13 Avis (5 endpoints)

Laisser un avis, liste publique par logement, liste des avis recus (hote), reponse de l'hote, suppression (admin).

### 6.14 Notifications (3 endpoints)

Liste, marquer une notification comme lue, marquer toutes comme lues.

### 6.15 Recherche (1 endpoint)

Recherche avancee de logements avec filtres : ville, dates, type, capacite, prix min/max, equipements, coordonnees GPS + rayon, tri (prix, note).

### 6.16 Statistiques (2 endpoints)

Stats hote (CA, taux d'occupation, RevPAR) globales et par logement, sur une periode donnee (mois, trimestre, annee).

### 6.17 Administration (11 endpoints)

Gestion des utilisateurs (liste, detail, desactivation, reactivation). Gestion des logements (liste, modification, suppression). Liste des reservations et paiements. Moderation des avis. Statistiques globales plateforme.

---

## 7. Strategie de tests

### 7.1 Niveaux de tests

Trois niveaux : unitaire, integration, fonctionnel.

Les tests unitaires (~45) couvrent la logique metier isolee : resolution de disponibilite, calcul de prix, protection contre les orphelins, validation des saisons, voters de securite, gestion de caution.

Les tests d'integration (~15) verifient les interactions avec PostgreSQL : contraintes d'exclusion btree_gist (concurrence), index uniques, requetes repositoires, coherence devis/reservation.

Les tests fonctionnels (~110) testent chaque endpoint de bout en bout avec authentification JWT : reponses attendues, codes d'erreur, autorisations, effets de bord.

### 7.2 Couverture des cas non-triviaux

Les 44 cas non-triviaux identifies (21 disponibilite, 10 tarification, 10 autorisation, 3 iCal/blocage) sont tous couverts par au moins un test.

### 7.3 Contraintes techniques

La base de test doit etre PostgreSQL (pas SQLite) en raison de la contrainte d'exclusion btree_gist. Chaque test fonctionnel repart d'une base propre. Le CI execute dans l'ordre : lint, analyse statique, tests unitaires, tests d'integration, tests fonctionnels (fail-fast).

---

## 8. Securite et conformite

### 8.1 Authentification

JWT avec expiration courte et mecanisme de rafraichissement. Le token contient uniquement le role et l'identifiant utilisateur (pas de donnees sensibles ni de permissions).

### 8.2 Mots de passe

Hashage avec l'algorithme par defaut de Symfony (bcrypt/argon2). Reinitialisation par token avec expiration.

### 8.3 RGPD

Principe de minimisation des donnees : seules les informations necessaires au fonctionnement du service sont collectees (email, nom, prenom, telephone). Pas de collecte de date de naissance, adresse postale, genre, nationalite.

Obligations a implementer :
- Politique de confidentialite accessible
- Consentement explicite au traitement des donnees a l'inscription
- Droit d'acces aux donnees personnelles
- Droit de rectification
- Droit a l'effacement (avec gestion des contraintes referentielles : reservations passees, avis)
- Portabilite des donnees

### 8.4 Isolation des donnees

Chaque hote ne voit que ses propres logements et reservations. Un staff ne peut acceder qu'aux logements de son perimetre, eux-memes appartenant a son hote. Aucune fuite de donnees inter-hotes n'est acceptable.

---

## 9. Evolutions prevues (V2)

### 9.1 Double validation des modifications de reservation

En V1, l'hote ou le client modifie les dates d'une reservation avec recalcul automatique du prix. En V2, toute modification entrainant un changement de prix devra etre validee par les deux parties (workflow asynchrone avec notifications, acceptation, refus, expiration de la proposition).

### 9.2 Paiement en ligne

Integration complete avec un prestataire de paiement (Stripe ou equivalent). Flux de paiement securise, gestion des webhooks, remboursements automatiques selon la politique d'annulation.

### 9.3 Notifications temps reel

Passage d'un modele de notifications pull (consultation par l'utilisateur) a un modele push (WebSocket ou Server-Sent Events) pour la messagerie et les alertes critiques.

---

## 10. Livrables de reference

Les documents techniques detailles sont fournis separement :

- Modele de donnees complet : fichier DBML pour dbdiagram.io (20 tables, contraintes, index)
- Cartographie API detaillee : tableau endpoint par endpoint avec methodes, roles, descriptions
- Strategie de tests detaillee : matrice test par test avec correspondance aux cas non-triviaux
