<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Bookmark;
use App\Entity\Offer;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OfferController extends AbstractController
{
    #[Route('/offers', name: 'app_offers')]
    public function list(EntityManagerInterface $em, Request $request): Response
    {
        $qb = $em->getRepository(Offer::class)->createQueryBuilder('o')
            ->orderBy('o.publishDate', 'DESC');

        $search = $request->query->get('q', '');
        if ($search) {
            $qb->andWhere('o.title LIKE :search OR o.description LIKE :search OR o.department LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $contractType = $request->query->get('contract');
        if ($contractType) {
            $qb->andWhere('o.contractType = :ct')->setParameter('ct', $contractType);
        }

        $offers = $qb->getQuery()->getResult();

        return $this->render('front/offers/list.html.twig', [
            'offers' => $offers,
            'search' => $search,
            'contractFilter' => $contractType,
        ]);
    }

    #[Route('/offers/{id}', name: 'app_offer_detail', requirements: ['id' => '\d+'])]
    public function detail(Offer $offer, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $existingApp = null;
        $bookmarked = false;
        if ($user) {
            $existingApp = $em->getRepository(Application::class)->findOneBy([
                'user' => $user, 'offer' => $offer,
            ]);
            $bookmarked = $em->getRepository(Bookmark::class)->findOneBy([
                'user' => $user, 'offer' => $offer,
            ]) !== null;
        }

        return $this->render('front/offers/detail.html.twig', [
            'offer' => $offer,
            'alreadyApplied' => $existingApp !== null,
            'bookmarked' => $bookmarked,
            'application' => $existingApp,
        ]);
    }

    #[Route('/offers/{id}/apply', name: 'app_offer_apply', methods: ['POST'])]
    public function apply(Offer $offer, Request $request, EntityManagerInterface $em, \App\Service\WorkflowEngine $workflowEngine): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $existing = $em->getRepository(Application::class)->findOneBy([
            'user' => $user, 'offer' => $offer,
        ]);
        if ($existing) {
            $this->addFlash('error', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('app_offer_detail', ['id' => $offer->getId()]);
        }

        $application = new Application();
        $application->setUser($user);
        $application->setOffer($offer);
        $application->setMotivationLetter($request->request->get('motivation', ''));
        $application->setStatus('Nouvelle');

        $cvFile = $request->files->get('cv');
        if ($cvFile) {
            $newFilename = 'app-cv-' . $user->getId() . '-' . uniqid() . '.' . $cvFile->guessExtension();
            $cvFile->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/cvs',
                $newFilename
            );
            $application->setCvFilePath('/uploads/cvs/' . $newFilename);
        }

        $offer->setApplicationsReceived($offer->getApplicationsReceived() + 1);
        $em->persist($application);
        $em->flush();

        // Trigger Automation Workflow
        $workflowEngine->processApplicationCreated($application);

        // Notify the recruiter
        $recruiter = $offer->getRecruiter();
        if ($recruiter) {
            $ns = new NotificationService($em);
            $ns->notify($recruiter, 'GENERAL', 'Nouvelle candidature', $user->getEmail() . ' a postulé à "' . $offer->getTitle() . '"', '/admin/offers/' . $offer->getId() . '/applications');
        }

        $this->addFlash('success', 'Candidature envoyée avec succès !');
        return $this->redirectToRoute('app_offer_detail', ['id' => $offer->getId()]);
    }

    #[Route('/offers/{id}/bookmark', name: 'app_offer_bookmark')]
    public function bookmark(Offer $offer, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $existing = $em->getRepository(Bookmark::class)->findOneBy([
            'user' => $user, 'offer' => $offer,
        ]);

        if ($existing) {
            $em->remove($existing);
            $this->addFlash('info', 'Offre retirée des favoris.');
        } else {
            $bookmark = new Bookmark();
            $bookmark->setUser($user);
            $bookmark->setOffer($offer);
            $em->persist($bookmark);
            $this->addFlash('success', 'Offre ajoutée aux favoris !');
        }
        $em->flush();

        return $this->redirectToRoute('app_offer_detail', ['id' => $offer->getId()]);
    }

    #[Route('/account/applications', name: 'app_my_applications')]
    public function myApplications(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $applications = $em->getRepository(Application::class)->findBy(
            ['user' => $this->getUser()],
            ['applicationDate' => 'DESC']
        );

        return $this->render('front/account/applications.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/account/bookmarks', name: 'app_my_bookmarks')]
    public function myBookmarks(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $bookmarks = $em->getRepository(Bookmark::class)->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('front/account/bookmarks.html.twig', [
            'bookmarks' => $bookmarks,
        ]);
    }
}
