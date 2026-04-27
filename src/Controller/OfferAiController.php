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
        $desc = mb_substr(strip_tags($offer->getDescription() ?? ''), 0, 400);

        $prompt = "Tu es un consultant RH expert du marché de l'emploi en Tunisie et au Maghreb. Analyse ce poste:
Titre: $title | Niveau: $level | Lieu: $location | Secteur: $dept
Description: $desc

Réponds UNIQUEMENT avec un objet JSON valide (pas de texte avant/après, pas de ```json):
{\"salary_estimation\": \"X - Y TND / mois\", \"interview_questions\": [\"Question technique probable 1?\", \"Question comportementale 2?\", \"Mise en situation 3?\"]}";

        return $this->callAi($prompt, [
            'salary_estimation' => 'Selon profil et expérience',
            'interview_questions' => [
                'Pouvez-vous décrire votre expérience la plus pertinente pour ce poste ?',
                'Comment gérez-vous les situations de stress ou de deadlines serrées ?',
                'Où vous voyez-vous dans 3 ans ?'
            ]
        ]);
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
