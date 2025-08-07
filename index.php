<?php

use Slim\Factory\AppFactory;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Microwave\Microwave;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Server\RequestHandlerInterface;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Auth/Auth.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING); // Suprime notices e warnings

// Cria a aplicação Slim
$app = AppFactory::create();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Middleware CORS (deve vir antes do middleware de autenticação)
$app->add(function (Request $request, RequestHandlerInterface $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});


// Middleware para parsear JSON
$app->addBodyParsingMiddleware();

// Rota de login (pública)
$app->post('/login', function (Request $request, Response $response) {
    $config = include __DIR__ . '/config/config.php';
    $auth = new Auth($config['auth']);

    $data = $request->getParsedBody();

    if (!isset($data['username']) || !isset($data['password'])) {
        $response->getBody()->write(json_encode(['error' => 'Username and password are required']));
        return $response->withStatus(400);
    }

    $token = $auth->login($data['username'], $data['password']);

    if ($token) {
        $response->getBody()->write(json_encode([
            'success' => true,
            'token' => $token,
            'message' => 'Login successful'
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } else {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Invalid credentials'
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
});

// Rota de logout (pública)
$app->post('/logout', function (Request $request, Response $response) {
    // Em JWT, o logout é feito no cliente descartando o token
    $response->getBody()->write(json_encode([
        'status' => 'success',
        'message' => 'Faça logout descartando o token no cliente'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Middleware de autenticação JWT
$authMiddleware = function (Request $request, RequestHandlerInterface $handler) {
    // Rotas que não precisam de autenticação
    $publicRoutes = ['/login', '/logout'];

    if (in_array($request->getUri()->getPath(), $publicRoutes)) {
        return $handler->handle($request);
    }

    // Verifica o token JWT
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Token de autenticação não fornecido'
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }

    $token = $matches[1];

    // Valida o token (usando sua classe Auth existente)
    $config = include __DIR__ . '/config/config.php';
    $auth = new Auth($config['auth']);

    if (!$auth->validateToken($token)) {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Token inválido ou expirado'
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }

    // Token válido, continua com a requisição
    return $handler->handle($request);
};

// Grupo de rotas protegidas
$app->group('', function (RouteCollectorProxy $group) {
    // Rota para iniciar o micro-ondas
    $group->post('/start', function (Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();
            $time = $data['time'] ?? 0;
            $power = $data['power'] ?? 0;

            $microwave = new Microwave();
            $result = $microwave->start((int)$time, (int)$power);

            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (InvalidArgumentException $e) {
            // Erros de validação (400 Bad Request)
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        } catch (Exception $e) {
            // Erros internos (500 Internal Server Error)
            error_log('Erro no endpoint /start: ' . $e->getMessage());

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Erro interno no servidor'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    });

    // Rota para pausar
    $group->post('/pause', function (Request $request, Response $response) {
        $microwave = new Microwave();
        $result = $microwave->pause();

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Rota para retomar
    $group->post('/resume', function (Request $request, Response $response) {
        $microwave = new Microwave();
        $result = $microwave->resume();

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Rota para verificar status
    $group->get('/status', function (Request $request, Response $response) {
        $microwave = new Microwave();
        $result = $microwave->getStatus();

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    });
})->add($authMiddleware);

// Executa a aplicação
$app->run();
