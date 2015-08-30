<?php
use Bitrix\Main\Data\Cache;

/**
 * @author: Stanislav Semenov (CJP2600)
 * @email: semenov@8il.ru
 * @url: http://8il.ru
 *
 * @date: 30.04.2014
 * @time: 17:02
 *
 * @update: 19.09.2014
 *
 * @description:
 * Component - the shell intended for lazy loading of heavy elements of web pages.
 * The component is designed very simple. The main task to execute code from the executable function,
 * put it in the cache using Ajax and then use part of the code directly from the cache without a lazy load
 *
 * @example:
 *
 *           $APPLICATION->IncludeComponent(
 *               '8il:composite.shape',
 *               '',
 *               array(
 *                   "ID" => "UniqueShapeComponentID", # ID -shape (Required, unique parameter)
 *                   "CACHE_TIME"    => 604800,        # Time caching (not required)
 *                   "USE_PRELOADER" => true,          # Use preloader (true / false) (Optional - default false)
 *                   "PRELOADER_IMG" => " ... ",       # The path of a custom preloader for (the default one that is in the images)
 *                   "CACHE_TAG"     => array("Pro")   # The tag for the cache.
 *                   "CALL_FUNCTION" => function() {
 *
 *                       echo time();
 *
 *                   }
 *               ),                                    # The anonymous function (body -shape)
 *               null,
 *               array(
 *                   'HIDE_ICONS' => 'Y'
 *               )
 *           );
 *
 *
 * Clear Cache EXAMPLE:
 *
 *         \CBitrixComponent::includeComponentClass("8il:composite.shape");
 *          if (class_exists('CCompositeShapeClass')) {
 *             \CCompositeShapeClass::clearCacheById($cacheId);
 *          }
 *
 * Class CCompositeShapeClass
 */
Class CCompositeShapeClass extends CBitrixComponent
{
    /**
     * @var string
     */
    private $uri;
    /**
     * @var string
     */
    private $sComponentId;
    /**
     * @var string
     */
    private $sUrlKey = "SHAPE";
    /**
     * @var array
     */
    private $error = array();
    /**
     * @var int
     */
    private $cacheTime = 604800;
    /**
     * @var array
     */
    private $arCacheTags = array();
    /**
     * @var string
     */
    private $cacheId = "";
    /**
     * @var bool
     */
    private $userGlobalIdFolder = false;
    /**
     * @var string
     */
    private $subDir = "isSetCacheDate";
    /**
     * @var bool
     */
    private $cachePath = false;
    /**
     * @var bool
     */
    private $useFlashData = true;

    /**
     * @param null $component
     */
    public function __construct($component = null)
    {
        parent::__construct($component);

        /** @var $security get the class object */
        $security = new Bitrix\Security\Filter\Request();

        /** @var $security Set the audit SQL ejection and XSS attack */
        $security->setAuditors(Array(
            'SQL' => new Bitrix\Security\Filter\Auditor\Sql(),
            'XSS' => new Bitrix\Security\Filter\Auditor\Xss()
        ));

        /** @var $arURI filtering incoming parameters. */
        $this->uri = $security->filter(array(
            'get' => $_GET,
            'request' => $_REQUEST,
            'post' => $_POST
        ), false);

    }

    /**
     * install options
     */
    private function initParams()
    {
        if ($this->arParams) {

            if (!isset($this->arParams['ID'])) {
                $this->setError("Do not set the component ID");
            }

            # preloader
            $this->arResult['USE_PRELOADER'] = (isset($this->arParams['USE_PRELOADER'])) ? $this->arParams['USE_PRELOADER'] : $this->bUsePreloader;
            $this->arResult['PRELOADER_IMG'] = $this->__path . "/images/preloader.gif";

            if ($this->arResult['USE_PRELOADER'] && (isset($this->arParams['PRELOADER_IMG']))) {
                $this->arResult['PRELOADER_IMG'] = $this->arParams['PRELOADER_IMG'];
            }

            # Set the ID of the component.
            $this->sComponentId = md5($this->arParams['ID']);
            $this->arResult['COMPONENT_ID'] = $this->sComponentId;

            # Check the installation url parameter transmission component_id
            if (isset($this->arParams['URL_KEY'])) {
                $this->sUrlKey = $this->arParams['URL_KEY'];
            }
            $this->sUrlKey .= $this->sComponentId;
            $this->arResult['URL_KEY'] = $this->sUrlKey;
            $this->setCacheTime($this->arParams['CACHE_TIME']);

            if (isset($this->arParams['CACHE_TAG'])) {
                if (!is_array($this->arParams['CACHE_TAG']) && !empty($this->arParams['CACHE_TAG'])) {
                    $this->arCacheTags = array($this->arParams['CACHE_TAG']);
                } else {
                    if (is_array($this->arParams['CACHE_TAG'])) {
                        $this->arCacheTags = $this->arParams['CACHE_TAG'];
                    }
                }
            }

        }

        $this->cacheId = (isset($this->arParams['CACHE_ID'])) ? $this->arParams['CACHE_ID'] : $this->sComponentId;
        $this->userGlobalIdFolder = (isset($this->arParams['ID_FOLDER'])) ? $this->arParams['ID_FOLDER'] : false;

        $cache_folder = ($this->userGlobalIdFolder) ? md5($this->arParams['ID']) . "/" : "";
        $this->cachePath = '/' . __CLASS__ . '/' . $this->subDir . '/' . $cache_folder;
        $this->useFlashData = (isset($this->arParams['USER_FLASH_DATA'])) ? $this->arParams['USER_FLASH_DATA'] : $this->useFlashData;
    }

    /**
     * Check the existence of the cache
     *
     * isSetCacheDate
     * @param null $cacheId
     * @param bool $refresh_cache
     * @return string
     */
    public function isSetCacheDate($cacheId = null, $refresh_cache = false)
    {
        $return = [];
        $this->cacheId = (is_null($cacheId)) ? $this->sComponentId : $cacheId;
        $cache = Cache::createInstance();

        if ((!$refresh_cache) && $cache->initCache($this->getCacheTime(), $this->cacheId, $this->cachePath)) {
            $return = $cache->getVars();
        }

        if (!empty($return)) {
            $this->arResult['RETURN'] = $return;

            return true;

        } else {
            $this->arResult['RETURN'] = null;

            return false;
        }
    }

    /**
     * callbackFunction
     * @param bool $refresh_cache
     * @internal param bool $bRefreshCache
     */
    private function callbackFunction($refresh_cache = false)
    {
        $new = $this->cacheId;
        if (isset($new[$this->sUrlKey])) {
            unset($new[$this->sUrlKey]);
            unset($new['sessid']);
        }
        $this->cacheId = $this->sComponentId;

        $return = [];
        $cache = Cache::createInstance();

        if ((!$refresh_cache) && $cache->initCache($this->getCacheTime(), $this->cacheId, $this->cachePath)) {
            $return = $cache->getVars();
        } elseif ($cache->startDataCache($this->getCacheTime(), $this->cacheId, $this->cachePath)) {

            if (isset($this->arParams['CALL_FUNCTION'])) {
                if (is_callable($this->arParams['CALL_FUNCTION'])) {
                    ob_start();
                    $this->arParams['CALL_FUNCTION']();
                    $return = ob_get_contents();
                    ob_end_clean();
                }
            }

            if (!$return) {
                $cache->abortDataCache();
            }

            $cache->endDataCache($return);
        }
        print($return);
    }

    /**
     * Check for the existence of the error
     *
     * checkError
     */
    private function checkError()
    {
        if ($arError = $this->getError()) {
            ShowError(implode("<br>", $arError));
            die();
        }
    }

    /**
     * The method of catching a request for the contents of the anonymous function at Ajax.
     *
     * getAjaxData
     */
    public function getAjaxData()
    {
        if (isset($this->uri['request'][$this->sUrlKey]) &&
            ($this->uri['request'][$this->sUrlKey] == $this->sComponentId) &&
            check_bitrix_sessid() &&
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        ) {
            $GLOBALS['APPLICATION']->RestartBuffer();
            $this->callbackFunction();
            die();
        }
    }

    /**
     * Pull tasks to perform
     *
     * @return mixed
     */
    public function executeComponent()
    {
        # Setting.
        $this->initParams();
        # Check for errors.
        $this->checkError();
        # Performing actions.
        $this->doActions();
        # Check the availability of cached data.
        if ($this->isSetCacheDate()) {
            print($this->arResult['RETURN']);
        } else {

            # set flash return delete cache elements
            if ($flashReturn = $this->getFlashdata($this->sComponentId)) {
                if ($this->useFlashData) {
                    $this->arResult['FLASH_RETURN'] = $flashReturn;
                }
            }
            /** AJAX to load the boot */
            $this->IncludeComponentTemplate('ajax');
        }
    }

    /**
     * do actions
     */
    public function doActions()
    {
        # Check for Ajax.
        $this->getAjaxData();
    }

    /**
     * Get cache time
     *
     * @return int
     */
    public function getCacheTime()
    {
        return $this->cacheTime;
    }

    /**
     * @param int $cacheTime
     */
    public function setCacheTime($cacheTime)
    {
        if (isset($cacheTime) && ($cacheTime) && !empty($cacheTime)) {
            $this->cacheTime = $cacheTime;
        }
    }

    /**
     * setter for setting error
     *
     * @param array $error
     */
    public function setError($error)
    {
        $this->error[] = $error;
    }

    /**
     * getter for going error
     *
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * setter for setting the cache id
     *
     * onIncludeComponentLang
     */
    public function onIncludeComponentLang()
    {
        parent::onIncludeComponentLang();
    }

    /**
     * getter for going cache id
     *
     * @param bool $additionalCacheID
     * @return string
     */
    public function getCacheID($additionalCacheID = false)
    {
        return parent::getCacheID($additionalCacheID);
    }

    /**
     * Clear cache
     *
     * clearCacheById
     * @param $cacheId
     */
    public static function clearCacheById($cacheId)
    {
        $class = new self();
        $class->cachePath = '/' . __CLASS__ . '/' . $class->subDir . '/' . $cacheId . '/';
        if ($class->isSetCacheDate($cacheId)) {
            $class->setFlashdata($cacheId, $class->arResult['RETURN']);
        }
        BXClearCache(true, '/' . __CLASS__ . '/' . $class->subDir . '/' . $cacheId . "/");
    }

    /**
     * set_flashdata
     * @param $newData
     * @param string $newVal
     */
    function setFlashdata($newData, $newVal = '')
    {
        if (is_string($newData)) {
            $newData = array($newData => $newVal);
        }

        if (count($newData) > 0) {
            foreach ($newData as $key => $val) {
                $flashdata_key = 'flash' . ':new:' . $key;
                $_SESSION[$flashdata_key] = $val;
            }
        }
    }

    /**
     * get_flashdata
     * @param $key
     * @return bool
     */
    function getFlashdata($key)
    {
        $flashdataKey = 'flash' . ':new:' . $key;
        if (isset($_SESSION[$flashdataKey]) && (!empty($_SESSION[$flashdataKey]))) {
            $data = $_SESSION[$flashdataKey];
            unset($_SESSION[$flashdataKey]);

            return $data;
        }

        return false;
    }


}