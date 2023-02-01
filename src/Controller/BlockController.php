<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\DishesRepository;

class BlockController extends AbstractController
{
//    #[Route('/day_dishes', name: 'day_dishes', methods: ['GET'])]
    public function dayDishesAction(ManagerRegistry $doctrine, DishesRepository $dishesRepository, $max = 3)
    {
        $category = $doctrine->getRepository(Category::class)->findOneBy(['name' => 'Plats']);
        $dishes = $dishesRepository->findStickies($category, $max);

        return $this->render('Partials/day_dishes.html.twig',
            array('dishes' => $dishes)
        );
    }
}
