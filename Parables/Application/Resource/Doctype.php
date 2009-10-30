<?php
class Parables_Application_Resource_Doctype extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Initialize doctype
     *
     * @return  void
     * @throws  Zend_Application_Resource_Exception
     */
    public function init()
    {
        $options = $this->getOptions();

        if (!array_key_exists('type', $options)) {
            throw new Zend_Application_Resource_Exception('Undefined doctype.');
        }

        $doctypeHelper = new Zend_View_Helper_Doctype();
        $doctypeHelper->doctype($options['type']);
    }
}
