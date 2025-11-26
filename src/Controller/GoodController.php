<?php

namespace App\Controller;

use App\Repository\GoodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;

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
}
