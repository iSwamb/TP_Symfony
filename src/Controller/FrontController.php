<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DishesRepository;
use App\Repository\UserRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class FrontController extends AbstractController
{
    #[Route('/equipe', name: 'front_team', methods: ['GET'])]
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render("front/frontteam.html.twig", [
            'controller_name' => 'FrontController'
        ]);
    }

//    methode qui liste les lignes de la table restaurant et les envoie à la vue
    #[Route('/equipe', name: 'front_team', methods: ['GET'])]
    public function team(UserRepository $userRepository): Response
    {
        return $this->render('front/frontteam.html.twig', [
            'equipe' => $userRepository->findAll(),
        ]);
    }

//    methode qui implémente une route vers /admin
    #[Route('/admin', name: 'admin_home', methods: ['GET'])]
    public function admin(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

//    methode qui implémente une route vers /carte, qui liste les données de la table Category et donne le nombre de plats par catégorie
    #[Route('/carte', name: 'front_dishes', methods: ['GET'])]
    public function carte(CategoryRepository $categoryRepository, DishesRepository $dishRepository): Response
    {
        $counts = [];
        foreach ($categoryRepository->findAll() as $category) {
            $counts += [
                $category->getId() => $dishRepository->count(['category' => $category->getId()
            ])];
        }
        return $this->render('front/frontcategory.html.twig', [
            'category' => $categoryRepository->findAll(),
            'counts' => $counts,
        ]);
    }

//    methode qui implémente une route vers /carte/{id}, qui liste les plats de la catégorie sélectionnée
    #[Route('/carte/{id}', name: 'front_dishes_category', methods: ['GET'])]
    public function showCategorie(CategoryRepository $categoryRepository, DishesRepository $dishRepository, $id): Response
    {
        return $this->render('front/frontcategorydetails.html.twig', [
            'categorie' => $categoryRepository->find($id),
            'dishes' => $dishRepository->findBy(['category' => $id]),
        ]);
    }
}
