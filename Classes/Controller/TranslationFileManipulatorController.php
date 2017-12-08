<?php

namespace PITS\TranslationHelper\Controller;

// This file is part of the PITS.TranslationHelper package.

use Neos\Flow\Annotations as Flow;

class TranslationFileManipulatorController extends \Neos\Flow\Mvc\Controller\ActionController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = 'Neos\Flow\Mvc\View\JsonView';

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
     * @Flow\Inject
     * @var \Neos\Flow\Security\Context
     */
    protected $securityContext;

    /**
     * This function receives the Ajax request and process this request.
     * Based on the request, it performs the add, delete, and update operations on the translation file.
     *
     * @param string $id Translation Id
     * @param string $label Translation Label
     * @param string $language Translation Language
     * @param int $cdataChecker Translation CDATA Content Checker
     * @param int $encodingChecker Translation Unit Encoding Decision Checker
     *
     * @return void
     */
    public function indexAction($id = "", $label = "", $language = '', $cdataChecker = 0, $encodingChecker = 0)
    {
        $isValid       = $this->commonSevices->validateTranslationLabel($label, $language);
        
        if ($isValid) {
            $output = ['status'  => 'error', 'message' => $isValid];
        } else {
            $file     = $this->commonSevices->getTranslationFileFullPath($language);
            $output   = $this->commonSevices->performOpertions($file, $id, $label, $cdataChecker, $encodingChecker);
        }

        $this->view->assign('value', $output);
    }
        
    /**
     * This function receives the Ajax request and process this request.
     * Based on the request, it removes the requested translation unit from the translation file.
     *
     * @param string $translationId
     * @param string $csrfToken
     *
     * @return void
     */
    public function deleteTransaltionUnitAction($translationId = "", $csrfToken = "")
    {
        if (trim($csrfToken) != trim($this->securityContext->getCsrfProtectionToken())) {
            $output = ["status"  => "error","message" => ''];
        } else {
            $output = $this->removeTranslationFiles($translationId);
        }
            
        $this->view->assign('value', $output);
    }

    /**
     * This function receives the Ajax request and process this request.
     * Based on the request, it adds translation unit to the translation file.
     *
     * @param string $id Translation Id
     * @param array $labels Translation Labels
     * @param array $languages Translation Languages
     * @param array $cdatas Translation CDATA Content Checker
     * @param array $encodings Translation Unit Encoding Decision Checker
     *
     * @return void
     */
    public function addTransaltionUnitAction($id = "", $labels = [], $languages = [], $cdatas = [], $encodings = [])
    {
        if ($this->validateTranslationUnits($id, $labels, $languages)) {
            $output = ['status' => 'error', 'message' => $this->validateTranslationUnits($id, $labels, $languages)];
        } else {
            $output = $this->addTransaltionUnits($id, $labels, $languages, $cdatas, $encodings);
        }
         
        $this->view->assign('value', $output);
    }
    
    /**
     * This function is used for validate a set of translation units
     *
     * @param string $id Translation Id
     * @param array $labels
     * @param array $languages
     *
     * @return array
     */
    private function validateTranslationUnits($id = "", $labels = [], $languages = [])
    {
        if (empty($languages) || !preg_match("/^[a-zA-Z]([a-zA-Z0-9_\.]*)[a-zA-Z0-9]$/i", $id)) {
            return $this->commonSevices->getTransaltionMessage('invalidTrasIdLangs');
        } elseif ($this->duplicateTransUnitExists($languages, $id)) {
            return $this->commonSevices->getTransaltionMessage('duplicateTransUnits');
        } elseif (!empty($this->validateTransLabels($labels, $languages))) {
            return $this->commonSevices->getTransaltionMessage('invalidTransLabel');
        }
        
        return false;
    }
    
    /**
     * This function is used for check whether the duplicate translation unit exits or not
     *
     * @param array $languages
     * @param string $id
     *
     * @return bool
     */
    private function duplicateTransUnitExists($languages, $id)
    {
        foreach ($languages as $language) {
            $file = $this->commonSevices->getTranslationFileFullPath($language);
            if (is_file($file) && file_exists($file) && $this->commonSevices->isTranslationIdExists($file, $id)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * This function is used for validating set of translation labels
     *
     * @param array $labels
     * @param array $languages
     *
     * @return array
     */
    private function validateTransLabels($labels = [], $languages = [])
    {
        $errorMsg = [];
        foreach ($languages as $key => $language) {
            if (is_file($this->commonSevices->getTranslationFileFullPath($language))
                && file_exists($this->commonSevices->getTranslationFileFullPath($language))
                && isset($labels[$key]) && $this->commonSevices->validateTranslationLabel($labels[$key], $language)) {
                $errorMsg[] = $this->commonSevices->validateTranslationLabel($labels[$key], $language);
            }
        }
        
        return $errorMsg;
    }
    
    /**
     * This function is used for adding translation units to a set of translation files
     *
     * @param string $id
     * @param array $labels
     * @param array $languages
     * @param array $cdatas
     * @param array $encoding
     * @param mixed $encodings
     *
     * @return array
     */
    private function addTransaltionUnits($id = "", $labels = [], $languages = [], $cdatas = [], $encodings = [])
    {
        foreach ($languages as $key => $language) {
            $file = $this->commonSevices->getTranslationFileFullPath($language);
            if ((is_file($file) == true) && (file_exists($file) == true)) {
                $label            = isset($labels[$key])?$labels[$key]:'';
                $cdata            = isset($cdatas[$key])?$cdatas[$key]:0;
                $encoding         = isset($encodings[$key])?$cdatas[$key]:0;
                if (!$this->commonSevices->addTranslationUnit($file, $id, $label, $cdata, $encoding)) {
                    $message = $this->commonSevices->getTransaltionMessage('transUnitaddProblem');
                    
                    return ['status' => 'error', 'message' => $message];
                }
            }
        }
        
        $message = $this->commonSevices->getTransaltionMessage('dataWasSavedSuccessfully');
        
        return ['status' => 'success', 'message' => $message];
    }

    /**
     * This function is used for removing translation files for a particular language.
     *
     * @param string $id Translation unit Id
     *
     * @return array
     */
    private function removeTranslationFiles($id)
    {
        foreach ($this->commonSevices->getLanguages() as $language) {
            $file = $this->commonSevices->getTranslationFileFullPath($language);
            if (!is_file($file) || !file_exists($file) || !$this->commonSevices->removeTranslationUnit($file, $id)) {
                return [
                    "status"  => "error",
                    "message" => $this->commonSevices->getTransaltionMessage('transUnitRemovalProblem'),
                ];
            }
        }
        
        return [
            "status"  => "success",
            "message" => $this->commonSevices->getTransaltionMessage('dataWasSavedSuccessfully'),
        ];
    }
}
