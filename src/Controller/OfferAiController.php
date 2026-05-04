<?php

namespace App\Controller;

use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OfferAiController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $projectDir;

    public function __construct(HttpClientInterface $httpClient, string $projectDir)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
        $this->projectDir = $projectDir;
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FEATURE 1: AI Resume Fit Analyzer
    //  Source: OpenRouter API (free-tier LLM, e.g. Llama 3 / Mistral)
    //  Logic: Sends the candidate's Profile summary + Offer description
    //         to an LLM which returns a JSON with score + strengths/weaknesses
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/api/offers/{id}/match', name: 'api_offer_match', methods: ['GET'])]
    public function matchCandidate(Offer $offer): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user->getProfile()) {
            return $this->json(['error' => 'Veuillez compléter votre profil pour utiliser cette fonctionnalité.'], 400);
        }

        $profile = $user->getProfile();

        // ── Extract CV text from uploaded PDF ──
        $cvText = '';
        $cvPath = $profile->getCvPath();
        if ($cvPath) {
            // cvPath may be stored as relative path or just filename
            $fullPath = $this->projectDir . '/public' . $cvPath;
            if (!file_exists($fullPath)) {
                $fullPath = $this->projectDir . '/public/uploads/cvs/' . basename($cvPath);
            }
            if (file_exists($fullPath)) {
                try {
                    $parser = new PdfParser();
                    $pdf = $parser->parseFile($fullPath);
                    $cvText = $pdf->getText();
                    // Limit to ~1500 chars to fit in prompt
                    $cvText = mb_substr(trim(preg_replace('/\s+/', ' ', $cvText)), 0, 1500);
                } catch (\Exception $e) {
                    $cvText = '[Erreur de lecture du CV]';
                }
            }
        }

        // ── Build candidate profile text ──
        $profileText = 'Titre: ' . ($profile->getProfessionalTitle() ?? 'Non renseigné')
            . ' | Expérience: ' . ($profile->getYearsOfExperience() ?? 0) . ' ans'
            . ' | Localisation: ' . ($profile->getLocation() ?? 'Non renseigné');

        $skills = $profile->getSkills();
        if (!empty($skills)) {
            $profileText .= ' | Compétences: ' . implode(', ', $skills);
        }

        if ($profile->getSummary()) {
            $profileText .= ' | Résumé: ' . mb_substr($profile->getSummary(), 0, 300);
        }

        // ── Build offer text ──
        $offerText = $offer->getTitle()
            . ' (' . ($offer->getContractType() ?? '') . ', ' . ($offer->getExperienceLevel() ?? '') . ')'
            . ' | Lieu: ' . ($offer->getLocation() ?? '')
            . ' | Dept: ' . ($offer->getDepartment() ?? '')
            . ' | Description: ' . mb_substr(strip_tags($offer->getDescription() ?? ''), 0, 800);

        // ── Build prompt ──
        $cvSection = $cvText ? "\nCV DU CANDIDAT (extrait du PDF):\n$cvText" : '\n[Aucun CV uploadé]';

        $prompt = "You are an expert HR recruiter. Analyze the candidate's CV and profile against this job offer. Give a realistic compatibility score from 0 to 100.

JOB OFFER:
$offerText

CANDIDATE PROFILE:
$profileText
$cvSection

INSTRUCTIONS:
- Score 0-30: Very poor match (missing most requirements)
- Score 30-50: Weak match (some relevant experience)
- Score 50-70: Decent match (meets several requirements)
- Score 70-85: Strong match (meets most requirements)
- Score 85-100: Excellent match (exceeds requirements)
- Base your analysis primarily on the CV content if available, then on the profile data.
- Strengths: list 2-3 specific matches between CV/profile and job requirements.
- Weaknesses: list 2-3 specific gaps or missing skills.
- Reply in the same language as the job description.

Respond with ONLY valid JSON:
{\"score\": 65, \"strengths\": [\"3 ans d'expérience en développement Java comme requis\", \"Maîtrise de Spring Boot mentionné dans le CV\"], \"weaknesses\": [\"Pas d'expérience avec Kubernetes demandé dans l'offre\", \"Niveau d'anglais non mentionné\"]}";

        return $this->callAi($prompt, [
            'score' => $cvText ? 45 : 30,
            'strengths' => $cvText ? ['CV analysé - profil en cours d\'évaluation'] : ['Aucun CV uploadé - analyse limitée au profil'],
            'weaknesses' => $cvText ? ['Analyse IA temporairement indisponible'] : ['Uploadez votre CV pour une analyse complète']
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FEATURE 2: AI Salary Estimator
    //  Source: OpenRouter API (free-tier LLM)
    //  Logic: Reads the offer's title, experience level, and location
    //         to generate a local market salary estimation
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FEATURE 3: AI Interview Prep Generator
    //  Source: OpenRouter API (free-tier LLM)
    //  Logic: Analyzes the specific job description to predict
    //         the 3 most likely interview questions
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/api/offers/{id}/insights', name: 'api_offer_insights', methods: ['GET'])]
    public function offerInsights(Offer $offer): JsonResponse
    {
        $title = $offer->getTitle();
        $level = $offer->getExperienceLevel() ?: 'Junior/Intermédiaire';
        $location = $offer->getLocation() ?: 'Tunisie';
        $dept = $offer->getDepartment() ?: 'Informatique';
        $contract = $offer->getContractType() ?: 'CDI';
        $desc = mb_substr(strip_tags($offer->getDescription() ?? ''), 0, 400);

        // Build salary context based on offer data
        $salaryHint = '';
        if ($offer->getSalaryMin() && $offer->getSalaryMax()) {
            $salaryHint = 'Le recruteur propose: ' . $offer->getSalaryMin() . ' - ' . $offer->getSalaryMax() . ' TND/mois. ';
        }

        // Add randomness to force the LLM to generate different questions on every refresh
        $randomSeed = time() . rand(1000, 9999);

        $prompt = "Analyze this job posting and produce TWO things: a salary estimate for Tunisia, and 3 interview questions.

JOB POSTING:
Title: $title | Level: $level | Location: $location | Sector: $dept | Contract: $contract
Description: $desc
$salaryHint

SALARY GUIDELINES (Tunisia, monthly net TND):
Junior(0-2yr): 800-2000 | Mid(2-5yr): 1500-3500 | Senior(5+yr): 2500-6000 | Manager: 3500-7000 | Stage: 300-800

INSTRUCTIONS:
1. Pick a realistic salary range in TND/mois based on the job details above.
2. Write exactly 3 interview questions that a recruiter would ask for THIS SPECIFIC job. Each question must reference concrete skills, tools, or scenarios from the job description. Never use generic questions.
3. Vary your output using seed=$randomSeed
4. Use the same language as the job description.

OUTPUT FORMAT (respond with ONLY this JSON, nothing else):
{\"salary_estimation\": \"1500 - 2500 TND / mois\", \"interview_questions\": [\"Si un client signale un problème réseau critique en production, décrivez votre processus de diagnostic étape par étape.\", \"Quelle expérience avez-vous avec la configuration de VLANs et le routage inter-VLAN dans un environnement Cisco?\", \"Comment géreriez-vous la migration d'une infrastructure on-premise vers le cloud pour notre département?\"]}

IMPORTANT: The example above is for a DIFFERENT job. You MUST generate NEW questions specific to: $title";

        return $this->callAi($prompt, [
            'salary_estimation' => $this->estimateFallbackSalary($level, $contract),
            'interview_questions' => $this->generateFallbackQuestions($title, $dept, $level)
        ]);
    }

    /**
     * Fallback salary estimation when AI is unavailable.
     */
    private function estimateFallbackSalary(string $level, string $contract): string
    {
        if (stripos($contract, 'Stage') !== false) {
            return '300 - 800 TND / mois';
        }
        $level = mb_strtolower($level);
        if (str_contains($level, 'senior') || str_contains($level, 'expert') || str_contains($level, '5+')) {
            return '3 000 - 5 000 TND / mois';
        }
        if (str_contains($level, 'junior') || str_contains($level, 'débutant') || str_contains($level, '0-2')) {
            return '1 200 - 2 000 TND / mois';
        }
        return '1 800 - 3 000 TND / mois';
    }

    /**
     * Generate job-specific fallback interview questions when AI is unavailable.
     */
    private function generateFallbackQuestions(string $title, string $dept, string $level): array
    {
        $titleLower = mb_strtolower($title);
        $deptLower = mb_strtolower($dept);

        // Technical question based on job title / department
        $technical = 'Décrivez un projet technique que vous avez mené en lien avec ce poste.';
        if (str_contains($titleLower, 'develop') || str_contains($titleLower, 'dev') || str_contains($deptLower, 'info')) {
            $technical = 'Quelle architecture logicielle proposeriez-vous pour un projet « ' . $title . ' » et pourquoi ?';
        } elseif (str_contains($titleLower, 'design') || str_contains($titleLower, 'ux')) {
            $technical = 'Présentez votre processus de conception UX/UI pour un nouveau produit digital.';
        } elseif (str_contains($titleLower, 'market') || str_contains($deptLower, 'market')) {
            $technical = 'Comment mesureriez-vous le ROI d\'une campagne marketing pour ce type de poste ?';
        } elseif (str_contains($titleLower, 'data') || str_contains($titleLower, 'analyst')) {
            $technical = 'Décrivez votre approche pour nettoyer, analyser et visualiser un jeu de données complexe.';
        } elseif (str_contains($titleLower, 'commercial') || str_contains($titleLower, 'vente')) {
            $technical = 'Quelle stratégie de prospection adopteriez-vous pour atteindre vos objectifs commerciaux ?';
        } elseif (str_contains($titleLower, 'rh') || str_contains($deptLower, 'ressources')) {
            $technical = 'Comment géreriez-vous un processus de recrutement de A à Z pour ce département ?';
        } elseif (str_contains($titleLower, 'comptab') || str_contains($deptLower, 'financ')) {
            $technical = 'Décrivez votre expérience avec la clôture mensuelle et les normes comptables tunisiennes.';
        }

        // Behavioral question based on level
        $behavioral = 'Racontez une situation où vous avez dû gérer un conflit au travail. Comment l\'avez-vous résolu ?';
        if (str_contains(mb_strtolower($level), 'senior') || str_contains(mb_strtolower($level), 'manager')) {
            $behavioral = 'Décrivez comment vous avez mené une équipe à travers un projet complexe avec des délais serrés.';
        } elseif (str_contains(mb_strtolower($level), 'junior') || str_contains(mb_strtolower($level), 'stage')) {
            $behavioral = 'Parlez-nous d\'un projet académique ou personnel qui démontre votre motivation pour le poste de ' . $title . '.';
        }

        // Situational question always specific to the role
        $situational = 'Si vous rejoigniez notre équipe comme ' . $title . ', quelles seraient vos priorités durant les 90 premiers jours ?';

        return [$technical, $behavioral, $situational];
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FEATURE 4: Job Market News Hub
    //  Source: Google News RSS Feed (free, no API key required)
    //  Logic: Constructs a search query from the user's selected field,
    //         fetches the live RSS XML feed, parses <item> elements,
    //         and returns structured JSON with title, link, source, date.
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/api/market-news', name: 'api_market_news', methods: ['GET'])]
    public function marketNews(Request $request): JsonResponse
    {
        $field = $request->query->get('field', 'informatique');

        // Map user-friendly labels to effective Google News search queries
        $queryMap = [
            'informatique'  => 'recrutement+développeur+informatique+emploi',
            'finance'       => 'recrutement+finance+banque+emploi',
            'marketing'     => 'recrutement+marketing+digital+emploi',
            'rh'            => 'recrutement+ressources+humaines+emploi',
            'sante'         => 'recrutement+santé+médical+emploi',
            'ingenierie'    => 'recrutement+ingénieur+industrie+emploi',
            'design'        => 'recrutement+design+UX+UI+emploi',
            'data'          => 'recrutement+data+science+intelligence+artificielle+emploi',
        ];

        $searchQuery = $queryMap[$field] ?? $queryMap['informatique'];

        try {
            $rssUrl = 'https://news.google.com/rss/search?q=' . urlencode($searchQuery) . '&hl=fr&gl=FR&ceid=FR:fr';

            $response = $this->httpClient->request('GET', $rssUrl, [
                'timeout' => 8
            ]);

            $xml = $response->getContent(false);
            $feed = @simplexml_load_string($xml);

            if (!$feed || !isset($feed->channel->item)) {
                return $this->json(['articles' => [], 'source' => 'Google News RSS']);
            }

            $articles = [];
            $count = 0;
            foreach ($feed->channel->item as $item) {
                if ($count >= 6) break;
                $articles[] = [
                    'title'  => (string)$item->title,
                    'link'   => (string)$item->link,
                    'source' => (string)$item->source,
                    'date'   => date('d M Y', strtotime((string)$item->pubDate)),
                ];
                $count++;
            }

            return $this->json(['articles' => $articles, 'source' => 'Google News RSS']);

        } catch (\Exception $e) {
            return $this->json([
                'articles' => [],
                'source' => 'Google News RSS',
                'error' => 'Impossible de charger les actualités.'
            ]);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  SHARED: OpenRouter AI Call Helper
    //  Provider: OpenRouter.ai (aggregator for free LLMs)
    //  Models used: openrouter/auto (auto-routes to best available
    //               free model: Llama 3, Mistral, Gemma, etc.)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    private function callAi(string $prompt, array $fallback): JsonResponse
    {
        // Try OpenRouter first
        if (!empty($this->apiKey) && str_starts_with($this->apiKey, 'sk-or')) {
            $result = $this->callOpenRouter($prompt);
            if ($result !== null) return $this->json($result);
        }

        // Try Gemini as fallback
        $geminiKey = $_ENV['GEMINI_API_KEY'] ?? '';
        if (!empty($geminiKey) && !str_starts_with($geminiKey, 'sk-or') && $geminiKey !== 'your_gemini_api_key_here') {
            $result = $this->callGemini($prompt, $geminiKey);
            if ($result !== null) return $this->json($result);
        }

        return $this->json($fallback);
    }

    private function callOpenRouter(string $prompt): ?array
    {
        // Rotate through multiple free models to avoid rate limits
        $freeModels = [
            'nvidia/nemotron-3-super-120b-a12b:free',
            'nvidia/nemotron-3-nano-30b-a3b:free',
            'openai/gpt-oss-20b:free',
            'meta-llama/llama-3.3-70b-instruct:free',
            'google/gemma-3-27b-it:free',
        ];

        // Shuffle so we don't always hit the same model first
        shuffle($freeModels);

        foreach ($freeModels as $model) {
            try {
                $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                        'HTTP-Referer'  => 'http://localhost:8000',
                        'X-Title'       => 'Talentos',
                    ],
                    'json' => [
                        'model'       => $model,
                        'messages'    => [
                            ['role' => 'system', 'content' => 'You ALWAYS respond with pure valid JSON. No text, no markdown, no explanation.'],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'temperature' => 0.85,
                        'max_tokens'  => 500,
                    ],
                    'timeout' => 18,
                ]);
                $body = $response->toArray(false);
                if (isset($body['choices'][0]['message']['content'])) {
                    $result = $this->parseJsonFromRaw($body['choices'][0]['message']['content']);
                    if ($result !== null) {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                // This model failed (rate-limited/timeout), try next
                continue;
            }
        }
        return null;
    }

    private function callGemini(string $prompt, string $apiKey): ?array
    {
        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.8, 'maxOutputTokens' => 500],
                ],
                'timeout' => 15,
            ]);
            $body = $response->toArray(false);
            if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                return $this->parseJsonFromRaw($body['candidates'][0]['content']['parts'][0]['text']);
            }
        } catch (\Exception $e) {}
        return null;
    }

    private function parseJsonFromRaw(string $raw): ?array
    {
        $raw = trim($raw);
        // Strip markdown code fences
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```\s*$/', '', $raw);
        // Extract JSON object
        if (preg_match('/\{[\s\S]*\}/', $raw, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        return null;
    }
}
