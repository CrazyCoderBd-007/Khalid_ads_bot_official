$web_app_url = "https://xxxxxxx.com/?id={$chatId}";

$keyboard = [
    'inline_keyboard' => [
        [
            [
                'text' => '🚀 Start Now',
                'web_app' => ['url' => $web_app_url]
            ]
        ]
    ]
];

$message = "<b>👋 Welcome to the <u>Ultimate Ad-Watching Bot</u>!</b>\n\n"
         . "💸 <i>Earn real rewards by watching short ads</i>\n"
         . "⚡ <i>Instant credits, no waiting!</i>\n"
         . "🎯 <b>Your time = Your money!</b>\n\n"
         . "<u>🔥 Don't miss out on free earnings. Click below to start now!</u>\n\n"
         . "<i>👨‍💻 Developed by</i> <b>@itsmekhalid007</b>";

BotAPI::sendMessage([
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'HTML',
    'reply_markup' => json_encode($keyboard)
]);
