<?php

namespace NikhilPandey\TpLink;

use NikhilPandey\TpLink\Exceptions\UndefinedAuthException;
use NikhilPandey\TpLink\Exceptions\InvalidAuthException;
use NikhilPandey\TpLink\Exceptions\UnknownResponseException;
use NikhilPandey\TpLink\Exceptions\UndefinedHostException;

class Router
{
    const DISCONNECTED = 0;
    const CONNECTED = 1;
    const CONNECTING = 2;

    const SECONDARY_DISABLED = 0;
    const SECONDARY_DYNAMIC_IP = 1;
    const SECONDARY_STATIC_IP = 2;

    const CONNECTION_ON_DEMAND = 1;
    const CONNECTION_AUTO = 2;
    const CONNECTION_TIME_BASED = 3;
    const CONNECTION_MANUAL = 4;

    /**
     * The IP address of the host.
     * @var string
     */
    private $host;

    /**
     * The basic authentication string to connect to the router.
     * @var string
     */
    private $auth;

    /**
     * The mac address for the WAN connection.
     * @var string
     */
    private $mac;

    /**
     * The username for the WAN connection.
     * @var string
     */
    private $username;

    /**
     * The password for the WAN connection.
     * @var string
     */
    private $password;

    /**
     * Create a new router instance.
     * @param string $host     The address for the router.
     * @param string $routerUsername The username for logging in to the router.
     * @param string $routerPassword The password for logging in to the router.
     */
    public function __construct($host = null, $routerUsername = null, $routerPassword = null)
    {
        $this->setHost($host);
        // Generate the auth string only if the
        // username and password was provided in
        // the constructor
        if (!(is_null($routerUsername) || is_null($routerPassword))) {
            $this->setAuth($routerUsername, $routerPassword);
        }
    }

    /**
     * Static way to create a new instnce.
     * @param  string $host The address for the router.
     * @return Router
     */
    public static function at($host)
    {
        return new static($host);
    }

    /**
     * Set the host address.
     * @param string $host The host address.
     * @return Router
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Set the username.
     * @param string $username The username.
     * @return  Router
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the password.
     * @param string $password The password.
     * @return  Router
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Generate the basic authentication string.
     * @param  string $username The username for logging in to the router.
     * @param  string $password The password for logging in to the router.
     * @return Router
     */
    public function setAuth($username, $password)
    {
        $this->auth = 'Basic '.base64_encode($username.':'.$password);

        return $this;
    }

    /**
     * Set both username and password.
     * @param string $username The username.
     * @param string $password The password.
     * @return  Router
     */
    public function setUsernameAndPassword($username, $password)
    {
        // Use the username and password from the
        // current configuration and use it if a
        // username and password was not provided
        if (is_null($username) && is_null($password)) {
            $config = $this->getConfigAssoc();
            $this->setUsername($config['username'])
                ->setPassword($config['password']);
        } else {
            $this->setUsername($username)
                ->setPassword($password);
        }

        return $this;
    }

    /**
     * Send the request to the specified url.
     * @param  string $url     The url to send request to.
     * @param  string $referer The referer to be used to trick the router.
     * @return string|bool     Response from the curl request.  
     */
    private function sendRequest($url, $referer)
    {
        if (!$this->host) {
            throw new UndefinedHostException('Host address not provided.');
        }
        if (!$this->auth) {
            throw new UndefinedAuthException('Router username/password undefined.');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://{$this->host}{$url}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Host: {$this->host}",
            "Referer: {$referer}",
            "Authorization: {$this->auth}",
        ]);
        $response = curl_exec($ch);
        $this->checkForError($response);
        curl_close($ch);

        return $response;
    }

    /**
     * Change the mac address of the router.
     * @param  string $mac The mac address.
     * @return Router
     */
    public function changeMacAddress($mac = null)
    {
        $mac = is_null($mac) ?: $this->mac;
        $this->sendRequest(
            "/userRpm/MacCloneCfgRpm.htm?mac1={$mac}&wan=1&Save=Save",
            "http://{$this->host}/userRpm/MacCloneCfgRpm.htm"
        );

        return $this;
    }

    /**
     * Change the PPPoE user of the router and reconnect the PPPoE connection.
     * @param  string|null $username New username for the PPPoE connection.
     * @param  string|null $password The password for the PPPoE connection.
     * @param  string|null $interval The interval to wait before reconnecting.
     * @return Router
     */
    public function changeUserAndReconnect($username = null, $password = null, $interval = null)
    {
        return $this->reconnect($interval, $username, $password);
    }

    /**
     * Connect to the PPPoE connection and optionally change the user.
     * @param  string|null $username New username for the PPPoE connection.
     * @param  string|null $password The password for the PPPoE connection.
     * @param  string|null $interval The interval to wait before reconnecting.
     * @return Router
     */
    public function connect($username = null, $password = null)
    {
        $this->setUsernameAndPassword($username, $password);

        $this->sendRequest(
            "/userRpm/PPPoECfgRpm.htm?wan=0&wantype=2&acc={$this->username}&psw={$this->password}&confirm={$this->password}&SecType=0&sta_ip=0.0.0.0&sta_mask=0.0.0.0&linktype=2&Connect=Connect",
            "http://{$this->host}/userRpm/PPPoECfgRpm.htm"
        );

        return $this;
    }

    /**
     * Disconnect the PPPoE connection and optionally change the user.
     * @param  string|null $username New username for the PPPoE connection.
     * @param  string|null $password The password for the PPPoE connection.
     * @return Router
     */
    public function disconnect($username = null, $password = null)
    {
        $this->setUsernameAndPassword($username, $password);

        $this->sendRequest(
            "/userRpm/PPPoECfgRpm.htm?wan=0&wantype=2&acc={$this->username}&psw={$this->password}&confirm={$password}&SecType=0&sta_ip=0.0.0.0&sta_mask=0.0.0.0&linktype=2&Disconnect=Disconnect",
            "http://{$this->host}/userRpm/PPPoECfgRpm.htm"
        );

        return $this;
    }

    /**
     * Reconnect the PPPoE connection and optionally change the user.
     * @param  string|null $interval The interval to wait between connection.
     * @param  string|null $username New username for the PPPoE connection.
     * @param  string|null $password The password for the PPPoE connection.
     * @return Router
     */
    public function reconnect($interval = null, $username = null, $password = null)
    {
        $this->disconnect()
            ->wait($interval)
            ->connect($username, $password);

        return $this;
    }

    /**
     * Sleeps for few seconds.
     * @param  int $interval Seconds to wait for.
     * @return Router
     */
    public function wait($interval = null)
    {
        sleep(is_null($interval) ?: 10);

        return $this;
    }

    /**
     * Get the current router configuration.
     * @return array The configuration.
     */
    public function getConfig()
    {
        return $this->parseWANConfig($this->sendRequest(
            '/userRpm/PPPoECfgRpm.htm',
            "http://{$this->host}/userRpm/WanCfgRpm.htm"
        ));
    }

    /**
     * Get the current router configuration as an associative array.
     * @return array The configuration.
     */
    public function getConfigAssoc()
    {
        $config = $this->getConfig();

        return [
            'username' => $config[7],
            'password' => $config[8],
            'status' => $config[26],
            'connection_mode' => $config[20],
            'secondary_connection_type' => $config[29],
        ];
    }

    /**
     * Checks for any error in the response.
     * @param  string $response The response from the router.
     */
    private function checkForError($response)
    {
        if ($response == false) {
            throw new UnknownResponseException('Unknown response received.');
        } elseif (strpos($response, 'HTTP/1.1 401') !== false) {
            throw new InvalidAuthException('Router username/password invalid.');
        }
    }

    /**
     * Parse the current WAN configuration response.
     * @param  string $response The response from the router.
     * @return array            The array of current configuration.
     */
    private function parseWANConfig($response)
    {
        preg_match("/(?:var pppoeInf = new Array\()([\s\S]*?)(?:\))/", $response, $matches);
        if (count($matches) != 2) {
            throw new UnknownResponseException('Unknown response received.');
        }

        return explode(',', str_replace(["\n", '"'], '', trim($matches[1])));
    }
}
