<?php

require __DIR__.'/vendor/autoload.php';

use Telegram\Bot\Api;
use Dotenv\Dotenv;
use Telegram\Bot\Exceptions\TelegramSDKException;

function checkSite(string $url): bool
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
    ]);
    curl_exec($ch);

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return $statusCode >= 200 && $statusCode < 400;
}

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$list = explode(';', env('LIST'));
foreach ($list as $siteInfo) {
    [$url, $token] = explode('|', $siteInfo);

    if (checkSite($url) === true) {
        continue;
    }

    $upToken = strtoupper($token);
    $telegramApi = env("TELEGRAM_API_$upToken");
    $receiversIds = env("RECEIVERS_$upToken");

    try {
        $telegram = new Api($telegramApi);
    } catch (TelegramSDKException $e) {
        print "Необходимо заполнить параметр TELEGRAM_API_$upToken" . PHP_EOL;
        exit;
    }

    if ($receiversIds) {
        $chatIds = explode(',', $receiversIds);
        foreach ($chatIds as $chatId) {
            $message = 'Проблема с подключением к сайту ' . $url;
            try {
                if (empty($chatId)) {
                    print $message . PHP_EOL;
                } else {
                    $telegram->sendMessage(['chat_id' => $chatId, 'text' => $message]);
                }
            } catch (TelegramSDKException $e) {
                error_log("Проблема с отправкой сообщения об ошибке с чатом $chatId: " . $e->getMessage());
            }
        }
    }
}
