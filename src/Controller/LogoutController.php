<?php

namespace App\Controller;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class LogoutController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/auth/logout', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        $body = json_decode($request->getContent(), true);
        $refreshToken = $body['refresh_token'] ?? null;

        if (null !== $refreshToken && '' !== $refreshToken) {
            // Invalidate specific refresh token
            $this->em->createQuery('DELETE FROM '.RefreshToken::class.' rt WHERE rt.username = :username AND rt.refreshToken = :token')
                ->setParameter('username', $user->getUserIdentifier())
                ->setParameter('token', $refreshToken)
                ->execute();
        } else {
            // Invalidate all refresh tokens for this user
            $this->em->createQuery('DELETE FROM '.RefreshToken::class.' rt WHERE rt.username = :username')
                ->setParameter('username', $user->getUserIdentifier())
                ->execute();
        }

        return new JsonResponse(['status' => 'logged_out']);
    }
}
