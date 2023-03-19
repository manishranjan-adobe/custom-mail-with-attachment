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

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as ResourceQuote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\CustomerFactory;
use Casio\SMC\Logger\AbandonmentLogger;
use Casio\SMC\Helper\Data;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\Store;

class LoggedAndGuestRequestInfo
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Get country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CustomerFactory
     */
    public $customerFactory;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param QuoteFactory $quoteFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerFactory $customerFactory
     * @param AbandonmentLogger $abandonmentLogger
     * @param ResourceQuote $resourceQuote
     * @param Data $helperData
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        QuoteFactory $quoteFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        CustomerFactory $customerFactory,
        AbandonmentLogger $abandonmentLogger,
        ResourceQuote $resourceQuote,
        Data $helperData,
        EncryptorInterface $encryptor
    ) {
        $this->productRepository = $productRepository;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->customerFactory = $customerFactory;
        $this->abandonmentLogger = $abandonmentLogger;
        $this->resourceQuote = $resourceQuote;
        $this->helperData = $helperData;
        $this->encryptor = $encryptor;
    }

    /**
     * Get quote id
     *
     * @param integer $quoteId
     * @param string $storeId
     * @return Quote
     */
    public function getQuote($quoteId, $storeId)
    {
        return $this->quoteFactory->create()->setStoreId($storeId)->load($quoteId);
    }

    /**
     * Getting base url
     *
     * @param string $websiteId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBaseUrl($websiteId)
    {
       return $this->scopeConfig->getValue(Store::XML_PATH_SECURE_BASE_LINK_URL, ScopeInterface::SCOPE_WEBSITE, $websiteId);
    }

    /**
     * Getting current time
     *
     * @return false|string
     */
    public function getRegData()
    {
        return date("Y/m/d h:i:s", time());
    }

    /** Getting current time
     *
     * @return false|string
     */
    public function getRegDataForContactKey()
    {
        return date("Ymdh:i:s", time());
    }

    /**
     * Getting customer casio sub attribute value
     *
     * @param string $id
     * @return mixed
     */
    public function getCasioSub($id)
    {
        $customer =$this->customerFactory->create()->load($id);
         return $customer->getCasioSub();
    }

    /**
     * LoggedIn customer request head body
     *
     * @param Quote $quoteItems
     * @param string $websiteId
     * @return array
     * @throws NoSuchEntityException
     */
    public function loggedInBodyHead($quoteItems, $websiteId)
    {
        $casioSub= $this->getCasioSub($quoteItems->getCustomerId());
        $baseUrl = $this->getBaseUrl($websiteId);
        $casioSubHash = ($casioSub == '') ? '' : $this->encryptor->hash($casioSub);
        $contactKey = $casioSubHash.'_'.$quoteItems->getId().'_'.$this->getRegDataForContactKey();
        $arr= [
            "mailaddress" => $quoteItems->getCustomerEmail(),
            "rid" => $casioSubHash,
            "lastname" => $quoteItems->getCustomerLastname(),
            "firstname" => $quoteItems->getCustomerFirstname(),
            "cart_url_checkout" => $baseUrl.
                'casioIdAuth/login?redirect_uri='.$baseUrl.'checkout',
            "cart_url_mybag" => $baseUrl.
                'casioIdAuth/login?redirect_uri='.$baseUrl.'checkout/cart',
            'contactkey' => $contactKey
        ];
        return [
            "ContactKey" => $contactKey,
            "EventDefinitionKey" => $this->helperData->getSMCEventApiAbondonmentKey($websiteId),
            "Data"=> $arr
        ];
    }

    /**
     * Guest customer request head body
     *
     * @param Quote $quoteItems
     * @param string $websiteId
     * @return array
     * @throws NoSuchEntityException
     */
    public function guestBodyHead($quoteItems, $websiteId)
    {
        $baseUrl = $this->getBaseUrl($websiteId);
        $contactKey = $quoteItems->getBillingAddress()->getEmail().'_'.$quoteItems->getId().'_'.$this->getRegDataForContactKey();
         $arr= [
             "mailaddress" => $quoteItems->getBillingAddress()->getEmail(),
             "rid" => '',
             "lastname" => $quoteItems->getBillingAddress()->getLastname(),
             "firstname" => $quoteItems->getBillingAddress()->getFirstname(),
             "cart_url_checkout" => $baseUrl.'checkout',
             "cart_url_mybag" => $baseUrl.'checkout/cart',
             "contactkey" => $contactKey
         ];
         return [
            "ContactKey" => $contactKey,
            "EventDefinitionKey" => $this->helperData->getSMCEventApiAbondonmentKey($websiteId),
            "Data"=> $arr
         ];
    }

    /**
     * Request body common field for logged and guest customer
     *
     * @param Quote $quote
     * @param string $websiteId
     * @param string $storeId
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function requestBody($quote, $websiteId, $storeId)
    {
        $customerEmail='';
        $sendingCount = $quote->getEventTriggerCount()==0 ? 1 : 2;
        $quoteItems = $this->getQuote((int)$quote->getQuoteId(), $storeId);
        $level = 1;
        $userId = $quoteItems->getCustomerId();

        try {
            if ($userId) {
                $customerEmail = $quoteItems->getCustomerEmail();
                if(!$customerEmail){
                    $customerEmail = $quoteItems->getCustomer()->getEmail();
                }
                $bodyHead = $this->loggedInBodyHead($quoteItems, $websiteId);
            } else {
                $customerEmail = $quoteItems->getBillingAddress()->getEmail();
                $bodyHead = $this->guestBodyHead($quoteItems, $websiteId);
            }
        } catch (\Exception $e) {
            $this->abandonmentLogger->critical($e->getMessage());
            return null;
        }


        /* checking the email */
        if ($customerEmail=='' || $customerEmail=='NULL') {
            $this->abandonmentLogger->info('email empty for quote '.$quote->getQuoteId());
            return null;
        }
        $items = [];
        foreach ($quoteItems->getAllItems() as $value) {
            try {
                $product = $value->getProduct();
                $data = $this->productRepository->get($product->getSku(), false, $storeId);
                /*checking the exclude attribute */
                $excludeCartAttribute = $data->getExcludeCartAbandonmentAlert();
                if ($excludeCartAttribute) {
                    $this->abandonmentLogger->info('product Exclude Cart AbandonmentAlert enable quote: '.$quote->getQuoteId().' sku '.$product->getSku());
                    continue;
                }
                if ($level <= 5) {
                    $items[] = [
                        "brand_" . $level => $data->getCasioBrand(),
                        "sku_" . $level => $product->getSku(),
                        "product_name_" . $level => $product->getName(),
                        "productview_url_" . $level => $data->getCasioProductdetailUrl(),
                        "main_imageurl_" . $level => $data->getCasioImageUrl(),
                        "sub_imageurl1_" . $level => $data->getCasioAdditionalImage1(),
                        "sub_imageurl2_" . $level => $data->getCasioAdditionalImage2(),
                        "sub_imageurl3_" . $level => $data->getCasioAdditionalImage3(),
                        "sub_imageurl4_" . $level => $data->getCasioAdditionalImage4(),
                        "sub_imageurl5_" . $level => $data->getCasioAdditionalImage5(),
                        "short_description_" . $level => $data->getShortDescription(),
                        "price_" . $level => $product->getPrice(),
                        "special_price_" . $level => $data->getSpecialPrice(),
                        "reg_date" => $this->getRegData(),
                        "sending_count" => $sendingCount
                    ];
                }
                $level++;
            }catch (\Exception $e) {
                $this->abandonmentLogger->critical($e->getMessage());
                continue;
            }
        }
        /* checking the items is empty */
        if (count($items) < 1) {
            $this->abandonmentLogger->info('item empty for quote: '.$quote->getQuoteId());
            return null;
        }
        $items = array_merge([], ...$items);
        $body = array_merge($bodyHead['Data'], $items);
        $bodyHead['Data'] = $body;
        return $bodyHead;
    }
}
