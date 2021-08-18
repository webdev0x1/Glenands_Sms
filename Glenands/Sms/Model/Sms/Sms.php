<?php
namespace Glenands\Sms\Model\Sms;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Marketplace\Helper\Cache;
use Magento\Backend\Model\UrlInterface;

/**
 * @api
 * @since 100.0.2
 */
class Sms
{
    const STATE_ACCEPTED = 'SUBMIT_ACCEPTED';
    /**
     * \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Curl
     */
    protected $curlClient;

    /**
     * @var string
     */
    protected $urlPrefix = '';

    /**
     * @var string
     */
    protected $apiUrl = '';

    /**
     * @var \Magento\Marketplace\Helper\Cache
     */
    protected $cache;

    /**
     * @param Curl $curl
     * @param Cache $cache
     * @param UrlInterface $backendUrl
     * @param  \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(Curl $curl, Cache $cache, UrlInterface $backendUrl, \Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->apiUrl = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/api_url');
        $this->curlClient = $curl;
        $this->cache = $cache;
        $this->_storeManager = $storeManager;
        $this->backendUrl = $backendUrl;
    }

    /**
     * Get scope config
     *
     * @return ScopeConfigInterface
     * @deprecated 100.0.10
     */
    private function getScopeConfig()
    {
        if (!($this->scopeConfig instanceof \Magento\Framework\App\Config\ScopeConfigInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\App\Config\ScopeConfigInterface::class
            );
        } else {
            return $this->scopeConfig;
        }
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->urlPrefix . $this->apiUrl;
    }

    /**
     * Gets partners json
     *
     * @return bool
     */
    public function sendOtp($number, $otp)
    {
        $apiUrl = $this->getApiUrl();
        try {
            $headers = ["Content-Type" => "application/json", "Content-Length" => "200"];
            $this->getCurlClient()->setOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $this->getCurlClient()->setOption(CURLOPT_HEADER, 0);
            $this->getCurlClient()->setOption(CURLOPT_TIMEOUT, 60);
            $this->getCurlClient()->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->getCurlClient()->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
            //set curl header
            $this->getCurlClient()->addHeader("Content-Type", "application/json");
            //get request with url
            $userName = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/username');
            $password = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/password');
            $from = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/from');
            $this->getCurlClient()->setOption(CURLOPT_USERPWD, $userName . ":" . $password);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYPEER, false);

            $apiUrl = str_replace("+", "", $apiUrl.'?username='.$userName.'&password='.$password.'&unicode=false&from='.$from.'&to='.$number.'&text=Dear%20User,%20Your%20verification%20code%20is%20'.$otp.'%20.%20Team-%20Glenands&dltContentId=1707162299867118499');
            
            $this->getCurlClient()->get($apiUrl);
            
            $response = json_decode($this->getCurlClient()->getBody(), true);
            if ($response['state'] == self::STATE_ACCEPTED) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function sendForgotPasswordLink($phone, $link) {
        $auth_token = $this->getScopeConfig()->getValue('glenands_sms/bitly_configuration/auth_token');
        $apiUrl = $this->getApiUrl();
        if(empty($auth_token)) {
            return false;
        }
        try {
            $headers = ["Content-Type" => "application/json", 'Authorization' => 'Bearer '.$auth_token];
            $this->getCurlClient()->setHeaders($headers);
            $this->getCurlClient()->setOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);    
            //$this->getCurlClient()->setOption(CURLOPT_HEADER, 0);
            $this->getCurlClient()->setOption(CURLOPT_TIMEOUT, 60);
            $this->getCurlClient()->setOption(CURLOPT_RETURNTRANSFER, true);
            //$this->getCurlClient()->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYPEER, false);
           // $this->getCurlClient()->setOption(CURLOPT_HTTPHEADER, ['Authorization: Bearer ']);
            $this->getCurlClient()->post('https://api-ssl.bitly.com/v4/shorten', json_encode(["long_url" => $link, "domain" => "bit.ly"]));
            $responseUrl = json_decode($this->getCurlClient()->getBody(), true);
            

            $headers = ["Content-Type" => "application/json", "Content-Length" => "200"];
            $this->getCurlClient()->setOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $this->getCurlClient()->setOption(CURLOPT_HEADER, 0);
            $this->getCurlClient()->setOption(CURLOPT_TIMEOUT, 60);
            $this->getCurlClient()->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->getCurlClient()->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
            $userName = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/username');
            $password = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/password');
            $from = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/from');
            $this->getCurlClient()->setOption(CURLOPT_USERPWD, $userName . ":" . $password);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYPEER, false);

            // http_build_query();
            $apiUrl = str_replace("+", "", $apiUrl.'?username='.$userName.'&password='.$password.'&unicode=false&from='.$from.'&to='.$phone.'&text=Dear%20User%20!%20Your%20password%20reset%20link%20is%20'. $responseUrl['link'] .'.%20Click%20this%20link%20to%20reset%20password.%20Team-%20Glenands&dltContentId=1707162299884277804');
            $this->getCurlClient()->get($apiUrl);
            
            $response = json_decode($this->getCurlClient()->getBody(), true);
            
            if ($response['state'] == self::STATE_ACCEPTED) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
                return false;
        }
    }


    /**
     * Gets partners json
     *
     * @return bool
     */
    public function sendShimentNotification($number)
    {
        $apiUrl = $this->getApiUrl();
        try {
            $headers = ["Content-Type" => "application/json", "Content-Length" => "200"];
            $this->getCurlClient()->setOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $this->getCurlClient()->setOption(CURLOPT_HEADER, 0);
            $this->getCurlClient()->setOption(CURLOPT_TIMEOUT, 60);
            $this->getCurlClient()->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->getCurlClient()->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
            //set curl header
            $this->getCurlClient()->addHeader("Content-Type", "application/json");
            //get request with url
            $userName = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/username');
            $password = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/password');
            $from = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/from');
            $this->getCurlClient()->setOption(CURLOPT_USERPWD, $userName . ":" . $password);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYPEER, false);

            echo $apiUrl = str_replace("+", "", $apiUrl.'?username='.$userName.'&password='.$password.'&unicode=false&from='.$from.'&to='.$number.'&text=Order%20Initiated%20by%20Glenands%20Pet%20Stores%20!&dltContentId=1707162324037311097');
            
            $this->getCurlClient()->get($apiUrl);
            
            $response = json_decode($this->getCurlClient()->getBody(), true);
            if ($response['state'] == self::STATE_ACCEPTED) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
                print_r($e->getMessage());die();
                return false;
        }
    }


    /**
     * Gets partners json
     *
     * @return bool
     */
    public function sendOrderNotification($number, $orderNumber)
    {
        $apiUrl = $this->getApiUrl();
        try {
            $headers = ["Content-Type" => "application/json", "Content-Length" => "200"];
            $this->getCurlClient()->setOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $this->getCurlClient()->setOption(CURLOPT_HEADER, 0);
            $this->getCurlClient()->setOption(CURLOPT_TIMEOUT, 60);
            $this->getCurlClient()->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->getCurlClient()->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
            //set curl header
            $this->getCurlClient()->addHeader("Content-Type", "application/json");
            //get request with url
            $userName = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/username');
            $password = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/password');
            $from = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/from');
            $this->getCurlClient()->setOption(CURLOPT_USERPWD, $userName . ":" . $password);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYPEER, false);

            $apiUrl = str_replace("+", "", $apiUrl.'?username='.$userName.'&password='.$password.'&unicode=false&from='.$from.'&to='.$number.'&text=Your%20Order%20'.$orderNumber.'%20is%20successfully%20placed.%20Thank%20you%20for%20shopping%20with%20Glenands%20Pet%20Stores.%20Stay%20Home%20Stay%20Safe!&dltContentId=1707162314266308073');
            
            $this->getCurlClient()->get($apiUrl);
            
            $response = json_decode($this->getCurlClient()->getBody(), true);
            if ($response['state'] == self::STATE_ACCEPTED) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
                print_r($e->getMessage());
        }
    }

    /**
     * Gets partners json
     *
     * @return bool
     */
    public function sendOrderPaymentNotification($number, $customerName, $orderNumber, $amt, $accountLink)
    {
        $apiUrl = $this->getApiUrl();
        try {
            $headers = ["Content-Type" => "application/json", 'Authorization' => 'Bearer 2ea7e0e4c94d4424f6b29ba2003e71e34900ade8'];
            $this->getCurlClient()->setHeaders($headers);
            $this->getCurlClient()->setOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);    
            //$this->getCurlClient()->setOption(CURLOPT_HEADER, 0);
            $this->getCurlClient()->setOption(CURLOPT_TIMEOUT, 60);
            $this->getCurlClient()->setOption(CURLOPT_RETURNTRANSFER, true);
            //$this->getCurlClient()->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->getCurlClient()->post('https://api-ssl.bitly.com/v4/shorten', json_encode(["long_url" => $accountLink, "domain" => "bit.ly"]));
            $responseUrl = json_decode($this->getCurlClient()->getBody(), true);

            $accountLink = $responseUrl['link'];

            $headers = ["Content-Type" => "application/json", "Content-Length" => "200"];
            $this->getCurlClient()->setOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $this->getCurlClient()->setOption(CURLOPT_HEADER, 0);
            $this->getCurlClient()->setOption(CURLOPT_TIMEOUT, 60);
            $this->getCurlClient()->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->getCurlClient()->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
            //set curl header
            $this->getCurlClient()->addHeader("Content-Type", "application/json");
            //get request with url
            $userName = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/username');
            $password = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/password');
            $from = $this->getScopeConfig()->getValue('glenands_sms/api_configuration/from');
            $this->getCurlClient()->setOption(CURLOPT_USERPWD, $userName . ":" . $password);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYPEER, false);

            $apiUrl = str_replace("+", "", $apiUrl.'?username='.$userName.'&password='.$password.'&unicode=false&from='.$from.'&to='.$number.'&text=Thank%20you%20'.$customerName.'%20for%20placing%20an%20order%20with%20Glenands%20Pet%20Stores.%20Your%20Order%20Number%20'.$orderNumber.'%20and%20the%20amount%20'.$amt.'%20has%20been%20received.%20Your%20order%20will%20be%20delivered%20soon.%20Manage%20your%20account-'.$accountLink.'%20Team-%20Glenands.&dltContentId=1707162324030877091');
            
            $this->getCurlClient()->get($apiUrl);
            
            $response = json_decode($this->getCurlClient()->getBody(), true);

            if ($response['state'] == self::STATE_ACCEPTED) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
                print_r($e->getMessage());
        }
    }



    /**
     * @return Curl
     */
    public function getCurlClient()
    {
        return $this->curlClient;
    }

    /**
     * @return cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return string
     */
    public function getReferer()
    {
        return $this->_storeManager->getStore()->getBaseUrl()
        . 'customer/account/login';
    }
}