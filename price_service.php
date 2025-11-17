<?php

function getRealTimeCryptoPrices() {
    $prices = [
        'BTC' => 45000.00, // Fallback price
        'ETH' => 3000.00   // Fallback price
    ];
    
    try {
        // Using CoinGecko API (free, no API key required for basic usage)
        $url = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum&vs_currencies=usd';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            
            if (isset($data['bitcoin']['usd'])) {
                $prices['BTC'] = $data['bitcoin']['usd'];
            }
            if (isset($data['ethereum']['usd'])) {
                $prices['ETH'] = $data['ethereum']['usd'];
            }
        }
    } catch (Exception $e) {
        // Use fallback prices if API fails
        error_log("Price API error: " . $e->getMessage());
    }
    
    return $prices;
}

// Alternative API (Binance) if CoinGecko fails
function getBinancePrices() {
    $prices = [
        'BTC' => 45000.00,
        'ETH' => 3000.00
    ];
    
    try {
        // BTC price
        $btc_url = 'https://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $btc_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['price'])) {
                $prices['BTC'] = $data['price'];
            }
        }
        
        // ETH price
        $eth_url = 'https://api.binance.com/api/v3/ticker/price?symbol=ETHUSDT';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $eth_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['price'])) {
                $prices['ETH'] = $data['price'];
            }
        }
    } catch (Exception $e) {
        error_log("Binance API error: " . $e->getMessage());
    }
    
    return $prices;
}

?>