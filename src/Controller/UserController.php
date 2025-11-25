<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use App\Repository\UserRepository;


final class UserController extends AbstractController
{
    #[Route('/api/users', name: 'app_api_user')]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();
        $response = array_map(fn($user) => [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ], $users);
        return $this->json($response);
    }
}
