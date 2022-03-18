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
     * @Route("/", name="login")
     */
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        
        
        $form = array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST'){
            $ldap_dn = "dc=clinique,dc=chatelet,dc=com";
            $ldap_password = "";
            $ldap_con = ldap_connect("192.168.1.59");
        

            if(ldap_bind($ldap_con, $ldap_dn, $ldap_password)){
                echo 'Bind Success';

            
                $form['valide'] = true;
                $email = $_POST['email'];
                $mdp = $_POST['mdp'];
                
                $ip = null;
                //Code pour connecter l'ad
                //if présence dans l'annuaire AD ici

                //if yes 
                    $utilisateur = $utilisateurRepository->findOneByEmail($email);
                    print_r($utilisateur);
                    //Comparaison des MDP hashés via l'ad
                    
                    //Code pour récup l'ip en fonction du proxy : 
                    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                        $ip = $_SERVER['HTTP_CLIENT_IP'];
                    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                    } else {
                        $ip = $_SERVER['REMOTE_ADDR'];
                    }
                    //if $utilisateur->navigateur || $utilisateur->ip !== $ip
                    //if yes
                        //Lancer vérification authentificator QR code si non enregistré ou demander code si enregistré en DB

                    //if no
                        //Rediriger vers l'accueil avec les variables session pour permettre l'affichage des infos utilisateurs.
                
                //if no 

                //blacklist IP
            }
        

        }
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
