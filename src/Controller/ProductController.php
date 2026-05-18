<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProductController extends AbstractController
{
    #[Route('/product/{id}', name: 'app_product')]
    public function index(int $id, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        return $this->render('product/index.html.twig', [
            'controller_name' => 'ProductController',
            'product' => $product,
        ]);
    }

    #[Route('/api/products', name: 'app_products')]
    #[IsGranted('ROLE_USER')]
    public function api(ProductRepository $productRepository, UrlHelper $urlHelper): Response
    {
        $products = $productRepository->findAll();

        $data = [];

        foreach ($products as $product) {

            $imageName = $product->getPicture();
            $imageUrl = null;

            if ($imageName) {
                $imageUrl = $urlHelper->getAbsoluteUrl('/product_img/' . $imageName);
            }

            // 3. On construit le tableau de données pour le JSON
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'shortDescription' => $product->getShortDescription(),
                'longDescription' => $product->getFullDescription(),
                'imageUrl' => $imageUrl, // C'est ici que ton front-end aura le lien cliquable direct !
            ];
        }

        // On retourne une réponse JSON
        return $this->json($data, 200, [], ['groups' => 'product:read']);
    }
}
