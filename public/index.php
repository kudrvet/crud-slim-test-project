<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;

$container = new Container();
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();
$repo = new \App\PostRepository();

$app->get('/destroy', function ($request, $response) {

    $_SESSION = [];
    session_destroy();
    return $response->withRedirect('/users');
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => '', 'id' => ''],
        'errors' => ['nickname' => '', 'email' => '', 'id' => ''],
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml',$params);
});

$app->get('/users', function ($request, $response) use ($repo, $router) {
    $users  = $repo->all();
    $message = $this->get('flash')->getMessages();
    $params =[
        'users' => $users,
        'flash' => $message,
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml',$params);

})->setName('users');

$app->post('/users', function ($request, $response) use ($repo, $router) {

    $user = $request->getParsedBodyParam('user');
    $validator = new \App\Validator();
    $errors = $validator->validate($user);

    if(count($errors) === 0) {

        $repo->save($user);
        $this->get('flash')->addMessage('success', 'New user has been added');
        return $response->withRedirect($router->urlFor('users'), 302);
    }
    $params = ['errors' => $errors, 'user' => $user];
    return $this->get('renderer')->render($response, 'users/new.phtml',$params);
});

$app->get('/users/{id}',function ($request, $response, $args) use ($repo, $router) {
    $users = $repo->all();
    $id = $args['id'];
    $user = collect($users)->firstWhere('id',$id);
    if(is_null($user)) {
        return $response->withStatus(404);
    }
    $params = ['user' => $user,
               'url_for_delete_user' => $router->urlFor('deleteUser',['id'=>$id])
              ];
    return $this->get('renderer')->render($response, 'users/show.phtml',$params);
}
);

$app->get('/users/{id}/edit',function ($request, $response, $args) use ($repo) {
    $id = $args['id'];
    $user = $repo->find($id);
    $params = ['user' => $user, 'errors' => []];
    return $this->get('renderer')->render($response, 'users/edit.phtml',$params);
});

$app->patch('/users/{id}',function ($request, $response, $args) use ($repo, $router) {

    $id = $args['id'];
    $user = $repo->find($id);

    $data = $request->getParsedBodyParam('user');
    $data['id'] = $id;
    $validator = new \App\Validator();
    $errors = $validator->validate($data);

    if(count($errors) == 0 ) {
        $repo->save($data);
        $this->get('flash')->addMessage('success', 'Information was updated');
        return $response->withRedirect($router->urlFor('users'));
    }

    $params = ['userData' => $data, 'user'=> $user, 'errors' => $errors];
    return $this->get('renderer')->render($response->withStatus(422), 'users/edit.phtml',$params);
});

$app->delete('/users/{id}', function ($request, $response, array $args) use ($repo, $router) {
    $id = $args['id'];
    $repo->destroy($id);
    $this->get('flash')->addMessage('success', 'School has been deleted');
    return $response->withRedirect($router->urlFor('users'));
})->setName('deleteUser');
$app->run();


