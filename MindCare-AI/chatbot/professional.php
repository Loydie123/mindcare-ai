<?php
session_start();
require_once '../config/dbcon.php';

$incomingPrompt = trim($_POST['user_prompt'] ?? ($_POST['prompt'] ?? ''));
$reason = trim($_POST['reason'] ?? '');

// Debug: Log the received reason
error_log("Received reason: " . ($reason ?: 'none'));

if (empty($incomingPrompt)) {
  echo "Please enter a message.";
  exit;
}

// Extract raw user message if frontend sent a composed prompt with a Message: marker
$userPrompt = $incomingPrompt;
if (stripos($incomingPrompt, 'Message:') !== false) {
  if (preg_match('/Message:\s*(.+)$/is', $incomingPrompt, $m)) {
    $userPrompt = trim($m[1]);
  }
}

// ✅ Static Professional Responses by Category
$data = [
  // Academic Performance
  "struggling with studies" => "**Let's break this down together:**\n• First, know that academic challenges are common\n• We can create a study strategy that works for you\n• **Key Points:**\n  • Small steps lead to big progress\n  • You're not alone in this journey",
  
  // Self-Control/Behavior
  "can't control myself" => "**Understanding behavioral patterns:**\n• Let's identify your triggers\n• We'll develop coping strategies together\n• **Remember:**\n  • You have the power to change\n  • Every small improvement matters",
  
  // Bullying
  "being bullied" => "**Your safety and well-being matter:**\n• You're not alone in this\n• It's not your fault\n• **Important steps:**\n  • Tell a trusted adult\n  • Document incidents\n  • Stay with friends when possible",
  
  // Family Issues
  "family problems" => "**Family dynamics can be challenging:**\n• Your feelings are valid\n• We can work on communication strategies\n• **Remember:**\n  • You deserve support\n  • There are ways to improve things",
  
  // Tagalog Responses
  "hindi ko na kaya" => "**Unawain natin ito:**\n• Hindi ka nag-iisa\n• May solusyon tayo sa bawat problema\n• **Tandaan:**\n  • Kaya mo ito\n  • Nandito kami para tumulong",
  
  "nahihirapan ako" => "**Magkasama tayo dito:**\n• Normal lang ang nararamdaman mo\n• May mga hakbang tayo para makatulong\n• **Mahahalagang Punto:**\n  • May pag-asa palagi\n  • Hindi ka nag-iisa sa laban na ito",
  
  // General Support
  "i need help" => "**I'm here to support you:**\n• You've taken a brave step by reaching out\n• We'll work through this together\n• **Remember:**\n  • Asking for help is a sign of strength\n  • You don't have to face this alone",
  
  "pagod na ako" => "**Naiintindihan kita:**\n• Normal lang mapagod\n• Magpahinga kung kailangan\n• **Tandaan:**\n  • Hindi ka nag-iisa\n  • May bukas pa para sa'yo"
];

// ✅ Static match (case-insensitive)
$response = null;
$hay = mb_strtolower($userPrompt, 'UTF-8');
foreach ($data as $key => $value) {
  if (mb_strpos($hay, mb_strtolower($key, 'UTF-8')) !== false) {
    $response = $value;
    break;
  }
}

// ✅ If no match → Ask Groq with language-matching behavior
if (!$response) {
  // Language detection
  function detect_language($text) {
    $t = mb_strtolower(' ' . $text . ' ', 'UTF-8');
    $markers = [' ang ', ' mga ', ' ng ', ' sa ', ' hindi', ' oo', ' po', ' opo', ' sige', ' kamusta', ' kumusta', ' salamat', ' sana ', ' kasi', ' naman', ' lang ', ' pala', ' wag', ' huwag', ' talaga', ' basta', ' pero', ' tsaka', ' ganyan', ' ganun', ' ganon', ' ako', ' ikaw', ' siya', ' natin', ' namin', ' ninyo'];
    $count = 0;
    foreach ($markers as $m) { if (mb_strpos($t, $m, 0, 'UTF-8') !== false) $count++; }
    return $count >= 2 ? 'tl' : 'en';
  }
  $lang = detect_language($userPrompt);

  $secretKey = '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';
  $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

  $messages = [
    [
      'role' => 'system',
      'content' => "You are a mental health professional. Follow these guidelines strictly:

1. Language Matching:
   - If user writes in English → respond in English
   - If user writes in Tagalog → respond in Tagalog (conversational, not deep Tagalog)

2. Professional Response Format:
   - Begin with a professional greeting
   - Use bullet points (•) for structured advice
   - Use **bold** for emphasizing clinical points
   - End EVERY response with a structured summary:
     * For English: '**Professional Recommendations:**'
     * For Tagalog: '**Mga Pangunahing Rekomendasyon:**'

3. Clinical Approach:
   - Maintain professional therapeutic tone
   - Provide evidence-based suggestions
   - Include coping strategies
   - Suggest practical exercises when appropriate

Example format:
[Your professional assessment with **key clinical terms**]

• Clinical observation 1
• Recommended strategy
• Practical exercise

**Professional Recommendations:**
• [Key clinical recommendation 1]
• [Key clinical recommendation 2]"
    ]
  ];
  if (!empty($reason)) {
    $messages[] = [ 'role' => 'system', 'content' => "Context to consider (do not change language): Reason/Referral: {$reason}" ];
  }
  $messages[] = [ 'role' => 'user', 'content' => $userPrompt ];

  $data = [
    'messages' => $messages,
    'model' => 'openai/gpt-3.5-turbo',
    'temperature' => 0.7
  ];

  $ch = curl_init($endpoint);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $secretKey,
    'Content-Type: application/json',
    'HTTP-Referer: https://mindcare-ai.com', // Replace with your actual domain
    'X-Title: MindCare-AI'
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

  $res = curl_exec($ch);
  if ($res === false) {
    echo "⚠️ Error: " . curl_error($ch);
    exit;
  }
  curl_close($ch);

  $json = json_decode($res, true);
  $response = trim($json['choices'][0]['message']['content'] ?? '⚠️ AI Error: No response.');
}

// ✅ Log to chat_logs if logged in
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, message, reply, mode) VALUES (?, ?, ?, 'professional')");
  if ($stmt) {
    $stmt->bind_param("iss", $_SESSION['user_id'], $userPrompt, $response);
    $stmt->execute();
    $stmt->close();
  }
}

// ✅ Show output
echo "<strong>AI:</strong> " . htmlspecialchars($response);
