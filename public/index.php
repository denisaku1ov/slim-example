<?php


// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

use function Symfony\Component\String\s;

$users = json_decode(file_get_contents('test.txt'));;
//print_r($users);

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});

$app->get('/users', function ($request, $response) use ($users) {

    $params = [
        'users' => $users
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});




$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});


$app->get('/users/new', function ($request, $response) use ($users) {
    $id = count($users) + 1;
    $params = [
        'user' => ['name' => '', 'mail' => '', 'id' => $id],
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});


$app->post('/users', function ($request, $response) use ($users) {
    $user = $request->getParsedBodyParam('user');
    $users[] = $user;
    $encodedUsers = json_encode($users);
    file_put_contents('test.txt', $encodedUsers);
    return $response->withRedirect('/users');
});




$app->run();