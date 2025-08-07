<?php
require_once 'Token.php';

class Auth {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function login($username, $password) {
        // Verifica se usuário existe
        if (!isset($this->config['users'][$username])) {
            return false;
        }
        
        $user = $this->config['users'][$username];
        
        // Verifica senha
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        // Gera token
        return Token::generate(
            $username,
            $this->config['secret_key'],
            $this->config['token_expiration']
        );
    }
    
    public function validateToken($token) {
        return Token::validate($token, $this->config['secret_key']);
    }
}