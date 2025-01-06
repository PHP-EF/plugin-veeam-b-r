<?php
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **

// PLUGIN INFORMATION - This should match what is in plugin.json
$GLOBALS['plugins']['VeeamPlugin'] = [ // Plugin Name
	'name' => 'VeeamPlugin', // Plugin Name
	'author' => 'TinyTechLabUK', // Who wrote the plugin
	'category' => 'Veeam B&R', // One to Two Word Description
	'link' => 'https://github.com/PHP-EF/plugin-veeam-b-r', // Link to plugin info
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'api' => '/api/plugin/VeeamPlugin/settings', // api route for settings page, or null if no settings page
];

class VeeamPlugin extends ib {

	public function __construct() {
		parent::__construct();
	}

	public function _pluginGetSettings() {
		$VeeamPlugin = new VeeamPlugin();
		$VeeamPluginLabels = $VeeamPlugin->GetVeeamPluginLabels() ?? null;
		$VeeamPluginLabelsKeyValuePairs = [];
		$VeeamPluginLabelsKeyValuePairs[] = [
			"name" => "None",
			"value" => ""
		];
		if ($VeeamPluginLabels) {
			$VeeamPluginLabelsKeyValuePairs = array_merge($VeeamPluginLabelsKeyValuePairs,array_map(function($item) {
				return [
					"name" => $item['name'],
					"value" => $item['name']
				];
			}, $VeeamPluginLabels));
		}
		return array(
            'Plugin Settings' => array(
                $this->settingsOption('auth', 'ACL-READ', ['label' => 'VEEAM B&R Read ACL']),
                $this->settingsOption('auth', 'ACL-WRITE', ['label' => 'VEEAM B&R Write ACL']),
                $this->settingsOption('auth', 'ACL-ADMIN', ['label' => 'VEEAM B&R Admin ACL']),
                $this->settingsOption('auth', 'ACL-JOB', ['label' => 'Grants access to use VEEAM Integration'])
            ),
            'VEEAM B&R Settings' => array(
                $this->settingsOption('url', 'Veeam-URL', [
                    'label' => 'VEEAM B&R URL',
                    'description' => 'The URL of your VEEAM Enterprise Manager (e.g., https://veeamURL:PORT)'
                ]),
                $this->settingsOption('text', 'Veeam-Username', [
                    'label' => 'VEEAM Username',
                    'description' => 'Username for VEEAM Enterprise Manager API'
                ]),
                $this->settingsOption('password', 'Veeam-Password', [
                    'label' => 'VEEAM Password',
                    'description' => 'Password for VEEAM Enterprise Manager API'
                ])
            )
        );
    }
}
    // private $config;
    // private $api;
    // private $token;

    // public function __construct($api) {
    //     $this->api = $api;
    //     $this->config = $api->config;
    //     $this->token = null;
    // }



//     private function authenticate() {
//         $veeamConfig = $this->config->get("Plugins", "veeam-b-r");
//         $veeamUrl = $veeamConfig["Veeam-URL"] ?? null;
//         $username = $veeamConfig["Veeam-Username"] ?? null;
//         $password = $veeamConfig["Veeam-Password"] ?? null;

//         if (!$veeamUrl || !$username || !$password) {
//             $this->api->setAPIResponse('Error', 'VEEAM configuration missing');
//             return false;
//         }

//         // Build authentication request
//         $authUrl = rtrim($veeamUrl, '/') . '/api/sessionMngr/?v=latest';
//         $headers = [
//             'Authorization' => 'Basic ' . base64_encode("$username:$password"),
//             'Content-Type' => 'application/json',
//             'X-API-Version' => '1.0-rev1'
//         ];

//         try {
//             $result = $this->api->query->post($authUrl, "", $headers, null, true);
//             if ($result && $result->headers && isset($result->headers['X-RestSvcSessionId'])) {
//                 $this->token = $result->headers['X-RestSvcSessionId'][0];
//                 return true;
//             }
//         } catch (Exception $e) {
//             $this->api->setAPIResponse('Error', 'Authentication failed: ' . $e->getMessage());
//             return false;
//         }

//         $this->api->setAPIResponse('Error', 'Authentication failed');
//         return false;
//     }

//     private function queryVeeam($method, $uri, $data = "") {
//         if (!$this->token && !$this->authenticate()) {
//             return false;
//         }

//         $veeamConfig = $this->config->get("Plugins", "veeam-b-r");
//         $veeamUrl = $veeamConfig["Veeam-URL"] ?? null;

//         if (!$veeamUrl) {
//             $this->api->setAPIResponse('Error', 'VEEAM URL Missing');
//             return false;
//         }

//         $headers = [
//             'X-RestSvcSessionId' => $this->token,
//             'Content-Type' => 'application/json'
//         ];

//         $url = rtrim($veeamUrl, '/') . '/api/' . ltrim($uri, '/');
        
//         try {
//             $result = $this->api->query->$method($url, $data, $headers, null, true);
//             if ($result && $result->body) {
//                 $output = json_decode($result->body, true);
//                 $this->api->setAPIResponseData($output);
//                 return $output;
//             }
//         } catch (Exception $e) {
//             $this->api->setAPIResponse('Error', 'API request failed: ' . $e->getMessage());
//             return false;
//         }

//         return false;
//     }

//     public function GetBackupHistory() {
//         // Get jobs from the last month
//         $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
//         $result = $this->queryVeeam(
//             "get", 
//             "reports/summary/job_statistics?fromTime=$thirtyDaysAgo"
//         );

//         if ($result) {
//             return $result;
//         }

//         $this->api->setAPIResponse('Warning', 'No backup history returned from the API');
//         return false;
//     }

//     public function GetJobDetails($jobId) {
//         $result = $this->queryVeeam("get", "jobs/$jobId");
//         if ($result) {
//             return $result;
//         }

//         $this->api->setAPIResponse('Warning', 'No job details returned from the API');
//         return false;
//     }
// }
