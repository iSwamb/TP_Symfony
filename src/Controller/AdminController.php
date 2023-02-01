<?php

namespace App\Controller;

use App\Entity\Allergens;
use App\Entity\Category;
use App\Entity\Dishes;
use App\Entity\User;
use App\Repository\DishesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Length;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/import-dishes', name: 'import_dish')]
    public function importDishesAction(EntityManagerInterface $em) {
        $json = file_get_contents('C:\Users\clem9\Documents\exercice_symfony\myProject\config\dishes.json');
        $data = json_decode($json,true);

        $dishRepo = $em->getRepository(Dishes::class);
        $categoryRepo = $em->getRepository(Category::class);
        $allergenRepo = $em->getRepository(Allergens::class);
        $userRepo = $em->getRepository(User::class);

        foreach(["desserts","entrees","plats" ] as $type)
        {
            $category = $categoryRepo->findOneBy(array("name" => ucfirst($type)));
            // If category does not exist, create it.
            if ($category && isset($data[$type])) {
                foreach ($data[$type] as $dishArray) {
                    $dish = $dishRepo->findOneBy(
                        array("name" => $dishArray["name"])
                    );
                    if (!$dish) {
                        $dish = new Dishes(); // Insert
                    }
                    $user = $userRepo->findAll();
                    $dish->setName($dishArray["name"]);
                    $dish->setCategory($category);
                    $dish->setDescription($dishArray["text"]);
                    $dish->setCalories($dishArray["calories"]);
                    $dish->setImage($dishArray["image"]);
                    $dish->setSticky($dishArray["sticky"]);
                    $dish->setPrice($dishArray["price"]);
                    $dish->setUser($user[0]);
                    foreach ($dishArray["allergens"] as $allergenArray) {
                        $allergen = $allergenRepo->findOneBy(
                            array("name" => $allergenArray)
                        );
                        if (!$allergen) {
                            $allergen = new Allergens();
                        }
                        $allergen->setName($allergenArray);
                        $em->persist($allergen);
                        $dish->addAllergen($allergen);
                        // Update if exist, insert if not.
                    }
                    $em->persist($dish);
                    $em->flush();
                }
            }
        }
        return $this->redirectToRoute('front_dishes');
    }

    #[Route('/admin/dishes', name: 'admin_dishes')]
    public function dishList(ManagerRegistry $doctrine, DishesRepository $dishesRepository) {

        $category = $doctrine->getRepository(Category::class)->findOneBy(['name' => 'Plats']);
        $dishes = $dishesRepository->findAll();

        return $this->render('admin/dish_crud.html.twig',
            array('dishes' => $dishes)
        );
    }

    #[Route('/admin/dish/new', name: 'dish_crud')]
    public function dishCrudAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, DishesRepository $dishesRepository, ValidatorInterface $validator): Response
    {
        $dish = new Dishes();

        $form = $this->createFormBuilder($dish)
            ->add('name', TextType::class)
            ->add('description', TextType::class)
            ->add('calories', IntegerType::class)
            ->add('image', TextType::class, [
                'data' => 'https://via.placeholder.com/360x225'
            ])
            ->add('sticky', CheckboxType::class)
            ->add('price', IntegerType::class)
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
            ])
            ->add('allergens', EntityType::class, [
                'class' => Allergens::class,
                'choice_label' => 'name',
                'multiple' => true,
            ])
            ->add('save', SubmitType::class, ['label' => 'Create Dish'])
            ->getForm();

        $form->handleRequest($request);

//        if ($form->isSubmitted() && $form->isValid()) {
//            $dish = $form->getData();
//            $entityManager->persist($dish);
//            $entityManager->flush();
//
//            echo "Dish created !";
//            return $this->redirectToRoute('admin_dishes');
//        }
//        else {
//            $description = $form["description"]->getData();
//            $errors = $validator->validate($description, [
//                new Length([
//                    'min' => 10,
//                    'max' => 100,
//                    'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
//                    'maxMessage' => 'La description ne peut pas contenir plus de {{ limit }} caractères',
//                ]),
//            ]);
//        }

        if ($form->isSubmitted() && $form->isValid()) {
            $dish = $form->getData();
            $entityManager->persist($dish);
            $entityManager->flush();

            echo "Dish created !";
            return $this->redirectToRoute('admin_dishes');
        }
        else {
            $description = $form["description"]->getData();
            $errors = $validator->validate($description, [
                new Length([
                    'min' => 10,
                    'max' => 100,
                    'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                    'maxMessage' => 'La description ne peut pas contenir plus de {{ limit }} caractères',
                ]),
            ]);
        }

        return $this->render('admin/dish_new.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors,
        ]);
    }

    #[Route('/admin/dish/{id}/edit', name: 'dish_edit')]
    public function dishEditAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, DishesRepository $dishesRepository, $id): Response
    {
        $dish = $dishesRepository->find($id);

        $form = $this->createFormBuilder($dish)
            ->add('name', TextType::class)
            ->add('description', TextType::class)
            ->add('calories', IntegerType::class)
            ->add('image', TextType::class)
            ->add('sticky', CheckboxType::class)
            ->add('price', IntegerType::class)
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
            ])
            ->add('allergens', EntityType::class, [
                'class' => Allergens::class,
                'choice_label' => 'name',
                'multiple' => true,
            ])
            ->add('save', SubmitType::class, ['label' => 'Edit Dish'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dish = $form->getData();
            $entityManager->persist($dish);
            $entityManager->flush();

            echo "Dish edited !";
            return $this->redirectToRoute('admin_dishes');
        }

        return $this->render('admin/dish_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/dish/{id}/delete', name: 'dish_delete')]
    public function dishDeleteAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, DishesRepository $dishesRepository, $id): Response
    {
        $dish = $dishesRepository->find($id);
        $entityManager->remove($dish);
        $entityManager->flush();

        echo "Dish deleted !";
        return $this->redirectToRoute('admin_dishes');
    }

    #[Route('/admin/dish/{id}/details', name: 'dish_show')]
    public function dishShowAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, DishesRepository $dishesRepository, $id): Response
    {
        $dish = $dishesRepository->find($id);
        return $this->render('admin/dish_show.html.twig', [
            'dish' => $dish,
        ]);
    }
}
