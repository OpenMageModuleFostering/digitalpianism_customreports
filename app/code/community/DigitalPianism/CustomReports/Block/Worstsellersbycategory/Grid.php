<?php
class DigitalPianism_CustomReports_Block_Worstsellersbycategory_Grid extends AW_Advancedreports_Block_Advanced_Bestsellers_Grid
{

    public function __construct()
    {
        Mage_Adminhtml_Block_Widget_Grid::__construct();
        $this->setId('worstsellersbycategoryReportGrid');
    }
	
    protected function _prepareCollection()
    {
		// Get the session
		$session = Mage::getSingleton('core/session');
		
		// Dates for one week
		$store = Mage_Core_Model_App::ADMIN_STORE_ID;
		$timezone = Mage::app()->getStore($store)->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
		date_default_timezone_set($timezone);
		
		// Automatic -30 days if no dates provided
		if ($session->getFrom())
		{
			$sDate = $session->getFrom();
			$sDate = str_replace('/', '-', $sDate);
			$sDate = strtotime($sDate);
			$sDate = date('Y-m-d H:i:s', $sDate); 
		}
		else
		{
			$sDate = date('Y-m-d 00:00:00',
				Mage::getModel('core/date')->timestamp(strtotime('-30 days'))
			);
		}
		
		if ($session->getTo())
		{
			$eDate = $session->getTo();
			$eDate = str_replace('/', '-', $eDate);
			$eDate = strtotime($eDate);
			$eDate = date('Y-m-d H:i:s', $eDate); 
		}
		else
		{
			$eDate = date('Y-m-d 23:59:59', 
				Mage::getModel('core/date')->timestamp(time())
			);
		}
		
		###############################################################################

		$start = new Zend_Date($sDate);
		$start->setTimeZone("UTC");

        $end = new Zend_Date($eDate);
		$end->setTimeZone("UTC");

		###############################################################################

		$from = $start->toString("Y-MM-dd HH:mm:ss");
		$to = $end->toString("Y-MM-dd HH:mm:ss");
		
		
		// Get the bestsellers product using Magento collection
		$bestSellers = Mage::getResourceModel('reports/product_collection')
			->addOrderedQtyAndTotal($sDate, $eDate)
			->addAttributeToSelect('*')
			->setOrder('ordered_qty');
			
		//echo $bestSellers->printlogquery(true);
		
		// Array that will contain the data
		$arrayBestSellers = array();
		foreach ($bestSellers as $productSold)
		{			
			// We fill the array with the data
			$arrayBestSellers[$productSold->getEntityId()] = array(
				'ordered_qty'	=>	$productSold->getOrderedQty(),
				'views'			=>	0,
				'product_id'	=>	$productSold->getEntityId()
			);
		}
			
		// Get the most viewed products
		$mostViewed = Mage::getResourceModel('reports/product_collection')
			->addAttributeToSelect('*')
			->addViewsCount($from, $to);
			
		//echo $mostViewed->printlogquery(true);
			
		// Array that will contain the data
		$arrayMostViewed = array();
		foreach ($mostViewed as $productViewed)
		{
			// If the product has been pushed to the first array
			// That means it has been sold
			if (array_key_exists($productViewed->getEntityId(),$arrayBestSellers) && is_array($arrayBestSellers[$productViewed->getEntityId()]))
			{
				// We get the number of views
				$arrayBestSellers[$productViewed->getEntityId()]['views'] = $productViewed->getViews();
			}
			// Else it is a product that has never been sold
			else
			{
				// We fill the array with the data
				$arrayBestSellers[$productViewed->getEntityId()] = array(
					'ordered_qty'	=>	0,
					'views'			=>	$productViewed->getViews(),
					'product_id'	=>	$productViewed->getEntityId()
				);
			}
		}
        
		// Array that will contain the data
		$arrayWorstSellers = array();
		foreach ($arrayBestSellers as $worstSellerProductId => $worstSellerProduct)
		{
			// Get Product ID
			$id = $worstSellerProduct['product_id'];
			
			// Get Sold Quantity and Total
			$orderedQty = $worstSellerProduct['ordered_qty'];
			$views = $worstSellerProduct['views'];
			
			// Load the potential parent product ids
			$parentProduct = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($id);
			
			// If a product is an associated product
			if (!empty($parentProduct) && isset($parentProduct[0]))
			{
				// Get the parent configurable product id
				$productId = $parentProduct[0];
			}
			else
			{	
				// Get the simple product id
				$productId = $id;
			}
			
			// Get all categories of this product
			$categories = Mage::getResourceModel('catalog/category_collection')
							->joinField('product_id',
								'catalog/category_product',
								'product_id',
								'category_id = entity_id',
								null)
							->addAttributeToSelect('name')
							->addAttributeToSelect('parent_id')
							->addFieldToFilter('product_id', $productId);

			// Export this collection to array so we could iterate on it's elements
			$categories = $categories->exportToArray();
			// Get categories names
			foreach($categories as $category)
			{
				// Get Category ID
				$categoryID = $category['entity_id'];
				// Get Category Name
				$categoryName = $category['name'];
				
				// If category already in the array, we add data
				if (array_key_exists($categoryID, $arrayWorstSellers))
				{
					// We update the ordered quantity
					$arrayWorstSellers[$categoryID]['ordered_qty'] += $orderedQty;
					
					// We udpate the ordered total
					$arrayWorstSellers[$categoryID]['views'] += $views;
				}
				else
				{
					// For the categories called 'ALL' we need to add the parent category name 
					if (strtolower($categoryName)=='all')
					{
						// Get the parent category Name
						$parentCategoryName = Mage::getModel('catalog/category')->load($category['parent_id'])->getName();
						// Add the parent category name 
						$categoryName = $parentCategoryName . " > " . $categoryName;
					}
					
					// Else we create a new entry with the data
					$arrayWorstSellers[$categoryID] = array(
						'name'			=>	$categoryName,
						'ordered_qty'	=>	$orderedQty,
						'views'			=>	$views
					);
				}
			}
		}
		
		// Obtain a list of columns to sort the array using subkeys
		$views = array();
		$qty = array();
		foreach ($arrayWorstSellers as $key => $row) {
			$views[$key] = $row['views'];
			$qty[$key] = $row['ordered_qty'];
		}

		// Sort the data with qty ascending, views descending
		// Add $arrayWorstSellers as the last parameter, to sort by the common key
		array_multisort($qty, SORT_ASC, $views, SORT_DESC, $arrayWorstSellers);
		
		// Convert the array to a collection
		$collection = new Varien_Data_Collection();
		foreach($arrayWorstSellers as $category){
			$rowObj = new Varien_Object();
			$rowObj->setData($category);
			$collection->addItem($rowObj);
		}
		
        $this->setCollection($collection);
		
        Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
         $this->addColumn('name', array(
            'header'    => Mage::helper('reports')->__('Category Name'),
			'width'     => '50',
            'index'     => 'name'
        ));

        $this->addColumn('ordered_qty', array(
            'header'    => Mage::helper('reports')->__('Ordered Quantity'),
            'width'     => '150',
            'index'     => 'ordered_qty',
        ));

        $this->addColumn('views', array(
            'header'    => Mage::helper('reports')->__('Views'),
            'width'     => '150',
            'index'     => 'views',
        ));

        $this->addExportType('*/*/exportWorstsellersbycategoryCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportWorstsellersbycategoryExcel', Mage::helper('reports')->__('Excel'));

        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }

}