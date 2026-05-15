<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderDetails;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(CartService $cartService, ProductRepository $productRepository): Response
    {
        $cartSession = $cartService->getCart();
        $cartDetailed = [];
        $total = 0;

        foreach ($cartSession as $id => $data) {
            // On va chercher le produit en BDD pour avoir son nom, son image, etc.
            $product = $productRepository->find($id);

            if ($product) {
                $subTotal = $data['price'] * $data['quantity'];
                $total += $subTotal;

                $cartDetailed[] = [
                    'product' => $product,
                    'quantity' => $data['quantity'],
                    'price' => $data['price'], // Le prix au moment de l'ajout
                    'subTotal' => $subTotal
                ];
            }
        }

        return $this->render('cart/index.html.twig', [
            'items' => $cartDetailed,
            'total' => $total
        ]);
    }


    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function add(Product $product, Request $request, CartService $cartService): Response
    {
        // 1. On récupère la quantité depuis le formulaire (par défaut 1)
        $quantity = (int) $request->request->get('quantity', 1);

        // Securité : On évite les quantités négatives ou nulles
        if ($quantity < 1) {
            $quantity = 0;
        }

        // 2. On appelle notre service avec la structure choisie (id, prix, quantité)
        $cartService->add($product->getId(), $product->getPrice(), $quantity);

        // 4. On redirige l'utilisateur (par exemple vers la page du panier)
        return $this->redirectToRoute('app_product', ['id' => $product->getId()]);
    }

    #[Route('/cart/validate', name: 'cart_validate')]
    public function validate(CartService $cartService, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartService->getCart();
        if (empty($cart)) {
            return $this->redirectToRoute('cart_index');
        }

        // 3. On ouvre manuellement la transaction SQL
        $em->getConnection()->beginTransaction();

        try {
            // 4. Initialisation de la commande principale
            $order = new Order();
            $order->setUser($user);
            $order->setCreatedAt(new \DateTimeImmutable());

            $totalGlobal = 0;

            // 5. Boucle sur les éléments du panier pour créer les lignes
            foreach ($cart as $id => $data) {
                $product = $productRepository->find($id);

                if (!$product) {
                    throw new \Exception("Le produit avec l'ID #{$id} n'est plus disponible. Votre commande a été annulée.");
                }

                $orderLine = new OrderDetails();
                $orderLine->setProduct($product);
                $orderLine->setPrice($product->getPrice()); // On prend le prix exact de la BDD actuel
                $orderLine->setQuantity($data['quantity']);
                $orderLine->setRelatedOrder($order);

                $totalGlobal += $product->getPrice() * $data['quantity'];
                $em->persist($orderLine);
            }

            // On injecte le total final dans la commande et on la prépare
            $order->setTotal($totalGlobal);
            $em->persist($order);

            // 6. Envoi de toutes les requêtes INSERT à la base de données
            $em->flush();

            // 7. Si le flush n'a jeté aucune erreur, on valide définitivement la transaction
            $em->getConnection()->commit();

            // 8. C'est seulement ici, après réussite de la BDD, qu'on vide le panier de la session
            $cartService->clear();
            return $this->redirectToRoute('app_cart');

        } catch (\Exception $e) {
            if ($em->getConnection()->isTransactionActive()) {
                // Si oui (par exemple, l'erreur vient d'un produit introuvable AVANT le flush), on annule.
                $em->getConnection()->rollBack();
            }
            return $this->redirectToRoute('app_cart');
        }
    }

    #[Route('/cart/clear', name: 'cart_clear')]
    public function clear(CartService $cartService): Response
    {
        $cartService->clear();

        return $this->redirectToRoute('app_cart');
    }
}
