<?php

// CORS
$router->group(['middleware' => ['cors', 'key']], function () use ($router) {

    // @Module Thanos
    $router->get('/', function () use ($router) {
        return env('APP_NAME') . '-OK';
    });
    $router->group(['prefix' => '/thanos', 'middleware' => 'thanos'], function () use ($router) {
        $router->delete('/snap', 'ThanosController@snap');
    });

    // [P1-S2] @Module Master Data
    $router->group(['prefix' => '/master-data'], function () use ($router) {

        $router->get('/proposal-prefix/list', 'MasterDataController@listProposalPrefix');

        $router->group(['middleware' => 'secret'], function () use ($router) {
            $router->get('/meeting-subject/list', 'MasterDataController@listMeetingSubject');
        });
    });

    // [P1-S2] @Module Utilities
    $router->group(['prefix' => '/utilities'], function () use ($router) {

        $router->group(['middleware' => 'member'], function () use ($router) {
            $router->post('/file/upload', 'UtilitiesController@upload');
            $router->delete('/file/delete/{id}', 'UtilitiesController@delete');
        });
    });
});

// Non-CORS
$router->get('/utilities/file/render/{primary}/{module}/{reference}/{ext}', 'UtilitiesController@render');
