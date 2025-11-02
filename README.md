# Suivi Carburant Mali — API

Documentation de l'API fournie par le projet `suivi-carburant`.

## Vue d'ensemble

Application Laravel pour le suivi des stations-service, leurs types de carburant et le statut (disponible / rupture / attente). Deux types d'utilisateurs principaux :
- Admins (gestion, approbation, statistiques)
- Stations (mise à jour du statut carburant)

L'API utilise Laravel Sanctum pour l'authentification par token.

## Authentification

Les routes d'authentification exposées :


Payload (login) — JSON (exemple)

```
{
	"email": "admin@example.com",
	"password": "votre-mot-de-passe"
}
```

Réponse (succès) :

```
{
	"message": "Connexion réussie",
	"token": "<token_sanctum>",
	"admin|station": { /* utilisateur */ }
}
```
Importez ce JSON dans Postman. Après `Admin - Login` et `Station - Login`, copiez le champ `token` de la réponse et collez-le dans les variables d'environnement `adminToken` et `stationToken` respectivement.

## Payloads de test et réponses attendues

Ci-dessous des exemples prêts à coller dans Postman (Body → raw → JSON) et les réponses JSON attendues. Utilisez le header `Authorization: Bearer <token>` pour les routes protégées.

1) POST /admin/login
Request:
```json
{
	"email": "admin@example.com",
	"password": "secret"
}
```
Response 200:
```json
{
	"message": "Connexion réussie",
	"token": "eyJ...<token_sanctum>",
	"admin": { "id": 1, "name": "Admin Test", "email": "admin@example.com" }
}
```

2) POST /public/stations/register
Request:
```json
{
	"name": "Station A",
	"address": "Rue X, N°12",
	"quartier": "Centre",
	"commune": "Bamako",
	"gerant_name": "M. Kone",
	"phone": "+22312345678",
	"email": "station@example.com",
	"password": "secretpass",
	"fuel_types": [1, 2],
	"latitude": -12.3456,
	"longitude": 7.8910
}
```
Response 201:
```json
{
	"message": "Demande envoyée avec succès. En attente de validation.",
	"data": {
		"id": 12,
		"name": "Station A",
		"email": "station@example.com",
		"status": "pending",
		"fuelTypes": [{ "id": 1, "name": "Essence" }, { "id": 2, "name": "Gazoil" }]
	}
}
```

3) POST /stations/login
Request:
```json
{
	"email": "station@example.com",
	"password": "secretpass"
}
```
Response 200 (si approved):
```json
{
	"message": "Connexion réussie",
	"token": "eyJ...<token_sanctum>",
	"station": { "id": 12, "name": "Station A", "email": "station@example.com", "status": "approved" }
}
```

4) POST /stations/status-change (auth station)
Request:
```json
{
	"fuel_type_id": 1,
	"status": "disponible"
}
```
Response 200:
```json
{
	"message": "Statut du carburant mis à jour avec succès.",
	"data": { "id": 45, "station_id": 12, "fuel_type_id": 1, "status": "disponible", "created_at": "2025-11-02T11:00:00Z" }
}
```

5) POST /public/stations/{stationId}/report
Request:
```json
{
	"type": "incident",
	"message": "Rupture signalée depuis 2h"
}
```
Response 201:
```json
{
	"message": "Signalement envoyé avec succès. Merci pour votre contribution 🙏",
	"data": { "id": 21, "station_id": 12, "type": "incident", "message": "Rupture signalée depuis 2h", "created_at": "2025-11-02T11:15:00Z" }
}
```

6) GET /admin/stations (auth admin)
Response 200 (extrait) :
```json
[
	{
		"id": 12,
		"name": "Station A",
		"commune": "Bamako",
		"latitude": -12.3456,
		"longitude": 7.8910,
		"is_active": true,
		"updated_at": "2025-11-02T11:00:00Z",
		"fuel_statuses": [ { "fuel_type": "Essence", "status": "disponible", "updated_at": "2025-11-02T11:00:00Z" } ]
	}
]
```

7) POST /admin/stations/{id}/approve (auth admin)
Request: none
Response 200:
```json
{ "message": "Station approuvée avec succès." }
```

8) POST /admin/stations/{id}/reject (auth admin)
Request:
```json
{ "reason": "Informations manquantes" }
```
Response 200:
```json
{ "message": "Station refusée avec succès." }
```

9) POST /admin/stations/{id}/disable (auth admin)
Request: none
Response 200:
```json
{ "message": "Station désactivée avec succès." }
```

10) POST /admin/stations/{id}/reactivate (auth admin)
Request: none
Response 200:
```json
{ "message": "Station réactivée avec succès." }
```

11) GET /admin/stations/reports (auth admin)
Response 200 (paginé) :
```json
{
	"success": true,
	"message": "Liste des signalements récupérée avec succès",
	"data": {
		"current_page": 1,
		"data": [ { "id": 21, "station_id": 12, "type": "incident", "message": "Rupture...", "station": { "id": 12, "name": "Station A" } } ],
		"last_page": 1,
		"per_page": 10,
		"total": 1
	}
}
```

12) POST /admin/stations/notifications/{id}/read (auth admin)
Request: none
Response 200:
```json
{ "success": true, "message": "Notification marquée comme lue" }
```

13) GET /admin/stations/export (auth admin)
Request: query `?commune=Bamako&status=approved`
Response: fichier téléchargeable (XLSX) ou 500 JSON en cas d'erreur.

Conseils pour les tests automatisés Postman : vérifier le code HTTP et la présence des champs `data.id`, `message`, et les changements d'état en base si possible.

Utiliser l'en-tête Authorization avec le token renvoyé : `Authorization: Bearer <token>` pour toutes les routes protégées.

## Endpoints publics (sans auth)

- GET /public/stations
	- Liste des stations approuvées.
	- Query params possibles : `sort`, `order`, `search`, `fuel`, `status` (voir filtres dans `StationController@index`).

- GET /public/stations/{id}
	- Détail d'une station (inclus `fuel_statuses`, `visits_count`, coordonnées)

- POST /public/stations/register
	- Enregistrement d'une station (création d'une demande). Utilise `StoreStationRequest`.
	- Payload (exemple) :

```
{
	"name": "Station A",
	"address": "Rue X, N°12",
	"quartier": "Quartier",
	"commune": "Commune",
	"gerant_name": "Nom Gérant",
	"phone": "+22312345678",
	"email": "station@example.com",
	"password": "secretpass",
	"fuel_types": [1,2],
	"latitude": -12.34,
	"longitude": 8.90
}
```

- POST /public/stations/{stationId}/report (implémentation via `ReportControllerUsager@store`)
	- Envoi d'un signalement pour une station.
	- Payload :
```
{
	"type": "incident|erreur|autre",
	"message": "Texte du signalement (<=1000)"
}
```

Réponse (succès) : 201 créé + objet `report`.

Si une même station atteint exactement 5 signalements, une `AdminNotification` est créée automatiquement.

## Endpoints station (auth requis, guard station)

- POST /stations/status-change
	- Met à jour (createOrUpdate) le statut d'un type de carburant pour la station authentifiée.
	- Payload :
```
{
	"fuel_type_id": 2,
	"status": "disponible|peu|rupture"
}
```

Réponse : objet `StationStatus` mis à jour.

## Endpoints admin (auth requis)

Toutes ces routes sont protégées par `auth:sanctum` et accessibles aux admins seulement.

- GET /admin/stations
	- Liste des stations (filtres avancés). Query params supportés : `commune`, `status` (string ou tableau), `search`, `quartier`, `visits_min`, `visits_max`, `fuel`, `status_filter`, `updated_from`, `updated_to`, `sort_by`, `sort_order`.
	- Exemple de réponse : tableau d'objets contenant `id`, `name`, `commune`, `latitude`, `longitude`, `is_active`, `updated_at`, `fuel_statuses` (par carburant).

- GET /admin/stations/{id}/history
	- Historique des statuts d'une station.

- POST /admin/stations/{id}/approve
	- Approuve une demande en attente.
	- Effets : `status` => `approved`, mot de passe généré et hashé (`stationXXXX`), email envoyé au gérant.

- POST /admin/stations/{id}/reject
	- Refuse une demande.
	- Payload : `{ "reason": "string" }` (requis)
	- Effets : `status` => `rejected`, `rejection_reason` mis à jour, email envoyé au gérant.

- POST /admin/stations/{id}/disable
	- Désactive une station approuvée.
	- Effets : `status` => `rejected`, `rejection_reason` = 'Station désactivée par administrateur', `password` => null, email envoyé.

- POST /admin/stations/{id}/reactivate
	- Réactive une station désactivée (`status === 'rejected'` attendu).
	- Effets : `status` => `approved`, `rejection_reason` => null, mot de passe ré-initialisé (hashé) et envoyé par email.

- GET /admin/stations/reports
	- Liste des reports (search sur message ou nom de station possible). Pagination par 10.

- GET /admin/stations/reports/{id}
	- Détail d'un report.

- DELETE /admin/stations/reports/{id}
	- Supprime un report.

- GET /admin/stations/notifications
	- Liste des `AdminNotification`.

- POST /admin/stations/notifications/{id}/read
	- Marque une notification admin comme lue (met `is_read` à true).

- GET /admin/stations/export
	- Lance l'export Excel multi-feuilles (`DashboardExport`). Accepte query params `commune`, `status`.

- GET /admin/stations/stats
	- Rend des métriques : `total`, `approved`, `rejected`, `pending`, `last_update`.

## Modèles principaux (champs importants)

- Station
	- id, name, address, quartier, commune, gerant_name, phone, email (unique), status (pending|approved|rejected), rejection_reason, password (hashé), is_active (bool), latitude, longitude, created_at, updated_at

- StationStatus
	- id, station_id, fuel_type_id, status (disponible|rupture|attente), created_at, updated_at

- FuelType
	- id, name

- Report
	- id, station_id, type (incident|erreur|autre), message, created_at

- StationVisit
	- id, station_id, ip_address, device, commune, quartier, visited_at

- StationNotification
	- id, station_id, title, message, read (boolean)

- AdminNotification
	- id, title, message, is_read (boolean)

## Exemples rapides (curl)

Récupérer la liste publique des stations :

```
curl -s "http://localhost/api/public/stations"
```

Connexion admin (exemple) :

```
curl -X POST -H "Content-Type: application/json" \
	-d '{"email":"admin@example.com","password":"secret"}' \
	http://localhost/api/admin/login
```

Appel protégé (exemple) :

```
curl -H "Authorization: Bearer <TOKEN>" http://localhost/api/admin/stations
```

## Notes importantes

- Le compte admin est attendu créé via un seeder (vérifiez `database/seeders` ou le seeder fourni) — assurez-vous que le mot de passe du seeder est hashé.
- Les mots de passe des stations sont générés côté admin (approve/reactivate) et envoyés par email en clair (attention sécurité). Les mots de passe sont stockés hashés dans la DB.
- Incohérence de champ notifications : `AdminNotification` utilise `is_read` alors que `StationNotification` utilise `read`. Si vous unifiez la logique front/backend, choisissez un nom commun.
- Caching : plusieurs endpoints utilisent `Cache::remember` et le trait `StationHelper` fournit `bustStationCaches($stationId)` pour invalider les caches après modification.
- Guard station : `StationRequestController@updateFuelStatus` utilise `auth('station')->user()` — vérifiez `config/auth.php` pour la configuration du guard/provider.

## Prochaines étapes suggérées

- Générer une collection Postman / OpenAPI pour faciliter l'intégration front.
- Ajouter des notifications en base (création de `StationNotification`) lors des actions admin pour que la station puisse voir l'historique dans l'interface.
- Standardiser le champ `read`/`is_read` et documenter le format exact des dates renvoyées (ISO 8601).

Si vous voulez, je peux :
- produire la collection Postman automatiquement ; ou
- normaliser et appliquer directement une correction pour `is_read` vs `read` et ajouter la création de `StationNotification` dans les actions `approve/reject/disable/reactivate`.

---

Documentation générée automatiquement par analyse du code — date: 2025-11-02

