<?php

namespace App\Controller;

use App\Entity\Good;
use App\Repository\GoodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GoodController extends AbstractController
{
    private GoodRepository $goodRepository;

    public function __construct(GoodRepository $goodRepository)
    {
        $this->goodRepository = $goodRepository;
    }

    #[Route('/api/goods', name: 'app_api_goods')]
    public function index(): JsonResponse
    {
        $goods = $this->goodRepository->findAll();

        $data = array_map(fn($good) => [
            'id' => $good->getId(),
            'name' => $good->getName(),
            'comment' => $good->getComment(),
            'count' => $good->getCount(),
        ], $goods);

        return $this->json($data);
    }
}
