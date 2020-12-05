<?php


// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

use function Symfony\Component\String\s;

$users = json_decode(file_get_contents('test.txt'));;
//print_r($users);
$availableIds = array_map(fn($user) => $user->id, $users);
//print_r($availableIds);

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('<a href="/users">пользователи </a>');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});



$app->get('/users', function ($request, $response) use ($users) {

    $params = [
        'users' => $users
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');
//^^ именнованный маршрут

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});


$app->get('/users/new', function ($request, $response) use ($users) {
    $id = count($users);
    $params = [
        'user' => ['name' => '', 'mail' => '', 'id' => $id],
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, array $args) use ($availableIds) {
    $id = $args['id'];
    if (in_array($id, $availableIds)) {
        return $response->write("ID ПОЛЬЗОВАТЕЛЯ: {$id}");
    }
    return $response->withStatus(404)->write("<a href=\"/users/new\">Новый пользователь </a> <h1>ОШИБКА 404</h1>");
});

$app->post('/users', function ($request, $response) use ($users, $router) {
    $user = $request->getParsedBodyParam('user');
    print_r($user);
    $users[] = $user;
    $encodedUsers = json_encode($users);
    file_put_contents('test.txt', $encodedUsers);
    return $response->withRedirect($router->urlFor('users'));
});




$app->run();