<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

class CartController extends Controller
{
    /**
     * Display a listing of the cart items.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            // Authenticated user - get cart from database
            $cart = Cart::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();
            
            if (!$cart) {
                return response()->json(['cart' => []]);
            }
            
            return response()->json([
                'cart' => json_decode($cart->items, true) ?? []
            ]);
        } else {
            // Guest user - get cart from session
            $cart = $request->session()->get('cart', []);
            logger('Session cart :: ', $cart);
            return response()->json([
                'cart' => $cart
            ]);
        }
    }

    /**
     * Store a newly created item in the cart.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'integer|min:1'
        ]);

        $user = Auth::user();
        
        if ($user) {
            // Authenticated user - store in database
            $cart = Cart::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();

            if (!$cart) {
                $cart = new Cart();
                $cart->user_id = $user->id;
                $cart->status = 'active';
                $cart->items = json_encode([]);
            }

            $items = json_decode($cart->items, true) ?? [];
            
            // Check if product already in cart, update quantity if so
            $found = false;
            foreach ($items as &$item) {
                if ($item['product_id'] == $request->input('product_id')) {
                    $item['quantity'] += $request->input('quantity', 1);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $items[] = [
                    'product_id' => $request->input('product_id'),
                    'quantity' => $request->input('quantity', 1)
                ];
            }

            $cart->items = json_encode($items);
            $cart->save();

            return response()->json([
                'success' => true,
                'message' => "Added to cart",
                'cart' => $items
            ]);
        } else {
            // Guest user - store in session
            $cart = $request->session()->get('cart', []);
            
            // Check if product already in cart, update quantity if so
            $found = false;
            foreach ($cart as &$cartItem) {
                if ($cartItem['product_id'] == $request->input('product_id')) {
                    $cartItem['quantity'] += $request->input('quantity', 1);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $cart[] = [
                    'product_id' => $request->input('product_id'),
                    'quantity' => $request->input('quantity', 1)
                ];
            }

            $request->session()->put('cart', $cart);

            logger('Session cart :: ', $request->session()->get('cart'));

            return response()->json([
                'success' => true,
                'message' => "Added to cart",
                'cart' => $cart
            ]);
        }
    }

    /**
     * Display the specified cart item.
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();
        
        if ($user) {
            // Authenticated user - get from database
            $cart = Cart::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();
            
            if (!$cart) {
                return response()->json(['message' => 'Cart not found'], 404);
            }
            
            $items = json_decode($cart->items, true) ?? [];
            $item = collect($items)->firstWhere('product_id', $id);

            if (!$item) {
                return response()->json(['message' => 'Item not found in cart'], 404);
            }

            $product = Product::find($id);

            return response()->json([
                'product' => $product,
                'quantity' => $item['quantity']
            ]);
        } else {
            // Guest user - get from session
            $cart = $request->session()->get('cart', []);
            $item = collect($cart)->firstWhere('product_id', $id);

            if (!$item) {
                return response()->json(['message' => 'Item not found in cart'], 404);
            }

            $product = Product::with(['productImages'])->find($id);

            return response()->json([
                'product' => $product,
                'quantity' => $item['quantity']
            ]);
        }
    }

    /**
     * Update the specified cart item.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        
        if ($user) {
            // Authenticated user - update in database
            $cart = Cart::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();
            
            if (!$cart) {
                return response()->json(['message' => 'Cart not found'], 404);
            }
            
            $items = json_decode($cart->items, true) ?? [];
            $updated = false;

            foreach ($items as &$item) {
                if ($item['product_id'] == $id) {
                    $item['quantity'] = $request->input('quantity');
                    $updated = true;
                    break;
                }
            }

            if (!$updated) {
                return response()->json(['message' => 'Item not found in cart'], 404);
            }

            $cart->items = json_encode($items);
            $cart->save();

            return response()->json([
                'message' => "Cart item {$id} updated",
                'cart' => $items
            ]);
        } else {
            // Guest user - update in session
            $cart = $request->session()->get('cart', []);
            $updated = false;

            foreach ($cart as &$cartItem) {
                if ($cartItem['product_id'] == $id) {
                    $cartItem['quantity'] = $request->input('quantity');
                    $updated = true;
                    break;
                }
            }

            if (!$updated) {
                return response()->json(['message' => 'Item not found in cart'], 404);
            }

            $request->session()->put('cart', $cart);

            return response()->json([
                'message' => "Cart item {$id} updated",
                'cart' => $cart
            ]);
        }
    }

    /**
     * Remove the specified item from the cart.
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        
        if ($user) {
            // Authenticated user - remove from database
            $cart = Cart::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();
            
            if (!$cart) {
                return response()->json(['message' => 'Cart not found'], 404);
            }
            
            $items = json_decode($cart->items, true) ?? [];
            $newItems = array_filter($items, function ($item) use ($id) {
                return $item['product_id'] != $id;
            });

            $cart->items = json_encode(array_values($newItems));
            $cart->save();

            return response()->json([
                'message' => "Item {$id} removed from cart",
                'cart' => array_values($newItems)
            ]);
        } else {
            // Guest user - remove from session
            $cart = $request->session()->get('cart', []);
            $newCart = array_filter($cart, function ($item) use ($id) {
                return $item['product_id'] != $id;
            });

            $request->session()->put('cart', array_values($newCart));

            return response()->json([
                'message' => "Item {$id} removed from cart",
                'cart' => array_values($newCart)
            ]);
        }
    }

    /**
     * Merge guest cart with user cart when logging in.
     */
    public function mergeGuestCart(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $guestCart = $request->session()->get('cart', []);
        if (empty($guestCart)) {
            return response()->json(['message' => 'No guest cart to merge']);
        }

        // Get or create user cart
        $userCart = Cart::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->first();

        if (!$userCart) {
            $userCart = new Cart();
            $userCart->user_id = $user->id;
            $userCart->status = 'active';
            $userCart->items = json_encode([]);
        }

        $userItems = json_decode($userCart->items, true) ?? [];
        
        // Merge guest cart items with user cart
        foreach ($guestCart as $guestItem) {
            $found = false;
            foreach ($userItems as &$userItem) {
                if ($userItem['product_id'] == $guestItem['product_id']) {
                    $userItem['quantity'] += $guestItem['quantity'];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $userItems[] = $guestItem;
            }
        }

        $userCart->items = json_encode($userItems);
        $userCart->save();

        // Clear guest cart from session
        $request->session()->forget('cart');

        return response()->json([
            'success' => true,
            'message' => 'Guest cart merged successfully',
            'cart' => $userItems
        ]);
    }
}
