<?php
/**
 * This file is part of the Vistar project.
 * This source code under MIT license
 *
 * Copyright (c)  2017 Vistar project <https://github.com/vistarsvo/>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace vistarsvo\webclient;

/**
 * Class PhantomJSClient
 * @package helpers\webclient
 */
class PhantomJSClient implements ClientInterface
{
    const OPTION_USERAGENT = 'user-agent';
    const OPTION_FOLLOW_REDIRECTS = 'follow-redirects';
    const OPTION_LOAD_IMAGES = 'load-images';
    const OPTION_COOKIE_PATH = 'cookies-file';//--cookies-file=/path/to/cookies.txt
    const OPTION_IGNORE_SSL_ERRORS = 'ignore-ssl-errors';
    const OPTION_TIMEOUT = 'timeout';

    /** @var string  */
    private $dir = '';
    /** @var string  */
    private $cmd = 'xvfb-run phantomjs';
    /** @var array  */
    private $_proxy = [];
    /** @var array  */
    private $_options = [
        self::OPTION_USERAGENT => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:55.0) Gecko/20100101 Firefox/55.0',
        self::OPTION_LOAD_IMAGES => 'false',
        self::OPTION_IGNORE_SSL_ERRORS => 'true',
        self::OPTION_TIMEOUT => 0
    ];
    /** @var int  */
    private $_statusCode = 0;
    /** @var array  */
    private $_headers = [];
    /** @var string  */
    private $_error = '';

    public function __construct($run_int_xvfb = true)
    {
        if (!$run_int_xvfb) $this->cmd = 'phantomjs';
        $this->dir = __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * PhantomJS run some script
     * @param string $script
     * @return string
     */
    public function runScript(string $script = 'get.js')
    {
        return shell_exec($this->cmd . ' ' . $this->options() . $this->dir . $script . $this->args());
    }

    /**
     * Reset last known info about request
     */
    private function reset()
    {
        $this->_error = '';
        $this->_statusCode = 0;
        $this->_headers = [];
    }

    /**
     * This is main method - get page content
     * @param string $url
     * @return string
     */
    public function getContent(string $url): string
    {
        $this->reset();
        return $this->parseAnswer(shell_exec($this->cmd . ' ' . $this->options() . $this->dir . 'get.js ' . $url . $this->args()));
    }

    /**
     * Parse data from phantomJS
     * @param string $jsonAnswer
     * @return string
     */
    private function parseAnswer(string $jsonAnswer): string
    {
        if (strpos($jsonAnswer, 'QStandardPaths') !== false) {
            $jsonAnswer = substr($jsonAnswer, strpos($jsonAnswer, '{'));
        } elseif (strpos($jsonAnswer, '{') !== false && strpos($jsonAnswer, '{') > 0) {
            $jsonAnswer = substr($jsonAnswer, strpos($jsonAnswer, '{'));
        }
        $jsonAnswer = trim($jsonAnswer);
        $json = json_decode(trim($jsonAnswer));

        if (!empty($json->answer->result) && $json->answer->result == 'error') {
            $this->_error = $json->answer->message;
        }

        if (!empty($json->answer->statusCode)) {
            $this->_statusCode = $json->answer->statusCode;
        }

        if (!empty($json->headers)) {
            $this->_headers = $json->headers;
        }

        if (isset($json->answer->content)) {
            return $json->answer->content;
        } else {
            return '';
        }
    }

    /**
     * Set option Key=>Value in array
     * @param $optionName
     * @param $optionValue
     */
    public function setClientOption($optionName, $optionValue): void
    {
        $this->_options[$optionName] = $optionValue;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->_statusCode;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->_error;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->_headers as $header) {
            $key = strtolower($header->name);
            if (!isset($headers[$key])) $headers[$key] = [];
            if (isset($header->value)) {
                $headers[$key][] = $header->value;
            }
        }
        ksort($headers);
        return $headers;
    }

    /**
     * Set proxy options
     * @param array $proxy
     */
    public function setProxy(array $proxy)
    {
        $this->_proxy = $proxy;
    }

    /**
     * Clear proxy options
     */
    public function clearProxy()
    {
        $this->_proxy = [];
    }

    /**
     * Generate args after script name
     * @return string
     */
    private function args(): string
    {
        $args = [];

        if (isset($this->_options[self::OPTION_FOLLOW_REDIRECTS])) {
            $args[] = $this->_options[self::OPTION_FOLLOW_REDIRECTS];
        }

        if (isset($this->_options[self::OPTION_USERAGENT])) {
            $args[] = '"' . $this->_options[self::OPTION_USERAGENT] . '"';
        }

        if (isset($this->_options[self::OPTION_TIMEOUT])) {
            $args[] = $this->_options[self::OPTION_TIMEOUT] ;
        }

        return ' ' . implode(' ', $args);
    }

    /**
     * Generate string run phantomJS options
     * @return String
     */
    private function options() : String
    {
        $options = [];
        // Load imgs
        if (isset($this->_options[self::OPTION_LOAD_IMAGES])) {
            $options[] = '--load-images=' . var_export($this->_options[self::OPTION_LOAD_IMAGES]);
        }
        // CookiePath
        if (isset($this->_options[self::OPTION_COOKIE_PATH])) {
            $options[] = '--cookies-file=' . var_export($this->_options[self::OPTION_COOKIE_PATH]);
        }
        // Ignore SSL certificate errors
        if (isset($this->_options[self::OPTION_IGNORE_SSL_ERRORS])) {
            $options[] = '--ignore-ssl-errors=' . var_export($this->_options[self::OPTION_IGNORE_SSL_ERRORS]);
        }
        // Proxy
        if (isset($this->_proxy['ip']) && isset($this->_proxy['port'])) {
            $options[] = '--proxy=' .  $this->_proxy['ip'] . ':' . $this->_proxy['port'];

            if (isset($this->_proxy['login']) && isset($this->_proxy['password'])) {
                $options[] = '--proxy-auth=' .  $this->_proxy['login'] . ':' . $this->_proxy['password'];
            }

            if (isset($this->_proxy['type'])) {
                $options[] = '--proxy-type=' .  $this->_proxy['type'];
            }
        }
        return implode(' ', $options) . ' ';
    }
}