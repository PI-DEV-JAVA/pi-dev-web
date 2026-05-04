<?php

namespace App\Controller;

use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
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
        $profileText = ($profile->getProfessionalTitle() ?? 'Non renseigné')
            . '. Expérience: ' . ($profile->getYearsOfExperience() ?? 0) . ' ans. '
            . ($profile->getSummary() ?? '');

        $offerText = $offer->getTitle()
            . ' (' . ($offer->getContractType() ?? '') . ', ' . ($offer->getExperienceLevel() ?? '') . '). '
            . mb_substr(strip_tags($offer->getDescription() ?? ''), 0, 600);

        $prompt = "Tu es un expert RH. Compare ce profil candidat avec cette offre d'emploi et donne un score de compatibilité réaliste de 0 à 100.

OFFRE: $offerText

CANDIDAT: $profileText

Réponds UNIQUEMENT avec un objet JSON valide (pas de texte avant/après, pas de ```json):
{\"score\": 75, \"strengths\": [\"Atout 1\", \"Atout 2\"], \"weaknesses\": [\"Lacune 1\", \"Lacune 2\"]}";

        return $this->callAi($prompt, [
            'score' => 50,
            'strengths' => ['Profil en cours d\'évaluation'],
            'weaknesses' => ['Complétez votre profil pour une meilleure analyse']
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

        $prompt = "You are an HR salary expert specializing in the Tunisian job market.

TASK: Estimate a realistic monthly salary range in TND (Tunisian Dinar) for this position, then suggest 3 likely interview questions.

JOB DETAILS:
- Title: $title
- Level: $level
- Location: $location
- Sector: $dept
- Contract: $contract
- Description: $desc
{$salaryHint}

TUNISIAN SALARY REFERENCE (monthly net, 2024-2025):
- Junior (0-2 yrs): 800-1500 TND for most sectors, 1200-2000 TND for IT/engineering
- Mid (2-5 yrs): 1500-2500 TND general, 2000-3500 TND IT/engineering
- Senior (5+ yrs): 2500-4000 TND general, 3000-6000 TND IT/engineering
- Manager: 3500-7000+ TND
- Internship (Stage): 300-800 TND

RULES:
- ALWAYS provide a concrete salary range in TND/mois. NEVER say 'non applicable' or refuse.
- Adapt the range based on the job title, experience level, location, and sector.
- Interview questions should be specific to this role (1 technical, 1 behavioral, 1 situational).
- Reply in the SAME LANGUAGE as the job description.

Respond ONLY with a valid JSON object (no text before/after, no markdown):
{\"salary_estimation\": \"X - Y TND / mois\", \"interview_questions\": [\"Technical question?\", \"Behavioral question?\", \"Situational question?\"]}";

        return $this->callAi($prompt, [
            'salary_estimation' => $this->estimateFallbackSalary($level, $contract),
            'interview_questions' => [
                'Pouvez-vous décrire votre expérience la plus pertinente pour ce poste ?',
                'Comment gérez-vous les situations de stress ou de deadlines serrées ?',
                'Où vous voyez-vous dans 3 ans ?'
            ]
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
        if (empty($this->apiKey) || !str_starts_with($this->apiKey, 'sk-or')) {
            return $this->json($fallback);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => 'http://localhost:8000',
                    'X-Title'       => 'Talentos',
                ],
                'json' => [
                    'model'       => 'openrouter/auto',
                    'messages'    => [
                        ['role' => 'system', 'content' => 'Tu réponds TOUJOURS en JSON pur. Jamais de texte, jamais de markdown.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                    'max_tokens'  => 400,
                ],
                'timeout' => 15,
            ]);

            $body = $response->toArray(false);

            if (isset($body['choices'][0]['message']['content'])) {
                $raw = trim($body['choices'][0]['message']['content']);

                // Strip markdown code fences if model wraps in ```json ... ```
                $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
                $raw = preg_replace('/\s*```\s*$/', '', $raw);

                // Try to extract JSON object from any surrounding text
                if (preg_match('/\{[\s\S]*\}/', $raw, $matches)) {
                    $data = json_decode($matches[0], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $this->json($data);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silent fallback
        }

        return $this->json($fallback);
    }
}
