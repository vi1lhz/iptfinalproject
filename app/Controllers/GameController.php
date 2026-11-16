<?php

namespace App\Controllers;

use App\Models\Game;
use App\Models\Item;
use App\Services\GameCatalog;
use Core\Controller;
use Core\HttpException;
use Core\Request;
use Core\Response;

/** Pages powered by the FreeToGame API. */
class GameController extends Controller
{
    /** /games?q=&genre=&platform= */
    public function index(Request $request): Response
    {
        $sync = GameCatalog::instance()->sync();   // uses the 24 h cache; calls the API only when stale
        $filters = $request->only(['q', 'genre', 'platform']);

        return $this->view('games/index', [
            'title'   => 'Games',
            'games'   => Game::browse($filters),
            'genres'  => Game::genres(),
            'filters' => $filters,
            'sync'    => $sync,
        ]);
    }

    /** /games/{id} – game details from the API + LootVault listings for that game. */
    public function show(Request $request, string $id): Response
    {
        $game = GameCatalog::instance()->details((int) $id);
        if (!$game) {
            throw new HttpException(404, 'Game not found in the catalog.');
        }

        return $this->view('games/show', [
            'title'    => $game['title'],
            'game'     => $game,
            'listings' => Item::browse(['game' => $game['id'], 'per_page' => 24, 'sort' => 'popular'])['items'],
        ]);
    }

    /** GET /api/games – our own JSON endpoint over the cached catalog. */
    public function api(Request $request): Response
    {
        $games = Game::browse($request->only(['q', 'genre', 'platform']));

        return $this->json([
            'source' => 'FreeToGame.com (cached by LootVault)',
            'total'  => count($games),
            'data'   => array_map(fn ($g) => [
                'id'            => (int) $g['id'],
                'title'         => $g['title'],
                'genre'         => $g['genre'],
                'platform'      => $g['platform'],
                'publisher'     => $g['publisher'],
                'listing_count' => (int) $g['listing_count'],
                'url'           => url('/games/' . $g['id']),
            ], $games),
        ]);
    }
}
