<?php

/**
 * Created by: Erlang Parasu 2019.
 * 
 * Sample app to run additional task without blocking the http response
 */

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
    /** @var Response $response */
    $response = $next($request, $response);
    if (empty(getPendingTask())) {
        return $response;
    }

    $content = (string) $response->getBody();
    $size = mb_strlen($content);

    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    ignore_user_abort(true);
    header('Content-Type: application/json');
    header('Connection: close');
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
    $strTime = date('Y_m_d__H_i_s');
    setPendingTask(function () use ($strTime) {
        // Sample of long running background task
        echo 'pending task start';
        sleep(10);
        file_put_contents('log_for_request__'.$strTime.'.txt', '');
        echo 'pending task end';
    });

    $data = [
        'success' => true,
        'time' => $strTime,
    ];

    return $response->withJson($data);
});

$app->run();
