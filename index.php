<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__.'/vendor/autoload.php';

// Pending Task
$pendingTask = null;
function getPendingTask() {
    global $pendingTask;
    return $pendingTask;
};
function setPendingTask($callable) {
    global $pendingTask;
    $pendingTask = $callable;
};
function runPendingTask() {
    global $pendingTask;
    $pendingTask();
};

// Middleware
$pendingTaskMiddleware = function (Request $request, Response $response, $next) {
    $response = $next($request, $response);
    if (empty(getPendingTask())) {
        return $response;
    }

    $content = $response->getBody()->getContents();
    $size = $response->getBody()->getSize();

    ignore_user_abort(true);
    header('Content-Type: application/json');
    header('Connection: Close');
    header('Keep-Alive: timeout=0, max=0');
    header('Content-Length: '.$size.'');
    echo $content;
    ob_flush();
    flush();

    runPendingTask();
    exit();
};

$app = new \Slim\App;
$app->add($pendingTaskMiddleware);
$app->get('/', function (Request $request, Response $response, array $args) {
    setPendingTask(function () {
        echo 'BG Start';

        sleep(10); // eg. Resquest to an API
        // exec('notepad'); // Test for Windows

        echo 'BG End';
    });

    $response->withJson([
        'success' => true,
        'time' => date('Y-m-d H:i:s'),
    ]);

    return $response;
});

$app->run();
