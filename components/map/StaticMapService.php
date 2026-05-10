<?php

namespace app\components\map;

use Yii;
use app\models\Itineraire;

class StaticMapService
{
    private const TILE_SIZE = 256;
    private const TILE_URL  = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    private const FONT      = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

    private int    $width    = 1240;
    private int    $height   = 877;
    private int    $zoom;
    private float  $originX;  // fractional tile X of canvas top-left corner
    private float  $originY;  // fractional tile Y of canvas top-left corner
    private string $tileCache;
    private string $outputPath;
    private string $logFile;

    /** @var \GdImage */
    private $img;

    public function __construct()
    {
        $this->tileCache  = Yii::getAlias('@runtime/cache-tiles');
        $this->outputPath = Yii::getAlias(Yii::$app->params['pathCacheGmap']);
        $this->logFile    = Yii::getAlias(Yii::$app->params['logFile']);

        foreach ([$this->tileCache, $this->outputPath] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
    }

    public function generate(Itineraire $iti): bool
    {
        $output = $this->outputPath . '/' . $iti->id . '.jpg';

        try {
            $trackPoints = $this->loadTrack($iti);
            $markers     = $this->buildMarkers($iti);
            $allPoints   = array_merge(
                $trackPoints,
                array_map(fn($m) => [$m['lat'], $m['lon']], $markers)
            );

            if (empty($allPoints)) {
                $this->log("SKIP {$iti->id}: aucune coordonnée");
                return false;
            }

            [$minLat, $maxLat, $minLon, $maxLon] = $this->boundingBox($allPoints);

            // Marge autour du tracé
            $latPad = max(($maxLat - $minLat) * 0.15, 0.002);
            $lonPad = max(($maxLon - $minLon) * 0.15, 0.003);
            $minLat -= $latPad; $maxLat += $latPad;
            $minLon -= $lonPad; $maxLon += $lonPad;

            $this->zoom    = $this->chooseBestZoom($minLat, $maxLat, $minLon, $maxLon);
            [$cx, $cy]     = $this->toFractionalTile(($minLat + $maxLat) / 2, ($minLon + $maxLon) / 2);
            $this->originX = $cx - $this->width  / 2 / self::TILE_SIZE;
            $this->originY = $cy - $this->height / 2 / self::TILE_SIZE;

            $this->log("DEBUG {$iti->id}: zoom={$this->zoom} bbox=[$minLat,$maxLat,$minLon,$maxLon] track=" . count($trackPoints) . "pts markers=" . count($markers));

            // Vérifier que les points du tracé tombent bien dans le canvas
            if (count($trackPoints) >= 2) {
                [$px0, $py0] = $this->toPixel($trackPoints[0][0], $trackPoints[0][1]);
                $mid = $trackPoints[(int)(count($trackPoints)/2)];
                [$pxm, $pym] = $this->toPixel($mid[0], $mid[1]);
                $this->log("DEBUG pixels: premier=[$px0,$py0] milieu=[$pxm,$pym] canvas={$this->width}x{$this->height}");
            }

            $this->img = imagecreatetruecolor($this->width, $this->height);

            $this->composeTiles();

            if (count($trackPoints) >= 2) {
                $this->drawTrack($trackPoints);
            }

            foreach ($markers as $m) {
                $this->drawPin($m['lat'], $m['lon'], $m['label'], $m['depart']);
            }

            imagejpeg($this->img, $output, 85);
            imagedestroy($this->img);

            return file_exists($output);

        } catch (\Throwable $e) {
            $this->log("FAIL {$iti->id}: " . $e->getMessage());
            return false;
        }
    }

    // ── Chargement du tracé ────────────────────────────────────────────────

    private function loadTrack(Itineraire $iti): array
    {
        if ($iti->doc_gpx) return $this->parseGpx($iti->doc_gpx);
        if ($iti->doc_kml) return $this->parseKml($iti->doc_kml);
        return [];
    }

    private function parseGpx(string $url): array
    {
        $xml = $this->fetchXml($url);
        if (!$xml) return [];

        // Le GPX déclare un namespace par défaut — on l'enregistre pour XPath
        $ns = $xml->getNamespaces(true);
        $gpxNs = $ns[''] ?? 'http://www.topografix.com/GPX/1/1';
        $xml->registerXPathNamespace('g', $gpxNs);

        $points = [];
        foreach ($xml->xpath('//g:trkpt') as $pt) {
            $points[] = [(float)$pt['lat'], (float)$pt['lon']];
        }
        if (empty($points)) {
            foreach ($xml->xpath('//g:rtept') as $pt) {
                $points[] = [(float)$pt['lat'], (float)$pt['lon']];
            }
        }
        return $points;
    }

    private function parseKml(string $url): array
    {
        $xml = $this->fetchXml($url);
        if (!$xml) return [];

        $xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
        $coords = $xml->xpath('//kml:coordinates') ?: $xml->xpath('//coordinates') ?: [];

        $points = [];
        foreach ($coords as $block) {
            foreach (preg_split('/\s+/', trim((string)$block)) as $triple) {
                $parts = explode(',', $triple);
                if (count($parts) >= 2 && ((float)$parts[0] || (float)$parts[1])) {
                    $points[] = [(float)$parts[1], (float)$parts[0]]; // KML : lon,lat
                }
            }
        }
        return $points;
    }

    private function fetchXml(string $url): ?\SimpleXMLElement
    {
        $ctx  = stream_context_create(['http' => [
            'timeout' => 15,
            'header'  => "User-Agent: topoguide-map/1.0\r\n",
        ]]);
        $data = @file_get_contents($url, false, $ctx);
        if (!$data) return null;
        libxml_use_internal_errors(true);
        return simplexml_load_string($data) ?: null;
    }

    // ── Marqueurs ──────────────────────────────────────────────────────────

    private function buildMarkers(Itineraire $iti): array
    {
        $markers = [];
        if ($iti->latitude && $iti->longitude) {
            $markers[] = [
                'lat'    => (float)$iti->latitude,
                'lon'    => (float)$iti->longitude,
                'label'  => 'D',
                'depart' => true,
            ];
        }
        foreach ($iti->getEtapes() as $i => $etape) {
            $lat = $this->parseCoord($etape['latitudedecimalegooglemap']  ?? $etape['lat'] ?? null);
            $lon = $this->parseCoord($etape['longitudedecimalegooglemap'] ?? $etape['lon'] ?? null);
            if ($lat !== null && $lon !== null) {
                $markers[] = [
                    'lat'    => $lat,
                    'lon'    => $lon,
                    'label'  => (string)($i + 1),
                    'depart' => false,
                ];
            }
        }
        return $markers;
    }

    private function parseCoord(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        $clean = (float)preg_replace('/[^\d.\-]/', '', (string)$value);
        return $clean != 0.0 ? $clean : null;
    }

    // ── Géométrie ─────────────────────────────────────────────────────────

    private function boundingBox(array $points): array
    {
        $lats = array_column($points, 0);
        $lons = array_column($points, 1);
        return [min($lats), max($lats), min($lons), max($lons)];
    }

    private function chooseBestZoom(float $minLat, float $maxLat, float $minLon, float $maxLon): int
    {
        for ($z = 16; $z >= 4; $z--) {
            [$x1, $y1] = $this->toFractionalTile($maxLat, $minLon, $z);
            [$x2, $y2] = $this->toFractionalTile($minLat, $maxLon, $z);
            if (($x2 - $x1) * self::TILE_SIZE <= $this->width  * 0.80 &&
                ($y2 - $y1) * self::TILE_SIZE <= $this->height * 0.80) {
                return $z;
            }
        }
        return 4;
    }

    private function toFractionalTile(float $lat, float $lon, ?int $zoom = null): array
    {
        $z      = $zoom ?? $this->zoom;
        $n      = 2 ** $z;
        $x      = ($lon + 180) / 360 * $n;
        $latRad = deg2rad($lat);
        $y      = (1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * $n;
        return [$x, $y];
    }

    private function toPixel(float $lat, float $lon): array
    {
        [$tx, $ty] = $this->toFractionalTile($lat, $lon);
        return [
            (int)round(($tx - $this->originX) * self::TILE_SIZE),
            (int)round(($ty - $this->originY) * self::TILE_SIZE),
        ];
    }

    // ── Assemblage des tuiles ─────────────────────────────────────────────

    private function composeTiles(): void
    {
        $txStart = (int)floor($this->originX);
        $tyStart = (int)floor($this->originY);
        $txEnd   = (int)ceil($this->originX + $this->width  / self::TILE_SIZE);
        $tyEnd   = (int)ceil($this->originY + $this->height / self::TILE_SIZE);
        $maxIdx  = 2 ** $this->zoom - 1;

        for ($tx = $txStart; $tx <= $txEnd; $tx++) {
            for ($ty = $tyStart; $ty <= $tyEnd; $ty++) {
                if ($tx < 0 || $ty < 0 || $tx > $maxIdx || $ty > $maxIdx) continue;

                $tile = $this->fetchTile($this->zoom, $tx, $ty);
                if (!$tile) continue;

                $destX = (int)round(($tx - $this->originX) * self::TILE_SIZE);
                $destY = (int)round(($ty - $this->originY) * self::TILE_SIZE);
                imagecopy($this->img, $tile, $destX, $destY, 0, 0, self::TILE_SIZE, self::TILE_SIZE);
                imagedestroy($tile);
            }
        }
    }

    private function fetchTile(int $z, int $x, int $y): ?\GdImage
    {
        $cacheFile = "{$this->tileCache}/$z/$x/$y.png";

        if (!file_exists($cacheFile)) {
            $dir = dirname($cacheFile);
            if (!is_dir($dir)) mkdir($dir, 0775, true);

            $url  = str_replace(['{z}', '{x}', '{y}'], [$z, $x, $y], self::TILE_URL);
            $ctx  = stream_context_create(['http' => [
                'timeout' => 10,
                'header'  => "User-Agent: topoguide-map/1.0\r\nReferer: http://topoguide.local/\r\n",
            ]]);
            $data = @file_get_contents($url, false, $ctx);
            if ($data) file_put_contents($cacheFile, $data);
        }

        return file_exists($cacheFile) ? (@imagecreatefrompng($cacheFile) ?: null) : null;
    }

    // ── Dessin du tracé ───────────────────────────────────────────────────

    private function drawTrack(array $points): void
    {
        // Contour blanc pour faire ressortir le trait sur fond clair
        $white = imagecolorallocate($this->img, 255, 255, 255);
        imagesetthickness($this->img, 6);
        $this->drawPolyline($points, $white);

        // Trait bleu foncé principal
        $blue = imagecolorallocate($this->img, 15, 50, 140);
        imagesetthickness($this->img, 3);
        $this->drawPolyline($points, $blue);

        imagesetthickness($this->img, 1);
    }

    private function drawPolyline(array $points, int $color, int $dx = 0, int $dy = 0): void
    {
        $prev = null;
        foreach ($points as $pt) {
            [$px, $py] = $this->toPixel($pt[0], $pt[1]);
            if ($prev !== null) {
                imageline($this->img, $prev[0] + $dx, $prev[1] + $dy, $px + $dx, $py + $dy, $color);
            }
            $prev = [$px, $py];
        }
    }

    // ── Dessin des marqueurs façon Google Maps ─────────────────────────────

    private function drawPin(float $lat, float $lon, string $label, bool $depart): void
    {
        [$tipX, $tipY] = $this->toPixel($lat, $lon);

        $r     = 15;  // rayon du cercle
        $tail  = 18;  // hauteur de la queue sous le cercle
        $cirCy = $tipY - $tail - $r;

        $fill   = $depart
            ? imagecolorallocate($this->img, 45, 136, 45)   // vert départ
            : imagecolorallocate($this->img, 210, 35, 35);  // rouge étape
        $white  = imagecolorallocate($this->img, 255, 255, 255);
        $shadow = imagecolorallocatealpha($this->img, 0, 0, 0, 90);

        // Ombre
        imagefilledellipse($this->img, $tipX + 2, $cirCy + 2, $r * 2 + 4, $r * 2 + 4, $shadow);

        // Queue triangulaire
        imagefilledpolygon($this->img, [
            $tipX - $r + 5, $cirCy + $r - 3,
            $tipX + $r - 5, $cirCy + $r - 3,
            $tipX, $tipY,
        ], $fill);

        // Cercle principal
        imagefilledellipse($this->img, $tipX, $cirCy, $r * 2, $r * 2, $fill);

        // Liseré blanc
        imageellipse($this->img, $tipX, $cirCy, $r * 2,     $r * 2,     $white);
        imageellipse($this->img, $tipX, $cirCy, $r * 2 - 2, $r * 2 - 2, $white);

        // Texte centré
        $this->drawPinLabel($tipX, $cirCy, $label, $white);
    }

    private function drawPinLabel(int $cx, int $cy, string $label, int $color): void
    {
        $size = strlen($label) > 1 ? 9.0 : 11.0;

        if (file_exists(self::FONT)) {
            $bbox = imagettfbbox($size, 0, self::FONT, $label);
            $tw   = abs($bbox[4] - $bbox[0]);
            $th   = abs($bbox[5] - $bbox[1]);
            imagettftext(
                $this->img, $size, 0,
                $cx - (int)($tw / 2),
                $cy + (int)($th / 2),
                $color, self::FONT, $label
            );
        } else {
            $font = 4;
            imagestring($this->img, $font,
                $cx - (int)(imagefontwidth($font) * strlen($label) / 2),
                $cy - (int)(imagefontheight($font) / 2),
                $label, $color
            );
        }
    }

    // ── Log ───────────────────────────────────────────────────────────────

    private function log(string $msg): void
    {
        @file_put_contents($this->logFile, date('Y-m-d H:i:s') . " [staticmap] $msg\n", FILE_APPEND);
    }
}
