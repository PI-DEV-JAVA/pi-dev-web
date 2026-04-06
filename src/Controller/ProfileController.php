<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function view(): Response
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!$profile || !$profile->isProfileCompleted()) {
            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('front/account/profile.html.twig', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $em->persist($profile);
        }

        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile picture upload
            $pictureFile = $form->get('profilePicture')->getData();
            if ($pictureFile) {
                $newFilename = 'avatar-' . $user->getId() . '-' . uniqid() . '.' . $pictureFile->guessExtension();
                try {
                    $pictureFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                        $newFilename
                    );
                    $profile->setProfilePicturePath('/uploads/avatars/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de la photo.');
                }
            }

            // Handle CV upload
            $cvFile = $form->get('cvFile')->getData();
            if ($cvFile) {
                $newFilename = 'cv-' . $user->getId() . '-' . uniqid() . '.pdf';
                try {
                    $cvFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/cvs',
                        $newFilename
                    );
                    $profile->setCvPath('/uploads/cvs/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du CV.');
                }
            }

            // Mark as completed if required fields are filled
            if ($profile->getFirstName() && $profile->getLastName()) {
                $profile->setProfileCompleted(true);
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('front/account/profile_edit.html.twig', [
            'form' => $form->createView(),
            'profile' => $profile,
        ]);
    }
}
