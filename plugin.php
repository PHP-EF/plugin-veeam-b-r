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
	'version' => '1.0.3', // SemVer of plugin
	'image' => 'logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'api' => '/api/plugin/VeeamPlugin/settings', // api route for settings page, or null if no settings page
];

class VeeamPlugin extends phpef {
    public function __construct() {
        parent::__construct();
    }

        //Protected function to define the settings for this plugin
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

        //Protected function to define the api and build the required api for the plugin
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

        //Protected function to define the Veam URL to build the required URI for the Veeam Plugin
    private function getVeeamUrl() {
        $config = $this->config->get('Plugins', 'VeeamPlugin');
        if (!isset($config['Veeam-URL']) || empty($config['Veeam-URL'])) {
            error_log("Veeam config: " . json_encode($config));
            throw new Exception("Veeam URL not configured. Please set 'Veeam-URL' in config.json");
        }
        // Remove trailing slash if present
        return rtrim($config['Veeam-URL'], '/');
    }

        //Protected function to decrypt the password and build out a valid token for Veeam Plugin
    private function getAccessToken($config) {
        // Check if we have a valid token
        if ($this->accessToken && $this->tokenExpiration && time() < $this->tokenExpiration) {
            return $this->accessToken;
        }

        try {
            // $config = $this->config->get('Plugins', 'VeeamPlugin');
            if (!isset($config['Veeam-Username']) || !isset($config['Veeam-Password'])) {
                throw new Exception("Veeam credentials not configured. Please set 'Veeam-Username' and 'Veeam-Password' in config.json");
            }
            try {
                $VeeamPassword = decrypt($config['Veeam-Password'],$this->config->get('Security','salt'));
            } catch (Exception $e) {
                $this->api->setAPIResponse('Error','Unable to decrypt Veeam Password');
                $this->logging->writeLog('VeeamPlugin','Unable to decrypt Veeam Password','error');
                return false;
            }

            $baseUrl = $this->getVeeamUrl();
            $url = $baseUrl . '/api/oauth2/token';
            error_log("Getting token from: " . $url);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP
            ]);
            
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'x-api-version: 1.2-rev0'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $postData = http_build_query([
                'grant_type' => 'password',
                'username' => $config['Veeam-Username'],
                'password' => $VeeamPassword
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            if ($error) {
                throw new Exception("Failed to get access token: " . $error);
            }
            
            if ($httpCode >= 400) {
                throw new Exception("Failed to get access token. HTTP Code: " . $httpCode . " Response: " . $response);
            }
            
            $tokenData = json_decode($response, true);
            if (!isset($tokenData['access_token'])) {
                throw new Exception("Invalid token response: " . $response);
            }
            
            $this->accessToken = $tokenData['access_token'];
            $this->tokenExpiration = time() + ($tokenData['expires_in'] ?? 3600);
            
            return $this->accessToken;
            
        } catch (Exception $e) {
            error_log("Error getting access token: " . $e->getMessage());
            throw $e;
        }
    }


        //Protected function to for making API Request to Veeam for Get/Post/Put/Delete
    public function makeApiRequest($Method, $Uri, $Data = "") {
        $config = $this->config->get('Plugins', 'VeeamPlugin');
        if (!isset($config['Veeam-URL']) || empty($config['Veeam-URL'])) {
            error_log("Veeam URL Missing in config");
            $this->api->setAPIResponse('Error','Veeam URL Missing');
            return false;
        }
        
        $veeamtoken = $this->getAccessToken($config);
        if (!isset($veeamtoken) || empty($veeamtoken)) {
            error_log("Veeam API Token Missing");
            $this->api->setAPIResponse('Error','Veeam API Key Missing');
            return false;
        }
        
        $headers = array(
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $veeamtoken,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'x-api-version' => '1.2-rev0'
        );
        
        $VeeamURL = $config['Veeam-URL'].'/api/'.$Uri;
        error_log("Making request to Veeam URL: " . $VeeamURL);
        error_log("Request Headers: " . print_r($headers, true));
        
        if (in_array($Method,["GET","get"])) {
            $Result = $this->api->query->$Method($VeeamURL,$headers);
        } else {
            $Result = $this->api->query->$Method($VeeamURL,$Data,$headers);
        }
        
        error_log("API Result: " . print_r($Result, true));
        
        if (isset($Result->status_code)){
            error_log("API Error - Status Code: " . $Result->status_code);
            $this->api->setAPIResponse('Error',$Result->status_code);
            return false;
        } else {
            return $Result;    
        }
    }
    private function refreshAuth() {
        // Refresh authentication logic here
        // For now, just reset the access token
        $this->accessToken = null;
        $this->tokenExpiration = null;
    }

        //// Everything after this line (204) is features and is permitted to be edited to build out the plugin features

    public function GetSessions() {
        try {
            if (!$this->auth->checkAccess($this->config->get("Plugins", "VeeamPlugin")['ACL-READ'] ?? "ACL-READ")) {
                throw new Exception("Access Denied - Missing READ permissions");
            }

            $sessions = $this->makeApiRequest("GET", "v1/sessions");
            $this->api->setAPIResponse('Success', 'Sessions retrieved');
            $this->api->setAPIResponseData($sessions['data']); // Just pass the data array directly
            return true;
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

    
    public function GetSessionWithID($sessionid) {
        try {
            $result = $this->makeApiRequest("GET", "v1/sessions?sessionIdFilter=" . $sessionid);
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