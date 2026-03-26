<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\Response;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\CartService;

class PosController extends Controller
{
    // ─── Show dashboard — display categories only ─────────────────────
    public function dashboard(): void
    {
        $this->requireAuth();

        $category   = new Category();
        $categories = $category->allOrdered();
        $cartCount  = CartService::count();

        $this->view('pos/dashboard', compact(
            'categories',
            'cartCount'
        ));
    }

    // ─── Show products by category ────────────────────────────────────
    public function categoryProducts(): void
    {
        $this->requireAuth();

        $categoryId = (int) $this->input('id');
        $search     = $this->input('q', '');

        if (!$categoryId) {
            $this->redirect('/pos/dashboard');
        }

        $product  = new Product();
        $category = new Category();

        $cat      = $category->find($categoryId);
        $products = !empty($search)
            ? $product->search($search)
            : $product->byCategory($categoryId);

        $cartCount = CartService::count();

        $this->view('pos/products', compact(
            'cat',
            'products',
            'cartCount',
            'search',
            'categoryId'
        ));
    }

    // ─── Show cart page ───────────────────────────────────────────────
    public function cart(): void
    {
        $this->requireAuth();

        $cart      = CartService::getCart();
        $customers = (new Customer())->allOrdered();
        $cartTotal = CartService::total();

        $this->view('pos/cart', compact(
            'cart',
            'customers',
            'cartTotal'
        ));
    }

    // ─── Add item to cart (AJAX) ──────────────────────────────────────
    public function addToCart(): void
    {
        $this->requireAuth();

        $productId = (int) $this->input('product_id');
        $quantity  = (int) $this->input('quantity', 1);

        if (!$productId) {
            Response::error('Product ID is required.', 422);
        }

        $product = new Product();
        $item    = $product->find($productId);

        if (!$item) {
            Response::error('Product not found.', 404);
        }

        if (!$product->hasStock($productId, $quantity)) {
            Response::error('Not enough stock available.', 422);
        }

        $cart = CartService::addItem($item, $quantity);

        Response::success([
            'cart'       => $cart,
            'cart_count' => CartService::count(),
            'cart_total' => CartService::total(),
        ], 'Item added to cart.');
    }

    // ─── Update cart item quantity (AJAX) ─────────────────────────────
    public function updateCart(): void
    {
        $this->requireAuth();

        $productId = (int) $this->input('product_id');
        $quantity  = (int) $this->input('quantity');

        if (!$productId) {
            Response::error('Product ID is required.', 422);
        }

        $cart = CartService::updateQuantity($productId, $quantity);

        Response::success([
            'cart'       => $cart,
            'cart_count' => CartService::count(),
            'cart_total' => CartService::total(),
        ], 'Cart updated.');
    }

    // ─── Remove item from cart (AJAX) ─────────────────────────────────
    public function removeFromCart(): void
    {
        $this->requireAuth();

        $productId = (int) $this->input('product_id');

        if (!$productId) {
            Response::error('Product ID is required.', 422);
        }

        $cart = CartService::removeItem($productId);

        Response::success([
            'cart'       => $cart,
            'cart_count' => CartService::count(),
            'cart_total' => CartService::total(),
        ], 'Item removed from cart.');
    }

    // ─── Show loading screen while pulling data ───────────────────────
    public function loading(): void
    {
        $this->requireAuth();
        $this->view('pos/loading');
    }

// ─── Show single product details ──────────────────────────────────
public function productDetails(): void
{
    $this->requireAuth();

    $productId  = (int) $this->input('id');

    if (!$productId) {
        $this->redirect('/pos/dashboard');
    }

    $product    = new Product();
    $item       = $product->findWithCarModels($productId);

    if (!$item) {
        $this->redirect('/pos/dashboard');
    }

    $categoryId = $item['category_id'];
    $cartCount  = CartService::count();

    $this->view('pos/product', compact('item', 'categoryId', 'cartCount'));
}

    }

/*
POS Controller Responsibilities

─────────────────────────────────────────
View Methods
─────────────────────────────────────────

dashboard()
    → Displays categories grid only.
    → Cashier clicks a category to see its products.

categoryProducts()
    → Displays products belonging to a specific category.
    → Supports search by name/SKU/part_number.
    → GET /pos/products?id={categoryId}&q={search}

cart()
    → Displays the cart page with all current items.
    → Passes customer list for optional customer selection.

loading()
    → Displays loading screen while pulling data from server.

─────────────────────────────────────────
Cart AJAX Methods
─────────────────────────────────────────

addToCart()      POST /cart/add
    → Validates product existence and stock availability.
    → Adds item to session cart.

updateCart()     POST /cart/update
    → Updates quantity for an existing cart item.
    → If quantity <= 0, removes the item.

removeFromCart() POST /cart/remove
    → Removes a specific item from the cart.
*/