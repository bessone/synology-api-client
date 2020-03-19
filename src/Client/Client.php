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
    public const REQUEST_TIMEOUT = 300000;

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
     * Process a request
     *
     * @param string $api
     * @param string $path
     * @param string $method
     * @param array $params
     * @param int $version
     * @param string $httpMethod
     * @return mixed
     *
     * @throws SynologyException
     */
    protected function request($service, $api, $path, $method, $params = array(), $version = null, $httpMethod = 'get', $file = null)
    {
        if (!is_array($params)) {
            $params = array(
                $params,
            );
        }

        if ($this->isConnected()) {
            $params['_sid'] = $this->getSessionId();
        }

        $params['api'] = $this->_namespace.'.'.$service.'.'.$api;
        $params['version'] = ((int)$version > 0) ? (int)$version : $this->_version;
        $params['method'] = $method;
        if ($file) {
            $files[$params['filename']] = file_get_contents($file);
        }

        // create a new cURL resource
        $ch = curl_init();

        if ($httpMethod !== 'post') {
            $url = $this->getBaseUrl().$path.'?'.http_build_query($params);
            $this->log($url, 'Requested Url');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
        } else {
            $getParam = [
                'api' => 'SYNO.FileStation.Upload',
                'method' => $params['method'],
                'version' => $params['version'],
                '_sid' => $params['_sid']
            ];
            $url = $this->getBaseUrl().$path.'?'.http_build_query($getParam);

            unset($params['method'], $params['api'], $params['version'], $params['_sid']);
            $params['size'] = filesize($file);
            $boundary = uniqid();
            $delimiter = '--' . $boundary;
            $postData = $this->builddataFiles($boundary, $params, $files);
            // set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: multipart/form-data; boundary=" . $delimiter,
                "Content-Length: " . strlen($postData),
            ]);
            curl_setopt($ch, CURLOPT_HEADER, 0);
        }

        // set URL and other appropriate options
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
            if (preg_match('#(plain|text|json)#', $info['content_type'])) {
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
     * @param $boundary
     * @param $fields
     * @param $files
     *
     * @return string
     */
    private function buildDataFiles($boundary, $fields, $files): string
    {
        $data = '';
        $eol = "\r\n";

        $delimiter = '--' . $boundary;

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
                . $content . $eol;
        }

        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="file"; filename="'.$name.'"' . $eol
                //. 'Content-Type: image/png'.$eol
                . 'Content-Type: application/octet-stream'.$eol
            ;

            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--".$eol;

        return $data;
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
            if ($data['success'] === true) {
                return $data['data'] ?? true;
            }

            if (array_key_exists($data['error']['code'], self::$_errorCodes)) {
                throw new SynologyException(self::$_errorCodes[$data['error']['code']]);
            }

            throw new SynologyException(null, $data['error']['code']);
        }

        return $json;
    }

    /**
     * Escape param string
     *
     * @param string $param
     * @return string
     */
    protected function escapeParam($param)
    {
        // Escape backslashes and commas.
        $param = str_replace('\\', '\\\\', $param);
        $param = str_replace(',', '\,', $param);

        return $param;
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
        return $this->request('API','Info', 'query.cgi', 'query', array('query' => 'all'));
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
            'session' => 'FileStation',
            'format' => 'sid'
        );
        $data = $this->request('API','Auth', 'auth.cgi', 'login', $options, 2);

        // save session name id
        $this->_sid = $data['sid'];

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
        $this->request('API', 'Auth', 'auth.cgi', 'logout', array(
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
