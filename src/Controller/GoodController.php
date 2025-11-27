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
        $entityManager->persist($good);
        $entityManager->flush();

        return $this->json([
            'id' => $good->getId(),
            'name' => $good->getName(),
            'comment' => $good->getComment(),
            'count' => $good->getCount(),
        ], 201);
    }
}
