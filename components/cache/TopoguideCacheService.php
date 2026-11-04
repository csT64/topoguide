<?php

namespace app\components\cache;

use Yii;

class TopoguideCacheService
{
    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = Yii::getAlias('@runtime/cache-topoguide');
    }

    public function getDir(string $lang): string
    {
        return $this->baseDir . '/' . $lang;
    }

    public function getFilePath(string $lang, string $syndicId, string $ext): string
    {
        return $this->getDir($lang) . '/' . $syndicId . '.' . $ext;
    }

    private function getMetaPath(string $lang, string $syndicId): string
    {
        return $this->getDir($lang) . '/' . $syndicId . '.meta';
    }

    public function ensureDir(string $lang): void
    {
        $dir = $this->getDir($lang);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    public function readMeta(string $lang, string $syndicId): ?array
    {
        $path = $this->getMetaPath($lang, $syndicId);
        if (!file_exists($path)) {
            return null;
        }
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    public function writeMeta(string $lang, string $syndicId, string $updatedAt): void
    {
        $this->ensureDir($lang);
        file_put_contents($this->getMetaPath($lang, $syndicId), json_encode([
            'generated_at' => date('Y-m-d H:i:s'),
            'updated_at'   => $updatedAt,
        ]));
    }

    // Returns true when the cache file must be regenerated.
    // updated_at is NOT updated on deactivation — so this check is fully independent
    // of the is_active/deleted_at check done by the caller.
    public function isCacheStale(array $meta, ?string $updatedAt): bool
    {
        if ($updatedAt === null) {
            return false;
        }
        return $updatedAt > ($meta['generated_at'] ?? '');
    }

    public function hasFile(string $lang, string $syndicId, string $ext): bool
    {
        return file_exists($this->getFilePath($lang, $syndicId, $ext));
    }

    public function writeFile(string $lang, string $syndicId, string $ext, string $content): void
    {
        $this->ensureDir($lang);
        file_put_contents($this->getFilePath($lang, $syndicId, $ext), $content);
    }

    public function readFile(string $lang, string $syndicId, string $ext): ?string
    {
        $path = $this->getFilePath($lang, $syndicId, $ext);
        return file_exists($path) ? file_get_contents($path) : null;
    }

    public function purge(string $lang, string $syndicId): void
    {
        foreach (['pdf', 'html'] as $ext) {
            $path = $this->getFilePath($lang, $syndicId, $ext);
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $meta = $this->getMetaPath($lang, $syndicId);
        if (file_exists($meta)) {
            @unlink($meta);
        }
    }

    public function purgeAllLangs(string $syndicId): void
    {
        foreach (['fr', 'en', 'es'] as $lang) {
            $this->purge($lang, $syndicId);
        }
    }

    public function countCachedFiles(): array
    {
        $stats = [];
        foreach (['fr', 'en', 'es'] as $lang) {
            $dir = $this->getDir($lang);
            $stats[$lang] = [
                'pdf'  => is_dir($dir) ? count(glob($dir . '/*.pdf') ?: []) : 0,
                'html' => is_dir($dir) ? count(glob($dir . '/*.html') ?: []) : 0,
            ];
        }
        return $stats;
    }

    public function listStaleIds(): array
    {
        $stale = [];
        foreach (['fr', 'en', 'es'] as $lang) {
            $dir = $this->getDir($lang);
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*.meta') ?: [] as $metaFile) {
                $syndicId = basename($metaFile, '.meta');
                $meta     = json_decode(file_get_contents($metaFile), true);
                $stale[]  = ['lang' => $lang, 'syndic_object_id' => $syndicId, 'generated_at' => $meta['generated_at'] ?? null];
            }
        }
        return $stale;
    }
}
