<?php

namespace App\Controller;

use App\Entity\EvenementRh;
use App\Form\EvenementRhType;
use App\Repository\EvenementRhRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/evenement')]
class EvenementController extends AbstractController
{
    #[Route('/', name: 'app_evenement_index', methods: ['GET'])]
    public function index(Request $request, EvenementRhRepository $evenementRhRepository): Response
    {
        $titre = $request->query->get('titre');
        $type = $request->query->get('type');
        $statut = $request->query->get('statut');

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenementRhRepository->findByFilters($titre, $type, $statut),
            'search_titre' => $titre,
            'search_type' => $type,
            'search_statut' => $statut,
        ]);
    }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evenementRh = new EvenementRh();
        $form = $this->createForm(EvenementRhType::class, $evenementRh);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evenementRh);
            $entityManager->flush();

            $this->addFlash('success', 'Événement ajouté avec succès.');
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenementRh,
            'form' => $form->createView(),
        ]);
    }

    // Pas de route "show" pour limiter les affichages d'ID/détails inutiles, 
    // l'édition se fait directement avec l'entité.
    
    #[Route('/{idEvent}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EvenementRh $evenementRh, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvenementRhType::class, $evenementRh);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Événement modifié avec succès.');
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenementRh,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{idEvent}', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, EvenementRh $evenementRh, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evenementRh->getIdEvent(), $request->request->get('_token'))) {
            $entityManager->remove($evenementRh);
            $entityManager->flush();
            $this->addFlash('success', 'Événement supprimé avec succès.');
        }

        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }
}
