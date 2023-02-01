<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DealController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render("front/index.html.twig", [
            'controller_name' => 'DealController'
        ]);
    }

    /*#[Route('deal/show/{index}', name: 'deal_show', methods: 'GET')]
    public function show(int $index): Response {
        return new Response($index);
    }*/
}
