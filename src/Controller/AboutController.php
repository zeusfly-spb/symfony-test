<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about') ]
    public function about(): Response
    {
        $number = random_int(0, 100);

        return $this->render('about.html.twig', [
            'number' => $number,
        ]);
    }
}
