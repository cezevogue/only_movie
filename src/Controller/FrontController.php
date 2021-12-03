<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Entity\Movies;
use App\Form\CategoriesType;
use App\Form\MoviesType;
use App\Repository\CategoriesRepository;
use App\Repository\MoviesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/addMovies", name="addMovies")
     */
    public function addMovies(Request $request, EntityManagerInterface $manager)
    {
        // Ici on injecte en dépendance Request (de symfony\component\HttpFoundation) afin de récupérer toutes les données chargées dans nos SUPERGLOBALES ($_POST, $_GET ...), on injecte de même l' EntityManagerInterface (de Doctrine\ORM) afin d'effectuer toute requête d'INSERT, de MODIFICATION ou de SUPPRESSION

        $movie = new Movies();
        // ici on instancie un nouvel objet vide de la classe Movies

        $form = $this->createForm(MoviesType::class, $movie, ['add' => true]);
        // ici on instancie un objet de la classe Form qui attend en argument sur quel formulaire il doit se baser et le liens avec l'entité avec l'entité en second argument affin qu'il puisse effectuer les controles

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()):
            // condition de traitement du formulaire (l'ordre des conditions est impératif)

            $coverFile = $form->get('cover')->getData();
            //dd($coverFile);
            $coverName = date('YmdHis') . uniqid() . $coverFile->getClientOriginalName();
            $coverFile->move($this->getParameter('cover_directory'),
                $coverName);
            //dd($movie);
            $movie->setCover($coverName);
            $manager->persist($movie);
            $manager->flush();


            $this->addFlash('success', 'Ajout effectué avec succès');
            return $this->redirectToRoute('home');


        endif;

        return $this->render('front/addMovies.html.twig', [
            'form' => $form->createView()

        ]);
    }

    /**
     * @Route("/editMovies/{id}", name="editMovies")
     */
    public function editMovies(Movies $movie, Request $request, EntityManagerInterface $manager)
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
            return $this->redirectToRoute('home');
        endif;


        return $this->render('front/editMovies.html.twig', [
            'form' => $form->createView(),
            'movie' => $movie

        ]);
    }

    /**
     * @Route("/addCategories", name="addCategories")
     * @Route("/editCategories/{id}", name="editCategories")
     */
    public function addCategories(Request $request, EntityManagerInterface $manager,CategoriesRepository $repository, $id=null)
    {

        $categories=$repository->findAll();

        if (!$id):
            $categorie = new Categories();
        else:
            $categorie=$repository->find($id);
        endif;


        $form = $this->createForm(CategoriesType::class, $categorie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()):
        $manager->persist($categorie);
        $manager->flush();

        $this->addFlash('success', 'Catégorie ajoutée avec succès' );
        return $this->redirectToRoute('home');
        endif;

        return $this->render('front/addCategories.html.twig', [
           'form'=>$form->createView(),
            'categories'=>$categories
        ]);


    }


    /**
     * @Route("/listMovies", name="listMovies")
     */
    public function listMovies(MoviesRepository $repository)
    {
       $movies=$repository->findAll();

        return $this->render("front/listMovies.html.twig", [
            'movies'=>$movies
        ]);
    }


}
