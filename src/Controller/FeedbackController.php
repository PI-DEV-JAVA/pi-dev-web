<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/feedback')]
class FeedbackController extends AbstractController
{
    #[Route('/', name: 'app_feedback_index', methods: ['GET'])]
    public function index(FeedbackRepository $feedbackRepository): Response
    {
        return $this->render('feedback/index.html.twig', [
            'feedbacks' => $feedbackRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $feedback = new Feedback();
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($feedback);
            $entityManager->flush();

            $this->addFlash('success', 'Feedback ajouté avec succès.');
            return $this->redirectToRoute('app_feedback_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('feedback/new.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{idFeedback}/edit', name: 'app_feedback_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Feedback modifié avec succès.');
            return $this->redirectToRoute('app_feedback_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('feedback/edit.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{idFeedback}', name: 'app_feedback_delete', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$feedback->getIdFeedback(), $request->request->get('_token'))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Feedback supprimé avec succès.');
        }

        return $this->redirectToRoute('app_feedback_index', [], Response::HTTP_SEE_OTHER);
    }
}
