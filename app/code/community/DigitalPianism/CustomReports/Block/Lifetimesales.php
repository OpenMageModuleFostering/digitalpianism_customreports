<?phpclass DigitalPianism_CustomReports_Block_Lifetimesales extends DigitalPianism_CustomReports_Block_Customreport{    public function __construct()    {        parent::__construct();        $this->setTemplate('digitalpianism/customreports/advancedgrid.phtml');		$this->setTitle('Custom Lifetimesales Report');		// Set the right URL for the form which handles the dates		$this->setFormAction(Mage::getUrl('*/*/index'));    }    public function _beforeToHtml()    {        $this->setChild('grid', $this->getLayout()->createBlock('customreports/lifetimesales_grid', 'customreports.grid'));        return $this;    }}