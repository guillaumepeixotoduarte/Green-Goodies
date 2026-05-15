<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    // On utilise RequestStack pour accéder à la session
    public function __construct(private RequestStack $requestStack) {}

    public function add(int $id, float $price, int $quantity = 1): void
    {
        $session = $this->requestStack->getSession();
        $cart = $session->get('panier', []);

        if ($quantity < 1 && isset($cart[$id])) {
            // Si la quantité est inférieure à 1 et que le produit existe, on le supprime du panier
            unset($cart[$id]);
        } elseif (isset($cart[$id])) {
            // Si le produit existe déjà, on augmente juste la quantité
            $cart[$id]['quantity'] = $quantity;
        }else {
            // Sinon, on crée le sous-tableau avec tes clés choisies
            $cart[$id] = [
                'price' => $price,
                'quantity' => $quantity
            ];
        }

        $session->set('panier', $cart);
    }

    public function getCart(): array
    {
        return $this->requestStack->getSession()->get('panier', []);
    }

    public function clear(): void
    {
        $this->requestStack->getSession()->remove('panier');
    }
}
