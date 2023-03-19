<?php
/**
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2022 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Adobe permits you to use and modify this file
 * in accordance with the terms of the Adobe license agreement
 * accompanying it (see LICENSE_ADOBE_PS.txt).
 * If you have received this file from a source other than Adobe,
 * then your use, modification, or distribution of it
 * requires the prior written permission from Adobe.
 */
declare(strict_types=1);

namespace Casio\SMC\Model;

use Casio\SMC\Helper\Data;
use Casio\SMC\Logger\AbandonmentLogger;
use Casio\SMC\Logger\Logger;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use Zend_Http_Response;

/**
 * Class Api
 * consume smc api
 */
class Api
{
    public const GRANT_TYPE = 'client_credentials';

    /**
     * @var ZendClientFactory
     */
    private ZendClientFactory $httpClient;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var Data
     */
    private Data $data;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var AbandonmentLogger
     */
    private AbandonmentLogger $abandonmentlogger;

    /**
     * @var WriterInterface
     */
    private WriterInterface $writer;

    /**
     * @var ReinitableConfigInterface
     */
    private ReinitableConfigInterface $reinitableConfig;

    /**
     * Api constructor.
     *
     * @param ZendClientFactory $httpClient
     * @param Json $json
     * @param Data $data
     * @param Logger $logger
     * @param AbandonmentLogger $abandonmentlogger
     * @param WriterInterface $writer
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        ZendClientFactory $httpClient,
        Json              $json,
        Data              $data,
        Logger            $logger,
        AbandonmentLogger $abandonmentlogger,
        WriterInterface   $writer,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->httpClient = $httpClient;
        $this->json = $json;
        $this->data = $data;
        $this->logger = $logger;
        $this->abandonmentlogger = $abandonmentlogger;
        $this->writer = $writer;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * Get Event Api Token
     *
     * @param array $params
     * @param string $websiteId
     * @return string|null
     * @throws Zend_Http_Client_Exception
     */
    public function getEventApiData($params, $websiteId)
    {
        /**
         * Get the access Token from integration Token API Response
         */
        $accessToken = $this->data->getSMCAuthenticationAccessToken($websiteId);
        $expiry = $this->data->getSMCAuthenticationExpiredIn($websiteId);
        $currentTime = date('Y-m-d H:i:s');
        if ($currentTime > $expiry) {
            $accessToken = $this->getAuthenticationApiData($websiteId)['access_token'];
            $expiredIn = $this->getAuthenticationApiData($websiteId)['expires_in'];
            $expiryTime =  date('i', $expiredIn);
            $timeXMin = '+ '.$expiryTime.' minutes';
            $expiry = date('Y-m-d H:i:s', strtotime($currentTime.$timeXMin));
            /**
             * Update access Expired  in system config
             */
            $this->writer->save(
                Data::XML_PATH_AUTHENTICATION_EXPIRED_IN,
                $expiry,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
            /**
             * Update access token in system config
             */
            $this->writer->save(
                Data::XML_PATH_AUTHENTICATION_ACCESS_TOKEN,
                $accessToken,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
            $this->logger->info('access code updated');
            $this->reinitableConfig->reinit();
        }
        $response = '';
        if ($accessToken) {
            $this->logger->info('Smc Api Called');
            $apiUrl = $this->data->getSMCEventApiGeneralApi();
            $header = [
                'Authorization' => 'Bearer ' . $accessToken
            ];
            try {
                $eventApiResponse = $this->api(
                    $apiUrl,
                    Zend_Http_Client::POST,
                    $params,
                    $header
                );
                $response = $eventApiResponse;

            } catch (LocalizedException $e) {
                $this->logger->critical($e->getMessage());
            }
        }
        return $response;
    }

    /**
     * Get Authentication Api Data
     *
     * @param string $websiteId
     * @return string|void|null
     * @throws Zend_Http_Client_Exception
     */
    public function getAuthenticationApiData($websiteId)
    {
        $params = [
            'grant_type' => self::GRANT_TYPE,
            'client_id' => $this->data->getSMCAuthenticationClientId(),
            'client_secret' => $this->data->getSMCAuthenticationClientSecret(),
            'account_id' => $this->data->getSMCAuthenticationAccountId($websiteId)
        ];
        $apiUrl = $this->data->getSMCAuthenticationAPIUrl();

        try {
            $apiAuthentication = $this->api(
                $apiUrl,
                Zend_Http_Client::POST,
                $params
            );

            $jsonResponse = $apiAuthentication->getBody();
            return json_decode($jsonResponse, true);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Casio Api function
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @param array $headers
     * @return Zend_Http_Response
     * @throws LocalizedException
     * @throws Zend_Http_Client_Exception
     */
    public function api($url, $method, $params = [], $headers = [])
    {
        /**
         * ZendClient Api caller
         *
         * @var ZendClient $apiCaller
         */
        $apiCaller = $this->httpClient->create();
        $apiCaller->setUri($url);

        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $headers);

        $apiCaller->setHeaders($headers);
        $apiCaller->setParameterPost($params);
        $apiCaller->setMethod($method);
        return $apiCaller->request();
    }
}
