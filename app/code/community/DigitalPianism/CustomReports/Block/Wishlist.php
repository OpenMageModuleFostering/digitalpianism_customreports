<?php
class DigitalPianism_CustomReports_Block_Wishlist extends DigitalPianism_CustomReports_Block_Customreport
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('digitalpianism/customreports/grid.phtml');
		$this->setTitle('Wishlist Report');
    }

    public function _beforeToHtml()
    {
        $this->setChild('grid', $this->getLayout()->createBlock('customreports/wishlist_grid', 'customreports.grid'));
        return $this;
    }

}