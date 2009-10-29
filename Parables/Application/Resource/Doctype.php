<?php
class Parables_Application_Resource_Doctype extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * @var Zend_View
     */
    protected $_view = null;

    /**
     * Initialize doctype
     *
     * @return  void
     */
    public function init()
    {
        $options = $this->getOptions();
        if (array_key_exists('doctype', $options)) {
            $doctypeHelper = new Zend_View_Helper_Doctype();
            $doctypeHelper->doctype($options['doctype']);
        }
    }
}
