<?php declare(strict_types=1);

$app->get('/', 'App\Controller\DefaultController:getHelp');
$app->get('/status', 'App\Controller\DefaultController:getStatus');
$app->post('/login', 'App\Controller\User\LoginUser');

use Chadicus\Slim\OAuth2\Routes;
use Chadicus\Slim\OAuth2\Middleware\Authorization;


$server = $app->getContainer()->get('auth_server');

$app->group('/v1/auth', function () use($app, $server) {
    $app->post('/token', new Routes\Token($server))->setName('token');
    $app->map(['GET', 'POST'], '/revoke', new Routes\Revoke($server))->setName('revoke')->add(new Authorization($server, $app->getContainer()));
})->add(new App\Middleware\CORSMiddleware($app));

$app->get('/image', 'App\Controller\User\Image');

$app->group('/v1/api', function () use ($app, $server) {

    $app->group('/current_user', function() use ($app, $server) {
        $app->post('/create', 'App\Controller\User\CreateUser');
        $app->get('/show', 'App\Controller\User\ShowUser')->add(new Authorization($server, $app->getContainer()));
        $app->post('/change_password', 'App\Controller\User\ChangePassword')->add(new Authorization($server, $app->getContainer()));
        $app->post('/update_profile', 'App\Controller\User\UpdateProfile')->add(new Authorization($server, $app->getContainer()));
        $app->post('/change_email', 'App\Controller\User\ChangeEmail')->add(new Authorization($server, $app->getContainer()));
        $app->post('/send_verification_email', 'App\Controller\User\SendVerificationEmail')->add(new Authorization($server, $app->getContainer()));
        $app->post('/verify_email', 'App\Controller\User\VerifyEmail')->add(new Authorization($server, $app->getContainer()));
    })->add(new App\Middleware\CORSMiddleware($app));

    $app->group('/password_reset', function() use ($app, $server) {
        $app->post('/request', 'App\Controller\User\PasswordReset:request');
        $app->post('/reset', 'App\Controller\User\PasswordReset:reset');
    })->add(new App\Middleware\CORSMiddleware($app));

    $app->post('/images/upload', 'App\Controller\User\ImagesUpload')->add(new Authorization($server, $app->getContainer()))->add(new App\Middleware\CORSMiddleware($app));;

    $app->get('/users/show', 'App\Controller\User\ShowUser:showPublic')->add(new App\Middleware\CORSMiddleware($app));

    // ---

    $app->group('/own_listings', function() use ($app, $server) {
        $app->get('/show', 'App\Controller\Listings\Show');
        $app->get('/query', 'App\Controller\Listings\Query');
        $app->post('/update', 'App\Controller\Listings\Update');
        $app->post('/open', 'App\Controller\Listings\Open');
        $app->post('/close', 'App\Controller\Listings\Close');
        $app->post('/publish_draft', 'App\Controller\Listings\PublishDraft');
        $app->post('/create_draft', 'App\Controller\Listings\CreateDraft');
    })->add(new Authorization($server, $app->getContainer()))->add(new App\Middleware\CORSMiddleware($app));

    $app->group('/listings', function() use ($app, $server) {
        $app->get('/show', 'App\Controller\Listings\Show:showPublic');
        $app->get('/query', 'App\Controller\Listings\Query:queryPublic');
    })->add(new App\Middleware\CORSMiddleware($app));

    $app->group('/availability_exceptions', function() use ($app, $server) {
        $app->post('/create', 'App\Controller\AvailabilityExceptions\Create');
        $app->post('/delete', 'App\Controller\AvailabilityExceptions\Delete');
        $app->get('/query', 'App\Controller\AvailabilityExceptions\Query');
    })->add(new Authorization($server, $app->getContainer()))->add(new App\Middleware\CORSMiddleware($app));

    $app->group('/messages', function() use ($app, $server) {
        $app->post('/send', 'App\Controller\Messages\Send');
        $app->get('/query', 'App\Controller\Messages\Query');
    })->add(new Authorization($server, $app->getContainer()))->add(new App\Middleware\CORSMiddleware($app));

    $app->group('/reviews', function() use ($app, $server) {
        $app->get('/query', 'App\Controller\Reviews\Query');
        $app->get('/show', 'App\Controller\Reviews\Query:showReview');
    })->add(new App\Middleware\CORSMiddleware($app));

    // for later ----------------
    $app->group('/transactions', function() use ($app, $server) {
        $app->get('/query', 'App\Controller\Transactions\Query');
        $app->get('/show', 'App\Controller\Transactions\Show');
        $app->post('/initiate', 'App\Controller\Transactions\Initiate');
        $app->post('/initiate_peculative', 'App\Controller\Transactions\InitiateSpeculative');
    })->add(new Authorization($server, $app->getContainer()))->add(new App\Middleware\CORSMiddleware($app));

    $app->group('/timeslots', function() use ($app, $server) {
        $app->get('/query', 'App\Controller\Timeslots\Query');
    })->add(new Authorization($server, $app->getContainer()))->add(new App\Middleware\CORSMiddleware($app));


    $app->group('/bookings', function() use ($app, $server) {
        $app->get('/query', 'App\Controller\Bookings\Query');
    })->add(new Authorization($server, $app->getContainer()))->add(new App\Middleware\CORSMiddleware($app));


});
