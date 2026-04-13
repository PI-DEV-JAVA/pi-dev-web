<?php

namespace App\Controller\Admin;

use App\Entity\Interview;
use App\Entity\Meet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/interviews')]
class AdminInterviewController extends AbstractController
{
    private function isAdmin(): bool
    {
        return $this->getUser()->getRole() === 'ADMIN';
    }

    #[Route('', name: 'admin_interviews')]
    public function list(EntityManagerInterface $em, Request $request): Response
    {
        $qb = $em->getRepository(Interview::class)->createQueryBuilder('i')
            ->join('i.application', 'a')
            ->join('a.user', 'u')
            ->join('a.offer', 'o')
            ->addSelect('a', 'u', 'o');

        // Filter for HR
        if (!$this->isAdmin()) {
            $qb->andWhere('o.recruiterId = :uid')
               ->setParameter('uid', $this->getUser()->getId());
        }

        // Search
        $search = $request->query->get('q');
        if ($search) {
            $qb->andWhere('u.email LIKE :search OR o.title LIKE :search OR i.status LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sort
        $sort = $request->query->get('sort', 'date_desc');
        if ($sort === 'date_asc') {
            $qb->orderBy('i.interviewDate', 'ASC');
        } elseif ($sort === 'date_desc') {
            $qb->orderBy('i.interviewDate', 'DESC');
        } elseif ($sort === 'status') {
            $qb->orderBy('i.status', 'ASC');
        }

        $interviews = $qb->getQuery()->getResult();

        return $this->render('back/interviews/list.html.twig', [
            'interviews' => $interviews,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/api/meets/recruiter', name: 'api_meets_recruiter', methods: ['GET'])]
    public function recruiterCalendar(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $qb = $em->getRepository(Meet::class)->createQueryBuilder('m')
            ->join('m.interview', 'i')
            ->join('i.application', 'a')
            ->join('a.user', 'u')
            ->join('a.offer', 'o');

        if (!$this->isAdmin()) {
            $qb->where('o.recruiterId = :uid')->setParameter('uid', $user->getId());
        }
        $meets = $qb->getQuery()->getResult();

        $events = [];
        foreach ($meets as $meet) {
            if ($meet->getMeetDate()) {
                $events[] = [
                    'title' => $uEmail = $meet->getInterview()->getApplication()->getUser()->getEmail() . ' - ' . $meet->getTitle(),
                    'start' => $meet->getMeetDate()->format('Y-m-d\TH:i:s'),
                    'url' => $this->generateUrl('admin_interview_detail', ['id' => $meet->getInterview()->getId()]),
                    'backgroundColor' => '#ec4899',
                    'borderColor' => '#ec4899',
                ];
            }
        }
        return $this->json($events);
    }

    #[Route('/{id}', name: 'admin_interview_detail', requirements: ['id' => '\d+'])]
    public function detail(Interview $interview): Response
    {
        if (!$this->isAdmin() && $interview->getApplication()->getOffer()->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('back/interviews/detail.html.twig', [
            'interview' => $interview,
        ]);
    }

    #[Route('/{id}/meets/new', name: 'admin_interview_meet_new', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function meetNew(Interview $interview, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if (!$this->isAdmin() && $interview->getApplication()->getOffer()->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $meet = new Meet();
        $meet->setInterview($interview);
        
        $title = $request->request->get('title');
        $dateStr = $request->request->get('meetDate');
        $notes = $request->request->get('notes');
        
        $meet->setTitle($title);
        if ($dateStr) {
            $meet->setMeetDate(new \DateTime($dateStr));
        }

        $meet->setNotes($notes);
        
        // Generate random room ID (e.g., 6 digits or alphanumeric)
        $meet->setRoomId(bin2hex(random_bytes(6)));

        $errors = $validator->validate($meet);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        } else {
            $em->persist($meet);
            $em->flush();
            $this->addFlash('success', 'Meet créé avec succès. ID de la room: ' . $meet->getRoomId());
        }

        return $this->redirectToRoute('admin_interview_detail', ['id' => $interview->getId()]);
    }

    #[Route('/meets/{id}/grade', name: 'admin_interview_meet_grade', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function meetGrade(Meet $meet, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $interview = $meet->getInterview();
        if (!$this->isAdmin() && $interview->getApplication()->getOffer()->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $grade = $request->request->get('grade');
        $notes = $request->request->get('notes');

        if ($grade !== null && $grade !== '') {
            $meet->setGrade((float)$grade);
        }
        if ($notes !== null) {
            $meet->setNotes($notes);
        }

        $errors = $validator->validate($meet);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        } else {
            $em->flush();
            $this->addFlash('success', 'Meet mis à jour avec succès.');
        }

        return $this->redirectToRoute('admin_interview_detail', ['id' => $interview->getId()]);
    }

    #[Route('/{id}/edit', name: 'admin_interview_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editInterview(Interview $interview, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $interview->getApplication()->getOffer()->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $status = $request->request->get('status');
        $dateStr = $request->request->get('interviewDate');

        if ($status && in_array($status, ['PENDING', 'COMPLETED', 'CANCELLED'])) {
            $interview->setStatus($status);
        }
        if ($dateStr) {
            $interview->setInterviewDate(new \DateTime($dateStr));
        }

        $em->flush();
        $this->addFlash('success', 'Entretien mis à jour.');
        
        // Referer fallback
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('admin_interviews'));
    }

    #[Route('/{id}/delete', name: 'admin_interview_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteInterview(Interview $interview, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin() && $interview->getApplication()->getOffer()->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_interview_' . $interview->getId(), $request->request->get('_token'))) {
            $em->remove($interview);
            $em->flush();
            $this->addFlash('success', 'Entretien et ses Meets supprimés.');
        }

        return $this->redirectToRoute('admin_interviews');
    }

    #[Route('/meets/{id}/edit', name: 'admin_interview_meet_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editMeet(Meet $meet, Request $request, EntityManagerInterface $em): Response
    {
        $interview = $meet->getInterview();
        if (!$this->isAdmin() && $interview->getApplication()->getOffer()->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $title = $request->request->get('title');
        $dateStr = $request->request->get('meetDate');

        if ($title) {
            $meet->setTitle($title);
        }
        if ($dateStr) {
            $meet->setMeetDate(new \DateTime($dateStr));
        }

        $em->flush();
        $this->addFlash('success', 'Meet mis à jour.');
        return $this->redirectToRoute('admin_interview_detail', ['id' => $interview->getId()]);
    }

    #[Route('/meets/{id}/delete', name: 'admin_interview_meet_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteMeet(Meet $meet, Request $request, EntityManagerInterface $em): Response
    {
        $interview = $meet->getInterview();
        if (!$this->isAdmin() && $interview->getApplication()->getOffer()->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_meet_' . $meet->getId(), $request->request->get('_token'))) {
            $em->remove($meet);
            $em->flush();
            $this->addFlash('success', 'Meet supprimé.');
        }

        return $this->redirectToRoute('admin_interview_detail', ['id' => $interview->getId()]);
    }

    #[Route('/meets/{id}/notes/clear', name: 'admin_interview_meet_clear_notes', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function clearNotes(Meet $meet, Request $request, EntityManagerInterface $em): Response
    {
        $interview = $meet->getInterview();
        if (!$this->isAdmin() && $interview->getApplication()->getOffer()->getRecruiterId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('clear_notes_' . $meet->getId(), $request->request->get('_token'))) {
            $meet->setNotes(null);
            $em->flush();
            $this->addFlash('success', 'Notes effacées.');
        }

        return $this->redirectToRoute('admin_interview_detail', ['id' => $interview->getId()]);
    }
}
