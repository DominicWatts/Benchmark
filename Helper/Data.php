<?php


namespace Xigen\Benchmark\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Data helper class
 */
class Data extends AbstractHelper
{
    const DEBUG = false;

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
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistryInterface,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->stockRegistryInterface = $stockRegistryInterface;
        $this->dateTime = $dateTime;
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
}
