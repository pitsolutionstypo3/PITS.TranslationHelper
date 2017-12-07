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
     * This variable is used for getDirectoryFiles function.
     *
     * @var string
     */
    protected $parentFolderName = '';

    /**
     * @param \Neos\Flow\Mvc\View\ViewInterface $view
     *
     * @return void
     */
    public function initializeView(\Neos\Flow\Mvc\View\ViewInterface $view)
    {
        $this->view->assignMultiple(array(
            'localeIndicator' => $this->commonSevices->getLocale(),
        ));
    }

    /**
     * This function is used for displaying translation available package list.
     *
     * @return void
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
     * @param string $input
     * @param string $from
     *
     * @return void
     */
    public function redirectAction($input = '', $from = '')
    {
        $action = 'index';
        if ('packageKey' == trim($from)
            && sizeof($this->commonSevices->getPackages()) > 0
            && in_array(trim($input), $this->commonSevices->getPackages())) {
            $this->session->setpackageKey($input);
            $action = 'getFiles';
        } elseif ('translationFile' == trim($from)
                  && in_array($input, $this->commonSevices->getFiles($this->parentFolderName))) {
            $this->session->setFile($input);
            $action = 'getTranslations';
        }
        $this->redirect($action, 'Standard', 'PITS.TranslationHelper');
    }

    /**
     * This function is used for displaying available translation files in the selected package.
     *
     * @return void
     */
    public function getFilesAction()
    {
        $this->isValidLanguages();
        $this->parentFolderName      = '';
        $this->view->assignMultiple([
            'packageKey'                  => $this->session->getPackageKey(),
            'packageTranslationFiles'     => $this->commonSevices->getFiles($this->parentFolderName),
            'packageTranslationFilesSize' => sizeof($this->commonSevices->getFiles($this->parentFolderName)),
        ]);
    }

    /**
     * This function is used for displaying list of available translations in the selected translation file.
     */
    public function getTranslationsAction()
    {
        $this->isValidLanguages();
        $this->commonSevices->checkTranslationFilesExists();
        $this->view->assignMultiple([
            'translationFileName'     => $this->session->getFile(),
            'translationIds'          => $this->commonSevices->getTranslationIds(),
            'translationIdsSize'      => sizeof($this->commonSevices->getTranslationIds()),
            'translatedLanguages'     => $this->commonSevices->getLanguages(),
            'translatedLanguagesSize' => sizeof($this->commonSevices->getLanguages()),
            'packageKey'              => $this->session->getPackageKey(),
            'translationSource'       => $this->commonSevices->getPrefixFileName(),
        ]);
    }
    
    /**
     * This function is used for checking whether the site has valid languages or not
     * based on this check a redirection performed
     *
     * @return void
     */
    private function isValidLanguages()
    {
        if (empty($this->commonSevices->getLanguages())) {
            $this->redirect('index', 'Standard', 'Pits.TranslationHelper');
        }
    }
}
