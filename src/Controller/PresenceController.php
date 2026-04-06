<?php

namespace App\Controller;

use App\Entity\Presence;
use App\Form\PresenceType;
use App\Repository\PresenceRepository;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/presence')]
class PresenceController extends AbstractController
{
    #[Route('/', name: 'app_presence_index', methods: ['GET'])]
    public function index(PresenceRepository $presenceRepository): Response
    {
        return $this->render('presence/index.html.twig', [
            'presences' => $presenceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_presence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $presence = new Presence();
        $form = $this->createForm(PresenceType::class, $presence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $presence->setCodeQr(uniqid('qr_'));
            $entityManager->persist($presence);
            $entityManager->flush();

            $this->addFlash('success', 'Présence ajoutée avec succès.');
            return $this->redirectToRoute('app_presence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('presence/new.html.twig', [
            'presence' => $presence,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{idPresence}/edit', name: 'app_presence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Presence $presence, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PresenceType::class, $presence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Présence modifiée avec succès.');
            return $this->redirectToRoute('app_presence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('presence/edit.html.twig', [
            'presence' => $presence,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/generator', name: 'app_presence_generator', methods: ['GET'])]
    public function generator(): Response
    {
        return $this->render('presence/generator.html.twig');
    }

    #[Route('/qr/{code}', name: 'app_presence_qr', methods: ['GET'])]
    public function showQr(string $code, BuilderInterface $customQrCodeBuilder): Response
    {
        $result = $customQrCodeBuilder->build(
            writer: new \Endroid\QrCode\Writer\SvgWriter(),
            data: $code
        );
        return new Response($result->getString(), 200, ['Content-Type' => $result->getMimeType()]);
    }

    #[Route('/qr-download/{code}', name: 'app_presence_qr_download', methods: ['GET'])]
    public function downloadQr(string $code, BuilderInterface $customQrCodeBuilder): Response
    {
        $result = $customQrCodeBuilder->build(
            writer: new \Endroid\QrCode\Writer\SvgWriter(),
            data: $code
        );
        return new Response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'attachment; filename="qrcode-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $code) . '.svg"'
        ]);
    }



    #[Route('/scan', name: 'app_presence_scan', methods: ['GET', 'POST'])]
    public function scan(Request $request, PresenceRepository $presenceRepository, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $code = $request->request->get('code_qr');
            $presence = $presenceRepository->findOneBy(['codeQr' => $code]);

            if ($presence) {
                $presence->setEstPresent(true);
                $presence->setDateScan(new \DateTime());
                $entityManager->flush();
                $this->addFlash('success', 'Présence validée avec succès !');
            } else {
                $this->addFlash('error', 'Code QR invalide.');
            }
            return $this->redirectToRoute('app_presence_scan');
        }

        return $this->render('presence/scan.html.twig');
    }

    #[Route('/{idPresence}', name: 'app_presence_delete', methods: ['POST'])]
    public function delete(Request $request, Presence $presence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$presence->getIdPresence(), $request->request->get('_token'))) {
            $entityManager->remove($presence);
            $entityManager->flush();
            $this->addFlash('success', 'Présence supprimée avec succès.');
        }

        return $this->redirectToRoute('app_presence_index', [], Response::HTTP_SEE_OTHER);
    }
}
