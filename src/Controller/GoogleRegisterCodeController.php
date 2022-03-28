<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Repository\UtilisateurRepository;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Utilisateur;
use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;

class GoogleRegisterCodeController extends AbstractController
{
    /**
     * @Route("/google/register/code/{id}", name="app_google_register_code")
     */
    public function index(int $id): Response
    {
        dump($id);
        return $this->render('google_register_code/index.html.twig', [
            'controller_name' => 'GoogleRegisterCodeController',
            'id' => $id
        ]);
    }

    /**
     * @Route("/verif/{id}", name="app_verif")
     */
    public function verif(int $id): Response
    {
        return $this->render('google_register_code/codeForm.html.twig', [
            'controller_name' => 'GoogleRegisterCodeController',
            'id' => $id
        ]);
    }

    /**
     * @Route("/validation/{id}", name="app_validation")
     */
    public function validation(int $id, UtilisateurRepository $utilisateur, Request $request, ManagerRegistry $doctrine): Response
    {
        unset($_SESSION['USER']);
        $user = $utilisateur->find($id);
        $code = $request->request->get('codeGoogle');
        if ($user->getGoogleAuthenticatorSecret() == null) {
            $entityManager = $doctrine->getManager();

            $user->setGoogleAuthenticatorSecret($code);
            $entityManager->persist($user);
            
            // actually executes the queries (i.e. the INSERT query)
            $entityManager->flush();
        } else {
            if ($user->getGoogleAuthenticatorSecret() == $code) {
                $_SESSION['USER'] = true;
                $this->redirectToRoute("home");
            } else {
                $this->redirectToRoute("app_verif", ['id' => $id]);
            }
        }
        $_SESSION['USER'] = true;
        return $this->redirectToRoute("home");
    }




    /**
     * @Route("/login/qr/{id}", name="qr_code_ga")
     */
    public function displayGoogleAuthenticatorQrCode(GoogleAuthenticatorInterface $googleAuthenticator, UtilisateurRepository $utilisateurRepository, int $id): Response
    {
        $user = $utilisateurRepository->find($id);
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
