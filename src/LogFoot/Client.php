<?php

namespace LogFoot;

class Client
{
    private $api;
    private $project;
    private $secret;

    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    public function __construct($project, $secret, $api = 'logfoot.obodi.eu')
    {
        $this->project = $project;
        $this->secret = $secret;
        $this->api = $api;
    }

    public function post($subject, $content, $level = self::LEVEL_INFO)
    {
        $data = json_encode(array(
            'secret' => $this->secret,
            'project' => $this->project,
            'subject' => $subject,
            'context' => $content,
            'level' => $level
        ));

        // open a socket connection on port 80 - timeout: 30 sec
        $fp = fsockopen($this->api, 443, $errno, $errstr, 30);

        if ($fp) {

            // send the request headers:
            fwrite($fp, "POST / HTTP/1.1\r\n");

            fwrite($fp, "Content-type: application/json\r\n");
            fwrite($fp, "Content-length: " . strlen($data) . "\r\n");
            fwrite($fp, "Connection: close\r\n\r\n");
            fwrite($fp, $data);

            $result = '';
            while (!feof($fp)) {
                // receive the results of the request
                $result .= fgets($fp, 128);
            }
        } else {
            return array(
                'status' => 'err',
                'error' => sprintf('%s (%s)', $errstr, $errno)
            );
        }

        // close the socket connection:
        fclose($fp);

        // split the result header from the content
        $result = explode("\r\n\r\n", $result, 2);

        $header = isset($result[0]) ? $result[0] : '';
        $content = isset($result[1]) ? $result[1] : '';

        // return as structured array:
        return array(
            'status' => 'ok',
            'header' => $header,
            'content' => $content
        );
    }
}