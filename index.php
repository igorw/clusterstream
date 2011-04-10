<?php

require __DIR__.'/silex.phar';

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = new Silex\Application();

$app->register(new Silex\Extension\TwigExtension(), array(
    'twig.path'       => __DIR__.'/views',
    'twig.class_path' => __DIR__.'/vendor/twig/lib',
));

$app->register(new Silex\Extension\MonologExtension(), array(
    'monolog.logfile'       => __DIR__.'/development.log',
    'monolog.class_path'    => __DIR__.'/vendor/monolog/src',
));

$app['autoloader']->registerNamespaces(array(
    'Pusher'    => __DIR__.'/vendor/pusher-php/lib',
    'Buzz'      => __DIR__.'/vendor/buzz/lib',
));

$app['pusher'] = $app->share(function($app) {
    return new Pusher\Pusher($app['pusher.app_id'], $app['pusher.key'], $app['pusher.secret']);
});

$app['pusher.app_id'] = $_SERVER['PUSHER_APP_ID'];
$app['pusher.key'] = $_SERVER['PUSHER_KEY'];
$app['pusher.secret'] = $_SERVER['PUSHER_SECRET'];

$app->get('/', function() use ($app) {
    return $app['twig']->render('index.html.twig');
});

$app->post('/create', function() use ($app) {
    $pusher = $app['pusher'];
    $message = (string) $app['request']->get('message');
    $pusher['stream']->trigger('message', $message);
    
    return new Response('', 201);
});

$app->error(function(\Exception $e) use ($app) {
    $message = null;
    if ($e instanceof NotFoundHttpException) {
        $message = 'We are extremely sorry but the page you are looking for could not be found.';
    }
    
    $content = $app['twig']->render('error.html.twig', array(
        'message'   => $message,
        'exception' => $e,
    ));
    $code = ($e instanceof HttpException) ? $e->getStatusCode() : 500;
    return new Response($content, $code);
});

$app->run();
