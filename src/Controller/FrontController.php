<?php

namespace App\Controller;

use App\Entity\Actors;
use App\Entity\Cart;
use App\Entity\Categories;
use App\Entity\Movies;
use App\Entity\Orders;
use App\Entity\Pricing;
use App\Entity\Reviews;
use App\Form\ActorsType;
use App\Form\CategoriesType;
use App\Form\MoviesType;
use App\Form\PricingType;
use App\Repository\ActorsRepository;
use App\Repository\CategoriesRepository;
use App\Repository\MoviesRepository;
use App\Repository\PricingRepository;
use App\Repository\ReviewsRepository;
use App\Repository\UsersRepository;
use App\Service\Panier\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class FrontController extends AbstractController
{

    /**
     * @Route("/", name="home")
     */
    public function home(MoviesRepository $repository)
    {

        //ici on appelle le repository de Movies afin d'effectuer une requete de SELECT (affichage)
        // on recupere toutes les entrées de movies avec la méthode findAll()
        $movies = $repository->findAll();


        return $this->render('front/home.html.twig', [
            'movies' => $movies
        ]);

    }


    /**
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @Route("/usersMovies", name="usersMovies")
     * @Route("/addActor/{param}", name="addActor")
     * @IsGranted("ROLE_USER")
     */
    public function usersMovies(Request $request, EntityManagerInterface $manager, $param = null)
    {
        $affich = false;
        if ($param):
            // dd('coucou');
            $affich = true;
        endif;


        $movie = new Movies();
        $form = $this->createForm(MoviesType::class, $movie, ['add' => true]);
        $form->handleRequest($request);
        $actor = new Actors();
        $formActor = $this->createForm(ActorsType::class, $actor);
        $formActor->handleRequest($request);

        if ($formActor->isSubmitted() && $formActor->isValid()):
            $manager->persist($actor);
            $manager->flush();
            $affich = false;
            return $this->redirectToRoute('usersMovies', ['affich' => $affich]);

        endif;

        if ($form->isSubmitted() && $form->isValid()):
            $coverFile = $form->get('cover')->getData();
            //dd($coverFile);
            $coverName = date('YmdHis') . uniqid() . $coverFile->getClientOriginalName();
            $coverFile->move($this->getParameter('cover_directory'),
                $coverName);
            //dd($movie);
            $movie->setCover($coverName);
            $movie->setCreatedBy($this->getUser());
            $manager->persist($movie);
            $manager->flush();

            return $this->redirectToRoute('listUsersMovies');
        endif;


        return $this->render('front/usersMovies.html.twig', [
            'form' => $form->createView(),
            'formActor' => $formActor->createView(),
            'affich' => $affich
        ]);

    }


    /**
     * @Route("/listUsersMovies", name="listUsersMovies")
     * @IsGranted("ROLE_USER")
     */
    public function listUsersMovies(MoviesRepository $repository)
    {
        $movies = $repository->findBy(['CreatedBy' => $this->getUser()]);

        return $this->render('front/listUsersMovies.html.twig', [
            'movies' => $movies
        ]);
    }

    /**
     * @Route("/detailMovie/{id}", name="detailMovie")
     * @Route("/formReview/{id}/{param}", name="formReview")
     */
    public function detailMovie(MoviesRepository $repository, ReviewsRepository $reviewsRepository, Request $request, EntityManagerInterface $manager, $id = null, $param = null)
    {
        $affich = false;
        if ($param):
            $affich = true;
        endif;

        $movie = $repository->find($id);
        $reviews = $reviewsRepository->findBy(['movie' => $movie], ['publish_date' => 'DESC'], 5);
        //dd($reviews);
        $user = $this->getUser();
        $result = $reviewsRepository->findBy(['createdBy' => $user, 'movie' => $movie]);
        //dd($result);
        if (count($result) == 0):
            $review = new Reviews();

        else:
            $affich = false;
            $this->addFlash('danger', 'Vous avez déjà votez sur ce film');
        endif;
        if (!empty($_POST)):


            $comment = $request->request->get('review');
            $rating = $request->request->get('rating');


            $review->setCreatedBy($user)->setComment($comment)->setPublishDate(new \DateTime())->setRating($rating)->setMovie($movie);
            $manager->persist($review);
            $manager->flush();
            $this->addFlash('success', 'Merci pour votre contribution');
            return $this->redirectToRoute('detailMovie', ['id' => $id]);


        endif;


        return $this->render('front/detailMovie.html.twig', [
            'movie' => $movie,
            'affich' => $affich,
            'reviews' => $reviews
        ]);

    }

    /**
     * @Route("/reviews/{id}", name="reviews")
     */
    public function reviews(ReviewsRepository $reviewsRepository, MoviesRepository $repository, $id)
    {
        $movie = $repository->find($id);
        $reviews = $reviewsRepository->findBy(['movie' => $movie], ['publish_date' => 'DESC']);

        return $this->render('front/reviews.html.twig', [
            'reviews' => $reviews
        ]);
    }



    /**
     * @Route("/deleteUsersMovies/{id}", name="deleteUsersMovies")
     * @IsGranted("ROLE_USER")
     */
    public function deleteUsersMovies(Movies $movies, EntityManagerInterface $manager)
    {
        $this->addFlash('success', $movies->getTitle() . ' supprimé avec succès');
        $manager->remove($movies);
        $manager->flush();
        return $this->redirectToRoute('listUsersMovies');

    }

    /**
     * @Route("/editUsersMovies/{id}", name="editUsersMovies")
     * @IsGranted("ROLE_USER")
     */
    public function editUsersMovies(Movies $movie, Request $request, EntityManagerInterface $manager)
    {

        $form = $this->createForm(MoviesType::class, $movie, ['update' => true]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()):
            $coverFile = $form->get('coverUpdate')->getData();
            // si on a une photo en modification
            if ($coverFile):
                //alors on renomme le fichier
                $coverName = date('dmYHis') . uniqid() . $coverFile->getClientOriginalName();
                // puis on l'upload dans notre dossier 'uploads'
                $coverFile->move($this->getParameter('cover_directory'), $coverName);
                // on supprime l'ancienne photo présente dans le dossier d'uploads
                unlink($this->getParameter('cover_directory') . '/' . $movie->getCover());
                // on affecte le nouveau nom de fichier à notre objet
                $movie->setCover($coverName);

            endif;
            // on prépare la requête et la gardons en mémoire
            $manager->persist($movie);
            // on execute la ou les requetes
            $manager->flush();
            $this->addFlash('success', 'Modification effectuée avec succès');
            return $this->redirectToRoute('listUsersMovies');
        endif;


        return $this->render('front/editUsersMovies.html.twig', [
            'form' => $form->createView(),
            'movie' => $movie

        ]);
    }




    /**
     * @Route("/addCart/{id}/{route}", name="addCart")
     *
     */
    public function addCart($id, PanierService $panierService, $route)
    {
        $panierService->add($id);

        ($panierService->getFullCart());

        if ($route == 'home'):
            return $this->redirectToRoute('home');
        else:
            return $this->redirectToRoute('fullCart');
        endif;

    }

    /**
     * @Route("/removeCart/{id}", name="removeCart")
     *
     */
    public function removeCart($id, PanierService $panierService)
    {
        $panierService->remove($id);
        return $this->redirectToRoute('fullCart');


    }

    /**
     * @Route("/deleteCart/{id}", name="deleteCart")
     *
     */
    public function deleteCart($id, PanierService $panierService)
    {
        $panierService->delete($id);
        return $this->redirectToRoute('fullCart');


    }

    /**
     * @Route("/fullCart", name="fullCart")
     * @Route("/order/{param}", name="order")
     *
     */
    public function fullCart(PanierService $panierService, PricingRepository $repository, $param = null)
    {
        $pricings = $repository->findAll();
        $affich = false;
        if ($param):
            $affich = true;
        endif;


        $fullCart = $panierService->getFullCart();

        return $this->render('front/fullCart.html.twig', [
            'fullCart' => $fullCart,
            'affich' => $affich,
            'pricings' => $pricings
        ]);

    }


    /**
     *
     * @Route("/finalOrder/{id}", name="finalOrder")
     * @IsGranted("ROLE_USER")
     */
    public function order(PricingRepository $repository, PanierService $panierService, EntityManagerInterface $manager, $id = null)
    {
        if (!empty($_GET['pricing'])):
            $pricing = $repository->find($_GET['pricing']);
            $price = $pricing->getPrice();
            $panier = $panierService->getFullCart();
            $count = 0;
            foreach ($panier as $item):
                $count += $item['quantity'];
            endforeach;
            $total = $count * $price;
            $affich = true;
            return $this->render('front/fullCart.html.twig', [
                'affich' => $affich,
                'total' => $total,
                'pricings' => "",
                'price' => $_GET['pricing']

            ]);

        endif;

        if ($id):
            $forfait = $repository->find($id);
            $orders = new Orders();
            $orders->setDate(new \DateTime())->setPricing($forfait)->setUser($this->getUser());
            $panier = $panierService->getFullCart();

            foreach ($panier as $item):

                $cart = new Cart();
                $cart->setOrders($orders)->setMovies($item['movie'])->setQuantity($item['quantity']);
                $manager->persist($cart);
                    $panierService->delete($item['movie']->getId());
            endforeach;
            $manager->persist($orders);
            $manager->flush();
            $this->addFlash('success', "Merci pour votre achat");
            return $this->redirectToRoute('home');


        endif;


    }


}
