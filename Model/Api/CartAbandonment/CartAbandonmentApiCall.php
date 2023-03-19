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

namespace Casio\SMC\Model\Api\CartAbandonment;

use Casio\SMC\Helper\Data;
use Casio\SMC\Logger\AbandonmentLogger;
use Casio\SMC\Model\Api;
use Casio\SMC\Model\CartAbandonmentRepository;
use Casio\SMC\Model\Email\SMCEmail;
use Casio\SMC\Model\ResourceModel\CartAbandonment\Collection;
use Casio\SMC\Model\ResourceModel\CartAbandonment\CollectionFactory as CartCollection;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Zend_Http_Client_Exception;
use Casio\SMC\Model\Api\CartAbandonment\LoggedAndGuestRequestInfo;
use Casio\SMC\Model\ResourceModel\CartAbandonment as ResourceModelCartAbandonment;
use Casio\SMC\Model\CartAbandonmentFactory as ModelCartAbandonment;

class CartAbandonmentApiCall
{
    public const EVENT_ABANDONMENT_CART = 'Cart Abandonment';
    public const STATUS_SUCCESS = 201;

    /**
     * @var AbandonmentLogger
     */
    private $abandonmentLogger;

    /**
     * @var SMCEmail
     */
    private $smcEmail;

    /**
     * @var CartCollection
     */
    private $collection;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var Data
     */
    private Data $data;

    /**
     * @var CartAbandonmentRepository
     */
    private CartAbandonmentRepository $abandonmentRepository;

    /**
     * @var LoggedAndGuestRequestInfo
     */
    private LoggedAndGuestRequestInfo $loggedAndGuestRequestInfo;

    /**
     * Constructor call
     *
     * @param CartCollection $collection
     * @param Api $api
     * @param AbandonmentLogger $abandonmentLogger
     * @param SMCEmail $smcEmail
     * @param Data $data
     * @param CartAbandonmentRepository $abandonmentRepository
     * @param LoggedAndGuestRequestInfo $loggedAndGuestRequestInfo
     */
    public function __construct(
        CartCollection            $collection,
        Api                       $api,
        AbandonmentLogger         $abandonmentLogger,
        SMCEmail                  $smcEmail,
        Data                      $data,
        CartAbandonmentRepository $abandonmentRepository,
        LoggedAndGuestRequestInfo $loggedAndGuestRequestInfo,
        ResourceModelCartAbandonment $resourceModelCartAbandonment,
        ModelCartAbandonment $modelCartAbandonment
    ) {
        $this->collection = $collection;
        $this->api = $api;
        $this->abandonmentLogger = $abandonmentLogger;
        $this->smcEmail = $smcEmail;
        $this->data = $data;
        $this->abandonmentRepository = $abandonmentRepository;
        $this->loggedAndGuestRequestInfo = $loggedAndGuestRequestInfo;
        $this->resourceModelCartAbandonment = $resourceModelCartAbandonment;
        $this->modelCartAbandonment = $modelCartAbandonment;
    }

    /**
     * Get the cart abandonment api
     *
     * @param Collection $cartCollection
     * @param string $websiteId
     * @param string $storeId
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Http_Client_Exception
     */
    public function getCartAbandonmentApi($cartCollection, $websiteId, $storeId)
    {
        $successCount = 0;
        $failureCount = 0;
        $failedIds = [];
        $abandonmentQuoteCollection = $cartCollection;
        $currentTime =date('Y-m-d H:i:s', time());
        $this->abandonmentLogger->info('Total number of cart fetch : '.count($abandonmentQuoteCollection));
        $this->abandonmentLogger->info('Start Cart Abandonment E-mail integration');
        $xMinCartFirst = (int)$this->data->getSMCEventApiXminCartFirst($websiteId);
        $xMinCartSecond = (int)$this->data->getSMCEventApiXminCartSecond($websiteId);

        foreach ($abandonmentQuoteCollection as $quote) {
            $eventTriggered = $quote->getEventTriggerCount();
            $updatedAt = $quote->getUpdatedAt();
            $quoteId = $quote->getQuoteId();
            /**
             * X mins later since cart abandoned (1st time e-mail sending)
             */
            $firstTriggerTime = $this->getXTriggerTime($xMinCartFirst, $updatedAt);
            /**
             * X mins later since cart abandoned (2nd time e-mail sending)
             */
            $secondTriggerTime = $this->getXTriggerTime($xMinCartSecond, $updatedAt);
            $status = false;
            if ($eventTriggered == 0 && $firstTriggerTime < $currentTime) {
                $status = true;
            } elseif ($eventTriggered == 1 && $secondTriggerTime < $currentTime) {
                $status = true;
            }
            try {
                $this->abandonmentLogger->info('Validation for quote '.$quote->getQuoteId());
                $this->abandonmentLogger->info($eventTriggered);
                $this->abandonmentLogger->info($status);
                if (!$status) {
                    continue;
                }

                $this->abandonmentLogger->info('Before request body for quote '.$quote->getQuoteId());
                /**
                 * Get Logged and guest user quote info request body
                 */
                $requestBody = $this->loggedAndGuestRequestInfo->requestBody($quote, $websiteId, $storeId);

                if(is_array($requestBody)){
                    $this->abandonmentLogger->info('After request body for quote '.$quote->getQuoteId(), $requestBody);
                } else {
                    $this->abandonmentLogger->info('After request body for quote '.$quote->getQuoteId().' Null '.$requestBody);
                }

                if (empty($requestBody)) {
                    continue;
                }
                $apiResponse = $this->api->getEventApiData($requestBody, $websiteId);
                $responseBody = $apiResponse->getBody();
                if ($apiResponse->getStatus() == self::STATUS_SUCCESS) {
                    ++$successCount;
                    $modelCartAbandonment = $this->modelCartAbandonment->create();
                    $this->resourceModelCartAbandonment->load($modelCartAbandonment, $quoteId, 'quote_id');
                    $cartAbandonmentId = $modelCartAbandonment->getEntityId();
                    if (empty($eventTriggered) && !$cartAbandonmentId) {
                        $count = 1;
                        $this->createventTrigger($count, $quoteId);
                    } else {
                        $count = $eventTriggered + 1;
                        $this->updateEventTrigger($count, $cartAbandonmentId);
                    }
                } else {
                    ++$failureCount;
                    $failedIds[$quoteId] = $quoteId;
                    $this->abandonmentLogger->info('Cart Abandonment Logger.ERROR: Some errors are found on SMC
                response for quote id :' . $quoteId . '.{' . $responseBody . '}');
                }
            } catch (LocalizedException $e) {
                $this->abandonmentLogger->error('Some exception are occurred for quote id :' . $quoteId . '
               {' . $e->getmessage() . '}');
            }
        }
        $success = $successCount;
        $failure = $failureCount;
        die;
        $totalCount = $success + $failure;
        if ($storeId && $websiteId && $totalCount) {
            $this->smcEmail->sendEmail(self::EVENT_ABANDONMENT_CART, $success, $failure, $websiteId, $storeId);
        }
        $this->abandonmentLogger->info('Finish Cart Abandonment E-mail integration');
        $this->abandonmentLogger->info('Total number of cart sent to SMC : ' . $totalCount . '
         Success: ' . $success . ' Failure: ' . $failure . ': Failed quote id :
         %1', [$failedIds]);
    }

    /**
     * Create new row for adding event trigger count
     *
     * @param int $count
     * @param string $quoteId
     * @return void
     * @throws AlreadyExistsException
     */
    public function createventTrigger($count, $quoteId)
    {
        $data = [
            "event_trigger_count" => $count,
            "quote_id" => $quoteId
        ];
        $this->abandonmentRepository->createEventTrigger($data);
    }

    /**
     * Update  trigger count
     *
     * @param int $count
     * @param string $entityId
     * @return void
     * @throws AlreadyExistsException
     */
    public function updateEventTrigger($count, $entityId)
    {
        $data = [
            "event_trigger_count" => $count,
            "entity_id" => $entityId
        ];
        $this->abandonmentRepository->createEventTrigger($data);
    }

    /**
     * Get X Trigger Time for event
     *
     * @param string $xMin
     * @param string $updatedAt
     * @return false|string
     */
    public function getXTriggerTime($xMin, $updatedAt)
    {
        $timeXMin = '+ '.$xMin.' minutes';
        return date('Y-m-d H:i:s', strtotime($updatedAt.$timeXMin));
    }
}
