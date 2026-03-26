<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Response;
use App\Models\CarModel;
use App\Models\Product;

class SearchController extends Controller
{
    // ─── Search products by name, SKU, or part number ─────────────────
    public function products(): void
    {
        $this->requireAuth();

        $term       = $this->input('q', '');
        $categoryId = (int) $this->input('category_id', 0);

        if (strlen($term) < 2) {
            Response::error('Search term must be at least 2 characters.', 422);
        }

        $product = new Product();
        $results = $product->search($term, $categoryId);

        Response::success([
            'results' => $results,
            'count'   => count($results),
        ], 'Search completed.');
    }

    // ─── Search products by car model ─────────────────────────────────
    public function byCarModel(): void
    {
        $this->requireAuth();

        $carModelId = (int) $this->input('car_model_id');

        if (!$carModelId) {
            Response::error('Car model ID is required.', 422);
        }

        $carModel = new CarModel();
        $results  = $carModel->products($carModelId);

        Response::success([
            'results' => $results,
            'count'   => count($results),
        ], 'Search completed.');
    }

    // ─── Search car models by brand, model, or year ───────────────────
    public function carModels(): void
    {
        $this->requireAuth();

        $term = $this->input('q', '');

        if (strlen($term) < 2) {
            Response::error('Search term must be at least 2 characters.', 422);
        }

        $carModel = new CarModel();
        $results  = $carModel->search($term);

        Response::success([
            'results' => $results,
            'count'   => count($results),
        ], 'Search completed.');
    }
}
    /*
    Search Endpoints

    The following routes are registered in `index.php` and provide search capabilities within the POS system.

    GET /search/products
        Searches products by name, SKU, or part_number.

    GET /search/products/car-model
        Returns products compatible with a specific car model.

    GET /search/car-models
        Searches the car models database by brand, model name, or other attributes.
    */