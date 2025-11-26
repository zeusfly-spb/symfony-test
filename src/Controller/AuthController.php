<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\DTO\RegisterRequest;
use App\DTO\LoginRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;

class AuthController extends AbstractController
{
    public function __construct(private SerializerInterface $serializer) {}

    
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data && $request->isMethod('POST')) {
            $data = [
                'email' => $request->request->get('email'),
                'password' => $request->request->get('password')
            ];
        }

        $loginRequest = new LoginRequest();
        $loginRequest->email = $data['email'] ?? null;
        $loginRequest->password = $data['password'] ?? null;
        $errors = $validator->validate($loginRequest);
        if (\count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'errors' => $errorMessages], 400);
        }

        $user = $entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);
        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $jwtManager->create($user);
        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('user:read')
            ->toArray();
        $userJson = $this->serializer->serialize($user, 'json', $context);
        $userData = json_decode($userJson, true);
        return $this->json([
            'token' => $token,
            'user' => $userData
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function apiRegister(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): JsonResponse 
    {
        $data = json_decode($request->getContent(), true);

        if (!$data && $request->isMethod('POST')) {
            $data = [
                'email' => $request->request->get('email'),
                'password' => $request->request->get('password'),
                'name' => $request->request->get('name')
            ];
        }

        $registerRequest = new RegisterRequest();
        $registerRequest->email = $data['email'] ?? null;
        $registerRequest->password = $data['password'] ?? null;
        $registerRequest->name = $data['name'] ?? null;
        $errors = $validator->validate($registerRequest);

        if (\count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'errors' => $errorMessages], 400);
        }

        // Check if user already exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'User with this email already exists'], 409);
        }

        $user = new User();
        $user->setEmail($registerRequest->email);
        $user->setName($registerRequest->name);
        $user->setPassword($passwordHasher->hashPassword($user, $registerRequest->password));

        $entityManager->persist($user);
        $entityManager->flush();

        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('user:read')
            ->toArray();
        $userJson = $this->serializer->serialize($user, 'json', $context);
        $userData = json_decode($userJson, true);

        return $this->json([
            'message' => 'User registered successfully',
            'user' => $userData
        ], 201);
    }

    #[Route('/api/refresh', name: 'api_refresh', methods: ['POST'])]
    public function apiRefresh(JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ]
        ]);
    }

    #[Route('/api/profile', name: 'api_profile', methods: ['GET'])]
    public function apiProfile(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('user:read')
            ->toArray();
        $userJson = $this->serializer->serialize($user, 'json', $context);
        $userData = json_decode($userJson, true);

        return $this->json([
            'user' => $userData
        ]);
    }
}
