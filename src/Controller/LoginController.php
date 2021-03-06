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
use App\Service\MailerService;



class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function index(UtilisateurRepository $utilisateurRepository, Request $request, ManagerRegistry $doctrine, MailerService $mailer): Response
    {
        
        $ldap_dn = "dc=clinique,dc=chatelet,dc=com";
        $ldap_password = "";
        $ldap_tree = "OU=Utilisateurs,OU=IT,DC=com, DC=chatelet, DC=clinique";
        $ldap_con = ldap_connect("192.168.1.59");

        ldap_set_option($ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
        ldap_set_option($ldap_con, LDAP_OPT_REFERRALS, 0);
        try {
            ldap_bind($ldap_con, $ldap_dn, $ldap_password);
        } catch (\Exception $e) {
            return $this->redirectToRoute('maintenance');
        }

        $form = array();
        // $mailMsg = $mailer->sendEmail();
        // exit();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            //Code pour connecter l'ad

            if (TRUE === ldap_bind($ldap_con, $ldap_dn, $ldap_password)) {
                echo 'Bind Success';
                   /* $search_filter = '(cn=*)';
                        $attributes = array();
                        $attributes[] = 'givenname';
                        $attributes[] = 'mail';
                        $attributes[] = 'sn';
                        $result = ldap_search($ldap_con, "OU=Utilisateurs,OU=IT,DC=clinique,DC=chatelet,DC=com", $search_filter);
                        $entries = ldap_get_entries($ldap_connection, $result);
                        dump($result);
                        dump($entries);*/
  
                $form['valide'] = true;
                $email = $_POST['email'];
                $mdp = $_POST['mdp'];

                $ip = null;
                
                //if pr??sence dans l'annuaire AD ici (marche pas donc comparaison DB SQL)

                $utilisateur = $utilisateurRepository->findOneByEmail($request->request->get('email'))[0];
                if($email !== $utilisateur->getEmail()){
                    
                    //if yes 
                    //r??cup de l'user cibl??
                    $utilisateur = $utilisateurRepository->findOneByEmail($request->request->get('email'))[0];
                    //Comparaison des MDP hash??s via l'ad et email
                        //pas possible comme AD non fonctionnel
                    //Code pour r??cup l'ip et navigateur en fonction du proxy/ VPN : 
                    $ip = getIPAddress();
                    $navigateur = get_browser_name($_SERVER['HTTP_USER_AGENT']);

                    // if $utilisateur->navigateur || $utilisateur->ip !== $ip
                    if ($ip !== $utilisateur->getIp() || $navigateur !== $utilisateur->getNavigateur()) {
                        // if yes
                        if ($ip !== $utilisateur->getIp() && $navigateur == $utilisateur->getNavigateur()) {
                            // if yes
                            //Envoi d'un mail d'avertissement
                            $mailer->sendEmail("<h3>Une connection via un pc diff??rent de d'habitude ?? votre compte LeChatelet ?? ??t?? enregistr?? <h3>", "Nouvelle connection avec une ip differente", $utilisateur->getEmail());
                        }
                        if ($navigateur !== $utilisateur->getNavigateur()) {
                            //Envoi mail verification
                            $url ="https://localhost/LeChatelet/public/verif/".$utilisateur->getId();
                            $mailer->sendEmail("<h3>Une connection via un navigateur diff??rent de d'habitude ?? votre compte LeChatelet ?? ??t?? enregistr??. Veuiller confirmer qu'il s'agit bien de vous </h3> <br> Via l'url suivant: "."<a href=".$url."> Cliquez ici </a>", "Nouvelle connection avec un navigateur different", $utilisateur->getEmail());
                            //Redirection vers ou?
                            return $this->render('google_register_code/mailSend.html.twig', [
                                'controller_name' => 'MailSend',
                            ]);
                        }
                        
                        // Lancer v??rification authentificator QR code si non enregistr?? ou demander code si enregistr?? en DB

                        $utilisateur = $utilisateurRepository->findOneByEmail($request->request->get('email'));
                        if ($utilisateur == null) {
                            /*//Il n'y a pas d'utilisateur donc en en ajoute un dans la bdd
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
                            return $this->redirectToRoute('app_google_register_code', ['id' => $user[0]->getId()]);*/
                        } else {
                            //Verification google code

                            if ($utilisateur[0]->getGoogleAuthenticatorSecret() == null) {

                                return $this->redirectToRoute('app_google_register_code', ['id' => $utilisateur[0]->getId()]);
                            } else {

                                return $this->redirectToRoute('app_verif', ['id' => $utilisateur[0]->getId()]);
                            }
                        }
                    } else {
                        $_SESSION['USER'] = true;
                        $isLoggedIn = $_SESSION['USER'];
                        return $this->render('home/index.html.twig', array(
                            'controller_name' => 'HomeController',
                            'isLogged' => $isLoggedIn,
                        ));
                    }
                }else{
                

                // if no 
                
                // blacklist IP
                }
            } else {
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


//Return le navigateur utilis?? par l'utilisateur
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

# Premi??re connexion d'un utilisateur (front) 
    #L'utilisateur renseigne c'est identifiants dans 2 champs
    #(back)    #Requ??te LDAP isUserExist(email) : return true/false , si l'??mail de connexion existe dans l'active directory 
    #(bdd linux) Requete sql pour recup la table du bon user (li?? a l'??mail), on v??rifie le mdp + navigateur + ip?? --> ok login sucess
      #nav ou ip pas bon --> google auth
#Si il existe pas on reload la meme page avec un message d'erreur


#cas mdp bon et username --> Google auth 

//MAILER
/*


SI le navigateur est 


*/
