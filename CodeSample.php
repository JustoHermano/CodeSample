<?php

$homeApi = new HomeApi();
$home = new HomeAutomation($homeApi);
$home->run($argv);

class HomeAutomation {
    const ARG_HELP = 'help';
    const ARG_CMD = 'cmd';
    const ARG_ID = 'id';
    const ARG_ACTION = 'action';

    const REQUIRED_ARGS = [
        self::ARG_CMD,
    ];

    const CMDS_AND_REQUIRED_ARGS = [
        'runAction' => [
            self::ARG_ID,
            self::ARG_ACTION,
        ],
        'getDeviceActions' => [
            self::ARG_ID,
        ],
        'getRecentDevices' => [],
    ];

    const ERROR_API_MSG = 'Error occurred making request to api check api token';
    const ERROR_CMD_NOT_FOUND = 'Command not found';
    const ERROR_MISSING_ARG = 'Missing required arguments: ';

    var HomeApi $homeApi;

    function __construct(HomeApi $homeApi) {
        $this->homeApi = $homeApi;
    }

    public function run(array $argv): void {
        // Validate arguments and return array of processed arguments
        $validArgs = $this->validateAndProcessArgs($argv);

        // Check for help message first and print it
        self::printHelpMsg($validArgs);

        // Call home automation command
        if (method_exists($this, $validArgs[self::ARG_CMD])) {
            $this->checkForRequiredArgs(
                $validArgs,
                self::CMDS_AND_REQUIRED_ARGS[$validArgs[self::ARG_CMD]]
            );
            call_user_func([$this, $validArgs[self::ARG_CMD]], $validArgs);
        } else {
            self::printError(self::ERROR_CMD_NOT_FOUND);
        }
    }

    private function checkForRequiredArgs($validArgs, $requiredArgs) {
        $intersectOfArrays = array_intersect(array_keys($validArgs), $requiredArgs);
        if (count($intersectOfArrays) != count($requiredArgs)) {
            self::printError(
                self::ERROR_MISSING_ARG .  implode(',', $requiredArgs)
            );
        }
    }

    private function getRecentDevices(array $validArgs): void {
        $devices = $this->homeApi->getRecentDevices($validArgs);

        echo HomeApi::NUM_RECENT_DEVICES . ' Most Recent Devices:' . PHP_EOL;
        $max = min(count($devices), HomeApi::NUM_RECENT_DEVICES);
        for ($i=0 ; $i < $max; $i++) {
            echo 'Name: ' . $devices[$i]['name'] . ' ID: ' . $devices[$i]['id'] . PHP_EOL;
        }
    }

    private function runAction(array $validArgs): void {
        $this->homeApi->runAction($validArgs);
    }

    private function getDeviceActions(array $validArgs): void {
        // Check if id argument is set
        if (!isset($validArgs[self::ARG_ID])) {
            self::printError(self::ERROR_MISSING_ARG, );
        }

        $deviceInfo = $this->homeApi->getDeviceActions($validArgs);

        echo 'The following actions can be performed on ' . $deviceInfo['label'] . PHP_EOL;
        foreach ($deviceInfo['commands'] as $command) {
            echo $command . PHP_EOL;
        }
    }

    /**
     * Returns array of argument and values for example input is
     *  Input
     *  [
     *      '--help',
     *      '--cmd=23',
     *  ]
     *
     *  Return
     *  [
     *      'help' => '',
     *      'cmd' => 23
     *  ]
     *
     * @param array $argv
     * @return string[]
     */
    private function validateAndProcessArgs(array $argv): array {
        // Remove program name information
        unset($argv[0]);

        // Must have at least one argument
        if (count($argv) < 1) {
            self::printError();
        }

        // Get arguments into parsable format
        $arguments = [];
        foreach ($argv as $arg) {
            $arg = str_replace('--', '', $arg);
            $argumentData = explode('=', $arg);
            if (count($argumentData) > 2) {
                self::printError();
            }

            $arguments[$argumentData[0]] = $argumentData[1] ?? '';
        }

        // Confirm overall required arguments are there if we are running command
        // We skip if help is there as help take precedence over everything
        if (!isset($arguments[self::ARG_HELP])) {
            $this->checkForRequiredArgs($arguments, self::REQUIRED_ARGS);
        }

        return $arguments;
    }

    private static function printHelpMsg(array $arguments): void {
        if (!isset($arguments[self::ARG_HELP])) {
            return;
        }

        echo '
This script is used to get data from Hubitat api
NOTE: apiToken must be provided for all commands except help
Valid arguments are:
--help
--cmd=getRecentDevices,getDeviceActions,runAction
--action=string
--id=int
';

        // Stop execution as if we see help command we only want to print help message
        die();
    }

    public static function printError(string $context = null): void {
        echo 'Error occurred please use --help argument for usage' . PHP_EOL;
        if (!is_null($context)) {
            echo $context . PHP_EOL;
        }

        // Stop execution as we have hit an unexpected error
        die();
    }
}

class HomeApi {
    const BASE_URL_ENV_VAR = 'HOME_API_URL';
    const API_TOKEN = 'HOME_API_TOKEN';
    const DEVICE_ENDPOINT = '/devices';
    const NUM_RECENT_DEVICES = 10;

    const ENV_VAR_NOT_SET = 'Environment variables needs to be set to run script: ';

    var string $baseUrl;
    var string $token;

    function __construct() {
        $this->baseUrl = getenv(self::BASE_URL_ENV_VAR);
        $this->token = getenv(self::API_TOKEN);

        if(empty($this->baseUrl) || empty($this->token)) {
            HomeAutomation::printError(self::ENV_VAR_NOT_SET . self::BASE_URL_ENV_VAR . ',' . self::API_TOKEN);
        }
    }

    public function getRecentDevices(array $validArgs): array {
        return $this->apiRequest(self::DEVICE_ENDPOINT);
    }

    public function getDeviceActions(array $validArgs): array {
        return $this->apiRequest(
            self::DEVICE_ENDPOINT . '/' .$validArgs[HomeAutomation::ARG_ID]
        );
    }

    public function runAction(array $validArgs): array {
        return $this->apiRequest(
            self::DEVICE_ENDPOINT . '/' . $validArgs[HomeAutomation::ARG_ID] . '/' . $validArgs[HomeAutomation::ARG_ACTION]
        );
    }

    private function apiRequest($endpoint): array {
        $url = $this->baseUrl . $endpoint .  '?access_token=' . $this->token;

        // Setup curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
        curl_close($ch);
        // Decode response
        $result = json_decode($result, true);


        // If we have a null that means there was an error
        // TODO: Add error further error handling so we get specific error messages
        if (is_null($result)) {
            HomeAutomation::printError(HomeAutomation::ERROR_API_MSG);
        }

        return $result;
    }
}
