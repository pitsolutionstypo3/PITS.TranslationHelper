<?php
namespace PITS\TranslationHelper\Domain\ViewHelper;

/*
 * This file is part of the Pits.Newsletter package.
 */

use TYPO3\Flow\Annotations as Flow;

class CDATAContentCheckerViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * NOTE: This property has been introduced via code migration to ensure backwards-compatibility.
     * @see AbstractViewHelper::isOutputEscapingEnabled()
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @Flow\Inject
     * @var \PITS\TranslationHelper\Domain\Service\TranslationHelperCommonSevices
     */
    protected $translationHelperCommonSevices;

    /**
     * @Flow\Inject
     * @var \PITS\TranslationHelper\Domain\Session\TranslationManagement
     */
    protected $translationManagementSession;

    public function render(
        $translationlabelId = "",
        $package = "",
        $locale = "",
        $source = ""
    ) {
        $output                      = false;
        if ((empty($translationlabelId) == false) && (empty($package) == false) && (empty($locale) == false)) {
            $translationsResourcePath = $this->translationHelperCommonSevices->getFlowPackageResourceTranslationPath($package);
            $translationFile          = $this->translationManagementSession->getTranslationFile();
            $translationFileFullPath  = trim($translationsResourcePath) . trim($locale) . "/" . trim($translationFile);
            $translationNodeType = $this->translationHelperCommonSevices->getTranlationNodeTypeFromCurrentTranslationId($translationFileFullPath, $translationlabelId);
            if( empty($translationNodeType) == false ) {
              if( $translationNodeType == 4 ) {
                $output = true;
              }
            }
        }
        return $output;
    }

}
