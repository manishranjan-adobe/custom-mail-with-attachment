<?php
/*******************************************************************************
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
 ******************************************************************************/

namespace Casio\SMC\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Data
{
    /**
     * authentication api url
     */
    public const XML_PATH_AUTHENTICATION_API_URL = 'smc/authentication/api_url';
    /**
     * authentication client id
     */
    public const XML_PATH_AUTHENTICATION_CLIENT_ID = 'smc/authentication/client_id';
    /**
     * authentication client secret
     */
    public const XML_PATH_AUTHENTICATION_CLIENT_SECRET = 'smc/authentication/client_secret';
    /**
     * authentication account id
     */
    public const XML_PATH_AUTHENTICATION_ACCOUNT_ID = 'smc/authentication/account_id';
    /**
     * authentication access token
     */
    public const XML_PATH_AUTHENTICATION_ACCESS_TOKEN = 'smc/authentication/access_token';
    /**
     * authentication expired in
     */
    public const XML_PATH_AUTHENTICATION_EXPIRED_IN = 'smc/authentication/expired_in';
    /**
     * event_api  general url
     */
    public const XML_PATH_EVENT_API_GENERAL_API_URL = 'smc/event_api/general/api_url';
    /**
     * event_api result notification alert
     */
    public const XML_PATH_EVENT_API_RESULT_ALERT = 'smc/event_api/result_notification_alert/enabled';
    /**
     * event_api result notification email template
     */
    public const XML_PATH_EVENT_API_EMAIL_TEMPLATE =
        'smc/event_api/result_notification_alert/email_template';
    /**
     * event_api result notification send email
     */
    public const XML_PATH_EVENT_API_SEND_EMAIL = 'smc/event_api/result_notification_alert/send_email';
    /**
     * event_api result notification email sender
     */
    public const XML_PATH_EVENT_API_EMAIL_SENDER = 'smc/event_api/result_notification_alert/email_sender';
    /**
     * event_api cart abandonment enabled
     */
    public const XML_PATH_EVENT_API_CART_ABANDONMENT = 'smc/event_api/cart_abandonment_alert/enabled';
    /**
     * event_api cart abandonment schedule
     */
    public const XML_PATH_EVENT_API_CART_ABANDONMENT_SCHEDULE =
        'smc/event_api/cart_abandonment_alert/schedule';
    /**
     * event_api cart abandonment key
     */
    public const XML_PATH_EVENT_API_CART_ABANDONMENT_KEY =
        'smc/event_api/cart_abandonment_alert/cart_abandonment_key';
    /**
     * event_api cart abandonment x_mins_later_since_cart_first
     */
    public const XML_PATH_EVENT_API_CART_ABANDONMENT_X_MIN_FIRST =
        'smc/event_api/cart_abandonment_alert/x_mins_later_since_cart_first';
    /**
     * event_api cart abandonment x_mins_later_since_cart_second
     */
    public const XML_PATH_EVENT_API_CART_ABANDONMENT_X_MIN_SECOND =
        'smc/event_api/cart_abandonment_alert/x_mins_later_since_cart_second';
    /**
     * event_api coming soon alert enabled
     */
    public const XML_PATH_EVENT_API_COMING_SOON_ENABLED = 'smc/event_api/coming_soon_alert/enabled';
    /**
     * event_api coming soon alert schedule
     */
    public const XML_PATH_EVENT_API_COMING_SOON_SCHEDULE = 'smc/event_api/coming_soon_alert/schedule';
    /**
     * event_api coming soon alert new_sales_event_key
     */
    public const XML_PATH_EVENT_API_COMING_SOON_SALES_EVENT_KEY =
        'smc/event_api/coming_soon_alert/new_sales_event_key';
    /**
     * event_api coming soon alert new_sales_preorder_event_key
     */
    public const XML_PATH_EVENT_API_COMING_SOON_SALES_PREORDER_EVENT_KEY =
        'smc/event_api/coming_soon_alert/new_sales_preorder_event_key';
    /**
     * event_api coming soon alert new_lottery_sales_event_key
     */
    public const XML_PATH_EVENT_API_COMING_SOON_NEW_LOTTERY_KEY =
        'smc/event_api/coming_soon_alert/new_lottery_sales_event_key';
    /**
     * event_api configuration path enabled
     */
    public const XML_PATH_EVENT_API_CONFIGURATION_ENABLED = 'smc/event_api/configuration_path/enabled';
    /**
     * event_api configuration path restock_event_definition_key
     */
    public const XML_PATH_EVENT_API_CONFIGURATION_RESTOCK_KEY =
        'smc/event_api/configuration_path/restock_event_definition_key';

    /**
     * Define xml_path_start_date_of_cart_abandonment
     */
    public const XML_PATH_START_DATE_OF_CART_ABANDONMENT = 'smc/event_api/cart_abandonment_alert/start_date_of_cart_abandonment';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get config
     *
     * @param string $path
     * @param string|null $websiteId
     * @return mixed
     */
    public function getConfig($path, $websiteId)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }

    /**
     * Get authentication api url
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCAuthenticationAPIUrl($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_AUTHENTICATION_API_URL, $websiteId);
    }

    /**
     * Get authentication client id
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCAuthenticationClientId($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_AUTHENTICATION_CLIENT_ID, $websiteId);
    }

    /**
     * Get authentication client secret
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCAuthenticationClientSecret($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_AUTHENTICATION_CLIENT_SECRET, $websiteId);
    }

    /**
     * Get authentication account id
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCAuthenticationAccountId($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_AUTHENTICATION_ACCOUNT_ID, $websiteId);
    }

    /**
     * Get authentication access token
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCAuthenticationAccessToken($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_AUTHENTICATION_ACCESS_TOKEN, $websiteId);
    }

    /**
     * Get authentication expired in
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCAuthenticationExpiredIn($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_AUTHENTICATION_EXPIRED_IN, $websiteId);
    }

    /**
     * Get event_api general api url
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiGeneralApi($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_GENERAL_API_URL, $websiteId);
    }

    /**
     * Get event_api  result  alert api
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiResultAlert($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_RESULT_ALERT, $websiteId);
    }

    /**
     * Get event_api  result api
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiEmailTemplate($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_EMAIL_TEMPLATE, $websiteId);
    }

    /**
     * Get event_api send email
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiSendEmail($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_SEND_EMAIL, $websiteId);
    }

    /**
     * Get event_api email sender
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiEmailSender($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_EMAIL_SENDER, $websiteId);
    }

    /**
     * Get event_api abandonment cart
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiAbondonmentCart($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_CART_ABANDONMENT, $websiteId);
    }

    /**
     * Get event_api abandonment schedule
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiAbondonmentSchedule($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_CART_ABANDONMENT_SCHEDULE, $websiteId);
    }

    /**
     * Get event_api abandonment key
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiAbondonmentKey($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_CART_ABANDONMENT_KEY, $websiteId);
    }

    /**
     * Get x_mins_later_since_cart_first abandonment key
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiXminCartFirst($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_CART_ABANDONMENT_X_MIN_FIRST, $websiteId);
    }

    /**
     * Get x_mins_later_since_cart_second abandonment key
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiXminCartSecond($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_CART_ABANDONMENT_X_MIN_SECOND, $websiteId);
    }

    /**
     * Get event_api coming soon alert enabled
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiComingSoonAlert($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_COMING_SOON_ENABLED, $websiteId);
    }

    /**
     * Get event_api coming soon alert schedule
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiComingSoonSchedule($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_COMING_SOON_SCHEDULE, $websiteId);
    }

    /**
     * Get event_api coming soon alert new_sales_event_key
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiComingSoonNewSalesEventKey($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_COMING_SOON_SALES_EVENT_KEY, $websiteId);
    }

    /**
     * Get event_api coming soon alert new_sales_preorder_event_key
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiComingSoonNewSalesPreorder($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_COMING_SOON_SALES_PREORDER_EVENT_KEY, $websiteId);
    }

    /**
     * Get event_api coming soon alert new_lottery_sales_event_key
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiComingSoonNewLotterySalesEventKey($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_COMING_SOON_NEW_LOTTERY_KEY, $websiteId);
    }

    /**
     * Get event_api configuration path enabled
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiConfigurationPathEnabled($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_CONFIGURATION_ENABLED, $websiteId);
    }

    /**
     * Get event_api configuration path restock_event_definition_key
     *
     * @param string|null $websiteId
     * @return mixed
     */
    public function getSMCEventApiEventConfigurationKey($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_EVENT_API_CONFIGURATION_RESTOCK_KEY, $websiteId);
    }

    /**
     * Get event_api configuration start date of cart_abandonment
     *
     * @param string|null $websiteId
     * @return string
     */
    public function getCartAbandonmentStartDate($websiteId = null)
    {
        return $this->getConfig(self::XML_PATH_START_DATE_OF_CART_ABANDONMENT, $websiteId);
    }
}
