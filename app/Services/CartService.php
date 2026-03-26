<?php

namespace App\Services;

class CartService
{
    private static string $cartKey = 'pos_cart';

    // ─── Get current cart from session ───────────────────────────────
    public static function getCart(): array
    {
        return $_SESSION[self::$cartKey] ?? [];
    }

    // ─── Add item to cart ─────────────────────────────────────────────
    // If product already exists — increase quantity
    public static function addItem(array $product, int $quantity = 1): array
    {
        $cart      = self::getCart();
        $productId = $product['id'];

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']  += $quantity;
            $cart[$productId]['total_row']  =
                $cart[$productId]['quantity'] * $cart[$productId]['unit_price'];
        } else {
            $cart[$productId] = [
                'product_id'   => $product['id'],
                'api_id'       => $product['api_id'],
                'product_name' => $product['name'],
                'unit_price'   => $product['sell_price'],
                'quantity'     => $quantity,
                'total_row'    => $product['sell_price'] * $quantity,
            ];
        }

        $_SESSION[self::$cartKey] = $cart;
        return $cart;
    }

    // ─── Update item quantity ─────────────────────────────────────────
    public static function updateQuantity(int $productId, int $quantity): array
    {
        $cart = self::getCart();

        if (isset($cart[$productId])) {
            if ($quantity <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId]['quantity']  = $quantity;
                $cart[$productId]['total_row'] =
                    $quantity * $cart[$productId]['unit_price'];
            }
        }

        $_SESSION[self::$cartKey] = $cart;
        return $cart;
    }

    // ─── Remove item from cart ────────────────────────────────────────
    public static function removeItem(int $productId): array
    {
        $cart = self::getCart();
        unset($cart[$productId]);
        $_SESSION[self::$cartKey] = $cart;
        return $cart;
    }

    // ─── Clear all cart items ─────────────────────────────────────────
    public static function clear(): void
    {
        $_SESSION[self::$cartKey] = [];
    }

    // ─── Get total amount ─────────────────────────────────────────────
    public static function total(): float
    {
        return array_sum(array_column(self::getCart(), 'total_row'));
    }

    // ─── Get items count ──────────────────────────────────────────────
    public static function count(): int
    {
        return count(self::getCart());
    }

    // ─── Check if cart is empty ───────────────────────────────────────
    public static function isEmpty(): bool
    {
        return empty(self::getCart());
    }
}
