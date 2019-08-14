<?php
class DigitalPianism_CustomReports_Block_Customreport extends Mage_Adminhtml_Block_Template
{
	protected $_sideNote = null;
	
	public function setSideNote($nb)
	{
		$this->_sideNote = $nb;
	}
	
	public function getSideNote()
	{
		return $this->_sideNote;
	}
}