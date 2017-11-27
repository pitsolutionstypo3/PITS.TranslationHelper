<?php
namespace PITS\TranslationHelper\Controller;

/*
 * This file is part of the PITS.TranslationHelper package.
 */

use Neos\Flow\Annotations as Flow;

class StandardController extends \Neos\Flow\Mvc\Controller\ActionController
{

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

    /**
     * @var string
     */
    protected $localeIdentifier;

    /**
     * This variable is used for getTranslatedFilesFromDirectory function
     * @var string
     */
    protected $parentFolderName = "";

    /**
     * @param \Neos\Flow\Mvc\View\ViewInterface $view
     * @return void
     */
    public function initializeView(\Neos\Flow\Mvc\View\ViewInterface $view)
    {
        $this->localeIdentifier = $this->translationHelperCommonSevices->getLocaleIdentifier();
        $this->view->assignMultiple(array(
            'localeIndicator' => $this->localeIdentifier,
        ));
    }

    /**
     * This function is used for displaying translation available package list
     * @return void
     */
    public function indexAction()
    {
        // Reset translationManagementSession variables
        $this->translationManagementSession->setTranslationPackageKey("");
        $this->translationManagementSession->setTranslationFile("");

        $activePackages     = array();
        $activePackagesSize = 0;

        $translatedLanguages = $this->translationHelperCommonSevices->getCurrentActiveSiteLanguages();
        if (empty($translatedLanguages) == false) {
            $activePackages     = $this->translationHelperCommonSevices->getActivePackagesForTranslation();
            $activePackagesSize = sizeof($activePackages);
        }
        $this->view->assignMultiple(array(
            "activePackages"          => $activePackages,
            "activePackagesSize"      => $activePackagesSize,
            "translatedLanguagesSize" => sizeof($translatedLanguages),
        ));
    }

    /**
     * This function is used for redirecting to suitable actions within the current Controller
     * @param string $redirectInput
     * @param string $redirectFrom
     * @return void
     */
    public function redirectCorrectTranslationAction(
        $redirectInput = "",
        $redirectFrom = ""
    ) {
        $redirctAction = "index";
        if (trim($redirectFrom) == "packageKey") {
            $activePackages = $this->translationHelperCommonSevices->getActivePackagesForTranslation();
            if (sizeof($activePackages) > 0) {
                if (in_array(trim($redirectInput), $activePackages) == true) {
                    $this->translationManagementSession->setTranslationPackageKey($redirectInput);
                    $redirctAction = "getAvailableTranslationFilesInPackage";
                }
            }
        } else if (trim($redirectFrom) == "translationFile") {
            $packageTranslationFiles = $this->translationHelperCommonSevices->getAllTranslationFilesList($this->parentFolderName);
            if (in_array($redirectInput, $packageTranslationFiles) == true) {
                $this->translationManagementSession->setTranslationFile($redirectInput);
                $redirctAction = "getCurrentTranslationFileTranslations";
            }
        }
        $this->redirect($redirctAction, "Standard", "Pits.TranslationHelper");
        return "";
    }

    /**
     * This function is used for displaying available translation files in the selected package
     * @return void
     */
    public function getAvailableTranslationFilesInPackageAction()
    {
        $translatedLanguages = $this->translationHelperCommonSevices->getCurrentActiveSiteLanguages();
        if (empty($translatedLanguages) == true) {
            $this->redirect("index", "Standard", "Pits.TranslationHelper");
        }
        $this->parentFolderName      = "";
        $packageKey                  = $this->translationManagementSession->getTranslationPackageKey();
        $packageTranslationFiles     = $this->translationHelperCommonSevices->getAllTranslationFilesList($this->parentFolderName);
        $packageTranslationFilesSize = sizeof($packageTranslationFiles);
        $this->view->assignMultiple(array(
            "packageKey"                  => $packageKey,
            "packageTranslationFiles"     => $packageTranslationFiles,
            "packageTranslationFilesSize" => $packageTranslationFilesSize,
        ));

    }

    /**
     * This function is used for displaying list of available translations in the selected translation file
     * @return void
     */
    public function getCurrentTranslationFileTranslationsAction()
    {
        $translatedLanguages = $this->translationHelperCommonSevices->getCurrentActiveSiteLanguages();
        if (empty($translatedLanguages) == true) {
            $this->redirect("index", "Standard", "Pits.TranslationHelper");
        }
        $this->translationHelperCommonSevices->checkTranslationFilesExists();
        $translationIds      = $this->translationHelperCommonSevices->getUniqueTranslationIdsFromTranslationFile();
        $translationIdsSize  = sizeof($translationIds);
        $translationFileName = $this->translationManagementSession->getTranslationFile();
        $packageKey          = $this->translationManagementSession->getTranslationPackageKey();
        $translationSource   = $this->translationHelperCommonSevices->getPrefixFileName();
        $this->view->assignMultiple(array(
            "translationFileName"     => $translationFileName,
            "translationIds"          => $translationIds,
            "translationIdsSize"      => $translationIdsSize,
            "translatedLanguages"     => $translatedLanguages,
            "translatedLanguagesSize" => sizeof($translatedLanguages),
            "packageKey"              => $packageKey,
            "translationSource"       => $translationSource,
        ));
    }

}
