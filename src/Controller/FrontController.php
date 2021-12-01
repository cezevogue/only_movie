<?php

namespace App\Controller;

use App\Entity\Movies;
use App\Form\MoviesType;
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
    public function home()
    {


        return $this->render('front/home.html.twig');

    }

    /**
     * @Route("/addMovies", name="addMovies")
     */
    public function addMovies(Request $request, EntityManagerInterface $manager)
    {
        // Ici on injecte en dépendance Request (de symfony\component\HttpFoundation) afin de récupérer toutes les données chargées dans nos SUPERGLOBALES ($_POST, $_GET ...), on injecte de même l' EntityManagerInterface (de Doctrine\ORM) afin d'effectuer toute requête d'INSERT, de MODIFICATION ou de SUPPRESSION

        $movie = new Movies();
        // ici on instancie un nouvel objet vide de la classe Movies

        $form = $this->createForm(MoviesType::class, $movie);
        // ici on instancie un objet de la classe Form qui attend en argument sur quel formulaire il doit se baser et le liens avec l'entité avec l'entité en second argument affin qu'il puisse effectuer les controles

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()):
            // condition de traitement du formulaire (l'ordre des conditions est impératif)

            $coverFile = $form->get('cover')->getData();
            //dd($coverFile);
            $coverName=date('YmdHis').uniqid().$coverFile->getClientOriginalName();
            $coverFile->move($this->getParameter('cover_directory'),
                $coverName);
            //dd($movie);
            $movie->setCover($coverName);
            $manager->persist($movie);
            $manager->flush();




        endif;

        return $this->render('front/addMovies.html.twig', [
            'form' => $form->createView()

        ]);
    }


}
