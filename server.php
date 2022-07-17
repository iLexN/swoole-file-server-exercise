<?php
declare(strict_types=1);

use App\FileServer\Container\Container;

include 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$container = new Container();



$server = new Swoole\HTTP\Server('127.0.0.1', 9501);

$server->set([
    'worker_num' => 4, // The number of worker processes to start
//    'enable_static_handler' => true,
//    'document_root' => __DIR__ . '/public',
]);

// Triggered when the HTTP Server starts, connections are accepted after this callback is executed
$server->on('start', function ($server) use ($container) {
    $container->logger()->info("Swoole HTTP Server Started @ 127.0.0.1:9501", ['a']);
});
// Triggered when new worker processes starts
$server->on('WorkerStart', function ($server, $workerId) use ($container) {
    $container->logger()->info("Worker Started: $workerId", ['a']);
});


// The main HTTP server request callback event, entry point for all incoming HTTP requests
$server->on('request',
    function (Swoole\Http\Request $request, Swoole\Http\Response $response) use
    (
        $container
    ) {
        //($container->controllerDownload())($request, $response);
        ($container->controllerDownload())($request, $response);
    });

$server->start();
