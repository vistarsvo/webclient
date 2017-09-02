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

use yii\base\ErrorException;
use yii\httpclient\Exception;
use yii\httpclient\Client;
use yii\httpclient\Response;

/**
 * Class YiiHttpClient
 * @package helpers\webclient
 */
class Yii2HttpClient implements ClientInterface
{
    /** @var array http-client options */
    private $_clientOptions = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10, // connection timeout
        CURLOPT_TIMEOUT => 30, // data receiving timeout
        CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:55.0) Gecko/20100101 Firefox/55.0',
        CURLOPT_ACCEPT_ENCODING => 'gzip',
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false
    ];
    /** @var Client **/
    private $_client;
    /** @var  Response RAW client response object */
    private $_clientResponse;
    /** @var string Error message */
    private $_error = '';
    /** @var int server answer status code */
    private $_statusCode = 0;
    /** @var array Proxy info */
    private $_proxy = [];

    /**
     * YiiHttpClient constructor.
     * @param string $userAgent
     */
    public function __construct(string $userAgent = '')
    {
        if ($userAgent) $this->_clientOptions['userAgent'] = $userAgent;
        $this->_client = new Client([
            'transport' => 'yii\httpclient\CurlTransport'
        ]);
    }

    private function reset()
    {
        $this->_statusCode = 0;
        $this->_error = '';
    }

    /**
     * Add or update options key-value
     * @param $optionName
     * @param $optionValue
     */
    public function setClientOption($optionName, $optionValue): void
    {
        $this->_clientOptions[$optionName] = $optionValue;
        return;
    }

    /**
     * Return error info
     * @return string
     */
    public function getError(): string
    {
        return $this->_error;
    }

    /**
     * Return server answer status code
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->_statusCode;
    }

    public function getHeaders(): array
    {
        return $this->_clientResponse->headers->toArray();
    }

    /**
     * Get content and return in text format
     * @param string $url
     * @return string
     */
    public function getContent(string $url): string
    {
        $this->reset();
        try {
            $options = $this->_clientOptions;

            if (!empty($this->_proxy['ip']) && !empty($this->_proxy['port'])) {
                $options[CURLOPT_PROXY] = $this->_proxy['ip'] . ':' . $this->_proxy['port'];

                switch ($this->_proxy['type']) {
                    case 'HTTP' :
                        $options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
                        break;
                    case 'HTTPS' :
                        $options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
                        break;
                    case 'SOCKS4' :
                        $options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS4;
                        break;
                    case 'SOCKS5' :
                        $options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
                        break;
                    default :
                        $options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
                }

                if (!empty($this->_proxy['login']) && !empty($this->_proxy['password'])) {
                    $options[CURLOPT_PROXYUSERPWD] = $this->_proxy['login'] . ':' . $this->_proxy['password'];
                }
            }


            $clientResponse = $this->_client->createRequest()
                ->setMethod('get')
                ->setUrl($url)
                ->setOptions($this->_clientOptions)
                ->addHeaders(['Accept-Language' => 'ru,ru-RU;q=0.8,en-US;q=0.5,en;q=0.3'])
                ->addHeaders(['Accept-Encoding' => 'gzip,deflate'])
                ->send();

            $this->_clientResponse = $clientResponse;
            $this->_statusCode = $clientResponse->getStatusCode();

            if ($clientResponse->isOk) {
                $content = $clientResponse->getContent();
                return $content;
            } else {
                //todo check
                $this->_error = $clientResponse->getStatusCode();
                return $clientResponse->getContent();
            }
        } catch (Exception $exception) {
            $this->_error = $exception->getMessage() . "\n";
            return "";
        } catch (ErrorException $exception) {
            $this->_error = $exception->getMessage() . "\n";
            return "";
        }
    }

    /**
     * Get raw client response object
     * @return mixed
     */
    public function getClientResponse()
    {
        return $this->_clientResponse;
    }

    /**
     * Unset yii2 http client
     */
    public function __destruct()
    {
        unset($this->_client);
    }

    /**
     * Set proxy for client
     * @param array $proxy
     * @throws \Exception
     */
    public function setProxy(array $proxy)
    {
        if (empty($proxy['ip'])
            || empty($proxy['port'])
            || empty($proxy['type'])
        ) throw new \Exception('Proxy array data invalid. Require ip, port and type (HTTP,HTTPS....)');
        $proxy['type'] = strtoupper($proxy['type']);
        $this->_proxy = $proxy;
    }

    /**
     * Reset proxy for client
     */
    public function clearProxy()
    {
        $this->_proxy = [];
    }
}