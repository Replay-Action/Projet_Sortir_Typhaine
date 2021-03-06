<?php

namespace App\Controller;

use App\Entity\Images;
use App\Entity\Utilisateur;
use App\Form\RegistrationType;
use App\Form\UtilisateurType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UtilisateurController extends AbstractController
{


    /**
     * @route("/login", name="user_login")
     */
    public function login(AuthenticationUtils $utils): Response
    {
        return $this->render("utilisateur/login.html.twig", [
            'loginError' => $utils->getLastAuthenticationError(),
            'loginUsername' => $utils->getLastUsername(),

        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout()
    {
        $user = $this->getUser();
        return $this->render('utilisateur/login.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/register", name="user_register")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     */
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $encoder,
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $utilisateur = new Utilisateur;
        $registrationForm = $this->createForm(RegistrationType::class, $utilisateur);
        $registrationForm->handleRequest($request);

        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            $utilisateur->setPassword($encoder->encodePassword($utilisateur, $utilisateur->getPassword()));
            $entityManager->persist($utilisateur);
            $entityManager->flush();
            $this->addFlash('success', 'Inscription r??ussie');
            return $this->redirectToRoute('user_login');
        }

        return $this->render('utilisateur/register.html.twig', [
            'controller_name' => 'UtilisateurController',
            'registrationForm' => $registrationForm->createView(),
        ]);
    }

    /**
     * @Route("/gestion/{id}", name="utilisateur_gestion", methods={"GET","POST"})
     */
    public function gestionUtilisateur(int $id, Request $request, EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $utilisateur = new Utilisateur();
        $repository = $em->getRepository(Utilisateur::class);
        $utilisateur = $repository->findOneBy(['id' => $id]);
        $utilisateurForm = $this->createForm(UtilisateurType::class, $utilisateur);
        $utilisateurForm->handleRequest($request);


        if ($utilisateurForm->isSubmitted()) {
            $images = $utilisateurForm->get('images')->getData();
            foreach ($images as $image) {
                $fichier = md5(uniqid()) . '.' . $image->guessExtension();
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );
                $img = new Images();
                $img->setName($fichier);
                $utilisateur->addImage($img);
            }
            $utilisateur->setPassword($encoder->encodePassword($utilisateur, $utilisateur->getPassword()));
            $em->persist($utilisateur);
            $em->flush();
            $this->addFlash('success', 'Le profil a bien ??t?? modifi??');
            return $this->redirectToRoute('sortie_accueil');
        }
        return $this->render('utilisateur/gestion.html.twig', [
            "utilisateurForm" => $utilisateurForm->createView(),
            'user' => $user,
        ]);
    }


    /**
     * @Route("/afficher/{id}", name="utilisateur_afficher", requirements={"id":"\d+"})
     */
    public function afficher(int $id, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $utilisateur = $this->getDoctrine()->getRepository(Utilisateur::class)->find($id);
        return $this->render('utilisateur/afficher.html.twig', [
            'utilisateur' => $utilisateur,
            'user' => $user,
        ]);

    }
}

