<?php

namespace App\Command;

use App\Entity\Activity;
use App\Entity\Application;
use App\Entity\Choix;
use App\Entity\Event;
use App\Entity\EventComment;
use App\Entity\EventParticipation;
use App\Entity\Formation;
use App\Entity\Interview;
use App\Entity\Offer;
use App\Entity\Profile;
use App\Entity\Project;
use App\Entity\Question;
use App\Entity\Quiz;
use App\Entity\Seance;
use App\Entity\SupportTicket;
use App\Entity\TicketReply;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:populate', description: 'Populates the entire database with scenarios for all modules.')]
class PopulateDatabaseCommand extends Command
{
    private $em;
    private $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        $this->em = $em;
        $this->hasher = $hasher;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Disabling foreign key checks and truncating tables...');
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        
        $tables = [
            'ticket_replies', 'support_tickets', 'event_comments', 'event_participations', 'event_likes', 'event_feedback', 'events',
            'choix', 'questions', 'quiz', 'seances', 'formations',
            'interviews', 'applications', 'bookmarks', 'offers',
            'sync_messages', 'sync_users', 'syncs',
            'activity', 'project', 'profiles', 'users'
        ];
        
        foreach ($tables as $table) {
            try {
                $connection->executeStatement($platform->getTruncateTableSQL($table, true));
            } catch (\Exception $e) {
                // Table might not exist, silently ignore
            }
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        $io->success('Database cleared.');

        $password = '123456A';

        // ==========================================
        // 1. CORE USERS
        // ==========================================
        $io->info('Creating core users...');

        $hr = $this->createUser('omar.hamdi720@gmail.com', 'HR', 'Omar', 'Hamdi', $password);
        $recruiter = $this->createUser('recruiter@test.com', 'HR', 'Paul', 'Recruteur', $password);
        $instructor = $this->createUser('instructor@test.com', 'HR', 'Marie', 'Formatrice', $password);

        $emails = [
            'unimeet7@gmail.com' => ['Alice', 'Dupont'],
            'alaeddine.sahih01@gmail.com' => ['Bob', 'Martin'],
            'skandernafti0@gmail.com' => ['Charlie', 'Bernard'],
            'Hamdi.Omar@esprit.tn' => ['Diana', 'Thomas'],
            'skander.nafti@esprit.tn' => ['Eve', 'Petit'],
            'ayoub.hamed@esprit.tn' => ['Frank', 'Dubois']
        ];

        $candidates = [];
        foreach ($emails as $email => $name) {
            $candidates[] = $this->createUser($email, 'CANDIDATE', $name[0], $name[1], $password);
        }
        $this->em->flush();

        // ==========================================
        // 2. RECRUITMENT MODULE (Offers & Apps)
        // ==========================================
        $io->info('Creating Recruitment Data (Offers, Applications, Interviews)...');
        
        $offer1 = new Offer();
        $offer1->setTitle('Développeur Full-Stack Symfony / React');
        $offer1->setDescription("Nous cherchons un dev Full-Stack talentueux pour rejoindre l'équipe Core.");
        $offer1->setDepartment('Ingénierie');
        $offer1->setContractType('CDI');
        $offer1->setExperienceLevel('Intermédiaire');
        $offer1->setSalaryMin(40000);
        $offer1->setSalaryMax(60000);
        $offer1->setLocation('Paris / Remote');
        $offer1->setStatus('OPEN');
        $offer1->setPublishDate(new \DateTime('-15 days'));
        $offer1->setPositionsAvailable(2);
        $offer1->setRecruiter($recruiter);
        $this->em->persist($offer1);

        $offer2 = new Offer();
        $offer2->setTitle('Stage DevOps / Cloud AWS');
        $offer2->setDescription("Stage de fin d'études pour monter en compétence sur AWS et Terraform.");
        $offer2->setDepartment('Infrastructure');
        $offer2->setContractType('Stage');
        $offer2->setStatus('OPEN');
        $offer2->setPublishDate(new \DateTime('-5 days'));
        $offer2->setRecruiter($recruiter);
        $this->em->persist($offer2);

        // Application 1: Accepted & Interviewed (Alice)
        $app1 = new Application();
        $app1->setUser($candidates[0]);
        $app1->setOffer($offer1);
        $app1->setMotivationLetter('Je suis passionnée par Symfony et je maîtrise React.');
        $app1->setRecruiterResponse('ACCEPTED');
        $app1->setInterviewResult('PASSED');
        $this->em->persist($app1);

        $int1 = new Interview();
        $int1->setApplication($app1);
        $int1->setInterviewDate(new \DateTime('+2 days'));
        $int1->setStatus('SCHEDULED');
        $int1->setMeetingLink('https://meet.google.com/abc-defg-hij');
        $this->em->persist($int1);

        // Application 2: Rejected (Bob)
        $app2 = new Application();
        $app2->setUser($candidates[1]);
        $app2->setOffer($offer1);
        $app2->setMotivationLetter('Je cherche du travail vite.');
        $app2->setRecruiterResponse('REJECTED');
        $app2->setNotes('Manque de motivation et d\'expérience.');
        $this->em->persist($app2);

        // Application 3: Pending Stage (Charlie)
        $app3 = new Application();
        $app3->setUser($candidates[2]);
        $app3->setOffer($offer2);
        $app3->setMotivationLetter('Passionné par le Cloud.');
        $app3->setRecruiterResponse('PENDING');
        $this->em->persist($app3);


        // ==========================================
        // 3. LEARNING MODULE (Formations & Quiz)
        // ==========================================
        $io->info('Creating Learning Data (Formations, Seances, Quizzes)...');

        $f1 = new Formation();
        $f1->setTitre('Maîtrise de Symfony 7');
        $f1->setDescription('Apprenez les bases et concepts avancés de Symfony 7.');
        $f1->setNiveau('Intermédiaire');
        $f1->setDuree(10);
        if (method_exists($f1, 'setRecruiter')) $f1->setRecruiter($instructor);
        $this->em->persist($f1);

        $s1 = new Seance();
        $s1->setFormation($f1);
        $s1->setTitre('Introduction aux Controllers et Routing');
        $s1->setType('EN_LIGNE');
        $s1->setDateDebut(new \DateTime('+1 days'));
        $s1->setDateFin(new \DateTime('+1 days 2 hours'));
        $s1->setDureeMinutes(120);
        $s1->setStatut('TERMINEE');
        $this->em->persist($s1);

        $quiz1 = new Quiz();
        $quiz1->setFormation($f1);
        $quiz1->setTitre('Test de connaissances Symfony');
        $quiz1->setDuree(15);
        $this->em->persist($quiz1);

        $q1 = new Question();
        $q1->setQuiz($quiz1);
        $q1->setEnonce('Quel composant est utilisé pour valider des données dans Symfony ?');
        $this->em->persist($q1);

        $c1 = new Choix(); $c1->setQuestion($q1); $c1->setTexte('Validator Component'); $c1->setIsCorrect(true); $this->em->persist($c1);
        $c2 = new Choix(); $c2->setQuestion($q1); $c2->setTexte('Form Component'); $c2->setIsCorrect(false); $this->em->persist($c2);
        $c3 = new Choix(); $c3->setQuestion($q1); $c3->setTexte('Security Component'); $c3->setIsCorrect(false); $this->em->persist($c3);


        // ==========================================
        // 4. EVENTS MODULE
        // ==========================================
        $io->info('Creating Events...');

        $evt1 = new Event();
        $evt1->setTitle('Tech Job Fair 2026');
        $evt1->setDescription('Rencontrez les meilleurs recruteurs de la tech.');
        $evt1->setLocation('Palais des Congrès');
        $evt1->setOrganizer($hr);
        $evt1->setEventDate(new \DateTime('+10 days'));
        $evt1->setStatus('UPCOMING');
        if (method_exists($evt1, 'setEventType')) $evt1->setEventType('MEETUP');
        $this->em->persist($evt1);

        $part1 = new EventParticipation();
        $part1->setEvent($evt1);
        $part1->setUser($candidates[0]);
        if (method_exists($part1, 'setStatus')) $part1->setStatus('CONFIRMED');
        $this->em->persist($part1);

        $comm1 = new EventComment();
        $comm1->setEvent($evt1);
        $comm1->setUser($candidates[1]);
        $comm1->setContent('Hâte d\'y participer ! Est-ce qu\'il y aura des stands Cloud ?');
        $this->em->persist($comm1);


        // ==========================================
        // 5. SUPPORT MODULE
        // ==========================================
        $io->info('Creating Support Tickets...');

        $ticket1 = new SupportTicket();
        $ticket1->setUser($candidates[2]);
        $ticket1->setSubject('Problème avec la soumission de mon CV');
        $ticket1->setDescription('Je n\'arrive pas à uploader mon PDF sur l\'offre Stage DevOps.');
        if (method_exists($ticket1, 'setCategory')) $ticket1->setCategory('Technique');
        $this->em->persist($ticket1);

        $reply1 = new TicketReply();
        $reply1->setTicket($ticket1);
        $reply1->setUser($hr);
        $reply1->setMessage('Bonjour Charlie. Le fichier est-il bien inférieur à 5Mo ? Essayez de le compresser.');
        $this->em->persist($reply1);


        // ==========================================
        // 6. PROJECTS & ACTIVITIES (Leaderboard)
        // ==========================================
        $io->info('Creating Projects and Activities...');

        $p1 = new Project();
        $p1->setName('Plateforme E-Commerce V2');
        $p1->setProjectManager($hr);
        $this->em->persist($p1);

        $p2 = new Project();
        $p2->setName('Migration Cloud AWS');
        $p2->setProjectManager($hr);
        $this->em->persist($p2);

        // Candidate 0 (Alice) - Excellent First Time Approvals
        for ($i=1; $i<=5; $i++) {
            $a = new Activity(); $a->setEmployee($candidates[0]); $a->setProject($p1);
            $a->setDescription('Tâche accomplie avec succès ' . $i); $a->setHoursWorked(rand(3, 7));
            $a->setActivityDate(new \DateTime("-" . rand(1, 10) . " days"));
            $a->setReportStatus('REVIEWED'); $a->setUserReport('Le travail a été terminé sans problème.');
            $a->setExpectedDeadline(new \DateTime("+1 days")); 
            $this->em->persist($a);
        }

        // Candidate 1 (Bob) - High Rework
        for ($i=1; $i<=4; $i++) {
            $a = new Activity(); $a->setEmployee($candidates[1]); $a->setProject($p2);
            $a->setDescription('Développement Module ' . $i); $a->setHoursWorked(rand(5, 12));
            $a->setActivityDate(new \DateTime("-" . rand(1, 5) . " days"));
            if ($i <= 2) {
                $a->setReportStatus('REVISION_REQUESTED'); $a->setUserReport('J\'ai fini le module.');
                $a->setAdminFeedback('Il manque les tests unitaires et la documentation. Refusé.');
                $a->setRevisionCount(rand(1, 3));
            } else {
                $a->setReportStatus('REVIEWED'); $a->setUserReport('Module corrigé avec les tests.');
                $a->setRevisionCount(2);
            }
            $this->em->persist($a);
        }

        // Candidate 2 (Charlie) - Late Submissions
        for ($i=1; $i<=6; $i++) {
            $a = new Activity(); $a->setEmployee($candidates[2]); $a->setProject($p1);
            $a->setDescription('Correction Bug #' . (100+$i)); $a->setHoursWorked(rand(2, 6));
            $a->setActivityDate(new \DateTime("-" . rand(1, 4) . " days"));
            if ($i <= 4) {
                $a->setReportStatus('SUBMITTED'); $a->setUserReport('Désolé pour le retard.');
                $a->setExpectedDeadline(new \DateTime("-2 days"));
                $a->setIsLateSubmission(true); $a->setDelayInHours(rand(10, 48));
            } else {
                $a->setReportStatus('REVIEWED'); $a->setUserReport('Fini.');
                $a->setExpectedDeadline(new \DateTime("-1 days"));
                $a->setIsLateSubmission(true); $a->setDelayInHours(24);
            }
            $this->em->persist($a);
        }

        // Candidate 3 (Diana) - Mixed
        $a1 = new Activity(); $a1->setEmployee($candidates[3]); $a1->setProject($p2);
        $a1->setDescription('Configuration Serveur'); $a1->setHoursWorked(3);
        $a1->setActivityDate(new \DateTime()); $a1->setReportStatus('PENDING'); $this->em->persist($a1);

        $a2 = new Activity(); $a2->setEmployee($candidates[3]); $a2->setProject($p1);
        $a2->setDescription('Design Interface'); $a2->setHoursWorked(6);
        $a2->setActivityDate(new \DateTime("-1 days")); $a2->setReportStatus('REJECTED');
        $a2->setUserReport('Voici le design.'); $a2->setAdminFeedback('Le design ne respecte pas du tout la charte graphique. Refusé.');
        $this->em->persist($a2);

        $this->em->flush();
        
        $io->success('Database successfully fully populated with Offers, Quizzes, Events, Tickets, and Projects!');
        return Command::SUCCESS;
    }

    private function createUser(string $email, string $role, string $fn, string $ln, string $pwd): User
    {
        $u = new User();
        $u->setEmail($email);
        $u->setRole($role);
        $u->setPassword($this->hasher->hashPassword($u, $pwd));
        
        $p = new Profile();
        $p->setFirstName($fn);
        $p->setLastName($ln);
        $u->setProfile($p);
        
        $this->em->persist($u);
        return $u;
    }
}
