<?php
// **
// USED TO DEFINE API ENDPOINTS
// **

// Get Veeam Plugin Settings
$app->get('/plugin/VeeamPlugin/settings', function ($request, $response, $args) {
    $VeeamPlugin = new VeeamPlugin();
    if ($VeeamPlugin->auth->checkAccess('ADMIN-CONFIG')) {
        $VeeamPlugin->api->setAPIResponseData($VeeamPlugin->_pluginGetSettings());
    }
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Test Veeam Authentication
$app->get('/plugin/VeeamPlugin/test-auth', function ($request, $response, $args) {
    $VeeamPlugin = new VeeamPlugin();
    $VeeamPlugin->testAuth();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get Veeam License Report
$app->get('/plugin/VeeamPlugin/licenseinstances', function ($request, $response, $args) {
    $VeeamPlugin = new VeeamPlugin();
    $VeeamPlugin->GetLicenseCreateReport();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get Veeam Backup Sessions
$app->get('/plugin/VeeamPlugin/sessions', function ($request, $response, $args) {
    $VeeamPlugin = new VeeamPlugin();
    $VeeamPlugin->GetSessions();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get Veeam Backup Session with ID
$app->get('/plugin/VeeamPlugin/sessions/{session_id}/taskSessions', function ($request, $response, $args) {
    $VeeamPlugin = new VeeamPlugin();
    $VeeamPlugin->GetSessionWithID($args['session_id']);
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});