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

namespace Casio\SMC\Model\Email;

use Casio\SMC\Helper\Data;
use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Casio\SMC\Logger\Logger;

class SMCEmail extends AbstractHelper
{
    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private Data $data;

    /**
     * @var Emulation
     */
    private Emulation $appEmulation;

    /**
     * Email constructor
     *
     * @param Context $context
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param Data $data
     * @param Emulation $appEmulation
     */
    public function __construct(
        Context               $context,
        StateInterface        $inlineTranslation,
        TransportBuilder      $transportBuilder,
        StoreManagerInterface $storeManager,
        Data                  $data,
        Emulation             $appEmulation,
        Logger $smcLogger
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $context->getLogger();
        $this->storeManager = $storeManager;
        $this->data = $data;
        $this->appEmulation = $appEmulation;
        $this->smcLogger = $smcLogger;
    }

    /**
     * Send SMC Email
     *
     * @param string $event
     * @param string $success
     * @param string $failure
     * @param int $websiteId
     * @param string $storeId
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sendEmail($event, $success, $failure, $websiteId, $storeId)
    {
        /**
         * If it is enabled at website, result notification e-mail is sent at same website
         */
        if (!$this->data->getSMCEventApiResultAlert($websiteId)) {
            return;
        }
        $sender = $this->data->getSMCEventApiEmailSender($websiteId);
        $website = $this->storeManager->getWebsite($websiteId);
        $websiteName = '';
        if ($website) {
            $websiteName = $website->getCode();
        }
            $this->inlineTranslation->suspend();
            $templateOptions = ['area' => Area::AREA_FRONTEND, 'store' => $storeId];
            $transport = [
                'email_type' => $event,
                'success_count' => $success,
                'failure_count' => $failure,
                'website_name' => $websiteName,
            ];
            $recipientEmail = $this->data->getSMCEventApiSendEmail($websiteId);
            $transportObject = new DataObject($transport);
            $storeScope = ScopeInterface::SCOPE_STORE;
            $templateId = $this->data->getSMCEventApiEmailTemplate($websiteId);
            $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($transportObject->getData())
                ->setFrom(['name' => $event, 'email' => $sender])
                ->addTo($recipientEmail)->getTransport();
            try {
                $transport->sendMessage();
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage());
            }
            $this->inlineTranslation->resume();
    }
}
