<?php

namespace Synology\Api\Client;

/**
 * Abstract Class Client.
 */
abstract class Client
{

    public const PROTOCOL_HTTP = 'http';

    public const PROTOCOL_HTTPS = 'https';

    public const API_NAMESPACE = 'SYNO';

    public const API_SERVICE_NAME = 'API';

    public const CONNECT_TIMEOUT = 2000;
    public const REQUEST_TIMEOUT = 30000;

    private $_protocol = self::PROTOCOL_HTTP;

    private $_port = 80;

    private $_sid;

    private $_sessionName = 'default';

    private $_address = '';

    private $_version = 1;

    private $_serviceName;

    private $_namespace;

    private $_debug = false;

    private $_verifySSL;

    public static $_errorCodes = [
        100 => 'Unknown error',
        101 => 'No parameter of API, method or version',
        102 => 'The requested API does not exist',
        103 => 'The requested method does not exist',
        104 => 'The requested version does not support the functionality',
        105 => 'The logged in session does not have permission',
        106 => 'Session timeout',
        107 => 'Session interrupted by duplicate login',
    ];

    /**
     * Setup API
     *
     * @param string $serviceName
     * @param string $namespace
     * @param string $address
     * @param int $port
     * @param string $protocol
     * @param int $version
     * @param boolean $verifySSL
     */
    public function __construct(
        $serviceName,
        $namespace,
        $address,
        $port = null,
        $protocol = self::PROTOCOL_HTTP,
        $version = 1,
        $verifySSL = false
    ) {
        $this->_serviceName = $serviceName;
        $this->_namespace = $namespace;
        $this->_address = $address;
        $this->_verifySSL = $verifySSL;
        if ($port !== null && is_numeric($port)) {
            $this->_port = (int)$port;
        }

        if (!empty($protocol)) {
            $this->_protocol = $protocol;
        }

        $this->_version = $version;
    }

    /**
     * Get the base URL
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return $this->_protocol.'://'.$this->_address.':'.$this->_port.'/webapi/';
    }

    /**
     * Get ApiName
     *
     * @param string $api
     * @return string
     */
    private function getApiName($api): string
    {
        return $this->_namespace.'.'.$this->_serviceName.'.'.$api;
    }

    /**
     * Process a request
     *
     * @param string $api
     * @param string $path
     * @param string $method
     * @param array $params
     * @param int $version
     * @param string $httpMethod
     * @return array bool
     *
     * @throws SynologyException
     */
    protected function request($api, $path, $method, $params = array(), $version = null, $httpMethod = 'get'): array
    {
        if (!is_array($params)) {
            $params = array(
                $params,
            );
        }

        $params['api'] = $this->getApiName($api);
        $params['version'] = ((int)$version > 0) ? (int)$version : $this->_version;
        $params['method'] = $method;

        // create a new cURL resource
        $ch = curl_init();

        if ($httpMethod !== 'post') {
            $url = $this->getBaseUrl().$path.'?'.http_build_query($params);
            $this->log($url, 'Requested Url');

            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            $url = $this->getBaseUrl().$path;
            $this->log($url, 'Requested Url');
            $this->log($params, 'Post Variable');

            // set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::REQUEST_TIMEOUT);

        // Verify SSL or not
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->_verifySSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->_verifySSL);

        // grab URL and pass it to the browser
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $this->log($info['http_code'], 'Response code');
        if (200 === $info['http_code']) {
            if (preg_match('#(plain|text)#', $info['content_type'])) {
                return $this->parseRequest($result);
            }

            return $result;
        }

        if ($info['total_time'] >= (self::REQUEST_TIMEOUT / 1000)) {
            throw new SynologyException('Connection Timeout');
        }

        $this->log($result, 'Result');
        throw new SynologyException('Connection Error');
    }

    /**
     * @param $json
     *
     * @return array|bool
     * @throws SynologyException
     */
    private function parseRequest($json)
    {
        if (($data = json_decode(trim($json), true)) !== null) {
            if ($data->success === 1) {
                return $data->data ?? true;
            }

            if (array_key_exists($data->error->code, self::$_errorCodes)) {
                throw new SynologyException(self::$_errorCodes[$data->error->code]);
            }

            throw new SynologyException(null, $data->error->code);
        }

        return $json;
    }

    /**
     * Activate the debug mode
     *
     * @return Client
     */
    public function activateDebug(): Client
    {
        $this->_debug = true;

        return $this;
    }

    /**
     * Log different data
     *
     * @param mixed $value
     * @param string $key
     */
    protected function log($value, $key = null): void
    {
        if ($this->_debug) {
            if ($key != null) {
                echo $key.': ';
            }

            if (is_object($value) || is_array($value)) {
                $value = PHP_EOL.print_r($value, true);
            }

            echo $value.PHP_EOL;
        }
    }

    /**
     * Get a list of Service and Apis
     *
     * @return array
     * @throws SynologyException
     */
    public function getAvailableApi(): array
    {
        return $this->request('Info', 'query.cgi', 'query', array('query' => 'all'));
    }

    /**
     * Connect to Synology
     *
     * @param string $username
     * @param string $password
     * @param string $sessionName
     * @return Client
     * @throws SynologyException
     */
    public function connect($username, $password, $sessionName = null): Client
    {
        if (! empty($sessionName)) {
            $this->_sessionName = $sessionName;
        }

        $this->log($this->_sessionName, 'Connect Session');
        $this->log($username, 'User');

        $options = array(
            'account' => $username,
            'passwd' => $password,
            'session' => $this->_sessionName,
            'format' => 'sid'
        );
        $data = $this->request('Auth', 'auth.cgi', 'login', $options, 2);

        // save session name id
        $this->_sid = $data->sid;

        return $this;
    }

    /**
     * Logout from Synology
     *
     * @return Client
     * @throws SynologyException
     */
    public function disconnect(): Client
    {
        $this->log($this->_sessionName, 'Disconnect Session');
        $this->request('Auth', 'auth.cgi', 'logout', array(
            '_sid' => $this->_sid,
            'session' => $this->_sessionName
        ));
        $this->_sid = null;

        return $this;
    }

    /**
     * Return Session Id
     *
     * @throws SynologyException
     * @return string
     */
    public function getSessionId(): string
    {
        if ($this->_sid) {
            return $this->_sid;
        }

        throw new SynologyException('Missing session');
    }

    /**
     * Return true if connected
     *
     * @return boolean
     */
    public function isConnected(): bool
    {
        return null !== $this->_sid;
    }

    /**
     * Return Session Name
     *
     * @return string
     */
    public function getSessionName(): string
    {
        return $this->_sessionName;
    }

    /**
     * @throws SynologyException
     */
    public function __destruct()
    {
        if ($this->_sid !== null) {
            $this->disconnect();
        }
    }
}
