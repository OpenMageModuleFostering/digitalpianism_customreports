<?php
class DigitalPianism_CustomReports_Block_Noupsells extends DigitalPianism_CustomReports_Block_Customreport
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('digitalpianism/customreports/grid.phtml');
		$this->setTitle('Custom Products With No Upsells Report');
		$this->setSideNote('N.B.: the grid displays only configurable products. You can use the visibility and status filter to only get the products which are available on the store.');
    }

    public function _beforeToHtml()
    {
        $this->setChild('grid', $this->getLayout()->createBlock('customreports/noupsells_grid', 'customreports.grid'));
        return $this;
    }

}