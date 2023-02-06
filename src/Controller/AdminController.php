<?php

namespace App\Controller;

use App\Entity\Allergens;
use App\Entity\Category;
use App\Entity\Dishes;
use App\Entity\User;
use App\Repository\AllergensRepository;
use App\Repository\CategoryRepository;
use App\Repository\DishesRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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

    /**
     * METHODS OR THE DISHES CRUD
     */

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/dishes/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    // Method which import dishes from a JSON file to the database
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

    // Method which permits to list all the dishes on the CRUD
    #[Route('/admin/dishes', name: 'admin_dishes')]
    public function dishList(ManagerRegistry $doctrine, DishesRepository $dishesRepository) {

        $category = $doctrine->getRepository(Category::class)->findOneBy(['name' => 'Plats']);
        $dishes = $dishesRepository->findAll();

        return $this->render('admin/dishes/dish_crud.html.twig',
            array('dishes' => $dishes)
        );
    }

    // Method which permits to add a new dish to the dishes list
    #[Route('/admin/dish/new', name: 'dish_crud')]
    public function dishCrudAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, DishesRepository $dishesRepository, ValidatorInterface $validator): Response
    {
        $dish = new Dishes();

        $calories = array();
        for ($i = 1; $i <= 30; $i++) {
            $calories[10 * $i] = 10 * $i;
        }

        $form = $this->createFormBuilder($dish)
            ->add('name', TextType::class)
            ->add('description', TextType::class)
//            ->add('calories', IntegerType::class)

            ->add('calories', ChoiceType::class, [
                'choices' => $calories,
                'expanded' => false,
                'multiple' => false,
            ])
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

        return $this->render('admin/dishes/dish_new.html.twig', [
            'form' => $form->createView(),
            'errors' => $errors,
        ]);
    }

    // Method which permits to edit on of the dishes in the list
    #[Route('/admin/dish/{id}/edit', name: 'dish_edit')]
    public function dishEditAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, DishesRepository $dishesRepository, $id): Response
    {
        $dish = $dishesRepository->find($id);

        $calories = array();
        for ($i = 1; $i <= 30; $i++) {
            $calories[10 * $i] = 10 * $i;
        }

        $form = $this->createFormBuilder($dish)
            ->add('name', TextType::class)
            ->add('description', TextType::class)
            ->add('calories', ChoiceType::class, [
                'choices' => $calories,
                'expanded' => false,
                'multiple' => false,
            ])
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

        return $this->render('admin/dishes/dish_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Method which permits to delete one of the dishes in the list
    #[Route('/admin/dish/{id}/delete', name: 'dish_delete')]
    public function dishDeleteAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, DishesRepository $dishesRepository, $id): Response
    {
        $dish = $dishesRepository->find($id);
        $entityManager->remove($dish);
        $entityManager->flush();

        echo "Dish deleted !";
        return $this->redirectToRoute('admin_dishes');
    }

    // Method which permits to show the details of one of the dishes in the list
    #[Route('/admin/dish/{id}/details', name: 'dish_show')]
    public function dishShowAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, DishesRepository $dishesRepository, $id): Response
    {
        $dish = $dishesRepository->find($id);
        return $this->render('admin/dishes/dish_show.html.twig', [
            'dish' => $dish,
        ]);
    }


    /**
     * METHODS FOR THE USERS CRUD
     */

    // Method which permits to show the list of users
    #[Route('/admin/users', name: 'admin_users')]
    public function usersAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('admin/users/user_crud.html.twig', [
            'users' => $users,
        ]);
    }

    // Method which permits to create a new user
    #[Route('/admin/user/new', name: 'user_new')]
    public function userNewAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, UserRepository $userRepository): Response
    {
        $user = new User();

        $form = $this->createFormBuilder($user)
            ->add('username', TextType::class)
            ->add('email', EmailType::class)
            ->add('firstname', TextType::class)
            ->add('lastname', TextType::class)
            ->add('jobtitle', TextType::class)
            ->add('enabled', IntegerType::class, [
                'data' => 1,
            ])
            ->add('createdat', DateTimeType::class, [
                'data' => new \DateTime(),
            ])
            ->add('updatedat', DateTimeType::class, [
                'data' => new \DateTime(),
            ])
            ->add('save', SubmitType::class, ['label' => 'Create User'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $entityManager->persist($user);
            $entityManager->flush();

            echo "User created !";
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/user_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    // Method which permits to edit one of the users in the list
    #[Route('/admin/user/{id}/edit', name: 'user_edit')]
    public function userEditAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, UserRepository $userRepository, $id): Response
    {
        $user = $userRepository->find($id);

        $form = $this->createFormBuilder($user)
            ->add('username', TextType::class)
            ->add('email', EmailType::class)
            ->add('firstname', TextType::class)
            ->add('lastname', TextType::class)
            ->add('jobtitle', TextType::class)
            ->add('enabled', IntegerType::class, [
                'data' => 1,
            ])
            ->add('createdat', DateTimeType::class, [
                'data' => new \DateTime(),
            ])
            ->add('updatedat', DateTimeType::class, [
                'data' => new \DateTime(),
            ])
            ->add('save', SubmitType::class, ['label' => 'Edit User'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $entityManager->persist($user);
            $entityManager->flush();

            echo "User edited !";
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/user_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    // Method which permits to delete one of the users in the list
    #[Route('/admin/user/{id}/delete', name: 'user_delete')]
    public function userDeleteAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, UserRepository $userRepository, $id): Response
    {
        $user = $userRepository->find($id);
        $entityManager->remove($user);
        $entityManager->flush();

        echo "User deleted !";
        return $this->redirectToRoute('admin_users');
    }


    // Method which permits to show the details of one of the users in the list
    #[Route('/admin/user/{id}/details', name: 'user_show')]
    public function userShowAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, UserRepository $userRepository, $id): Response
    {
        $user = $userRepository->find($id);
        return $this->render('admin/users/user_show.html.twig', [
            'user' => $user,
        ]);
    }


    /**
     * METHODS FOR THE CATEGORIES CRUD
     */

    // Method which permits to show the list of categories
    #[Route('/admin/categories', name: 'admin_categories')]
    public function categoriesAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, CategoryRepository $categoriesRepository): Response
    {
        $categories = $categoriesRepository->findAll();
        return $this->render('admin/categories/category_crud.html.twig', [
            'categories' => $categories,
        ]);
    }

    // Method which permits to create a new category
    #[Route('/admin/category/new', name: 'category_new')]
    public function categoryNewAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, CategoryRepository $categoriesRepository): Response
    {
        $category = new Category();

        $form = $this->createFormBuilder($category)
            ->add('name', TextType::class)
            ->add('image', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Create Category'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            $entityManager->persist($category);
            $entityManager->flush();

            echo "Category created !";
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/category_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Method which permits to edit one of the categories in the list
    #[Route('/admin/category/{id}/edit', name: 'category_edit')]
    public function categoryEditAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, CategoryRepository $categoriesRepository, $id): Response
    {
        $category = $categoriesRepository->find($id);

        $form = $this->createFormBuilder($category)
            ->add('name', TextType::class)
            ->add('image', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Edit Category'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();
            $entityManager->persist($category);
            $entityManager->flush();

            echo "Category edited !";
            return $this->redirectToRoute('admin_categories');
        }

        return $this->render('admin/categories/category_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Method which permits to delete one of the categories in the list
    #[Route('/admin/category/{id}/delete', name: 'category_delete')]
    public function categoryDeleteAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, CategoryRepository $categoriesRepository, $id): Response
    {
        $category = $categoriesRepository->find($id);
        $entityManager->remove($category);
        $entityManager->flush();

        echo "Category deleted !";
        return $this->redirectToRoute('admin_categories');
    }

    // Method which permits to show the details of one of the categories in the list
    #[Route('/admin/category/{id}/details', name: 'category_show')]
    public function categoryShowAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, CategoryRepository $categoriesRepository, $id): Response
    {
        $category = $categoriesRepository->find($id);
        return $this->render('admin/categories/category_show.html.twig', [
            'category' => $category,
        ]);
    }


    /**
     * METHODS FOR THE ALLERGENS CRUD
     */

    // Method which permits to show the list of allergens
    #[Route('/admin/allergens', name: 'admin_allergens')]
    public function allergensAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, AllergensRepository $allergensRepository): Response
    {
        $allergens = $allergensRepository->findAll();
        return $this->render('admin/allergens/allergen_crud.html.twig', [
            'allergens' => $allergens,
        ]);
    }

    // Method which permits to create a new allergen
    #[Route('/admin/allergen/new', name: 'allergen_new')]
    public function allergenNewAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, AllergensRepository $allergensRepository): Response
    {
        $allergen = new Allergens();

        $form = $this->createFormBuilder($allergen)
            ->add('name', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Create Allergen'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $allergen = $form->getData();
            $entityManager->persist($allergen);
            $entityManager->flush();

            echo "Allergen created !";
            return $this->redirectToRoute('admin_allergens');
        }

        return $this->render('admin/allergens/allergen_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Method which permits to edit one of the allergens in the list
    #[Route('/admin/allergen/{id}/edit', name: 'allergen_edit')]
    public function allergenEditAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, AllergensRepository $allergensRepository, $id): Response
    {
        $allergen = $allergensRepository->find($id);

        $form = $this->createFormBuilder($allergen)
            ->add('name', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Edit Allergen'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $allergen = $form->getData();
            $entityManager->persist($allergen);
            $entityManager->flush();

            echo "Allergen edited !";
            return $this->redirectToRoute('admin_allergens');
        }

        return $this->render('admin/allergens/allergen_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Method which permits to delete one of the allergens in the list
    #[Route('/admin/allergen/{id}/delete', name: 'allergen_delete')]
    public function allergenDeleteAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, AllergensRepository $allergensRepository, $id): Response
    {
        $allergen = $allergensRepository->find($id);
        $entityManager->remove($allergen);
        $entityManager->flush();

        echo "Allergen deleted !";
        return $this->redirectToRoute('admin_allergens');
    }

    // Method which permits to show the details of one of the allergens in the list
    #[Route('/admin/allergen/{id}/details', name: 'allergen_show')]
    public function allergenShowAction(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, AllergensRepository $allergensRepository, $id): Response
    {
        $allergen = $allergensRepository->find($id);
        return $this->render('admin/allergens/allergen_show.html.twig', [
            'allergen' => $allergen,
        ]);
    }
}