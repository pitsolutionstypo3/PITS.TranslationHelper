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
     * @var \PITS\TranslationHelper\Domain\Service\CommonSevices
     */
    protected $commonSevices;

    /**
     * @Flow\Inject
     * @var \PITS\TranslationHelper\Domain\Session\TranslationManagement
     */
    protected $session;

    /**
     * @var string
     */
    protected $localeIdentifier;

    /**
     * This variable is used for getTranslatedFilesFromDirectory function
     *
     * @var string
     */
    protected $parentFolderName = "";

    /**
     * @param \Neos\Flow\Mvc\View\ViewInterface $view
     *
     * @return void
     */
    public function initializeView(\Neos\Flow\Mvc\View\ViewInterface $view)
    {
        $this->localeIdentifier = $this->commonSevices->getLocaleIdentifier();
        $this->view->assignMultiple(array(
            'localeIndicator' => $this->localeIdentifier,
        ));
    }

    /**
     * This function is used for displaying translation available package list
     *
     * @return void
     */
    public function indexAction()
    {
        // Reset session variables
        $this->session->setTranslationPackageKey("");
        $this->session->setTranslationFile("");

        $activePackages     = [];
        $activePackagesSize = 0;

        $translatedLanguages = $this->commonSevices->getCurrentActiveSiteLanguages();
        if (!empty($translatedLanguages)) {
            $activePackages     = $this->commonSevices->getActivePackagesForTranslation();
            $activePackagesSize = sizeof($activePackages);
        }
        $this->view->assignMultiple([
            "activePackages"          => $activePackages,
            "activePackagesSize"      => $activePackagesSize,
            "translatedLanguagesSize" => sizeof($translatedLanguages),
        ]);
    }

    /**
     * This function is used for redirecting to suitable actions within the current Controller
     *
     * @param string $redirectInput
     * @param string $redirectFrom
     *
     * @return void
     */
    public function redirectCorrectTranslationAction(
        $redirectInput = "",
        $redirectFrom = ""
    ) {
        $redirctAction = "index";
        if (trim($redirectFrom) == "packageKey") {
            $activePackages = $this->commonSevices->getActivePackagesForTranslation();
            if (sizeof($activePackages) > 0) {
                if (in_array(trim($redirectInput), $activePackages)) {
                    $this->session->setTranslationPackageKey($redirectInput);
                    $redirctAction = "getAvailableTranslationFilesInPackage";
                }
            }
        } elseif (trim($redirectFrom) == "translationFile") {
            $packageTranslationFiles = $this->commonSevices->getAllTranslationFilesList($this->parentFolderName);
            if (in_array($redirectInput, $packageTranslationFiles)) {
                $this->session->setTranslationFile($redirectInput);
                $redirctAction = "getCurrentTranslationFileTranslations";
            }
        }
        $this->redirect($redirctAction, "Standard", "Pits.TranslationHelper");
        
        return "";
    }

    /**
     * This function is used for displaying available translation files in the selected package
     *
     * @return void
     */
    public function getAvailableTranslationFilesInPackageAction()
    {
        $translatedLanguages = $this->commonSevices->getCurrentActiveSiteLanguages();
        if (empty($translatedLanguages)) {
            $this->redirect("index", "Standard", "Pits.TranslationHelper");
        }
        $this->parentFolderName      = "";
        $packageKey                  = $this->session->getTranslationPackageKey();
        $packageTranslationFiles     = $this->commonSevices->getAllTranslationFilesList($this->parentFolderName);
        $packageTranslationFilesSize = sizeof($packageTranslationFiles);
        $this->view->assignMultiple([
            "packageKey"                  => $packageKey,
            "packageTranslationFiles"     => $packageTranslationFiles,
            "packageTranslationFilesSize" => $packageTranslationFilesSize,
        ]);
    }

    /**
     * This function is used for displaying list of available translations in the selected translation file
     *
     * @return void
     */
    public function getCurrentTranslationFileTranslationsAction()
    {
        $translatedLanguages = $this->commonSevices->getCurrentActiveSiteLanguages();
        if (empty($translatedLanguages)) {
            $this->redirect("index", "Standard", "Pits.TranslationHelper");
        }
        $this->commonSevices->checkTranslationFilesExists();
        $translationIds      = $this->commonSevices->getUniqueTranslationIdsFromTranslationFile();
        $translationIdsSize  = sizeof($translationIds);
        $translationFileName = $this->session->getTranslationFile();
        $packageKey          = $this->session->getTranslationPackageKey();
        $translationSource   = $this->commonSevices->getPrefixFileName();
        $this->view->assignMultiple([
            "translationFileName"     => $translationFileName,
            "translationIds"          => $translationIds,
            "translationIdsSize"      => $translationIdsSize,
            "translatedLanguages"     => $translatedLanguages,
            "translatedLanguagesSize" => sizeof($translatedLanguages),
            "packageKey"              => $packageKey,
            "translationSource"       => $translationSource,
        ]);
    }
}
