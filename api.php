<?php
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