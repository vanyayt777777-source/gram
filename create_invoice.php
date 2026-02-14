<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

try {
    // Получаем данные из запроса
    $input = json_decode(file_get_contents('php://input'), true);
    $rubAmount = floatval($input['amount'] ?? 0);
    
    if ($rubAmount < 9) {
        throw new Exception('Сумма должна быть не менее 9 RUB (0.1 USDT)');
    }
    
    // Конвертируем в USDT
    $usdtAmount = round($rubAmount / USDT_RATE, 2);
    
    // Создаем invoice через API Crypto Bot
    $ch = curl_init('https://pay.crypt.bot/api/createInvoice');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Crypto-Pay-API-Token: ' . BOT_API_TOKEN
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'asset' => 'USDT',
        'amount' => (string)$usdtAmount,
        'description' => "Оплата на сумму {$rubAmount} RUB",
        'paid_btn_name' => 'openBot',
        'paid_btn_url' => 'https://t.me/CryptoTestBot',
        'currency_type' => 'crypto'
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        throw new Exception('CURL Error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("API вернул код {$httpCode}: {$response}");
    }
    
    $data = json_decode($response, true);
    
    if (!$data['ok']) {
        throw new Exception($data['error'] ?? 'Неизвестная ошибка API');
    }
    
    // Возвращаем результат клиенту
    echo json_encode([
        'success' => true,
        'pay_url' => $data['result']['pay_url'],
        'invoice_id' => $data['result']['invoice_id'],
        'amount_usdt' => $usdtAmount
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
