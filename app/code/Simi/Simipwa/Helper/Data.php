<?php

/**
 * Simipwa Helper
 */

namespace Simi\Simipwa\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use \Magento\Framework\DataObject;
use \Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList;
     */
    public $directionList;

    /**
     * @var \Magento\Framework\ObjectManagerInterface;
     */
    public $objectManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface as StoreManager;
     */
    public $storeManager;

    /**
     * @var \Magento\Framework\HTTP\Adapter\FileTransferFactory
     */
    public $httpFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     */
    public $countryCollectionFactory;

    /**
     * Data constructor.
     * @param Context $context
     * @param ObjectManagerInterface $manager
     * @param DirectoryList $directoryList
     * @param StoreManager $storemanager
     */
    public function __construct(Context $context, ObjectManagerInterface $manager, DirectoryList $directoryList, StoreManager $storemanager, CountryCollectionFactory $countryCollectionFactory)
    {
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->directionList = $directoryList;
        $this->objectManager = $manager;
        $this->storeManager = $storemanager;
        $this->httpFactory = $this->objectManager->create('\Magento\Framework\HTTP\Adapter\FileTransferFactory');
        parent::__construct($context);
    }

    /**
     * Get site map Data and cache to file
     */
    public function getSiteMaps($storeId)
    {
        if (!is_numeric($storeId)) {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                if ($store->getCode() === $storeId) {
                    $storeId = $store->getId();
                }
            }
        }
        $storePath = md5($storeId);
        $filePath = $this->directionList->getPath(DirectoryList::APP) . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'Simi' .
            DIRECTORY_SEPARATOR . 'Simipwa' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . $storePath . DIRECTORY_SEPARATOR;
        if (!is_dir($filePath)) {
            try {
                mkdir($filePath, 0777, true);
            } catch (\Exception $e) {
            }
        }
        $filePath .= 'sitemaps.json';
        if (file_exists($filePath)) {
            $sitemaps = file_get_contents($filePath);
            if (!$sitemaps) {
                $sitemaps = $this->getDataSiteMaps($storeId);
                file_put_contents($filePath, $sitemaps);
                return json_decode($sitemaps, true);
            }
            return json_decode($sitemaps, true);
        } else {
            $file = @fopen($filePath, 'w+');
            $sitemaps = $this->getDataSiteMaps($storeId);
            if ($file) {
                file_put_contents($filePath, $sitemaps);
                return json_decode($sitemaps, true);
            }
            return json_decode($sitemaps, true);
        }
    }

    /**
     * Get Product link, Category link and CMS from site mapp
     * @param $storeId
     * @return string
     */

    public function getDataSiteMaps($storeId)
    {
        $urls = [];
        // get categories
        $collection = $this->objectManager->get('Simi\Simipwa\Model\Catmap')->getCollection($storeId);
        $categories = new DataObject();
        $categories->setItems($collection);
        $categories_url = [];
        foreach ($categories->getItems() as $item) {
            $categories_url[] = [
                'id' => $item->getId(),
                'url' => $item->getUrl(),
                'hasChild' => $item->getChild() ? true : false,
            ];
        }
        $urls['categories_url'] = $categories_url;
        unset($collection);

        // get products
        $collection = $this->objectManager->get('Magento\Sitemap\Model\ResourceModel\Catalog\Product')->getCollection($storeId);
        $products = new DataObject();
        $products->setItems($collection);
        $products_url = [];
        foreach ($products->getItems() as $item) {
            $products_url[] = [
                'id' => $item->getId(),
                'url' => $item->getUrl(),
            ];
        }
        $urls['products_url'] = $products_url;
        unset($collection);

        // // get cms pages
        $cms_url = [];
        $collection = $this->objectManager->get('Magento\Sitemap\Model\ResourceModel\Cms\Page')->getCollection($storeId);
        foreach ($collection as $item) {
            $cms_url[] = [
                'id' => $item->getId(),
                'url' => $item->getUrl(),
            ];
        }
        $urls['cms_url'] = $cms_url;
        unset($collection);

        $result = [];
        $result['sitemaps'] = $urls;
        return json_encode($result);
    }

    /**
     * Clear the mobile caches
     */
    public function clearAppCaches()
    {
        $result = [];
        $stores = $this->storeManager->getStores();
        // clear site map
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $storeName = $store->getName();
            $flag = $this->_clearSiteMap($storeId);
            if ($flag) {
                $result[] = ['status' => 1, 'message' => "Clear site map store $storeName successfull!"];
            } else {
                $result[] = ['status' => 0, 'message' => "Clear site map store $storeName fail!"];
            }
        }

        return $result;
    }

    /**
     * clear site map
     * @param $storeId
     * @return bool
     */
    private function _clearSiteMap($storeId)
    {
        $storePath = md5($storeId);
        $filePath = $this->directionList->getPath(DirectoryList::APP) . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'Simi' .
            DIRECTORY_SEPARATOR . 'Simipwa' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . $storePath . DIRECTORY_SEPARATOR . "sitemaps.json";
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $file = @fopen($filePath, 'w+');
        $sitemaps = $this->getDataSiteMaps($storeId);
        if ($file) {
            file_put_contents($filePath, $sitemaps);
            return true;
        }

        return false;
    }

    public function countArray($array)
    {
        return count($array);
    }

    /**
     * Upload image and return uploaded image file name or false
     *
     * @throws Mage_Core_Exception
     * @param string $scope the request key for file
     * @return bool|string
     */
    public function uploadImage($scope)
    {
        $adapter = $this->httpFactory->create();
        /*
         *
         * Comment to avoid using direct new class initializing function
         * Uncomment it if customer want to use image validation back (Size of image and file
         * size)
         *
         *
         *
        $adapter->addValidator(new \Zend_Validate_File_ImageSize($this->imageSize));
        $adapter->addValidator(
            new \Zend_Validate_File_FilesSize(['max' => self::MAX_FILE_SIZE])
        );
         *
         */
        if ($adapter->isUploaded($scope)) {
            // validate image
            if (!$adapter->isValid($scope)) {
                throw new \Simi\Simipwa\Helper\SimiException(__('Uploaded image is not valid.'));
            }
            $uploader = $this->fileUploaderFactory->create(['fileId' => $scope]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $uploader->setAllowCreateFolders(true);
            $ext = $uploader->getFileExtension();
            if ($uploader->save($this->getBaseDir(), $scope . time() . '.' . $ext)) {
                return 'Simiconnector/' . $uploader->getUploadedFileName();
            }
        }
        return false;
    }

    /**
     * @return \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    public function getCountryCollection()
    {
        return $this->countryCollectionFactory->create();
    }
}
