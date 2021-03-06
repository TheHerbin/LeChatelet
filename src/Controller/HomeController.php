<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index(): Response
    {
        //returns the view 
        $isLoggedIn = isset($_SESSION['USER']);
        //var_dump($isLoggedIn);
        
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'isLogged' => $isLoggedIn
        ]);
    }

    /**
     * @Route("/deconnection", name="deconnection")
     */
    public function deconnection(): Response
    {
        session_unset();
        return $this->redirectToRoute("login"); 
    }
}
