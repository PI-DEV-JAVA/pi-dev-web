<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\ProfileView;
use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function view(): Response
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!$profile || !$profile->isProfileCompleted()) {
            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('front/account/profile.html.twig', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!$profile) {
            $profile = new Profile();
            $profile->setUser($user);
            $em->persist($profile);
        }

        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle cropped avatar (base64 from Cropper.js)
            $croppedData = $request->request->get('croppedAvatar', '');
            if ($croppedData && str_starts_with($croppedData, 'data:image/')) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0775, true); }
                $parts = explode(',', $croppedData, 2);
                $imageData = base64_decode($parts[1]);
                if ($imageData && strlen($imageData) <= 5 * 1024 * 1024) {
                    // Delete old avatar
                    if ($profile->getProfilePicturePath()) {
                        $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $profile->getProfilePicturePath();
                        if (file_exists($oldFile)) { @unlink($oldFile); }
                    }
                    $filename = 'avatar_' . $user->getId() . '_' . time() . '.jpg';
                    file_put_contents($uploadDir . '/' . $filename, $imageData);
                    $profile->setProfilePicturePath('/uploads/avatars/' . $filename);
                }
            } else {
                // Fallback: Handle profile picture file upload
                $pictureFile = $form->get('profilePicture')->getData();
                if ($pictureFile) {
                    $newFilename = 'avatar-' . $user->getId() . '-' . uniqid() . '.' . $pictureFile->guessExtension();
                    try {
                        $pictureFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                            $newFilename
                        );
                        $profile->setProfilePicturePath('/uploads/avatars/' . $newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors du téléchargement de la photo.');
                    }
                }
            }

            // Handle CV upload
            $cvFile = $form->get('cvFile')->getData();
            if ($cvFile) {
                $newFilename = 'cv-' . $user->getId() . '-' . uniqid() . '.pdf';
                try {
                    $cvFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/cvs',
                        $newFilename
                    );
                    $profile->setCvPath('/uploads/cvs/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du CV.');
                }
            }

            // Handle skills (JSON hidden field)
            $skillsJson = $request->request->get('skills_json', '[]');
            $skillsArray = json_decode($skillsJson, true);
            if (is_array($skillsArray)) {
                $profile->setSkills(array_values(array_unique(array_filter($skillsArray))));
            }

            // Mark as completed if required fields are filled
            if ($profile->getFirstName() && $profile->getLastName()) {
                $profile->setProfileCompleted(true);
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('front/account/profile_edit.html.twig', [
            'form' => $form->createView(),
            'profile' => $profile,
        ]);
    }

    #[Route('/profile/reset', name: 'app_profile_reset', methods: ['POST'])]
    public function resetProfile(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!$profile) {
            $this->addFlash('warning', 'Aucun profil à réinitialiser.');
            return $this->redirectToRoute('app_profile');
        }

        // Keep firstName & lastName, clear everything else
        $profile->setBirthDate(null);
        $profile->setPhoneNumber(null);
        $profile->setLocation(null);
        $profile->setProfessionalTitle(null);
        $profile->setYearsOfExperience(null);
        $profile->setSummary(null);

        // Remove uploaded files from disk
        $projectDir = $this->getParameter('kernel.project_dir');
        if ($profile->getProfilePicturePath()) {
            $path = $projectDir . '/public' . $profile->getProfilePicturePath();
            if (file_exists($path)) {
                @unlink($path);
            }
            $profile->setProfilePicturePath(null);
        }
        if ($profile->getCvPath()) {
            $path = $projectDir . '/public' . $profile->getCvPath();
            if (file_exists($path)) {
                @unlink($path);
            }
            $profile->setCvPath(null);
        }

        // Keep profileCompleted true since name is still there
        $em->flush();

        $this->addFlash('success', 'Profil réinitialisé. Seul votre nom a été conservé.');
        return $this->redirectToRoute('app_profile');
    }

    // ─────────────────────────────────────────────
    //  PUBLIC PROFILE VIEW (tracks views)
    // ─────────────────────────────────────────────
    #[Route('/u/{id}', name: 'app_user_profile', requirements: ['id' => '\d+'])]
    public function publicProfile(int $id, EntityManagerInterface $em): Response
    {
        $targetUser = $em->getRepository(User::class)->find($id);
        if (!$targetUser || !$targetUser->getProfile()) {
            throw $this->createNotFoundException();
        }

        // Track view (don't count self-views)
        $viewer = $this->getUser();
        if ($viewer instanceof User && $viewer->getId() !== $targetUser->getId()) {
            $view = new ProfileView();
            $view->setViewedUser($targetUser);
            $view->setViewer($viewer);
            $em->persist($view);
            $em->flush();
        }

        return $this->render('front/account/profile.html.twig', [
            'user' => $targetUser,
            'profile' => $targetUser->getProfile(),
        ]);
    }

    // ─────────────────────────────────────────────
    //  SKILLS AUTOCOMPLETE API
    // ─────────────────────────────────────────────
    #[Route('/api/skills/search', name: 'api_skills_search', methods: ['GET'])]
    public function searchSkills(Request $request): JsonResponse
    {
        $q = mb_strtolower(trim($request->query->get('q', '')));
        if (strlen($q) < 1) {
            return $this->json([]);
        }

        $allSkills = [
            // Programming & Web
            'JavaScript','TypeScript','Python','Java','C','C++','C#','PHP','Ruby','Go','Rust','Swift',
            'Kotlin','Scala','Dart','R','MATLAB','Perl','Haskell','Lua','Shell','Bash','PowerShell',
            'HTML','CSS','SASS','LESS','React','Angular','Vue.js','Svelte','Next.js','Nuxt.js',
            'Node.js','Express.js','Django','Flask','FastAPI','Spring Boot','Laravel','Symfony',
            'Ruby on Rails','ASP.NET','.NET Core','GraphQL','REST API','WebSocket',
            // Mobile
            'React Native','Flutter','SwiftUI','Android','iOS','Xamarin','Ionic',
            // Data & AI
            'Machine Learning','Deep Learning','NLP','Computer Vision','TensorFlow','PyTorch',
            'Scikit-learn','Pandas','NumPy','Keras','OpenCV','Data Science','Data Analytics',
            'Power BI','Tableau','Apache Spark','Hadoop','ETL','Data Engineering',
            // DevOps & Cloud
            'Docker','Kubernetes','AWS','Azure','Google Cloud','Terraform','Ansible','Jenkins',
            'CI/CD','GitHub Actions','GitLab CI','Linux','Nginx','Apache',
            // Databases
            'MySQL','PostgreSQL','MongoDB','Redis','Elasticsearch','SQLite','Oracle DB',
            'Firebase','Cassandra','DynamoDB','SQL Server','MariaDB',
            // Tools
            'Git','GitHub','GitLab','Bitbucket','Jira','Confluence','Figma','Adobe XD',
            'Photoshop','Illustrator','Postman','VS Code','IntelliJ IDEA','Webpack','Vite',
            // Design & UX
            'UI Design','UX Design','UX Research','Wireframing','Prototyping','Design Systems',
            'Responsive Design','Accessibility','User Testing',
            // Business & Management
            'Project Management','Agile','Scrum','Kanban','Product Management','Business Analysis',
            'Strategic Planning','Risk Management','Change Management','Lean','Six Sigma',
            // Marketing
            'Digital Marketing','SEO','SEM','Google Ads','Facebook Ads','Content Marketing',
            'Email Marketing','Social Media','Analytics','Growth Hacking','Copywriting',
            // Soft Skills
            'Leadership','Communication','Teamwork','Problem Solving','Critical Thinking',
            'Creativity','Adaptability','Time Management','Negotiation','Public Speaking',
            'Emotional Intelligence','Conflict Resolution','Decision Making',
            // Languages
            'French','English','Arabic','German','Spanish','Italian','Chinese','Japanese',
            // Other Tech
            'Blockchain','Web3','Solidity','Cybersecurity','Penetration Testing','Networking',
            'IoT','Embedded Systems','ROS','Unity','Unreal Engine','Game Development',
            'Microservices','System Design','API Design','OAuth','JWT','WebRTC',
        ];

        $results = array_values(array_filter($allSkills, function($s) use ($q) {
            return str_contains(mb_strtolower($s), $q);
        }));

        return $this->json(array_slice($results, 0, 8));
    }
}
