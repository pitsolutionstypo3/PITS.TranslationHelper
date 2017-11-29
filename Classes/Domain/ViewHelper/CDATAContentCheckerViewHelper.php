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

    public function render(
        $translationlabelId = "",
        $package = "",
        $locale = "",
        $source = ""
    ) {
        $output                      = false;
        if ((empty($translationlabelId) == false) && (empty($package) == false) && (empty($locale) == false)) {
            $translationsResourcePath = $this->commonSevices->getFlowPackageResourceTranslationPath($package);
            $translationFile          = $this->session->getTranslationFile();
            $translationFileFullPath  = trim($translationsResourcePath) . trim($locale) . "/" . trim($translationFile);
            $translationNodeType = $this->commonSevices->getTranlationNodeTypeFromCurrentTranslationId($translationFileFullPath, $translationlabelId);
            if (empty($translationNodeType) == false) {
                if ($translationNodeType == 4) {
                    $output = true;
                }
            }
        }
        return $output;
    }
}
