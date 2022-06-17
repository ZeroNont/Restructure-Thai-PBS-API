<?php
// [P1-S1] @Module Authentication and User Management
$router->group(['prefix' => '/user'], function () use ($router) {

    // @Sub-Module Permission
    $router->group(['prefix' => '/permission'], function () use ($router) {
        $router->group(['middleware' => 'member'], function () use ($router) {
            $router->get('/content/{actor}', 'PermissionController@content');
        });
        $router->group(['middleware' => 'admin'], function () use ($router) {
            $router->put('/update/{actor}', 'PermissionController@update');
        });
    });

    // @Sub-Module Auth
    $router->post('/login', 'AuthController@login');
    $router->group(['middleware' => 'member'], function () use ($router) {
        $router->delete('/logout', 'AuthController@logout');
        $router->get('/self/content', 'AuthController@content');
        $router->put('/self/update', 'AuthController@update');
        $router->put('/self/delete', 'AuthController@delete');
        $router->put('/self/password/update', 'AuthController@updatePassword'); // [P1-S2]
    });

    // @Sub-Module Invite
    $router->group(['prefix' => '/invite'], function () use ($router) {

        $router->put('/confirm/{code}', 'InviteController@confirm');
        $router->get('/content/{code}', 'InviteController@content');
        $router->get('/policy', 'InviteController@policy');

        $router->group(['middleware' => 'admin'], function () use ($router) {
            $router->post('/create', 'InviteController@create');
            $router->post('/resend', 'InviteController@resend');
            $router->post('/permanent/create', 'InviteController@permanentCreate');
        });
    });

    // @Sub-Module Member Management
    $router->group(['prefix' => '/management'], function () use ($router) {

        $router->group(['middleware' => 'admin'], function () use ($router) {
            $router->put('/update/{id}', 'MemberController@update');
            $router->delete('/delete/{id}', 'MemberController@delete');
            $router->put('/password/reset/{id}', 'MemberController@resetPassword'); // [P1-S2]
        });
        $router->group(['middleware' => 'member'], function () use ($router) {
            $router->get('/list', 'MemberController@list');
            $router->get('/content/{id}', 'MemberController@content');
            $router->get('/search', 'MemberController@search');
            $router->get('/leader', 'MemberController@leader');
        });
    });
});