<?php

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\Interview;
use App\Entity\Meet;
use App\Entity\Offer;
use App\Entity\User;
use App\Service\WorkflowEngine;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class WorkflowEngineTest extends TestCase
{
    public function testProcessApplicationCreatedAcceptsApplication(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        // We mock persist/flush blindly because the NotificationService called within might use them
        $em->method('persist')->willReturnCallback(function() {});
        $em->method('flush')->willReturnCallback(function() {});

        $user = $this->createMock(User::class);

        $offer = $this->createMock(Offer::class);
        $offer->method('getTitle')->willReturn('Développeur Backend');

        // We prepare a workflow configuration imitating drawflow JSON
        // trigger -> action: accept
        $workflowConfig = [
            'drawflow' => [
                'Home' => [
                    'data' => [
                        '1' => [
                            'name' => 'trigger-application-created',
                            'outputs' => [
                                'output_1' => [
                                    'connections' => [
                                        ['node' => '2']
                                    ]
                                ]
                            ]
                        ],
                        '2' => [
                            'name' => 'action-accept-interview',
                            'outputs' => []
                        ]
                    ]
                ]
            ]
        ];

        $application = $this->createMock(Application::class);
        $application->method('getUser')->willReturn($user);
        $application->method('getOffer')->willReturn($offer);
        $application->method('getWorkflow')->willReturn($workflowConfig);
        
        // Assert that the workflow eventually accepted the application
        $application->expects($this->once())->method('setStatus')->with('Acceptée');

        $engine = new WorkflowEngine($em);
        $engine->processApplicationCreated($application);
    }

    public function testProcessMeetGradedConditionRejectsApplication(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function() {});
        $em->method('flush')->willReturnCallback(function() {});

        $user = $this->createMock(User::class);
        
        $offer = $this->createMock(Offer::class);
        $offer->method('getTitle')->willReturn('Dev');

        // workflow: trigger(meet graded) -> condition(>= 10) 
        //   -> if passed (do nothing here)
        //   -> if failed -> reject application
        $workflowConfig = [
            'drawflow' => [
                'Home' => [
                    'data' => [
                        '1' => [
                            'name' => 'trigger-meet-graded',
                            'outputs' => [
                                'output_1' => [
                                    'connections' => [['node' => '2']]
                                ]
                            ]
                        ],
                        '2' => [
                            'name' => 'condition-grade',
                            'data' => [
                                'operator' => '>=',
                                'threshold' => 10
                            ],
                            'outputs' => [
                                'output_1' => [ // Path 1: Grade >= 10
                                    'connections' => []
                                ],
                                'output_2' => [ // Path 2: Grade < 10
                                    'connections' => [['node' => '3']]
                                ]
                            ]
                        ],
                        '3' => [
                            'name' => 'action-reject-interview',
                            'outputs' => []
                        ]
                    ]
                ]
            ]
        ];

        $application = $this->createMock(Application::class);
        $application->method('getUser')->willReturn($user);
        $application->method('getOffer')->willReturn($offer);
        $application->method('getWorkflow')->willReturn($workflowConfig);
        
        // Assert negative path is taken
        $application->expects($this->once())->method('setStatus')->with('Refusée');

        $interview = $this->createMock(Interview::class);
        $interview->method('getApplication')->willReturn($application);
        // Ensure interview is also cancelled properly
        $interview->expects($this->once())->method('setStatus')->with('CANCELLED');

        $meet = $this->createMock(Meet::class);
        $meet->method('getInterview')->willReturn($interview);
        $meet->method('getGrade')->willReturn(5.0); // Grade is lower than 10

        $engine = new WorkflowEngine($em);
        $engine->processMeetGraded($meet);
    }
}
