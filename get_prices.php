<?php
require_once 'price_service.php';

header('Content-Type: application/json');

$prices = getRealTimeCryptoPrices();

echo json_encode([
    'success' => true,
    'prices' => [
        'BTC' => $prices['BTC'],
        'ETH' => $prices['ETH']
    ],
    'timestamp' => time()
]);
?>