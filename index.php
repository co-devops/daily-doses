<?php

require __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;

Sentry\init(['dsn' => getenv('SENTRY_DSN') ]);

$app = AppFactory::create();

$app->addRoutingMiddleware();

$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Hello! ".serialize($args).' - '.($request->getUri()));
    return $response;
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name; ". serialize($args).' - ' .($request->getUri()));
    return $response;
});

$app->get('/events', function (Request $request, Response $response, array $args) {
    $client = new Google_Client([
        'scopes' => [Google_Service_Sheets::SPREADSHEETS_READONLY],
        'use_application_default_credentials' => true
    ]);

    $spreadsheetId = '1e2GXQAvCEeJ-iUtQzTCSI_US-6Hh1K_22rYbbokyzj0';
    $range = 'Events!A:G';
    $service = new Google_Service_Sheets($client);
    $sheet_response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $sheet_response->getValues();
    $response->getBody()->write(json_encode($values));
    return $response;
});

// Setup Sentry
$sentry = function (
    ServerRequestInterface $request,
    Throwable $exception
) use ($app) {
    Sentry\captureException($exception);
    $payload = [
        'error' => $exception->getMessage(),
        'uri' => $request->getUri()
    ];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(
        json_encode($payload, JSON_UNESCAPED_UNICODE)
    );

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(404);
};

// Sentry will handle errors
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler($sentry);


$app->run();
