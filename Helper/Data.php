<?php


namespace Xigen\Benchmark\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Data helper class
 */
class Data extends AbstractHelper
{
    const DEBUG = true;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepositoryInterface;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistryInterface;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->dateTime = $dateTime;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        parent::__construct($context);
    }

    /**
     * Return collection of random products.
     * @param int $limit
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getRandomProduct($limit = 1)
    {
        $collection = $this->productCollectionFactory
            ->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
            ->setPageSize($limit);
        $collection->getSelect()->order('RAND()');
        return $collection;
    }

    /**
     * Random stock figure - keep numbers sensible
     * @return int
     */
    public function getRandomStockNumber()
    {
        return rand(0, 30);
    }

    /**
     * Random status
     * @return int
     */
    public function getRandomStatus()
    {
        return rand(1, 2);
    }

    /**
     * Return array of random IDs.
     * @param int $limit
     * @return array
     */
    public function getRandomIds($limit = 1)
    {
        $products = $this->getRandomProduct($limit);
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product->getId();
        }
        return $ids;
    }

    /**
     * Return array of random SKUs.
     * @param int $limit
     * @return array
     */
    public function getRandomSku($limit = 1)
    {
        $products = $this->getRandomProduct($limit);
        $skus = [];
        foreach ($products as $product) {
            $skus[] = $product->getSku();
        }
        return $skus;
    }

    /**
     * Randomise true or false.
     * @return bool
     */
    public function getRandomTrueOrFalse()
    {
        return (bool) rand(0, 1);
    }

    /**
     * Get product by Id.
     * @param $productId
     * @param bool $editMode
     * @param null $storeId
     * @param bool $forceReload
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductById($productId, $editMode = false, $storeId = null, $forceReload = false)
    {
        try {
            return $this->productRepositoryInterface->getById($productId, $editMode, $storeId, $forceReload);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * Get product by SKU.
     * @param $sku
     * @param bool $editMode
     * @param null $storeId
     * @param bool $forceReload
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductBySku($sku, $editMode = false, $storeId = null, $forceReload = false)
    {
        try {
            return $this->productRepositoryInterface->get($sku, $editMode, $storeId, $forceReload);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * Update SKU stock
     * @param $sku
     * @param $qty
     * @param null $output
     * @return bool|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateSkuStock($sku, $qty, $output = null)
    {
        $product = $this->getProductBySku($sku);
        if (!$product || !$output) {
            return;
        }

        $availability = (((string) $qty <= 0) ? '0' : '1');

        $stockItem = $this->stockRegistryInterface->getStockItem($product->getId());
        $stockItem->setData('qty', (string) $qty);
        $stockItem->setData('is_in_stock', $availability);

        try {
            $this->stockRegistryInterface->updateStockItemBySku((string) $sku, $stockItem);
            if (self::DEBUG) {
                $output->writeln((string) __('%1 SKU: %2 => QTY : %3', $this->dateTime->gmtDate(), $product->getSku(), (string) $qty));
            }
            return true;
        } catch (Exception $e) {
            $this->logger->critical($e);
            if (self::DEBUG) {
                $output->writeln((string) __('%1 Problem SKU: %2 => QTY : %3', $this->dateTime->gmtDate(), $product->getSku(), (string) $qty));
            }
            return false;
        }
    }

    /**
     * Update SKU status
     * @param $sku
     * @param $status
     * @param null $output
     * @return bool|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateSkuStatus($sku, $status, $output = null)
    {
        $product = $this->getProductBySku($sku);
        if (!$product || !$output) {
            return;
        }
        try {
            $product->setStatus((int) $status);
            $product = $this->productRepositoryInterface->save($product);
            if (self::DEBUG) {
                $output->writeln((string) __('%1 SKU: %2 => Status : %3', $this->dateTime->gmtDate(), $product->getSku(), (string) $status));
            }
            return $product;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            if (self::DEBUG) {
                $output->writeln((string) __('%1 Problem SKU: %2 => Status : %3', $this->dateTime->gmtDate(), $product->getSku(), (string) $status));
            }
            return false;
        }
    }

    /**
     * Update SKU status
     * @param $sku
     * @param $status
     * @param null $output
     * @return bool|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateSkuPrice($sku, $output = null)
    {
        $product = $this->getProductBySku($sku);
        if (!$product || !$output) {
            return;
        }
        try {
            $price = $product->getPrice() + 0.01;
            $product->setPrice($price);
            $product = $this->productRepositoryInterface->save($product);
            if (self::DEBUG) {
                $output->writeln((string) __('%1 SKU: %2 => Price : %3', $this->dateTime->gmtDate(), $product->getSku(), (float) $price));
            }
            return $product;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            if (self::DEBUG) {
                $output->writeln((string) __('%1 Problem SKU: %2 => Price : %3', $this->dateTime->gmtDate(), $product->getSku(), (float) $price));
            }
            return false;
        }
    }

    /**
     * Return collection of random customers.
     * @param int $limit
     * @param int $websiteId
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRandomCustomer($limit = 1, $websiteId = 1)
    {
        $collection = $this->customerCollectionFactory
            ->create()
            ->addAttributeToSelect('*')
            ->setPageSize($limit);
        if ($websiteId) {
            $collection->addAttributeToFilter('website_id', ['eq' => $websiteId]);
        }
        $collection->getSelect()->order('RAND()');
        return $collection;
    }

    /**
     * Return array of random Customer IDs.
     * @param int $limit
     * @param int $websiteId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRandomCustomerId($limit = 1, $websiteId = 1)
    {
        $customers = $this->getRandomCustomer($limit, $websiteId);
        $ids = [];
        foreach ($customers as $customer) {
            $ids[] = $customer->getId();
        }
        return $ids;
    }

    /**
     * Get customer by Id.
     * @param int $customerId
     * @return \Magento\Customer\Model\Data\Customer
     */
    public function getCustomerById($customerId)
    {
        try {
            return $this->customerRepositoryInterface->getById($customerId);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    /**
     * Update Customer Tax VAT value
     * @param $customerId
     * @param $taxvat
     * @param null $output
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface|\Magento\Customer\Model\Data\Customer|void
     */
    public function updateCustomerTaxVat($customerId, $taxvat, $output = null)
    {
        $customer = $this->getCustomerById($customerId);
        if (!$customer || !$output) {
            return;
        }
        try {
            $customer->setTaxvat($taxvat);
            $customer = $this->customerRepositoryInterface->save($customer);
            if (self::DEBUG) {
                $output->writeln((string) __('%1 Customer : %2 => Tax Vat : %3', $this->dateTime->gmtDate(), $customer->getId(), (string) $taxvat));
            }
            return $customer;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            if (self::DEBUG) {
                $output->writeln((string) __('%1 Customer : %2 => Tax Vat : %3', $this->dateTime->gmtDate(), $customer->getId(), (string) $taxvat));
            }
            return false;
        }
    }

    /**
     * Get random VAT number
     * @return string
     */
    public function getRandomTaxVat()
    {
        return (string) __("%1 %2", "GB", rand(1000000, 9999999));
    }
}
