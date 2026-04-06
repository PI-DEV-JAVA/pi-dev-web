<?php

namespace App\Controller;

use App\Repository\EvenementRhRepository;
use App\Repository\ParticipationRepository;
use App\Repository\FeedbackRepository;
use App\Repository\PresenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        EvenementRhRepository $eventRepo,
        ParticipationRepository $partRepo,
        PresenceRepository $presenceRepo,
        FeedbackRepository $feedbackRepo
    ): Response {
        $events = $eventRepo->findAll();
        $participations = $partRepo->findAll();
        $presences = $presenceRepo->findBy(['estPresent' => true]);
        
        // Calcul Taux de présence
        $tauxPresence = count($participations) > 0 
            ? round((count($presences) / count($participations)) * 100) 
            : 0;

        // Calcul Note Moyenne
        $feedbacks = $feedbackRepo->findAll();
        $moyenne = 0;
        if (count($feedbacks) > 0) {
            $somme = array_reduce($feedbacks, fn($carry, $f) => $carry + $f->getNote(), 0);
            $moyenne = round($somme / count($feedbacks), 1);
        }

        // Data pour Chart.js (Répartition par type)
        $typesCount = [];
        foreach ($events as $event) {
            $type = $event->getTypeEvent() ?: 'Autre';
            if (!isset($typesCount[$type])) {
                $typesCount[$type] = 0;
            }
            $typesCount[$type]++;
        }

        return $this->render('dashboard/index.html.twig', [
            'total_events' => count($events),
            'total_participants' => count($participations),
            'taux_presence' => $tauxPresence,
            'note_moyenne' => $moyenne,
            'recent_events' => array_slice($events, -5), // Les 5 derniers événements
            'chart_labels' => array_keys($typesCount),
            'chart_data' => array_values($typesCount),
        ]);
    }
}
