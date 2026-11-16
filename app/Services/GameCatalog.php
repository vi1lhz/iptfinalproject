<?php

namespace App\Services;

use Core\Application;
use Core\Database;
use Core\Env;
use Core\Logger;

/**
 * GameCatalog – external API integration with the FreeToGame API.
 *
 *   GET https://www.freetogame.com/api/games?sort-by=popularity   → JSON array of games
 *   GET https://www.freetogame.com/api/game?id=452                 → one game's full details
 *
 * No API key / account is required. The provider asks for no more than 10 requests
 * per second and attribution with a link to FreeToGame.com (shown in the footer).
 *
 * Results are cached in the `games` table:
 *  - Game list: refreshed at most once every GAMES_CACHE_HOURS (default 24 h).
 *  - Game details (description, screenshots, system requirements): fetched the first
 *    time someone opens the game page, then refreshed weekly.
 *  - If the API is down, the cached rows are used. If nothing is cached yet, a bundled
 *    offline snapshot (database/seeds/games.json) is imported so the shop still works.
 *  - After a failure the API is not retried for 10 minutes, so pages stay fast.
 */
class GameCatalog
{
    private static ?GameCatalog $instance = null;

    /** @var callable(string): array{0:int,1:string}  HTTP GET → [status, body]; injectable for tests */
    private $httpGet;

    private string $lastSource = 'none';
    private ?string $lastError = null;

    public function __construct(
        private Database $db,
        ?callable $httpGet = null,
        private ?string $apiBase = null,
        private ?int $cacheHours = null,
        private ?string $failureFile = null,
        private ?string $snapshotFile = null
    ) {
        $this->httpGet = $httpGet ?? [$this, 'curlGet'];
        $this->apiBase = rtrim($this->apiBase ?? (string) Env::get('FREETOGAME_API_URL', 'https://www.freetogame.com/api'), '/');
        $this->cacheHours ??= (int) Env::get('GAMES_CACHE_HOURS', 24);

        $root = isset(Application::$root) ? Application::$root : dirname(__DIR__, 2);
        $this->failureFile ??= $root . '/storage/cache/freetogame_failed';
        $this->snapshotFile ??= $root . '/database/seeds/games.json';
    }

    public static function instance(): self
    {
        return self::$instance ??= new self(Database::getInstance());
    }

    public static function setInstance(?GameCatalog $catalog): void
    {
        self::$instance = $catalog;
    }

    // ================================================================ sync

    /**
     * Makes sure the games table is filled and reasonably fresh.
     * @return array{source: string, count: int, error: ?string}
     *   source = cache | api | stale | snapshot | backoff
     */
    public function sync(bool $force = false): array
    {
        $count = (int) $this->db->fetchValue('SELECT COUNT(*) FROM games');
        $lastSync = $this->lastSynced();
        $fresh = $lastSync && (time() - strtotime($lastSync)) < $this->cacheHours * 3600;

        if ($count > 0 && $fresh && !$force) {
            return $this->result('cache', $count);
        }

        if ($force) {
            $this->clearBackoff();
        }

        if ($this->inBackoff()) {
            $source = $count > 0 ? 'stale' : 'snapshot';
            if ($count === 0) {
                $count = $this->importSnapshot();
            }
            return $this->result($source, $count, 'API recently failed; retrying later.');
        }

        try {
            $games = $this->request('/games?sort-by=popularity');
            if (!array_is_list($games) || !$games || !isset($games[0]['id'], $games[0]['title'])) {
                throw new \RuntimeException('Unexpected response format from FreeToGame');
            }
            $saved = $this->saveGames($games);
            $this->clearBackoff();
            Logger::info('FreeToGame catalog synced', ['games' => $saved]);
            return $this->result('api', (int) $this->db->fetchValue('SELECT COUNT(*) FROM games'));
        } catch (\Throwable $e) {
            $this->startBackoff();
            Logger::error('FreeToGame sync failed: ' . $e->getMessage());

            if ($count === 0) {
                return $this->result('snapshot', $this->importSnapshot(), $e->getMessage());
            }
            return $this->result('stale', $count, $e->getMessage());
        }
    }

    /**
     * Full details for one game (description, screenshots, minimum requirements).
     * Fetched lazily from /game?id= and cached for 7 days.
     */
    public function details(int $id): ?array
    {
        $game = $this->db->fetch('SELECT * FROM games WHERE id = ?', [$id]);
        if (!$game) {
            return null;
        }

        $stale = !$game['details_synced_at'] || (time() - strtotime($game['details_synced_at'])) > 7 * 86400;
        if ($stale && !$this->inBackoff()) {
            try {
                $data = $this->request('/game?id=' . $id);
                if (($data['id'] ?? null) != $id) {
                    throw new \RuntimeException('Game details response did not match the requested id');
                }
                $this->saveDetails($id, $data);
                $game = $this->db->fetch('SELECT * FROM games WHERE id = ?', [$id]);
            } catch (\Throwable $e) {
                $this->startBackoff();
                Logger::error("FreeToGame details for #$id failed: " . $e->getMessage());
            }
        }

        $game['screenshots'] = json_decode((string) $game['screenshots'], true) ?: [];
        $game['requirements'] = json_decode((string) $game['requirements'], true) ?: [];
        return $game;
    }

    public function lastSource(): string
    {
        return $this->lastSource;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function lastSynced(): ?string
    {
        return $this->db->fetchValue('SELECT MAX(synced_at) FROM games') ?: null;
    }

    // ================================================================ HTTP

    /** Calls the API and decodes JSON. Throws on network errors, HTTP errors and invalid JSON. */
    private function request(string $path): array
    {
        [$status, $body] = ($this->httpGet)($this->apiBase . $path);

        if ($status === 404) {
            throw new \RuntimeException('FreeToGame: game or endpoint not found (HTTP 404)');
        }
        if ($status === 429) {
            throw new \RuntimeException('FreeToGame: too many requests (HTTP 429)');
        }
        if ($status >= 400 || $status === 0) {
            throw new \RuntimeException("FreeToGame returned HTTP $status");
        }

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \RuntimeException('FreeToGame returned a non-JSON response');
        }
        return $data;
    }

    /** Default HTTP client: cURL with short timeouts so a slow API can't hang the page. */
    private function curlGet(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_USERAGENT      => 'LootVault/1.0 (school project)',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException("cURL error: $error");
        }
        return [$status, (string) $body];
    }

    // ================================================================ storage

    /** Insert or update each game (portable "upsert" that works on MySQL and SQLite). */
    private function saveGames(array $games): int
    {
        $now = date('Y-m-d H:i:s');

        return $this->db->transaction(function (Database $db) use ($games, $now) {
            $saved = 0;
            foreach (array_values($games) as $rank => $g) {
                if (!isset($g['id'], $g['title'])) {
                    continue;
                }
                $row = [
                    'title'             => mb_substr((string) $g['title'], 0, 150),
                    'thumbnail'         => $g['thumbnail'] ?? null,
                    'short_description' => isset($g['short_description']) ? mb_substr(trim($g['short_description']), 0, 500) : null,
                    'genre'             => isset($g['genre']) ? trim($g['genre']) : null,
                    'platform'          => $g['platform'] ?? null,
                    'publisher'         => $g['publisher'] ?? null,
                    'developer'         => $g['developer'] ?? null,
                    'release_date'      => $g['release_date'] ?? null,
                    'game_url'          => $g['game_url'] ?? null,
                    'profile_url'       => $g['freetogame_profile_url'] ?? ($g['profile_url'] ?? null),
                    'popularity_rank'   => $rank + 1,
                    'synced_at'         => $now,
                ];

                if ($db->fetchValue('SELECT COUNT(*) FROM games WHERE id = ?', [(int) $g['id']])) {
                    $db->update('games', $row, 'id = ?', [(int) $g['id']]);
                } else {
                    $db->insert('games', ['id' => (int) $g['id']] + $row);
                }
                $saved++;
            }
            return $saved;
        });
    }

    private function saveDetails(int $id, array $data): void
    {
        $screens = array_values(array_filter(array_map(
            fn ($s) => is_array($s) ? ($s['image'] ?? null) : $s,
            $data['screenshots'] ?? []
        )));

        $this->db->update('games', [
            'description'       => $data['description'] ?? null,
            'screenshots'       => json_encode(array_slice($screens, 0, 6)),
            'requirements'      => json_encode($data['minimum_system_requirements'] ?? []),
            'details_synced_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    /** Loads the bundled offline sample (used when the API has never been reachable). */
    public function importSnapshot(): int
    {
        if (!is_file($this->snapshotFile)) {
            return 0;
        }
        $data = json_decode((string) file_get_contents($this->snapshotFile), true);
        $count = $this->saveGames($data['games'] ?? []);

        // Mark snapshot rows as old so the next request tries the live API again.
        $this->db->query("UPDATE games SET synced_at = '2000-01-01 00:00:00'");

        foreach ($data['details'] ?? [] as $id => $details) {
            $this->saveDetails((int) $id, $details);
            $this->db->query("UPDATE games SET details_synced_at = '2000-01-01 00:00:00' WHERE id = ?", [(int) $id]);
        }
        return $count;
    }

    // ================================================================ back-off

    private function inBackoff(): bool
    {
        return is_file($this->failureFile) && (time() - (int) file_get_contents($this->failureFile)) < 600;
    }

    private function startBackoff(): void
    {
        @file_put_contents($this->failureFile, (string) time());
    }

    private function clearBackoff(): void
    {
        if (is_file($this->failureFile)) {
            @unlink($this->failureFile);
        }
    }

    private function result(string $source, int $count, ?string $error = null): array
    {
        $this->lastSource = $source;
        $this->lastError = $error;
        return ['source' => $source, 'count' => $count, 'error' => $error];
    }
}
