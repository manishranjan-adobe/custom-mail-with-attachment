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

namespace Casio\SMC\Cron;

use Casio\SMC\Helper\Data;
use Casio\SMC\Logger\Logger;
use Casio\SMC\Model\Api\CartAbandonment\CartAbandonmentApiCall;
use Casio\SMC\Model\Email\SMCEmail;
use Casio\SMC\Model\ResourceModel\CartAbandonment\CollectionFactory as CartCollection;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Casio\SMC\Logger\AbandonmentLogger;

/**
 * Class for cron to run cart abandonment alert api integration
 */
class CartAbandonmentAlert
{
    /**
     * @var int
     */
    private $secondsInDay = 86400;
    /**
     * @var string
     */
    private $quoteLifetime = 'checkout/cart/delete_quote_after';

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var CartAbandonmentApiCall
     */
    private CartAbandonmentApiCall $cartAbandonmentApiCall;

    /**
     * @var CartCollection
     */
    private CartCollection $collection;

    /**
     * @var WebsiteCollectionFactory
     */
    private WebsiteCollectionFactory $websiteCollectionFactory;
    /**
     * @var Data
     */
    private Data $data;
    /**
     * @var StoreWebsiteRelationInterface
     */
    private StoreWebsiteRelationInterface $storeWebsiteRelation;
    /**
     * @var SMCEmail
     */
    private SMCEmail $SMCEmail;
    /**
     * @var StoreRepositoryInterface
     */
    private StoreRepositoryInterface $storeRepository;
    /**
     * @var AbandonmentLogger
     */
    private AbandonmentLogger $abandonmentLogger;


    /**
     * Api constructor
     *
     * @param CartAbandonmentApiCall $cartAbandonmentApiCall
     * @param Logger $logger
     * @param CartCollection $collection
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param Data $data
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param SMCEmail $SMCEmail
     * @param StoreRepositoryInterface $storeRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param AbandonmentLogger $abandonmentLogger
     */
    public function __construct(
        CartAbandonmentApiCall        $cartAbandonmentApiCall,
        Logger                        $logger,
        CartCollection                $collection,
        WebsiteCollectionFactory      $websiteCollectionFactory,
        Data                          $data,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        SMCEmail                      $SMCEmail,
        QuoteCollectionFactory $quoteCollectionFactory,
        StoreRepositoryInterface $storeRepository,
        ScopeConfigInterface $scopeConfig,
        AbandonmentLogger $abandonmentLogger
    ) {
        $this->logger = $logger;
        $this->cartAbandonmentApiCall = $cartAbandonmentApiCall;
        $this->collection = $collection;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->data = $data;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->SMCEmail = $SMCEmail;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->storeRepository = $storeRepository;
        $this->scopeConfig = $scopeConfig;
        $this->abandonmentLogger = $abandonmentLogger;
    }

    /**
     * Execute cron for event api
     *
     * @return void
     */
    public function execute()
    {
        try {
            /**
             * Triger the Abandonment event api based on cron schedule
             */
            $websiteCollection = $this->websiteCollectionFactory->create();
            foreach ($websiteCollection->getItems() as $item) {
                $websiteId = $item->getWebsiteId();
                $isCartAbandonment = $this->data->getSMCEventApiAbondonmentCart($websiteId);
                if ($isCartAbandonment) {
                    $storeIds = $this->storeWebsiteRelation->getStoreByWebsiteId($websiteId);
                    foreach ($storeIds as $storeId) {
                        $store = $this->storeRepository->getById($storeId);
                        $lifetime = $this->scopeConfig->getValue(
                            $this->quoteLifetime,
                            ScopeInterface::SCOPE_STORE,
                            $store->getCode()
                        );
                        $lifetime *= $this->secondsInDay;
                        $this->abandonmentLogger->info('Updated Date should be gt: '.date("Y-m-d", time() - $lifetime) . 'store'. $storeId.' and website_id: '. $websiteId);
                        $cartCollection = $this->getCartCollection($websiteId)->addFieldToFilter('main_table.store_id', ['eq' => $storeId])
                                ->addFieldToFilter('main_table.updated_at', ['gt' => date("Y-m-d", time() - $lifetime)]);
                        if (count($cartCollection) > 0) {
                            $this->cartAbandonmentApiCall->getCartAbandonmentApi($cartCollection, $websiteId, $storeId);
                        } else {
                            $this->abandonmentLogger->info('No abandonment cart available for store_id: '. $storeId.' and website_id: '. $websiteId);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Get the cart collection
     *
     * @param string $websiteId
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    public function getCartCollection($websiteId)
    {
        $this->quoteCollection = $this->quoteCollectionFactory->create();
        $this->quoteCollection->getSelect()->joinLeft(
            ['c_abandonment' => 'casio_cart_abandonment_info'],
            "c_abandonment.quote_id = main_table.entity_id",
            [
                'quote_id' => 'main_table.entity_id',
                'updated_at'=>'main_table.updated_at',
                'customer_id' => 'main_table.customer_id',
                'customer_email' => 'main_table.customer_email',
                'store_id'=>'main_table.store_id',
                'event_trigger_count' => 'c_abandonment.event_trigger_count'
            ]
        )->where(
            'main_table.is_active = ?',
            1
        )->where(
            'main_table.customer_email IS NOT NULL'
        )->where(
            'main_table.items_count != ?',
            0
        )->where(
            'c_abandonment.event_trigger_count IS NULL OR c_abandonment.event_trigger_count < ?',
             2
        )->where(
            'main_table.created_at > ?',
            $this->data->getCartAbandonmentStartDate($websiteId)
        );
        $this->quoteCollection->getSelect()->group('main_table.entity_id');
        return $this->quoteCollection;
    }
}
