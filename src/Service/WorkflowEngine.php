<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Interview;
use App\Entity\Meet;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;

class WorkflowEngine
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function processMeetGraded(Meet $meet)
    {
        $interview = $meet->getInterview();
        if (!$interview) return;
        
        $application = $interview->getApplication();
        if (!$application) return;
        
        $offer = $application->getOffer();
        if (!$offer) return;
        
        $candidate = $application->getUser();

        $workflowConfig = $application->getWorkflow() ?? $offer->getWorkflow();
        if (!$workflowConfig || !isset($workflowConfig['drawflow']['Home']['data'])) {
            return;
        }

        $nodes = $workflowConfig['drawflow']['Home']['data'];
        
        // Find trigger nodes
        $triggerNodes = array_filter($nodes, fn($n) => $n['name'] === 'trigger-meet-graded');
        foreach ($triggerNodes as $nodeId => $nodeData) {
            $this->walk($nodes, $nodeId, $meet, $interview, $application, $candidate);
        }
    }

    private function walk(array $nodes, $currentNodeId, Meet $meet, Interview $interview, Application $application, $candidate)
    {
        if (!isset($nodes[$currentNodeId])) return;
        $node = $nodes[$currentNodeId];
        $outputs = $node['outputs'];

        $proceedToOptions = [];

        if ($node['name'] === 'trigger-meet-graded') {
            // Unconditionally proceed to output_1
            if (!empty($outputs['output_1']['connections'])) {
                foreach ($outputs['output_1']['connections'] as $conn) {
                    $proceedToOptions[] = $conn['node'];
                }
            }
        } elseif ($node['name'] === 'condition-grade') {
            $threshold = (float)($node['data']['threshold'] ?? 10);
            $operator = $node['data']['operator'] ?? '>';
            $grade = $meet->getGrade() ?? 0;
            
            $conditionMet = false;
            if ($operator === '>' && $grade > $threshold) $conditionMet = true;
            if ($operator === '>=' && $grade >= $threshold) $conditionMet = true;
            if ($operator === '<' && $grade < $threshold) $conditionMet = true;
            if ($operator === '<=' && $grade <= $threshold) $conditionMet = true;

            $outPort = $conditionMet ? 'output_1' : 'output_2';
            if (!empty($outputs[$outPort]['connections'])) {
                foreach ($outputs[$outPort]['connections'] as $conn) {
                    $proceedToOptions[] = $conn['node'];
                }
            }
        } elseif ($node['name'] === 'action-schedule-meet') {
            $meetType = $node['data']['meetType'] ?? 'TECHNICAL';
            $title = $node['data']['title'] ?? 'Entretien (' . $meetType . ')';
            
            // Generate a secondary Meet ONLY IF one of the same type doesn't exist to prevent infinite loops manually
            $existing = $this->em->getRepository(Meet::class)->findOneBy(['interview' => $interview, 'meetType' => $meetType]);
            if(!$existing) {
                $newMeet = new Meet();
                $newMeet->setInterview($interview);
                $newMeet->setTitle($title);
                // Schedule carefully next day
                $d = new \DateTime('+1 day');
                $d->setTime(10, 0);
                $newMeet->setMeetDate($d);
                $newMeet->setMeetType($meetType);
                
                $roomId = 'ROOM-' . strtoupper(substr(md5(uniqid()), 0, 8));
                $newMeet->setRoomId($roomId);
                
                $this->em->persist($newMeet);
                $this->em->flush();

                $ns = new NotificationService($this->em);
                $ns->notify($candidate, 'MEET_SCHEDULED', 'Suite du Processus (Entretien)', 'Un nouvel entretien de type '.$meetType.' a été automatisé suite à vos résultats positifs.', '/interviews');
            }

            if (!empty($outputs['output_1']['connections'])) {
                foreach ($outputs['output_1']['connections'] as $conn) {
                    $proceedToOptions[] = $conn['node'];
                }
            }
        } elseif ($node['name'] === 'action-accept-interview') {
             $interview->setStatus('COMPLETED');
             $application->setStatus('Acceptée');
             $this->em->flush();

             $ns = new NotificationService($this->em);
             $ns->notify($candidate, 'OFFER_DECISION', '✅ Félicitations', 'Votre candidature a été définitivement acceptée pour l\'offre : ' . $application->getOffer()->getTitle(), '/account/applications');
        } elseif ($node['name'] === 'action-reject-interview') {
             $interview->setStatus('CANCELLED');
             $application->setStatus('Refusée');
             $this->em->flush();

             $ns = new NotificationService($this->em);
             $ns->notify($candidate, 'OFFER_DECISION', '❌ Décision HR', 'Votre candidature n\'a malheureusement pas été retenue à l\'issue des entretiens.', '/account/applications');
        }

        foreach ($proceedToOptions as $nextNodeId) {
            $this->walk($nodes, $nextNodeId, $meet, $interview, $application, $candidate);
        }
    }
}
