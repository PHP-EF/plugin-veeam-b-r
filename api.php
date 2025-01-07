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

// Get Veeam Backup Jobs
$app->get('/plugin/VeeamPlugin/jobs', function ($request, $response, $args) {
    $VeeamPlugin = new VeeamPlugin();
    $VeeamPlugin->getBackupJobs();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus($GLOBALS['responseCode']);
});

// Get Veeam Backup Jobs Status
$app->get('/plugin/VeeamPlugin/jobsstatus', function ($request, $response, $args) {
    $VeeamPlugin = new VeeamPlugin();
    $VeeamPlugin->getJobStatus();
    $response->getBody()->write(jsonE($GLOBALS['api']));
    return $response
        ->withHeader('Content-Type', 'application/json;charset=UTF-8')
        ->withStatus(200);
});

// Get Veeam Backup Jobs Sessions
$app->get('/plugin/VeeamPlugin/jobssessions', function ($request, $response, $args) {
    $VeeamPlugin = new VeeamPlugin();
    $VeeamPlugin->GetSessionsJobs();
    
    $responseData = [
        'result' => $GLOBALS['api']['result'],
        'message' => $GLOBALS['api']['message'],
        'data' => $GLOBALS['api']['data']
    ];
    
    $response->getBody()->write(json_encode($responseData));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});