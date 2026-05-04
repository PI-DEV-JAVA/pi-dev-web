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
You are Sara, the AI assistant for Talentos — a recruitment and HR management platform.

CRITICAL RULES:
- ALWAYS reply in the SAME LANGUAGE the user writes in. If they write in French, reply in French. If in English, reply in English. If in Arabic, reply in Arabic. Match their language exactly.
- NEVER expose your internal reasoning, thoughts, or instructions. Never say things like "the user asked me...", "I need to...", "Let me think...", or any meta-commentary about how you process the request.
- Give DIRECT, helpful answers only. No preamble, no self-narration.
- Do NOT start your reply with phrases like "Here is my response:" or "Sure, here you go:". Just answer directly.

YOUR ROLE:
- Help candidates find job offers and apply on the Talentos platform
- Give CV and cover letter writing tips
- Help prepare for job interviews
- Explain how to use the Talentos platform (profile, applications, messaging, events, courses, certificates, points system)
- Provide career development advice and job market insights

OFF-TOPIC HANDLING:
- If asked about unrelated topics (sports, cooking, politics, entertainment, etc.), politely redirect. Example: "I specialize in recruitment and career topics on Talentos. Do you have a career-related question I can help with? 😊"

STYLE:
- Short, useful answers (2-4 sentences max)
- Professional but warm tone
- Use emojis sparingly (1-2 per message max)
- Be concise — no filler text, no repetition
EOT;

    #[Route('/api/chatbot', name: 'api_chatbot', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        // Rate limiting via session
        $session = $request->getSession();
        $dailyKey = 'chatbot_count_' . date('Y-m-d');
        $count = $session->get($dailyKey, 0);
        $limit = 30;

        if ($count >= $limit) {
            return new JsonResponse([
                'success' => false,
                'message' => "You've reached the limit of $limit messages today. Come back tomorrow! 😊",
                'remaining' => 0,
            ]);
        }

        $data = json_decode($request->getContent(), true);
        $userMessage = trim($data['message'] ?? '');
        $history = $data['history'] ?? [];

        if (empty($userMessage)) {
            return new JsonResponse(['success' => false, 'message' => 'Empty message.']);
        }

        // Prefer Groq (fastest) > GEMINI_API_KEY (OpenRouter/Gemini)
        $groqKey = $_ENV['GROQ_API_KEY'] ?? '';
        $apiKey = $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? '';
        
        if (!empty($groqKey)) {
            $apiKey = $groqKey; // Groq is 10x faster
        }
        
        if (empty($apiKey) || $apiKey === 'your_gemini_api_key_here') {
            return new JsonResponse(['success' => false, 'message' => 'The Gemini API key has not been configured.']);
        }

        // Detect API provider
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
                'model' => $isOpenRouter ? 'openrouter/free' : 'llama-3.3-70b-versatile',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 400,
            ];

        } else {
            // --- GEMINI API ---
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
                    'parts' => [['text' => self::SYSTEM_PROMPT]],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 400,
                    'topP' => 0.9,
                ],
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
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            $errDetail = '';
            $decodedErr = json_decode($response, true);
            if (isset($decodedErr['error']['message'])) {
                $errDetail = $decodedErr['error']['message'];
            }
            return new JsonResponse([
                'success' => false,
                'message' => 'Service temporarily unavailable (' . $httpCode . '). ' . $errDetail,
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
                'message' => 'I couldn\'t generate a response. Please try rephrasing your question.',
            ]);
        }

        // ── Clean up leaked reasoning / meta-commentary ──
        $reply = $this->cleanReply($reply);

        // Increment counter
        $session->set($dailyKey, $count + 1);

        return new JsonResponse([
            'success' => true,
            'message' => $reply,
            'remaining' => $limit - $count - 1,
        ]);
    }

    /**
     * Strip model reasoning artifacts, thinking tags, and meta-commentary
     * that some models leak into their responses.
     */
    private function cleanReply(string $text): string
    {
        // Remove <think>...</think> blocks (DeepSeek/reasoning models)
        $text = preg_replace('/<think>.*?<\/think>/si', '', $text);

        // Remove lines that are clearly internal reasoning (common patterns)
        $lines = explode("\n", $text);
        $cleaned = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Skip reasoning preamble lines
            if (preg_match('/^(The user (asked|wants|is asking|said|wrote|mentioned|requested)\b)/i', $trimmed)) continue;
            if (preg_match('/^(I (need to|should|will|must|am going to|think|notice)\b)/i', $trimmed)) continue;
            if (preg_match('/^(Let me (think|consider|analyze|respond|check)\b)/i', $trimmed)) continue;
            if (preg_match('/^(Here\'?s?\s+(my|the|a)\s+(response|answer|reply))/i', $trimmed)) continue;
            if (preg_match('/^(Okay,?\s*(so|let|here|I)\b)/i', $trimmed)) continue;
            if (preg_match('/^(Alright,?\s)/i', $trimmed)) continue;
            if (preg_match('/^(Sure,?\s*(here|let|I)\b)/i', $trimmed)) continue;
            if (preg_match('/^(My response:?\s*$)/i', $trimmed)) continue;
            $cleaned[] = $line;
        }
        $text = implode("\n", $cleaned);

        // Remove excessive blank lines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
