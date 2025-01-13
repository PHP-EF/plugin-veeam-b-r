<?php
//// Everything after this line (2) is Core Functionality and no changes are permitted until after line (188).
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **

// PLUGIN INFORMATION - This should match what is in plugin.json
$GLOBALS['plugins']['VeeamPlugin'] = [ // Plugin Name
	'name' => 'VeeamPlugin', // Plugin Name
	'author' => 'TinyTechLabUK', // Who wrote the plugin
	'category' => 'Veeam B&R', // One to Two Word Description
	'link' => 'https://github.com/PHP-EF/plugin-veeam-b-r', // Link to plugin info
	'version' => '1.0.4', // SemVer of plugin
	'image' => 'logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'api' => '/api/plugin/VeeamPlugin/settings', // api route for settings page, or null if no settings page
];

class VeeamPlugin extends phpef {
    private $pluginConfig;

    public function __construct() {
        parent::__construct();
        $this->pluginConfig = $this->config->get('Plugins','VeeamPlugin') ?? [];
    }

    // Function to define the settings for this plugin
    public function _pluginGetSettings() {
        return array(
            'Plugin Settings' => array(
                $this->settingsOption('auth', 'ACL-READ', ['label' => 'VEEAM B&R Read ACL']),
                $this->settingsOption('auth', 'ACL-WRITE', ['label' => 'VEEAM B&R Write ACL']),
                $this->settingsOption('auth', 'ACL-ADMIN', ['label' => 'VEEAM B&R Admin ACL']),
                $this->settingsOption('auth', 'ACL-JOB', ['label' => 'Grants access to use VEEAM Integration'])
            ),
            'VEEAM B&R Settings' => array(
                $this->settingsOption('url', 'Veeam-URL', [
                    'label' => 'VEEAM Enterprise Manager URL',
                    'description' => 'The URL of your VEEAM Enterprise Manager (e.g., https://veeamserver:9419). Uses port 9419 for HTTPS.'
                ]),
                $this->settingsOption('input', 'Veeam-Username', [
                    'label' => 'VEEAM Enterprise Manager Username',
                    'description' => 'Username with permissions to access Veeam Enterprise Manager'
                ]),
                $this->settingsOption('password', 'Veeam-Password', [
                    'label' => 'VEEAM Enterprise Manager Password',
                    'description' => 'Password for Veeam Enterprise Manager authentication'
                ])
            )
        );
    }
    
    // Function to define the api and build the required api for the plugin
    private function getApiEndpoint($path) {
        $baseUrl = $this->getVeeamUrl();
        // Ensure path starts with /api
        if (strpos($path, '/api/') !== 0) {
            $path = '/api/' . ltrim($path, '/');
        }
        $url = $baseUrl . $path;
        error_log("Full API URL: " . $url);
        return $url;
    }

    // Function to define the Veam URL to build the required URI for the Veeam Plugin
    private function getVeeamUrl() {
        if (!isset($this->pluginConfig['Veeam-URL']) || empty($this->pluginConfig['Veeam-URL'])) {
            throw new Exception("Veeam URL not configured. Please set 'Veeam-URL' in config.json");
        }
        // Remove trailing slash if present
        return rtrim($this->pluginConfig['Veeam-URL'], '/');
    }

    // Function to for making API Request to Veeam for Get/Post/Put/Delete
    public function makeApiRequest($Method, $Uri, $Data = "") {
        if (empty($this->pluginConfig['Veeam-URL'])) {
            error_log("Veeam URL Missing in config");
            $this->api->setAPIResponse('Error', 'Veeam URL Missing');
            return false;
        }
    
        $VeeamToken = $this->getAccessToken();
        if (empty($VeeamToken)) {
            error_log("Veeam API Token Missing");
            $this->api->setAPIResponse('Error', 'Veeam API Key Missing');
            return false;
        }
    
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $VeeamToken,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'x-api-version' => '1.2-rev0'
        ];
    
        $VeeamURL = $this->pluginConfig['Veeam-URL'] . '/api/' . $Uri;
    
        $Result = $this->executeApiRequest($Method, $VeeamURL, $Data, $headers);
    
        if (isset($Result->status_code) && $Result->status_code == 401) {
            $this->logging->writeLog('VeeamPlugin', "Token invalid or expired, attempting refresh..", "info");
            try {
                $VeeamToken = $this->getAccessToken(true);
                if (!$VeeamToken) {
                    $this->logging->writeLog('VeeamPlugin', "Failed to refresh API token..", "error");
                    return false;
                } else {
                    $this->logging->writeLog('VeeamPlugin', "Successfully refreshed API token", "info");
                }
                $headers['Authorization'] = 'Bearer ' . $VeeamToken;
                $Result = $this->executeApiRequest($Method, $VeeamURL, $Data, $headers);
                return $Result;
            } catch (Exception $e) {
                $this->logging->writeLog('VeeamPlugin', "Error refreshing access token: " . $e->getMessage(), 'error');
                throw $e;
            }
        }
        if (isset($Result->status_code) && $Result->status_code != 200) {
            $this->api->setAPIResponse('Error', "HTTP Error: $Result->status_code");
            $this->logging->writeLog('VeeamPlugin', "HTTP Error: $Result->status_code", "warning");
            return false;
        }
        return $Result;
    }
    
    private function executeApiRequest($Method, $VeeamURL, $Data, $headers) {
        try {
            if (in_array($Method, ["GET", "get"])) {
                return $this->api->query->$Method($VeeamURL, $headers);
            } else {
                return $this->api->query->$Method($VeeamURL, $Data, $headers);
            }
        } catch (Exception $e) {
            $this->logging->writeLog('VeeamPlugin', "API request failed: " . $e->getMessage(), 'error');
            $this->api->setAPIResponse('Error', 'API request failed');
            return false;
        }
    }

    // Function to decrypt the password and build out a valid token for Veeam Plugin
    private function getAccessToken($force = false) {
        $Username = $this->pluginConfig['Veeam-Username'] ?? null;
        $Password = $this->pluginConfig['Veeam-Password'] ?? null;
        $Token = $this->pluginConfig['Veeam-Token'] ?? null;
        // Check if we have a valid token
        if (!$force && $Token['accessToken'] && isset($Token['expires']) && time() < $Token['expires']) {
            return $Token['accessToken'];
        } else {
            try {
                if (!isset($Username) || !isset($Password)) {
                    throw new Exception("Veeam credentials not configured. Please set 'Veeam-Username' and 'Veeam-Password' in config.json");
                }
                try {
                    $PasswordDecrypted = decrypt($Password,$this->config->get('Security','salt'));
                } catch (Exception $e) {
                    $this->api->setAPIResponse('Error','Unable to decrypt Veeam Password');
                    $this->logging->writeLog('VeeamPlugin','Unable to decrypt Veeam Password','error');
                    return false;
                }
    
                $postData = [
                    'grant_type' => 'password',
                    'username' => $Username,
                    'password' => $PasswordDecrypted
                ];
    
                $headers = [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                    'x-api-version' => '1.2-rev0'
                ];
    
                $baseUrl = $this->getVeeamUrl();
                $url = $baseUrl . '/api/oauth2/token';
                $Result = $this->api->query->post($url,$postData,$headers);
                            
                if ($error) {
                    throw new Exception("Failed to get access token: " . $error);
                }
                
                if ($httpCode >= 400) {
                    throw new Exception("Failed to get access token. HTTP Code: " . $httpCode . " Response: " . $Result);
                }
    
                if (!isset($Result['access_token'])) {
                    throw new Exception("Invalid token response: " . $Result);
                }            
                
                $tokenResult = array(
                    'accessToken' => $Result['access_token'],
                    'expires' => time() + ($Result['.expires'] ?? 3600)
                );

                $config = $this->config->get();
                $data = [
                    "Veeam-Token" => $tokenResult
                ];
                $this->config->setPlugin($config, $data, 'VeeamPlugin');
                
                return $tokenResult['accessToken'];
                
            } catch (Exception $e) {
                error_log("Error getting access token: " . $e->getMessage());
                throw $e;
            }
        }
    }
    
    // ** 
    // Everything after this line (204) is features and is permitted to be edited to build out the plugin features
    // **

    public function GetSessions() {
        try {
            if (!$this->auth->checkAccess($this->config->get("Plugins", "VeeamPlugin")['ACL-READ'] ?? "ACL-READ")) {
                throw new Exception("Access Denied - Missing READ permissions");
            }

            $sessions = $this->makeApiRequest("GET", "v1/sessions");
            if (!empty($sessions)) {
                $this->api->setAPIResponse('Success', 'Sessions retrieved');
                $this->api->setAPIResponseData($sessions['data']); // Just pass the data array directly
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }

    public function GetLicenseInstances() {
        try {
            $result = $this->makeApiRequest("GET", "v1/license/instances");
            error_log("License API Result Type: " . gettype($result));
            error_log("License API Raw Result: " . print_r($result, true));
            
            if ($result === false) {
                throw new Exception("API call returned false");
            }
            
            $this->api->setAPIResponse('Success', 'License report retrieved');
            $this->api->setAPIResponseData($result);
            return true;
        } catch (Exception $e) {
            error_log("License API Error: " . $e->getMessage());
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }
    
    public function GetTaskSessionsFromSessionID($sessionID) {
        try {
            $result = $this->makeApiRequest("GET", "v1/taskSessions?sessionIdFilter=" . $sessionID);
            error_log("Session API Result Type: " . gettype($result));
            error_log("Session API Raw Result: " . print_r($result, true));
            
            if ($result === false) {
                throw new Exception("API call returned false");
            }
            
            $this->api->setAPIResponse('Success', 'Session retrieved');
            $this->api->setAPIResponseData($result);
            return true;
        } catch (Exception $e) {
            error_log("Session API Error: " . $e->getMessage());
            $this->api->setAPIResponse('Error', $e->getMessage());
            return false;
        }
    }
}