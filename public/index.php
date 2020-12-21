<?php


// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use function Symfony\Component\String\s;
session_start();

$users = json_decode(file_get_contents('users.json'));;
//print_r($users);
function validate($user)
{
    $errors = [];
    if (empty($user['name'])) {
        $errors['name'] = "Введите имя";
    }
    if (empty($user['mail'])) {
        $errors['mail'] = "Введите email";
    }
    if (empty($user['id'])) {
        $errors['id'] = "Can't be blank";
    }
    return $errors;
}
//print_r($availableIds);



$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('<a href="/users">Пользователи </a>');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});



$app->get('/users', function ($request, $response) use ($users) {
    //flash message
    $flash = $this->get('flash')->getMessages();
    //Переключение между страницами
    $per = 10;
    $page = $request->getQueryParam('page', 1);
    $offset = ($page - 1) * $per;
    $sliceOfUsers = array_slice($users, $offset, $per);
    $params = [
        'flash' => $flash,
        'users' => $sliceOfUsers,
        'page' => $page
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
    if (count($users) === 0) {
        $id = 1;
    } else {
        $lastUser = $users[count($users) -1];
        $lastUserId = $lastUser->id;
        $id = $lastUserId + 1;
    }
    $params = [
        'errors' => [],
        'user' => ['name' => '', 'mail' => '', 'id' => $id],
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, array $args) use ($users) {
    $id = $args['id'];
    $availableIds = array_map(fn($user) => $user->id, $users);
    if (in_array($id, $availableIds)) {
        $user = collect($users)->firstWhere('id', $id);
        $params = [
            'user' => $user
        ];
        return $this->get('renderer')->render($response, 'users/show.phtml', $params);
    }
    return $response->withStatus(404)->write("<a href=\"/users/new\">Новый пользователь </a> <h1>ТАКОГО ПОЛЬЗОВАТЕЛЯ НЕ СУЩЕСТВУЕТ</h1>");
})->setName('user');

$app->post('/users', function ($request, $response) use ($users, $router) {

    $user = $request->getParsedBodyParam('user');
    $errors = validate($user);
    if (count($errors) === 0) {
        $users[] = $user;
        $encodedUsers = json_encode($users);
        file_put_contents('users.json', $encodedUsers);
        //flash message
        $this->get('flash')->addMessage('success', '<h3>ДОБАВЛЕН НОВЫЙ ПОЛЬЗОВАТЕЛЬ</h3>');
        return $response->withRedirect($router->urlFor('users'));
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')
        ->render($response->withStatus(422), 'users/new.phtml', $params);
});




$app->run();