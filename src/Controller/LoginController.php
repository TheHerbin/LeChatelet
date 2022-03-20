<?php

declare(strict_types=1);


namespace App\Controller;

use App\Entity\Utilisateur;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;


class LoginController extends AbstractController
{
    /**
     * @Route("/", name="login")
     */
    public function index(UtilisateurRepository $utilisateurRepository, Request $request, ManagerRegistry $doctrine): Response
    {
        $ldap_dn = "dc=clinique,dc=chatelet,dc=com";
        $ldap_password = "";
        $ldap_tree = "OU=SBSUsers,OU=Users,OU=MyBusiness,DC=myDomain,DC=local";
        $ldap_con = ldap_connect("192.168.1.59");

        ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldap_con, LDAP_OPT_REFERRALS, 0); 
        try{
            ldap_bind($ldap_con, $ldap_dn, $ldap_password);
        }catch(\Exception $e){
            return $this->redirectToRoute('maintenance');
        }

        $form = array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
           
            //Code pour connecter l'ad
        
            if (TRUE === ldap_bind($ldap_con, $ldap_dn, $ldap_password)) {
                echo 'Bind Success';
                /*   $search_filter = '(cn=)';
                        $attributes = array();
                        $attributes[] = 'givenname';
                        $attributes[] = 'mail';
                        $attributes[] = 'sn';
                        $result = ldap_search($ldap_con, $ldap_dn, $search_filter, $attributes);
                        $entries = ldap_get_entries($ldap_connection, $result);
        
                        print_r($result);
                        print_r($entries);
                        */

                $form['valide'] = true;
                $email = $_POST['email'];
                $mdp = $_POST['mdp'];

                $ip = null;

                //if présence dans l'annuaire AD ici
                //A FAIRE
                    //if yes 
                    //récup de l'user ciblé
                    $utilisateur = $utilisateurRepository->findOneByEmail($request->request->get('email'))[0];
                    //Comparaison des MDP hashés via l'ad et email
                
                    //Code pour récup l'ip et navigateur en fonction du proxy/ VPN : 
                    $ip = getIPAddress();
                    $navigateur = get_browser_name($_SERVER['HTTP_USER_AGENT']);

                    // if $utilisateur->navigateur || $utilisateur->ip !== $ip
                    if($ip !== $utilisateur->getIp() || $navigateur !== $utilisateur->getNavigateur()){
                        // if yes
                        // Lancer vérification authentificator QR code si non enregistré ou demander code si enregistré en DB

                            $utilisateur = $utilisateurRepository->findOneByEmail($request->request->get('email'));
                            if ($utilisateur == null) {
                                //Il n'y a pas d'utilisateur donc en en ajoute un dans la bdd
                                $entityManager = $doctrine->getManager();
    
                                $user = new Utilisateur();
                                $user->setEmail($request->request->get('email'));
    
                                //On initialise dans la bdd le navigateur par default
                                $user->setNavigateur(get_browser_name($_SERVER['HTTP_USER_AGENT']));
    
                                $user->setIp(getIPAddress());
    
                                // tell Doctrine you want to (eventually) save the Product (no queries yet)
                                $entityManager->persist($user);
    
                                // actually executes the queries (i.e. the INSERT query)
                                $entityManager->flush();
    
                                $user = $utilisateurRepository->findOneByEmail(($request->request->get('email')));
    
                                //redirection avec parametre mail
                                return $this->redirectToRoute('app_google_register_code', ['id' => $user[0]->getId()]);
                            } else {
                                //Verification google code
    
                                if ($utilisateur[0]->getGoogleAuthenticatorSecret() == null) {
    
                                    return $this->redirectToRoute('app_google_register_code', ['id' => $utilisateur[0]->getId()]);
                                } else {
    
                                    return $this->redirectToRoute('app_verif', ['id' => $utilisateur[0]->getId()]);
                                }
                            }
                        
                    }else{
                        $_SESSION['USER'] = true ;
                        $isLoggedIn = $_SESSION['USER'];
                        return $this->render('home/index.html.twig', array(
                            'controller_name' => 'HomeController',
                            'isLogged' => $isLoggedIn
                        ));
                    }
                    // if no
                    // Rediriger vers l'accueil avec les variables session pour permettre l'affichage des infos utilisateurs.

                // if no 
                //return $this->redirect('https://google.fr');
                // blacklist IP
            }else{
                return $this->redirectToRoute('maintenance');
            }

            
        }
        $isLoggedIn = isset($_SESSION['USER']);
        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
            'isLogged' => $isLoggedIn
        ]);
    }
}


//Return le navigateur utilisé par l'utilisateur
function get_browser_name($user_agent)
{
    $t = strtolower($user_agent);
    $t = " " . $t;
    if (strpos($t, 'opera') || strpos($t, 'opr/')) return 'Opera';
    elseif (strpos($t, 'edge')) return 'Edge';
    elseif (strpos($t, 'chrome')) return 'Chrome';
    elseif (strpos($t, 'safari')) return 'Safari';
    elseif (strpos($t, 'firefox')) return 'Firefox';
    elseif (strpos($t, 'msie') || strpos($t, 'trident/7')) return 'Internet Explorer';
    return 'Unkown';
}

function getIPAddress()
{
    //whether ip is from the share internet  
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    //whether ip is from the proxy  
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    //whether ip is from the remote address  
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
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

//MAILER
/*


SI le navigateur est 


*/
