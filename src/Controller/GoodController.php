<?php

namespace App\Controller;

use App\Repository\GoodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Good;
use App\DTO\GoodCreateRequest;

final class GoodController extends AbstractController
{
    public function __construct(
        private GoodRepository $goodRepository,
        private SerializerInterface $serializer    
    ) {}


    #[Route('/api/goods', name: 'app_api_goods', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $goods = $this->goodRepository->findAll();

        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('good:read')
            ->toArray();
        
        $json = $this->serializer->serialize($goods, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 200);
    }

    #[Route('/api/goods', name: 'app_api_goods_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        
        $data = json_decode($request->getContent(), true);
        if (!$data && $request->isMethod('POST')) {
            $data = [
                'name' => $request->request->get('name'),
                'comment' => $request->request->get('comment'),
                'count' => $request->request->get('count')
            ];
        }

        $goodCreateRequest = new GoodCreateRequest();
        $goodCreateRequest->name = $data['name'] ?? null;
        $goodCreateRequest->comment = $data['comment'] ?? null;
        $goodCreateRequest->count = $data['count'];
        $errors = $validator->validate($goodCreateRequest);
        if (\count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['error' => 'Good creating failed', 'errors' => $errorMessages], 400);
        }

        $good = new Good();
        $good->setName($data['name'] ?? '');
        $good->setComment($data['comment'] ?? null);
        $good->setCount($data['count'] ?? 0);
        $good->setUser($user); // Привязываем товар к текущему пользователю
        $entityManager->persist($good);
        $entityManager->flush();

        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('good:read')
            ->toArray();
        $json = $this->serializer->serialize($good, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 201);
    }

    #[Route('/api/goods/my', name: 'app_api_goods_my', methods: ['GET'])]
    public function my(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $goods = $this->goodRepository->findBy(['user' => $user]);

        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('good:read')
            ->toArray();
        $json = $this->serializer->serialize($goods, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 200);
    }

    #[Route('/api/goods/{id}', name: 'app_api_goods_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $good = $this->goodRepository->find($id);
        
        if (!$good) {
            return $this->json(['error' => 'Good not found'], 404);
        }

        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('good:detail')
            ->toArray();
        $json = $this->serializer->serialize($good, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 200);
    }

    #[Route('/api/goods/{id}', name: 'app_api_goods_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $good = $this->goodRepository->find($id);
        
        if (!$good) {
            return $this->json(['error' => 'Good not found'], 404);
        }

        // Проверка прав доступа: только владелец или админ может редактировать
        if ($good->getUser() !== $user && !\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->json(['error' => 'Forbidden: You can only edit your own goods'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $good->setName($data['name']);
        }
        if (isset($data['comment'])) {
            $good->setComment($data['comment']);
        }
        if (isset($data['count'])) {
            $good->setCount($data['count']);
        }

        $errors = $validator->validate($good);
        if (\count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'errors' => $errorMessages], 400);
        }

        $entityManager->flush();

        $context = new ObjectNormalizerContextBuilder()
            ->withGroups('good:read')
            ->toArray();
        $json = $this->serializer->serialize($good, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 200);
    }

    #[Route('/api/goods/{id}', name: 'app_api_goods_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $good = $this->goodRepository->find($id);
        
        if (!$good) {
            return $this->json(['error' => 'Good not found'], 404);
        }

        // Проверка прав доступа: только владелец или админ может удалять
        if ($good->getUser() !== $user && !\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->json(['error' => 'Forbidden: You can only delete your own goods'], 403);
        }

        $entityManager->remove($good);
        $entityManager->flush();

        return $this->json(['message' => 'Good deleted successfully'], 204);
    }
}
