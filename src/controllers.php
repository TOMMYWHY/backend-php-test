<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addGlobal('user', $app['session']->get('user'));

    return $twig;
}));


$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', [
        'readme' => file_get_contents('README.md'),
    ]);
});


$app->match('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $password = $request->get('password');

    if ($username) {
        $sql = "SELECT * FROM users WHERE username = '$username' and password = '$password'";
        $user = $app['db']->fetchAssoc($sql);

        if ($user){
            $app['session']->set('user', $user);
            return $app->redirect('/todo');
        }
    }

    return $app['twig']->render('login.html', array());
});


$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});


$app->get('/todo/{id}', function ($id) use ($app) {


    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    if ($id){
        $sql = "SELECT * FROM todos WHERE id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);

        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {
        $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}'";
        $todos = $app['db']->fetchAll($sql);
//		die($todos[0]);
//	    $todos =  $app['db']->fetchAll($sql)->getArrayResult()->setFirstResult(1)
//	                           ->setMaxResults(5);
//	    $paginator = new Paginator($todos, $fetchJoinCollection = true);
//	    $c = count($paginator);

        return $app['twig']->render('todos.html', [
            'todos' => $todos,
//			'pages' =>$c
        ]);
    }
})
->value('id', null);


$app->get('/todo/{id}/json', function ($id) use ($app){
	if ($id){
		$sql = "SELECT * FROM todos WHERE id = '$id'";
		$todo = $app['db']->fetchAssoc($sql);
//	    printf( $todo);

		$response = new Response( json_encode( $todo ) );
		$response->headers->set( 'Content-Type', 'application/json' );

		return $response;

	}
});


$app->post('/todo/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];
    $description = $request->get('description');
	if($description == null){
		return $app->redirect('/todo');
	}else{
		$sql = "INSERT INTO todos (user_id, description) VALUES ('$user_id', '$description')";
		$app['db']->executeUpdate($sql);

		return $app->redirect('/todo');
	}

});

$app->match('/todo/update/{id}', function ($id) use ($app) {

	$sql = "UPDATE todos SET status = abs(status-1) WHERE id = '$id'";

	$app['db']->executeUpdate($sql);

	return $app->redirect('/todo');
});

$app->match('/todo/delete/{id}', function ($id) use ($app) {

    $sql = "DELETE FROM todos WHERE id = '$id'";
    $app['db']->executeUpdate($sql);

    return $app->redirect('/todo');
});