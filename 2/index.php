<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';

Sentry\init(['dsn' => getenv('SENTRY_DSN') ]);

$app = AppFactory::create();

$app->addRoutingMiddleware();

$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Hello!");
    return $response;
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

// Setup Sentry
$sentry = function (
    ServerRequestInterface $request,
    Throwable $exception
) use ($app) {
    Sentry\captureException($exception);
    $payload = ['error' => $exception->getMessage()];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(404);
};

// Sentry will handle errors
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler($sentry);


$app->run();
