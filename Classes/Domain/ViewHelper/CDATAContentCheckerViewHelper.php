<?php
namespace PITS\TranslationHelper\Domain\ViewHelper;

/*
 * This file is part of the Pits.TranslationHelper package.
 */

use Neos\Flow\Annotations as Flow;

class CDATAContentCheckerViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper
{

    /**
     * NOTE: This property has been introduced via code migration to ensure backwards-compatibility.
     *
     * @see AbstractViewHelper::isOutputEscapingEnabled()
     *
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @Flow\Inject
     * @var \PITS\TranslationHelper\Domain\Service\CommonSevices
     */
    protected $commonSevices;

    /**
     * @Flow\Inject
     * @var \PITS\TranslationHelper\Domain\Session\TranslationManagement
     */
    protected $session;

    /**
     * Checks whether the current element is CDATA or not
     * 
     * @param string $id
     * @param string $locale
     * 
     * @return boolean
     */
    public function render($id = "", $locale = "")
    {
        if (!empty($id) && !empty($locale)) {
            $file = $this->commonSevices->getTranslationFileFullPath($locale);
            $nodeType = $this->commonSevices->getTranlationNodeType($file, $id);
            if (!empty($nodeType) && $nodeType == XML_CDATA_SECTION_NODE) {
                return true;
            }
        }
        return false;
    }
}
