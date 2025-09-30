<?php
session_start();
require_once '../config/dbcon.php'; // Database connection

$incomingPrompt = trim($_POST['user_prompt'] ?? ($_POST['prompt'] ?? ''));
$reason = trim($_POST['reason'] ?? '');

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

// ❌ Block off-topic terms
$off_topic = ['nba', 'basketball', 'politics', 'movie', 'celebrity', 'tiktok', 'crypto'];
foreach ($off_topic as $term) {
  if (stripos($userPrompt, $term) !== false) {
    echo "⚠️ Sorry, I can only help with mental health topics.";
    exit;
  }
}

// 🚫 Guest message limit
if (!isset($_SESSION['user_id'])) {
  $_SESSION['guest_count'] = ($_SESSION['guest_count'] ?? 0) + 1;
  if ($_SESSION['guest_count'] > 5) {
    echo "⚠️ You’ve reached the guest message limit. Please log in.";
    exit;
  }
}

// ✅ Groq API call with language-matching behavior
function detect_language($text) {
  $t = mb_strtolower(' ' . $text . ' ', 'UTF-8');
  $markers = [' ang ', ' mga ', ' ng ', ' sa ', ' hindi', ' oo', ' po', ' opo', ' sige', ' kamusta', ' kumusta', ' salamat', ' sana ', ' kasi', ' naman', ' lang ', ' pala', ' wag', ' huwag', ' talaga', ' basta', ' pero', ' tsaka', ' ganyan', ' ganun', ' ganon', ' ako', ' ikaw', ' siya', ' natin', ' namin', ' ninyo'];
  $count = 0;
  foreach ($markers as $m) {
    if (mb_strpos($t, $m, 0, 'UTF-8') !== false) $count++;
  }
  return $count >= 2 ? 'tl' : 'en';
}
$lang = detect_language($userPrompt);

$secretKey = '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';
$endpoint = 'https://openrouter.ai/api/v1/chat/completions';

$messages = [
  [
    'role' => 'system',
    'content' => "You are a kind and professional mental health assistant. Follow these rules strictly:

1. Language Matching:
   - If user writes in English → respond in English
   - If user writes in Tagalog → respond in Tagalog (conversational, not deep Tagalog)

2. Response Format:
   - Start with a warm greeting
   - Use bullet points (•) for listing important points
   - Use **bold** for emphasizing key concepts
   - End EVERY response with a 'Summary/Motto' section:
     * For English: '**Key Points to Remember:**'
     * For Tagalog: '**Mahahalagang Punto:**'

3. Keep responses:
   - Supportive and focused on mental wellness
   - Clear and easy to understand
   - Professional but friendly

Example format:
[Your detailed response with **bold** key terms]

• Point 1
• Point 2
• Point 3

**Key Points to Remember:**
• [Short, memorable takeaway 1]
• [Short, memorable takeaway 2]"
  ]
];
if (!empty($reason)) {
  $messages[] = [
    'role' => 'system',
    'content' => "Context to consider (do not change language): Reason/Referral: {$reason}"
  ];
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
  'HTTP-Referer: http://localhost',
  'X-Title: MindCare-AI',
  'User-Agent: MindCare/1.0'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
if ($response === false) {
  echo "⚠️ Error: " . curl_error($ch);
  exit;
}
curl_close($ch);

// ✅ Decode and extract AI reply
// Log the raw response for debugging
error_log("OpenRouter Response: " . $response);

$json = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "⚠️ Error: Invalid JSON response - " . json_last_error_msg();
    exit;
}

if (isset($json['error'])) {
    echo "⚠️ API Error: " . ($json['error']['message'] ?? 'Unknown error');
    exit;
}

if (!isset($json['choices'][0]['message']['content'])) {
    echo "⚠️ Error: Unexpected response format";
    error_log("Unexpected response format: " . print_r($json, true));
    exit;
}

$reply = trim($json['choices'][0]['message']['content']);

echo "<strong>AI:</strong> " . htmlspecialchars($reply);

// ✅ Log to database if user is logged in
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];
  $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, message, reply, mode) VALUES (?, ?, ?, 'basic')");
  if ($stmt) {
    $stmt->bind_param("iss", $user_id, $userPrompt, $reply);
    $stmt->execute();
    $stmt->close();
  }
}
