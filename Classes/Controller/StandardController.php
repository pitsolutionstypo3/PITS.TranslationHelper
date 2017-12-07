<?php

namespace PITS\TranslationHelper\Controller;

// This file is part of the PITS.TranslationHelper package.

use Neos\Flow\Annotations as Flow;

class StandardController extends \Neos\Flow\Mvc\Controller\ActionController
{
    /**
     * @Flow\Inject
     *
     * @var \PITS\TranslationHelper\Domain\Service\CommonSevices
     */
    protected $commonSevices;

    /**
     * @Flow\Inject
     *
     * @var \PITS\TranslationHelper\Domain\Session\TranslationManagement
     */
    protected $session;

    /**
     * @var string
     */
    protected $localeIdentifier;

    /**
     * This variable is used for getTranslatedFilesFromDirectory function.
     *
     * @var string
     */
    protected $parentFolderName = '';

    /**
     * @param \Neos\Flow\Mvc\View\ViewInterface $view
     */
    public function initializeView(\Neos\Flow\Mvc\View\ViewInterface $view)
    {
        $this->localeIdentifier = $this->commonSevices->getLocaleIdentifier();
        $this->view->assignMultiple(array(
            'localeIndicator' => $this->localeIdentifier,
        ));
    }

    /**
     * This function is used for displaying translation available package list.
     */
    public function indexAction()
    {
        // Reset session variables
        $this->session->setpackageKey('');
        $this->session->setFile('');

        $packages     = [];
        $packagesSize = 0;

        if (!empty($this->commonSevices->getLanguages())) {
            $packages     = $this->commonSevices->getPackages();
            $packagesSize = sizeof($packages);
        }
        $this->view->assignMultiple([
            'activePackages'          => $packages,
            'activePackagesSize'      => $packagesSize,
            'translatedLanguagesSize' => sizeof($this->commonSevices->getLanguages()),
        ]);
    }

    /**
     * This function is used for redirecting to suitable actions within the current Controller.
     *
     * @param string $redirectInput
     * @param string $redirectFrom
     */
    public function redirectCorrectTranslationAction(
        $redirectInput = '',
        $redirectFrom = ''
    ) {
        $redirctAction = 'index';
        if ('packageKey' == trim($redirectFrom)) {
            $activePackages = $this->commonSevices->getPackages();
            if (sizeof($activePackages) > 0) {
                if (in_array(trim($redirectInput), $activePackages)) {
                    $this->session->setpackageKey($redirectInput);
                    $redirctAction = 'getAvailableTranslationFilesInPackage';
                }
            }
        } elseif ('translationFile' == trim($redirectFrom)) {
            $packageTranslationFiles = $this->commonSevices->getAllTranslationFilesList($this->parentFolderName);
            if (in_array($redirectInput, $packageTranslationFiles)) {
                $this->session->setFile($redirectInput);
                $redirctAction = 'getCurrentTranslationFileTranslations';
            }
        }
        $this->redirect($redirctAction, 'Standard', 'Pits.TranslationHelper');

        return '';
    }

    /**
     * This function is used for displaying available translation files in the selected package.
     */
    public function getAvailableTranslationFilesInPackageAction()
    {
        $translatedLanguages = $this->commonSevices->getLanguages();
        if (empty($translatedLanguages)) {
            $this->redirect('index', 'Standard', 'Pits.TranslationHelper');
        }
        $this->parentFolderName      = '';
        $packageKey                  = $this->session->getPackageKey();
        $packageTranslationFiles     = $this->commonSevices->getAllTranslationFilesList($this->parentFolderName);
        $packageTranslationFilesSize = sizeof($packageTranslationFiles);
        $this->view->assignMultiple([
            'packageKey'                  => $packageKey,
            'packageTranslationFiles'     => $packageTranslationFiles,
            'packageTranslationFilesSize' => $packageTranslationFilesSize,
        ]);
    }

    /**
     * This function is used for displaying list of available translations in the selected translation file.
     */
    public function getCurrentTranslationFileTranslationsAction()
    {
        $translatedLanguages = $this->commonSevices->getLanguages();
        if (empty($translatedLanguages)) {
            $this->redirect('index', 'Standard', 'Pits.TranslationHelper');
        }
        $this->commonSevices->checkTranslationFilesExists();
        $translationIds      = $this->commonSevices->getUniqueTranslationIdsFromTranslationFile();
        $translationIdsSize  = sizeof($translationIds);
        $translationFileName = $this->session->getFile();
        $packageKey          = $this->session->getPackageKey();
        $translationSource   = $this->commonSevices->getPrefixFileName();
        $this->view->assignMultiple([
            'translationFileName'     => $translationFileName,
            'translationIds'          => $translationIds,
            'translationIdsSize'      => $translationIdsSize,
            'translatedLanguages'     => $translatedLanguages,
            'translatedLanguagesSize' => sizeof($translatedLanguages),
            'packageKey'              => $packageKey,
            'translationSource'       => $translationSource,
        ]);
    }
}
