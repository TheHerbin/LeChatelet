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
use Symfony\Component\HttpFoundation\RequestStack;


class LoginController extends AbstractController
{
    private $requestStack;


    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/login", name="login")
     */
    public function index(UtilisateurRepository $utilisateurRepository, $twig): Response
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

    public function login($twig, $db)
    {
        //a finir ici avant tout et modifier la db (enlever les deux champs en trop)
        $session = $this->requestStack->getSession();
        $form = array();

        if (isset($_POST['valider'])){
            $form['valide'] = true;
            $email = $_POST['email'];
            $mdp = $_POST['email'];
            $utilisateur = new Utilisateur($db);
            $unUtilisateur = $utilisateur->connect($email);
        }

        echo $twig->render('home/index.html.twig', array());
    }


}
