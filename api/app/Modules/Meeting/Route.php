<?php
// [P1-S2] @Module Meeting and Calendar
$router->group(['prefix' => '/meeting'], function () use ($router) {

    // @Sub-Module Template
    $router->group(['prefix' => '/template'], function () use ($router) {
        $router->group(['middleware' => 'secret'], function () use ($router) {
            $router->post('/create', 'TemplateController@create');
            $router->post('/clone', 'TemplateController@clone');
            $router->get('/content/{id}', 'TemplateController@content');
            $router->put('/update/{id}', 'TemplateController@update');
            $router->delete('/delete/{id}', 'TemplateController@delete');
        });
        $router->group(['middleware' => 'member'], function () use ($router) {
            $router->get('/list', 'TemplateController@list');
        });
    });

    // @Sub-Module Template Fill
    $router->group(['prefix' => '/template-fill'], function () use ($router) {
        $router->group(['middleware' => 'secret'], function () use ($router) {
            $router->post('/create', 'TemplateFillController@create');
            $router->put('/update/{id}', 'TemplateFillController@update');
            $router->delete('/delete/{id}', 'TemplateFillController@delete');
        });
    });

    // @Sub-Module Proposal
    $router->group(['prefix' => '/proposal'], function () use ($router) {
        $router->group(['middleware' => 'member'], function () use ($router) {
            $router->post('/create', 'ProposalController@create');
            $router->get('/list', 'ProposalController@list');
            $router->get('/content/{id}', 'ProposalController@content');
            $router->put('/update/{id}', 'ProposalController@update');
            $router->delete('/delete/{id}', 'ProposalController@delete');
            $router->put('/approve/{id}', 'ProposalController@approve');
        });
    });

    // @Sub-Module Agenda
    $router->group(['prefix' => '/agenda'], function () use ($router) {

        $router->put('/outsider/join/{reference}', 'AgendaController@outsider'); // Joining Decision for Outsider
        $router->get('/content-outsider/{id}/{reference}', 'AgendaController@contentOutsider');

        $router->group(['middleware' => 'secret'], function () use ($router) {
            $router->post('/create', 'AgendaController@create');
            $router->post('/clone/{id}', 'AgendaController@clone');
            $router->put('/update/{id}', 'AgendaController@update');
            $router->delete('/delete/{id}', 'AgendaController@delete');
            $router->post('/email', 'AgendaController@email');
            $router->put('/status/{id}', 'AgendaController@status');
            $router->put('/publish/{id}', 'AgendaController@publish');
        });

        $router->group(['middleware' => 'member'], function () use ($router) {
            $router->get('/content/{id}', 'AgendaController@content');
            $router->post('/short/{id}', 'AgendaController@short');
            $router->put('/insider/join/{id}', 'AgendaController@insider'); // Joining Decision for Insider
        });
    });

    // @Sub-Module Position and Attendee
    $router->group(['prefix' => '/position'], function () use ($router) {
        $router->group(['middleware' => 'secret'], function () use ($router) {
            $router->post('/create', 'PositionController@create');
            $router->get('/list/{id}', 'PositionController@list');
            $router->put('/update/{id}', 'PositionController@update');
            $router->delete('/delete/{id}', 'PositionController@delete');
        });
    });
    $router->group(['prefix' => '/attendee'], function () use ($router) {
        $router->group(['middleware' => 'secret'], function () use ($router) {
            $router->post('/create', 'AttendeeController@create');
            $router->delete('/delete/{id}', 'AttendeeController@delete');
            $router->put('/access/{id}', 'AttendeeController@access'); // Insider
            $router->put('/outsider/update/{id}', 'AttendeeController@outsider'); // Outsider
        });
    });

    // @Sub-Module Topic
    $router->group(['prefix' => '/topic'], function () use ($router) {
        $router->group(['middleware' => 'secret'], function () use ($router) {
            $router->post('/create', 'TopicController@create');
            $router->delete('/delete/{id}', 'TopicController@delete');
            $router->put('/update/{id}', 'TopicController@update');
            $router->get('/content/{id}', 'TopicController@content');
        });
    });

    // @Sub-Module Calendar
    $router->group(['prefix' => '/calendar'], function () use ($router) {
        $router->group(['middleware' => 'member'], function () use ($router) {
            $router->get('/monthly-agenda', 'CalendarController@monthly');
            $router->get('/appointment-status', 'CalendarController@status');
            $router->get('/today-appointment', 'CalendarController@today');
            $router->get('/waiting-confirm', 'CalendarController@waiting');
        });
    });

    // @Sub-Module Statistic
    $router->group(['prefix' => '/statistic'], function () use ($router) {
        $router->group(['middleware' => 'member'], function () use ($router) {
            $router->get('/monthly-statistic', 'StatisticController@month');
        });
    });
});