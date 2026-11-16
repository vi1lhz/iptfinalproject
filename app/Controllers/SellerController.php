<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Game;
use App\Services\GameCatalog;
use App\Models\Item;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;
use App\Services\UploadService;
use Core\Auth;
use Core\Controller;
use Core\HttpException;
use Core\Request;
use Core\Response;
use Core\Session;

class SellerController extends Controller
{
    private const RULES = [
        'title'         => 'required|min:5|max:150',
        'category_id'   => 'required|integer|exists:categories,id',
        'game_id'       => 'integer|exists:games,id',
        'price'         => 'required|numeric|min:20|max:500000',
        'stock'         => 'required|integer|min:0|max:9999',
        'delivery_type' => 'required|in:code,file',
        'description'   => 'required|min:20|max:5000',
        'delivery_content' => 'max:5000',
    ];

    /** Public seller profile page. */
    public function profile(Request $request, string $username): Response
    {
        $seller = User::findBy('username', $username);
        if (!$seller || $seller['is_banned']) {
            throw new HttpException(404, 'Seller not found.');
        }

        return $this->view('seller/profile', [
            'title'   => $seller['username'],
            'seller'  => $seller,
            'stats'   => User::sellerStats((int) $seller['id']),
            'items'   => Item::browse(['seller_id' => $seller['id'], 'per_page' => 24])['items'],
            'reviews' => Review::forSeller((int) $seller['id'], 6),
        ]);
    }

    public function dashboard(Request $request): Response
    {
        $id = Auth::id();
        return $this->view('seller/dashboard', [
            'title'  => 'Seller dashboard',
            'items'  => Item::forSeller($id),
            'sales'  => OrderItem::salesFor($id, 15),
            'totals' => OrderItem::sellerTotals($id),
            'stats'  => User::sellerStats($id),
            'user'   => User::find($id),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('seller/form', [
            'title'      => 'List a new item',
            'item'       => null,
            'categories' => Category::all('id ASC'),
            'games'      => $this->gameOptions(),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, self::RULES);
        $this->validateDelivery($request, $data, null);

        $uploads = new UploadService();
        $data['image'] = $uploads->image($request->file('image'));
        if ($data['delivery_type'] === 'file') {
            [$data['file_path'], $data['file_name']] = $uploads->protectedFile($request->file('digital_file'));
        }

        $data['game_id'] = !empty($data['game_id']) ? (int) $data['game_id'] : null;
        $data['seller_id'] = Auth::id();
        $data['slug'] = Item::makeSlug($data['title']);
        $data['status'] = (int) $data['stock'] > 0 ? 'active' : 'sold_out';

        Item::create($data);

        $this->flash('success', 'Your listing is live!');
        return $this->redirect('/items/' . $data['slug']);
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->view('seller/form', [
            'title'      => 'Edit listing',
            'item'       => $this->ownedItem((int) $id),
            'categories' => Category::all('id ASC'),
            'games'      => $this->gameOptions(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $item = $this->ownedItem((int) $id);
        $data = $this->validate($request, self::RULES + ['status' => 'in:active,paused']);
        $this->validateDelivery($request, $data, $item);

        $uploads = new UploadService();
        if ($image = $uploads->image($request->file('image'))) {
            $data['image'] = $image;
        }
        if ($data['delivery_type'] === 'file' && $request->file('digital_file')) {
            [$data['file_path'], $data['file_name']] = $uploads->protectedFile($request->file('digital_file'));
        }

        $data['game_id'] = !empty($data['game_id']) ? (int) $data['game_id'] : null;
        $data['status'] = (int) $data['stock'] <= 0 ? 'sold_out' : ($data['status'] ?? 'active');
        $data['updated_at'] = date('Y-m-d H:i:s');

        Item::update((int) $item['id'], $data);

        $this->flash('success', 'Listing updated.');
        return $this->redirect('/sell');
    }

    public function destroy(Request $request, string $id): Response
    {
        $item = $this->ownedItem((int) $id);
        // Soft delete: keeps past orders and reviews intact.
        Item::update((int) $item['id'], ['status' => 'removed', 'updated_at' => date('Y-m-d H:i:s')]);

        $this->flash('info', 'Listing removed.');
        return $this->redirect('/sell');
    }

    // ----------------------------------------------------------------

    /** Game list for the dropdown, refreshed from the FreeToGame API when the cache is old. */
    private function gameOptions(): array
    {
        GameCatalog::instance()->sync();
        return Game::options();
    }

    private function ownedItem(int $id): array
    {
        $item = Item::find($id);
        if (!$item || $item['status'] === 'removed') {
            throw new HttpException(404, 'Listing not found.');
        }
        if ((int) $item['seller_id'] !== Auth::id() && !Auth::hasRole('admin')) {
            throw new HttpException(403, 'You can only edit your own listings.');
        }
        return $item;
    }

    /** Code items need delivery text; file items need an uploaded file. */
    private function validateDelivery(Request $request, array $data, ?array $existing): void
    {
        $errors = [];
        if ($data['delivery_type'] === 'code' && trim((string) ($data['delivery_content'] ?? '')) === '') {
            $errors['delivery_content'] = 'Enter what the buyer receives (code, account login or instructions).';
        }
        if ($data['delivery_type'] === 'file' && !$request->file('digital_file') && empty($existing['file_path'])) {
            $errors['digital_file'] = 'Upload the file the buyer will download.';
        }
        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $request->all());
            throw new \Core\ValidationException($errors);
        }
    }
}
