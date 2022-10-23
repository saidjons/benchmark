<?php
use Swoole\Coroutine as Co;

use Swoole\Http\Server as HttpServer;

Co::set(['hook_flags' => SWOOLE_HOOK_TCP]);
$server = new Swoole\HTTP\Server("127.0.0.1", 9000);
$server->set([
    'worker_num' => 4,      // The number of worker processes to start
    'task_worker_num' => 4 , // The amount of task workers to start
    'backlog' => 128, 
]);
$server->on('start', function ($server) {
    echo "Server started at http://127.0.0.1:9000\n";
});
$server->on('task', function ($server) {
    echo "on task\n";
});
$server->on('request', function ($request, $response) {


go(function () use ($response){
    $db = new Swoole\Coroutine\Mysql;
    $server = [
        'host'     => '127.0.0.1',
        'user'     => 'root',
        'password' => '1209-hiRoot',
        'database' => 'benchmark',
    ];
    $db->connect($server);
    $sql = "INSERT INTO MyGuests (firstname, lastname, email) VALUES ('John', 'Doe', 'john@example.com')";
    $stmt = $db->prepare($sql);

    $ret = $stmt->execute();
    $response->header('Content-Type', 'application/json');
    $response->header('Accept', 'application/json');
    $response->end(json_encode(['message'=>'ok']));
});
  
});
$server->start();

// Enable the hook for MySQL: PDO/MySQLi

 