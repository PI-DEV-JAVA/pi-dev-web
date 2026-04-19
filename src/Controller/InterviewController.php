<?php

namespace App\Controller;

use App\Entity\Interview;
use App\Entity\Meet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class InterviewController extends AbstractController
{
    #[Route('/interviews', name: 'app_interviews')]
    public function list(EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $qb = $em->getRepository(Interview::class)->createQueryBuilder('i')
            ->join('i.application', 'a')
            ->join('a.user', 'u')
            ->join('a.offer', 'o')
            ->where('a.user = :user')
            ->setParameter('user', $user);

        // Search by offer title
        $search = $request->query->get('q');
        if ($search) {
            $qb->andWhere('o.title LIKE :search OR i.status LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sort
        $sort = $request->query->get('sort', 'date_desc');
        if ($sort === 'date_asc') {
            $qb->orderBy('i.interviewDate', 'ASC');
        } elseif ($sort === 'date_desc') {
            $qb->orderBy('i.interviewDate', 'DESC');
        }

        $interviews = $qb->getQuery()->getResult();

        return $this->render('front/account/interviews.html.twig', [
            'interviews' => $interviews,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/meet/{roomId}', name: 'app_meet_room')]
    public function room(string $roomId, EntityManagerInterface $em): Response
    {
        $meet = $em->getRepository(Meet::class)->findOneBy(['roomId' => $roomId]);

        if (!$meet) {
            throw $this->createNotFoundException('Salle de réunion introuvable.');
        }

        $userName = null;
        $isRecruiter = false;
        if ($this->getUser()) {
            $userName = $this->getUser()->getProfile() ? $this->getUser()->getProfile()->getFirstName() . ' ' . $this->getUser()->getProfile()->getLastName() : $this->getUser()->getEmail();
            if (in_array($this->getUser()->getRole(), ['HR', 'ADMIN'])) {
                $isRecruiter = true;
            }
        }

        return $this->render('front/meet/room.html.twig', [
            'meet' => $meet,
            'roomId' => $roomId,
            'userName' => $userName,
            'isRecruiter' => $isRecruiter,
        ]);
    }

    #[Route('/api/meets/candidate', name: 'api_meets_candidate', methods: ['GET'])]
    public function candidateCalendar(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        $meets = $em->getRepository(Meet::class)->createQueryBuilder('m')
            ->join('m.interview', 'i')
            ->join('i.application', 'a')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()->getResult();

        $events = [];
        foreach ($meets as $meet) {
            if ($meet->getMeetDate()) {
                $events[] = [
                    'title' => $meet->getInterview()->getApplication()->getOffer()->getTitle() . ' - ' . $meet->getTitle(),
                    'start' => $meet->getMeetDate()->format('Y-m-d\TH:i:s'),
                    'url' => $this->generateUrl('app_meet_room', ['roomId' => $meet->getRoomId()]),
                    'backgroundColor' => '#1a73e8',
                    'borderColor' => '#1a73e8',
                ];
            }
        }
        return $this->json($events);
    }

    #[Route('/account/calendar', name: 'app_candidate_calendar')]
    public function candidateCalendarView(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('front/account/calendar.html.twig');
    }

    #[Route('/api/meets/{id}/notes', name: 'api_meet_save_notes', methods: ['POST'])]
    public function saveNotes(Meet $meet, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!in_array($user->getRole(), ['HR', 'ADMIN'])) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['notes'])) {
            $meet->setNotes($data['notes']);
            $em->flush();
            return $this->json(['success' => true]);
        }
        return $this->json(['error' => 'Invalid data'], 400);
    }

    #[Route('/api/meets/{id}/run-code', name: 'api_meet_run_code', methods: ['POST'])]
    public function runCode(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $data = json_decode($request->getContent(), true);
        $language = $data['language'] ?? 'javascript';
        $code = $data['code'] ?? '';

        if (empty($code)) {
            return $this->json(['output' => '', 'error' => 'Code vide.']);
        }

        $tempDir = sys_get_temp_dir();
        $fileName = uniqid('code_');
        $filePath = $tempDir . '/' . $fileName;

        $process = null;
        
        switch ($language) {
            case 'javascript':
                $filePath .= '.js';
                file_put_contents($filePath, $code);
                $process = new Process(['node', $filePath]);
                break;
            case 'python':
                $filePath .= '.py';
                file_put_contents($filePath, $code);
                $process = new Process(['python3', $filePath]);
                break;
            case 'php':
                $filePath .= '.php';
                file_put_contents($filePath, $code);
                $process = new Process(['php', $filePath]);
                break;
            case 'java':
                // Java requires class name to match file name. We will extract it or use Main
                $className = 'Main';
                if (preg_match('/public\s+class\s+([A-Za-z0-9_]+)/', $code, $matches)) {
                    $className = $matches[1];
                }
                $filePath = $tempDir . '/' . $className . '.java';
                file_put_contents($filePath, $code);
                // Compile and run
                $process = new Process(['sh', '-c', "javac $filePath && java -cp $tempDir $className"]);
                break;
            case 'cpp':
                $filePath .= '.cpp';
                $outPath = $tempDir . '/' . $fileName . '.out';
                file_put_contents($filePath, $code);
                // Compile and run
                $process = new Process(['sh', '-c', "g++ $filePath -o $outPath && $outPath"]);
                break;
            default:
                return $this->json(['output' => '', 'error' => 'Langage non supporté.']);
        }

        try {
            $process->setTimeout(5); // 5 seconds timeout to prevent infinite loops
            $process->run();
            
            $output = $process->getOutput();
            $error = $process->getErrorOutput();
        } catch (\Exception $e) {
            $output = '';
            $error = $e->getMessage();
        } finally {
            // Cleanup
            @unlink($filePath);
            if ($language === 'java' && isset($className)) {
                @unlink($tempDir . '/' . $className . '.class');
            }
            if ($language === 'cpp' && isset($outPath)) {
                @unlink($outPath);
            }
        }

        return $this->json([
            'output' => $output,
            'error' => $error
        ]);
    }
}
