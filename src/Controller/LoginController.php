<?php

declare(strict_types=1);


namespace App\Controller;

use App\Entity\Utilisateur;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UtilisateurRepository;


class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
        ]);
    }

    /**
     * @Route("/login/qr", name="qr_code_ga")
     */
    public function displayGoogleAuthenticatorQrCode(GoogleAuthenticatorInterface $googleAuthenticator, UtilisateurRepository $utilisateurRepository): Response
    {
        $user = $utilisateurRepository->find(1);
        if (!($user instanceof GoogleAuthenticatorTwoFactorInterface)) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        return $this->displayQrCode($googleAuthenticator->getQRContent($user));
    }

    private function displayQrCode(string $qrCodeContent): Response
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrCodeContent)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(200)
            ->margin(0)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }
}

 

#Requete lDAP --> Regarde sur l'ad que l'utilisateur existe 

#L'utilisateur est sur la page d'acceuil (Page d'accueil) 

# Première connexion d'un utilisateur (front) 
    #L'utilisateur renseigne c'est identifiants dans 2 champs
    #(back)    #Requête LDAP isUserExist(email) : return true/false , si l'émail de connexion existe dans l'active directory 
    #(bdd linux) Requete sql pour recup la table du bon user (lié a l'émail), on vérifie le mdp + navigateur + ip?? --> ok login sucess
      #nav ou ip pas bon --> google auth
#Si il existe pas on reload la meme page avec un message d'erreur


#cas mdp bon et username --> Google auth 
