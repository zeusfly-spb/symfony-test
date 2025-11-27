<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;



final class UserController extends AbstractController
{
    public function __construct(private UserRepository $userRepository, private SerializerInterface $serializer)
    {}

    #[Route('/api/users', name: 'app_api_user')]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('user:detail')
            ->toArray();
        $json = $this->serializer->serialize($users, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 200);
    }

    #[Route('/api/users/{id}', name: 'app_api_user_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        // Используем группу user:detail (с email и roles)
        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('user:detail')
            ->toArray();
        
        $json = $this->serializer->serialize($user, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 200);
    }
}
