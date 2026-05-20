<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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

    #[Route('/user/delete', name: 'app_user_delete')]
    #[IsGranted('ROLE_USER')]
    public function delete(EntityManagerInterface $entityManager, Request $request, TokenStorageInterface $tokenStorage): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $entityManager->remove($user);
        $entityManager->flush();

        $request->getSession()->invalidate(); // Supprime la session PHP courante
        $tokenStorage->setToken(null); // Vide le token de sécurité de Symfony

        $this->addFlash('success', 'Votre compte a été supprimé. Nous sommes désolés de vous voir partir.');
        return $this->redirectToRoute('app_home');
    }
}
