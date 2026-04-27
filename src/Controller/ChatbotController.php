<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    private const SYSTEM_PROMPT = <<<EOT
Tu es Sara, l'assistante IA de Talentos — une plateforme de recrutement et de gestion RH.

🎯 TON RÔLE :
- Aider les candidats à trouver des offres d'emploi et postuler
- Donner des conseils pour rédiger un CV et une lettre de motivation
- Préparer aux entretiens d'embauche
- Expliquer comment utiliser la plateforme Talentos (créer un profil, postuler, suivre ses candidatures, messagerie, événements)
- Donner des insights sur le marché de l'emploi
- Conseiller sur le développement de carrière

🚫 CE QUE TU NE FAIS PAS :
- Tu ne réponds PAS à des questions hors sujet (sport, cuisine, politique, divertissement, etc.)
- Si on te pose une question hors de ton domaine, réponds poliment : "Je suis spécialisée dans le recrutement et l'utilisation de Talentos. Je ne peux malheureusement pas vous aider sur ce sujet. 😊 Avez-vous une question sur votre carrière ou notre plateforme ?"

💡 TON STYLE :
- Réponses courtes et utiles (2-4 phrases max)
- Ton professionnel mais chaleureux
- Utilise des emojis avec modération
- Réponds en français par défaut, mais adapte-toi à la langue du message
EOT;

    #[Route('/api/chatbot', name: 'api_chatbot', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        // Rate limiting via session
        $session = $request->getSession();
        $dailyKey = 'chatbot_count_' . date('Y-m-d');
        $count = $session->get($dailyKey, 0);
        $limit = 30; // 30 per day per session

        if ($count >= $limit) {
            return new JsonResponse([
                'success' => false,
                'message' => "Vous avez atteint la limite de $limit messages aujourd'hui. Revenez demain ! 😊",
                'remaining' => 0,
            ]);
        }

        $data = json_decode($request->getContent(), true);
        $userMessage = trim($data['message'] ?? '');
        $history = $data['history'] ?? [];

        if (empty($userMessage)) {
            return new JsonResponse(['success' => false, 'message' => 'Message vide.']);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? '';
        if (empty($apiKey) || $apiKey === 'your_gemini_api_key_here') {
            return new JsonResponse(['success' => false, 'message' => 'La clé d\'API Gemini n\'a pas été configurée.']);
        }
        // Common payload elements
        $isGroq = str_starts_with($apiKey, 'gsk_');
        $isOpenRouter = str_starts_with($apiKey, 'sk-or-');
        $isOpenAIFormat = $isGroq || $isOpenRouter;
        $url = '';
        $payloadArray = [];
        $httpHeader = ['Content-Type: application/json'];

        if ($isOpenAIFormat) {
            // --- GROQ / OPENROUTER API (OpenAI Compatible) ---
            if ($isOpenRouter) {
                $url = 'https://openrouter.ai/api/v1/chat/completions';
                // OpenRouter requires HTTP Referer for rankings (optional but good practice)
                $httpHeader[] = 'HTTP-Referer: http://localhost:8000'; 
                $httpHeader[] = 'X-Title: TalentosWeb';
            } else {
                $url = 'https://api.groq.com/openai/v1/chat/completions';
            }
            
            $httpHeader[] = 'Authorization: Bearer ' . $apiKey;
            
            $messages = [];
            $messages[] = ['role' => 'system', 'content' => self::SYSTEM_PROMPT];
            
            $recentHistory = array_slice($history, -10);
            foreach ($recentHistory as $msg) {
                $messages[] = ['role' => $msg['role'] ?? 'user', 'content' => $msg['content'] ?? ''];
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $payloadArray = [
                'model' => $isOpenRouter ? 'openrouter/free' : 'llama3-8b-8192', // Automatically chooses the most reliable free model
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 300
            ];
            
        } else {
            // --- GEMINI API ---
            // Fallback to gemini-1.5-flash which has fewer regional blocks than 2.0
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";
            
            $contents = [];
            $recentHistory = array_slice($history, -10);
            foreach ($recentHistory as $msg) {
                $role = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
                $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content'] ?? '']]];
            }
            $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

            $payloadArray = [
                'systemInstruction' => [
                    'role' => 'user', 
                    'parts' => [['text' => self::SYSTEM_PROMPT]]
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 300,
                    'topP' => 0.9,
                ]
            ];
        }

        $payload = json_encode($payloadArray);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $httpHeader,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            $errDetail = '';
            $decodedErr = json_decode($response, true);
            if ($isOpenAIFormat && isset($decodedErr['error']['message'])) {
                $errDetail = $decodedErr['error']['message'];
            } elseif (isset($decodedErr['error']['message'])) {
                $errDetail = $decodedErr['error']['message'];
            }
            return new JsonResponse([
                'success' => false,
                'message' => 'Service temporairement indisponible (' . $httpCode . '). ' . $errDetail,
            ]);
        }

        $result = json_decode($response, true);
        
        if ($isOpenAIFormat) {
            $reply = $result['choices'][0]['message']['content'] ?? null;
        } else {
            $reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        if (!$reply) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Je n\'ai pas pu générer une réponse. Reformulez votre question.',
            ]);
        }

        // Increment counter
        $session->set($dailyKey, $count + 1);

        return new JsonResponse([
            'success' => true,
            'message' => $reply,
            'remaining' => $limit - $count - 1,
        ]);
    }
}
