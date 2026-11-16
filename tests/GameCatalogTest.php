<?php

namespace Tests;

use App\Models\Game;
use App\Services\GameCatalog;
use Core\Database;

/** External API integration, tested with a fake HTTP client (no internet needed). */
class GameCatalogTest extends TestCase
{
    private Database $db;
    private array $calls = [];
    private string $backoffFile;

    private const LIST = '[{"id":452,"title":"Call of Duty: Warzone","thumbnail":"https://www.freetogame.com/g/452/thumbnail.jpg","short_description":"Battle royale.","game_url":"https://www.freetogame.com/open/call-of-duty-warzone","genre":"Shooter","platform":"PC (Windows)","publisher":"Activision","developer":"Infinity Ward","release_date":"2020-03-10","freetogame_profile_url":"https://www.freetogame.com/call-of-duty-warzone"},{"id":345,"title":"Forge of Empires","genre":"Strategy","platform":"Web Browser","publisher":"InnoGames"}]';

    public function setUp(): void
    {
        $this->db = $this->freshDatabase();
        $this->backoffFile = sys_get_temp_dir() . '/lv_test_backoff_' . uniqid();
    }

    public function tearDown(): void
    {
        @unlink($this->backoffFile);
    }

    /** Builds a catalog whose HTTP client returns the given [status, body] responses in order. */
    private function catalog(array $responses): GameCatalog
    {
        $http = function (string $url) use (&$responses) {
            $this->calls[] = $url;
            $next = array_shift($responses) ?? [500, ''];
            if ($next instanceof \Throwable) {
                throw $next;
            }
            return $next;
        };
        return new GameCatalog($this->db, $http, 'https://api.test', 24, $this->backoffFile, dirname(__DIR__) . '/database/seeds/games.json');
    }

    public function testSyncSavesGamesFromApi(): void
    {
        $result = $this->catalog([[200, self::LIST]])->sync();
        $this->assertSame('api', $result['source']);
        $this->assertSame(2, Game::count());
        $this->assertSame('https://api.test/games?sort-by=popularity', $this->calls[0]);
        $this->assertSame('https://www.freetogame.com/call-of-duty-warzone', Game::find(452)['profile_url']);
        $this->assertEquals(1, Game::find(452)['popularity_rank']);
    }

    public function testFreshCacheSkipsTheApi(): void
    {
        $catalog = $this->catalog([[200, self::LIST]]);
        $catalog->sync();
        $result = $catalog->sync();
        $this->assertSame('cache', $result['source']);
        $this->assertCount(1, $this->calls, 'API should be called only once within the cache window.');
    }

    public function testResyncUpdatesInsteadOfDuplicating(): void
    {
        $changed = str_replace('Call of Duty: Warzone', 'Warzone (renamed)', self::LIST);
        $catalog = $this->catalog([[200, self::LIST], [200, $changed]]);
        $catalog->sync();
        $catalog->sync(true);
        $this->assertSame(2, Game::count());
        $this->assertSame('Warzone (renamed)', Game::find(452)['title']);
    }

    public function testApiDownWithEmptyCacheLoadsSnapshot(): void
    {
        $result = $this->catalog([new \RuntimeException('cURL error: timeout')])->sync();
        $this->assertSame('snapshot', $result['source']);
        $this->assertTrue(Game::count() >= 10);
        $this->assertContains('timeout', (string) $result['error']);
    }

    public function testApiErrorKeepsExistingCache(): void
    {
        $catalog = $this->catalog([[200, self::LIST], [500, '{"status":0}']]);
        $catalog->sync();
        $result = $catalog->sync(true);
        $this->assertSame('stale', $result['source']);
        $this->assertSame(2, Game::count());
    }

    public function testMalformedJsonIsHandled(): void
    {
        $result = $this->catalog([[200, '<html>maintenance</html>']])->sync();
        $this->assertSame('snapshot', $result['source']);
    }

    public function testUnexpectedShapeIsRejected(): void
    {
        $result = $this->catalog([[200, '{"status":0,"status_message":"oops"}']])->sync();
        $this->assertSame('snapshot', $result['source']);
        $this->assertContains('Unexpected response', (string) $result['error']);
    }

    public function testBackoffPreventsRepeatedCallsAfterFailure(): void
    {
        $catalog = $this->catalog([[500, ''], [200, self::LIST]]);
        $catalog->sync();
        $this->db->query("UPDATE games SET synced_at = '2000-01-01 00:00:00'");
        $catalog->sync();
        $this->assertCount(1, $this->calls, 'Second sync should not call the API during back-off.');
    }

    public function testDetailsAreFetchedAndScreenshotObjectsFlattened(): void
    {
        $detail = '{"id":452,"title":"Call of Duty: Warzone","description":"Full description.","minimum_system_requirements":{"os":"Windows 10","memory":"8GB RAM"},"screenshots":[{"id":1,"image":"https://x/1.jpg"},{"id":2,"image":"https://x/2.jpg"}]}';
        $catalog = $this->catalog([[200, self::LIST], [200, $detail]]);
        $catalog->sync();
        $game = $catalog->details(452);
        $this->assertSame('Full description.', $game['description']);
        $this->assertSame(['https://x/1.jpg', 'https://x/2.jpg'], $game['screenshots']);
        $this->assertSame('8GB RAM', $game['requirements']['memory']);
        $this->assertSame('https://api.test/game?id=452', $this->calls[1]);

        $catalog->details(452);
        $this->assertCount(2, $this->calls, 'Details should be cached.');
    }

    public function testDetails404KeepsBasicInfo(): void
    {
        $catalog = $this->catalog([[200, self::LIST], [404, '{"status":0}']]);
        $catalog->sync();
        $game = $catalog->details(345);
        $this->assertSame('Forge of Empires', $game['title']);
        $this->assertSame([], $game['screenshots']);
    }

    public function testUnknownGameReturnsNull(): void
    {
        $this->assertNull($this->catalog([])->details(123456));
    }
}
