<?php
class Token {
    public static function generate($userId, $secretKey, $expiration) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'exp' => time() + $expiration
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secretKey, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    public static function validate($token, $secretKey) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        
        list($header, $payload, $signature) = $parts;
        
        // Verifica assinatura
        $validSignature = hash_hmac(
            'sha256',
            $header . "." . $payload,
            $secretKey,
            true
        );
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
        
        if ($base64Signature !== $signature) return false;
        
        // Verifica expiração
        $decodedPayload = json_decode(base64_decode($payload));
        if (isset($decodedPayload->exp) && $decodedPayload->exp < time()) {
            return false;
        }
        
        return $decodedPayload;
    }
}