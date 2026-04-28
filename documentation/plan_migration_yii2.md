# Plan de migration — Topoguide PDF + Cartes vers Yii2

> **Document compagnon** : [`synthese_technique_topoguide.md`](./synthese_technique_topoguide.md) regroupe la stack, la BDD et les modalités de déploiement sur les 3 environnements (local / recette / prod).

## Périmètre

**Inclus :**
- Génération des fiches PDF itinéraires (`GET /topoguide/{lang}/{id}.pdf`)
- Pages de rendu carte Leaflet (`gmap/Simple`, `gmap/Gpx`, `gmap/Kml`)
- Batch de capture carte (CutyCapt → JPG dans le cache)
- **Module d'administration** : CRUD tables, gestion des captures, visualisation des erreurs

**Exclu :**
- Syndication TourInSoft (application externe qui alimente les tables BDD)
- Moteur de recherche public, APIs JSON, widget

**Rapport à la BDD :**
Les tables `fr`, `en`, `es`, `producteur`, `ville` sont alimentées par l'application de syndication externe **et** éditables via l'interface d'administration. Les modèles Yii2 supportent donc la lecture et l'écriture.

---

## Contexte technique

| Élément | Décision |
|---|---|
| Nom du projet | `topoguide` |
| Nouveau dépôt Git | `csT64/topoguide` (à créer) |
| Template Yii2 | **Basic** (pas de frontend public à servir, uniquement PDF + admin) |
| Version PHP cible | **8.2** (paquet natif Debian 12) — compatible TCPDF 6.7+ et Yii2 2.0.51+ |
| Chemin d'installation | `/srv/topoguide` sur tous les environnements |
| Base de données | `topoguide` (MariaDB 10.5+) sur tous les environnements |
| Cache captures carte | `/cache/capture-gmap` sur tous les environnements |
| Serveur web | Apache 2.4 (mod_rewrite, mod_php ou PHP-FPM) |
| Front | jQuery 3 + Bootstrap 3 conservés (Bower) — interface admin uniquement |
| Déploiement | Manuel (`git pull` + `composer install` + migrations Yii2) |
| Accès PDF public | Sans restriction (URLs publiques) |

URLs cibles par environnement :

| Env | Domaine | Exemple PDF |
|---|---|---|
| Local | `http://api.local` | `http://api.local/topoguide/fr/ITIAQU064FS0000V.pdf` |
| Recette | `https://api.adt64.fr` | `https://api.adt64.fr/topoguide/fr/ITIAQU064FS0000V.pdf` |
| Production | `https://api.tourisme64.com` | `https://api.tourisme64.com/topoguide/fr/ITIAQU064FS0000V.pdf` |

---

## Architecture cible

```
app/
├── commands/
│   └── ScreenshotController.php       ← batch : capture carte → JPG
│
├── components/
│   ├── pdf/
│   │   ├── MYPDF.php                  ← extension TCPDF (header/footer)
│   │   ├── TopoguideHelpers.php       ← clean(), cleanGps(), titre2(), titre3()
│   │   └── TopoguideService.php       ← logique de construction du PDF
│   └── map/
│       └── ScreenshotService.php      ← appel CutyCapt, gestion cache JPG
│
├── config/
│   ├── web.php                        ← config web + URL rules
│   ├── console.php                    ← config console
│   ├── params.php                     ← constantes (paths, urls)
│   └── db.php                         ← connexion BDD
│
├── controllers/
│   ├── TopoguideController.php        ← action pdf($lang, $id)
│   └── GmapController.php             ← actions simple(), gpx(), kml()
│
├── models/
│   ├── Itineraire.php                 ← tables fr/en/es
│   ├── ItineraireSearch.php           ← modèle de recherche/filtres
│   ├── Producteur.php
│   ├── ProducteurSearch.php
│   ├── Ville.php
│   └── VilleSearch.php
│
├── modules/
│   └── admin/
│       ├── Module.php
│       ├── controllers/
│       │   ├── DefaultController.php      ← tableau de bord
│       │   ├── ItineraireController.php   ← CRUD itinéraires (fr/en/es)
│       │   ├── ProducteurController.php   ← CRUD producteurs
│       │   ├── VilleController.php        ← CRUD villes
│       │   ├── CarteController.php        ← gestion captures JPG
│       │   └── LogController.php          ← visualisation erreurs
│       └── views/
│           ├── layouts/
│           │   └── admin.php              ← layout backoffice
│           ├── default/
│           │   └── index.php              ← tableau de bord
│           ├── itineraire/
│           │   ├── index.php              ← liste + filtres + statut carte
│           │   ├── view.php               ← détail + aperçu PDF + carte
│           │   ├── create.php
│           │   └── update.php
│           ├── producteur/
│           │   ├── index.php
│           │   ├── view.php
│           │   ├── create.php
│           │   └── update.php
│           ├── ville/
│           │   ├── index.php
│           │   ├── view.php
│           │   └── update.php
│           ├── carte/
│           │   └── index.php              ← liste captures + statut + actions
│           └── log/
│               └── index.php              ← erreurs PDF + captures
│
├── views/
│   └── gmap/
│       ├── simple.php
│       ├── gpx.php
│       └── kml.php
│
└── web/
    ├── index.php
    └── .htaccess
```

---

## 1. Configuration et sécurité

### Authentification

L'accès à l'interface admin est protégé par une authentification Yii2 native (session + formulaire de login), en remplacement de la whitelist IP actuelle.

Plusieurs utilisateurs sont stockés dans la table `users` de la base `topoguide` :

```sql
CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(64)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,   -- password_hash PASSWORD_DEFAULT
    email        VARCHAR(128) NOT NULL UNIQUE,
    auth_key     VARCHAR(32)  NOT NULL,
    access_token VARCHAR(64)  DEFAULT NULL,
    status       TINYINT      NOT NULL DEFAULT 10, -- 10 = actif, 0 = désactivé
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Modèle `User` implémentant `yii\web\IdentityInterface`, avec contrôle d'accès :

```php
// modules/admin/Module.php
public function behaviors(): array
{
    return [
        'access' => [
            'class' => AccessControl::class,
            'rules' => [['allow' => true, 'roles' => ['@']]], // authentifié
        ],
    ];
}
```

Les URLs publiques PDF (`/topoguide/{lang}/{id}.pdf`) et les pages carte (`/gmap/*`) restent **accessibles sans authentification**.

### URL rules

```php
'urlManager' => [
    'rules' => [
        // PDF public
        'topoguide/<lang:[a-z]{2}>/<id:ITIAQU[^.]+>.pdf' => 'topoguide/pdf',

        // Cartes Leaflet (appelées par CutyCapt)
        'gmap/simple' => 'gmap/simple',
        'gmap/gpx'    => 'gmap/gpx',
        'gmap/kml'    => 'gmap/kml',

        // Admin
        'admin'                => 'admin/default/index',
        'admin/<controller>'   => 'admin/<controller>/index',
        'admin/<controller>/<action>' => 'admin/<controller>/<action>',
        'admin/<controller>/<action>/<id:\d+>' => 'admin/<controller>/<action>',
    ],
],
```

---

## 2. Modèles

### `Itineraire` — tables fr / en / es

```php
class Itineraire extends \yii\db\ActiveRecord
{
    public static function tableName(): string
    {
        return Yii::$app->language; // 'fr', 'en' ou 'es'
    }

    public function rules(): array
    {
        return [
            [['id'], 'required'],
            [['id'], 'string', 'max' => 32],
            [['titre_2', 'raison_sociale', 'commune_depart', 'commune_arrivee'], 'string'],
            [['distance_km', 'denivele'], 'number'],
            [['latitude', 'longitude'], 'number'],
            [['descriptif', 'photo', 'etapes', 'difficulte', 'type', 'equipement',
              'point_d_interet', 'point_d_attention', 'locomotion', 'duree'], 'safe'],
            [['is_active'], 'boolean'],
            [['date_maj'], 'safe'],
        ];
    }

    // Relations
    public function getProducteur(): ActiveQuery
    {
        return $this->hasOne(Producteur::class, ['id' => 'auteur']);
    }

    public function getVille(): ActiveQuery
    {
        return $this->hasOne(Ville::class, ['ville_code' => 'code_insee']);
    }

    // Helpers lecture JSON
    public function getTitle(): string
    {
        return $this->titre_2 ?: $this->raison_sociale ?: '';
    }

    public function getProducteurId(): string
    {
        return substr($this->auteur ?? '', 0, 16) ?: 'ORGAQU064FS00001';
    }

    public function getDifficulteVal(): string { /* json_decode ... */ }
    public function getTypeVal(): string        { /* json_decode ... */ }
    public function getDureeVal(): string       { /* json_decode ... */ }
    public function getPhotos(): array          { return json_decode($this->photo, true) ?: []; }
    public function getEtapes(): array          { return json_decode($this->etapes, true) ?: []; }
    public function getPoi(): array             { return json_decode($this->point_d_interet, true) ?: []; }
    public function getEquipements(): array     { return json_decode($this->equipement, true) ?: []; }
    public function getAttentions(): array      { return json_decode($this->point_d_attention, true) ?: []; }

    // Statut de la capture carte
    public function hasCarteCache(): bool
    {
        return file_exists(Yii::$app->params['pathCacheGmap'] . '/' . $this->id . '.jpg');
    }

    public function getCarteCacheDate(): ?string
    {
        $path = Yii::$app->params['pathCacheGmap'] . '/' . $this->id . '.jpg';
        return file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null;
    }
}
```

### `ItineraireSearch` — filtres pour GridView

```php
class ItineraireSearch extends Itineraire
{
    public string $langue = 'fr';

    public function rules(): array
    {
        return [
            [['id', 'titre_2', 'raison_sociale', 'commune_depart', 'auteur'], 'safe'],
            [['is_active'], 'boolean'],
            [['langue'], 'in', 'range' => ['fr', 'en', 'es']],
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        Yii::$app->language = $this->langue;
        $query = Itineraire::find();

        $this->load($params);

        $query->andFilterWhere(['like', 'id', $this->id])
              ->andFilterWhere(['like', 'titre_2', $this->titre_2])
              ->andFilterWhere(['like', 'commune_depart', $this->commune_depart])
              ->andFilterWhere(['like', 'auteur', $this->auteur]);

        if ($this->is_active !== null && $this->is_active !== '') {
            $query->andWhere(['is_active' => $this->is_active]);
        }

        return new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 30],
            'sort'       => ['defaultOrder' => ['date_maj' => SORT_DESC]],
        ]);
    }
}
```

---

## 3. Module Admin

### Tableau de bord — `DefaultController`

Affiche un résumé de l'état du système :

```php
public function actionIndex(): string
{
    $stats = [];
    foreach (['fr', 'en', 'es'] as $lang) {
        Yii::$app->language = $lang;
        $stats[$lang] = Itineraire::find()->count();
    }

    Yii::$app->language = 'fr';
    $manquantes = 0;
    foreach (Itineraire::find()->select('id')->column() as $id) {
        if (!file_exists(Yii::$app->params['pathCacheGmap'] . "/$id.jpg")) {
            $manquantes++;
        }
    }

    $logLines = $this->getRecentLogLines(20);

    return $this->render('index', compact('stats', 'manquantes', 'logLines'));
}
```

**Contenu du tableau de bord :**
- Nombre d'itinéraires par langue (fr / en / es)
- Nombre de producteurs
- Nombre de captures manquantes
- 20 dernières lignes du fichier log d'erreurs
- Bouton "Générer les captures manquantes"

---

### `ItineraireController` — CRUD + actions PDF/carte

```php
class ItineraireController extends Controller
{
    // Liste avec filtres, langue sélectionnable, statut carte
    public function actionIndex(): string
    {
        $searchModel  = new ItineraireSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', compact('searchModel', 'dataProvider'));
    }

    // Détail : aperçu des données, lien PDF, statut carte
    public function actionView(string $id, string $lang = 'fr'): string
    {
        return $this->render('view', ['model' => $this->findModel($id, $lang)]);
    }

    public function actionCreate(): Response|string { /* … */ }
    public function actionUpdate(string $id, string $lang = 'fr'): Response|string { /* … */ }
    public function actionDelete(string $id, string $lang = 'fr'): Response { /* … */ }

    // Déclenche la capture carte pour un itinéraire
    public function actionGenererCarte(string $id): Response
    {
        Yii::$app->language = 'fr';
        $iti = $this->findModel($id, 'fr');
        $ok  = (new ScreenshotService())->captureOne($iti);
        Yii::$app->session->addFlash($ok ? 'success' : 'error',
            $ok ? "Carte $id générée." : "Échec de la capture pour $id."
        );
        return $this->redirect(['index']);
    }

    // Supprime la capture JPG
    public function actionSupprimerCarte(string $id): Response
    {
        $path = Yii::$app->params['pathCacheGmap'] . "/$id.jpg";
        if (file_exists($path)) {
            unlink($path);
            Yii::$app->session->addFlash('success', "Carte $id supprimée.");
        }
        return $this->redirect(['index']);
    }

    // Ouvre le PDF dans un nouvel onglet
    public function actionPreviewPdf(string $id, string $lang = 'fr'): void
    {
        $this->redirect(['/topoguide/pdf', 'lang' => $lang, 'id' => $id]);
    }
}
```

**Vue `itineraire/index.php` — colonnes du GridView :**

| Colonne | Contenu |
|---|---|
| ID | Identifiant TourInSoft, cliquable |
| Titre | `titre_2` ou `raison_sociale` |
| Commune départ | `commune_depart` |
| Langue | Sélecteur fr / en / es |
| Carte | Icône verte (JPG présent) / rouge (absent) + date |
| Actions | Voir · Modifier · Supprimer · PDF · Carte |

---

### `CarteController` — Gestion globale des captures

Vue dédiée à l'état de toutes les captures :

```php
public function actionIndex(): string
{
    Yii::$app->language = 'fr';
    $itineraires = Itineraire::find()->orderBy('id')->all();
    $cachePath   = Yii::$app->params['pathCacheGmap'];

    $statuts = array_map(function (Itineraire $iti) use ($cachePath) {
        $path = "$cachePath/{$iti->id}.jpg";
        return [
            'id'       => $iti->id,
            'titre'    => $iti->getTitle(),
            'present'  => file_exists($path),
            'date'     => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
            'taille'   => file_exists($path) ? round(filesize($path) / 1024) . ' Ko' : '—',
        ];
    }, $itineraires);

    return $this->render('index', ['statuts' => $statuts]);
}

// Régénère toutes les captures manquantes (lance le batch en arrière-plan)
public function actionGenererManquantes(): Response
{
    $cmd = 'php ' . Yii::getAlias('@app') . '/../yii screenshot/run > /dev/null 2>&1 &';
    exec($cmd);
    Yii::$app->session->addFlash('success', 'Batch de génération lancé en arrière-plan.');
    return $this->redirect(['index']);
}

public function actionSupprimer(string $id): Response
{
    $path = Yii::$app->params['pathCacheGmap'] . "/$id.jpg";
    if (file_exists($path)) unlink($path);
    return $this->redirect(['index']);
}

public function actionApercu(string $id): void
{
    $path = Yii::$app->params['pathCacheGmap'] . "/$id.jpg";
    if (!file_exists($path)) throw new NotFoundHttpException();
    Yii::$app->response->sendFile($path, "$id.jpg", ['inline' => true]);
}
```

**Vue `carte/index.php` :**
- Tableau avec colonne statut (présent/absent), date de génération, taille fichier
- Filtre : "toutes" / "présentes seulement" / "manquantes seulement"
- Bouton global "Générer les captures manquantes"
- Par ligne : aperçu, régénérer, supprimer

---

### `LogController` — Visualisation des erreurs

```php
public function actionIndex(): string
{
    $logFile = Yii::$app->params['logFile'];
    $lines   = [];

    if (file_exists($logFile)) {
        $raw = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // Lire les 500 dernières lignes, les plus récentes en premier
        $lines = array_reverse(array_slice($raw, -500));
    }

    return $this->render('index', ['lines' => $lines, 'logFile' => $logFile]);
}

public function actionVider(): Response
{
    $logFile = Yii::$app->params['logFile'];
    if (file_exists($logFile)) file_put_contents($logFile, '');
    Yii::$app->session->addFlash('success', 'Log vidé.');
    return $this->redirect(['index']);
}
```

**Vue `log/index.php` :**
- Affichage coloré par niveau : `[Exception]` → rouge, `[producteur]` → orange, `[8]` (notice) → jaune
- Filtre par mot-clé (JS côté client)
- Bouton "Vider le log"
- Nombre total de lignes + date de la dernière entrée

---

### `ProducteurController` et `VilleController`

CRUD standard généré par `gii` (outil de génération de code Yii2), avec :

- `Producteur` : upload du logo (champ `logo`), aperçu de l'image
- `Ville` : modification du `default_zoom` (entier 1–18), coordonnées GPS

---

## 4. Contrôleurs publics (inchangés)

### `TopoguideController`

```php
class TopoguideController extends Controller
{
    public $layout = false;

    public function actionPdf(string $lang, string $id): void
    {
        Yii::$app->language = $lang;
        $iti = Itineraire::findOne(['id' => $id]);
        if (!$iti) throw new NotFoundHttpException("Itinéraire $id introuvable");
        (new TopoguideService($iti, $lang))->generate();
    }
}
```

### `GmapController`

```php
class GmapController extends Controller
{
    public $layout = false;

    public function actionSimple(float $lat, float $lon, int $zoom = 12): string
    {
        return $this->render('simple', compact('lat', 'lon', 'zoom'));
    }

    public function actionGpx(string $file, array $marker = []): string
    {
        return $this->render('gpx', compact('file', 'marker'));
    }

    public function actionKml(string $file, array $marker = []): string
    {
        return $this->render('kml', compact('file', 'marker'));
    }
}
```

---

## 5. Commande console (batch)

```bash
php yii screenshot/run               # captures manquantes ou modifiées depuis 24h
php yii screenshot/one ITIAQU064FS0004K  # une seule carte
```

Le batch peut également être déclenché depuis l'interface admin (`CarteController::actionGenererManquantes()`), qui l'exécute en arrière-plan avec `exec(...&)`.

---

## 6. Plan de migration par phases

### Phase 1 — Socle Yii2 *(~2 jours)*
- Installation Yii2 Basic ou Advanced
- Configuration `params.php`, `db.php`, `web.php`
- Modèles `Itineraire`, `Producteur`, `Ville` avec `rules()`
- Modèles de recherche `ItineraireSearch`, `ProducteurSearch`, `VilleSearch`
- Test de connexion BDD et lecture d'un itinéraire

### Phase 2 — PDF topoguide *(~3 jours)*
- Déplacer `MYPDF.php` et `TopoguideHelpers.php` dans `components/pdf/`
- Créer `TopoguideService` à partir de `iti_iti_pdf.php`
- Créer `TopoguideController::actionPdf()`
- Configurer la règle URL et le `.htaccess`
- Tests fr / en / es

### Phase 3 — Cartes Leaflet *(~2 jours)*
- Migrer `gmap/Simple.php`, `Gpx.php`, `Kml.php` en views `views/gmap/`
- Créer `GmapController`
- Vérifier le rendu dans un navigateur

### Phase 4 — Batch screenshot *(~2 jours)*
- Créer `ScreenshotService`
- Créer `commands/ScreenshotController.php`
- Tests et vérification des JPG générés
- Gérer les deux formats `etapes` (JSON et ancien `#|`)

### Phase 5 — Module admin *(~1 semaine)*
- Authentification (login/logout)
- CRUD `Itineraire` (fr/en/es) avec `ItineraireSearch`
- CRUD `Producteur` + upload logo
- CRUD `Ville`
- `CarteController` : liste statuts, aperçu, régénérer, supprimer
- `LogController` : visualisation et vidage du log d'erreurs
- `DefaultController` : tableau de bord

### Phase 6 — Recette et bascule *(~2 jours)*
- Tests comparatifs PDF (ancienne vs nouvelle app)
- Tests de génération de carte
- Tests CRUD admin
- Bascule VHost Apache

---

## 7. Points d'attention

**Format `etapes`** : le script de capture utilise encore l'ancien format sérialisé (`#`/`|`), tandis que le template PDF utilise `json_decode()`. Le `ScreenshotService` gère les deux formats pendant la transition.

**TCPDF et PHP 8** : si la migration s'accompagne d'une montée vers PHP 8, mettre à jour TCPDF vers ≥ 6.4.

**Upload logo producteur** : prévoir la gestion du répertoire `html/producteur/` pour les logos uploadés via l'admin.

**Génération batch depuis l'admin** : le `exec(...&)` lance le batch en arrière-plan sans retour immédiat. Pour un meilleur retour utilisateur, envisager une file de tâches (Yii2 Queue) à terme.

**Dépendances système** : `xvfb-run` et `cutycapt` restent inchangés.

---

## 8. Dépendances

### Composer (PHP)

| Dépendance | Version cible | Usage | Action |
|---|---|---|---|
| `php` | `>=8.2` | Runtime | Debian 12 natif |
| `yiisoft/yii2` | `~2.0.51` | Framework | Socle de la nouvelle app |
| `yiisoft/yii2-bootstrap` | `~2.0.11` | Widgets front Bootstrap 3 | Admin |
| `yiisoft/yii2-gii` | `~2.2.6` | Générateur CRUD | Dev uniquement (`'env' => YII_ENV_DEV`) |
| `tecnickcom/tcpdf` | `~6.7.0` | Génération PDF | Reprise |
| `monolog/monolog` | `~3.5` | Logs (facultatif, Yii2 a son composant de log) | Optionnel |
| `savant/savant3` | — | Templates actuels | **Supprimer** |
| `propel/propel` | — | ORM actuel | **Supprimer** → ActiveRecord |

### Bower (front admin)

| Dépendance | Version | Usage |
|---|---|---|
| jQuery | `3.x` | Yii2 GridView, Pjax, formulaires |
| Bootstrap | `3.3.x` | Layout admin |
| bootstrap-datepicker | `1.6.x` | Champs date |
| bootstrap-tokenfield | `0.12.x` | Multi-select |
| ekko-lightbox | `4.x` | Aperçu photos itinéraire |

### Système

| Dépendance | Usage |
|---|---|
| Apache 2.4 (+ mod_rewrite, mod_ssl) | Serveur web |
| MariaDB 10.5+ | Base de données |
| `xvfb-run` + `cutycapt` | Capture carte → JPG (inchangé) |
| Leaflet 1.3.1 (vendored dans `/web/gmap/`) | Rendu carte HTML |
