<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UserController extends AbstractController
{
    #[Route('/my-account', name: 'app_user_account')]
    #[IsGranted('ROLE_USER')] // Sécurise l'accès : interdit aux personnes non connectées
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $user->getOrders();

        return $this->render('user/index.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/my-account/toggle-api', name: 'app_user_toggle_api', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleApi(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setApiAccess(!$user->isApiAccess());
        $entityManager->flush();

        $this->addFlash('success', 'Vos accès API ont été mis à jour.');

        return $this->redirectToRoute('app_user_account');
    }
}
