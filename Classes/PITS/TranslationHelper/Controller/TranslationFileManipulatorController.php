<?php
namespace PITS\TranslationHelper\Controller;

/*
 * This file is part of the PITS.TranslationHelper package.
 */

use TYPO3\Flow\Annotations as Flow;

class TranslationFileManipulatorController extends \TYPO3\Flow\Mvc\Controller\ActionController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = 'TYPO3\Flow\Mvc\View\JsonView';

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
     * @Flow\Inject
     * @var \TYPO3\Flow\Security\Context
     */
    protected $securityContext;

    /**
     * This function receives the Ajax request and process this request. Based on the request, it performs the add, delete, and update operations on the translation file.
     * @param string $translationId
     * @param string $translationLabel
     * @param string $translationLanguage
     * @param string $translationCDATAContentChecker
     * @return void
     */
    public function indexAction(
        $translationId = "",
        $translationLabel = "",
        $translationLanguage = "",
        $translationCDATAContentChecker = 0
    ) {
        $output = array(
            "status"  => "error",
            "message" => "Invalid Inputs.",
        );
        $flag     = 0;
        $errorMsg = array();
        try {

            $availableSiteLanguages   = $this->translationHelperCommonSevices->getCurrentActiveSiteLanguages();
            $packageKey               = $this->translationManagementSession->getTranslationPackageKey();
            $translationsResourcePath = $this->translationHelperCommonSevices->getFlowPackageResourceTranslationPath($packageKey);
            $translationFile          = $this->translationManagementSession->getTranslationFile();
            $translationFileFullPath  = trim($translationsResourcePath) . trim($translationLanguage) . "/" . trim($translationFile);
            if (empty($availableSiteLanguages) == true) {
                $errorMsg[] = "No Available languages.";
                $flag       = 1;
            } else {
                $translationLanguage = filter_var($translationLanguage, FILTER_SANITIZE_STRING);
                if (empty($translationLanguage) == true) {
                    $errorMsg[] = "Invalid Language Input.";
                    $flag       = 1;
                } else {
                    if (in_array(trim($translationLanguage), $availableSiteLanguages) == false) {
                        $errorMsg[] = "Given Language is not used in this site.";
                        $flag       = 1;
                    }
                }
            }
            if (is_file($translationFileFullPath) == false) {
                $errorMsg[] = "Incorrect translation file full path.";
                $flag       = 1;
            } else {
                if (file_exists($translationFileFullPath) == false) {
                    $errorMsg[] = "translation file does not exist.";
                    $flag       = 1;
                }
            }

            if ($flag == 1) {
                $output = array(
                    "status"  => "error",
                    "message" => implode(",", $errorMsg),
                );
            } else {
                $output = $this->translationHelperCommonSevices->performCURDOpertionsOnTranslationFiles($translationFileFullPath, $translationId, $translationLabel, $translationCDATAContentChecker);
            }

        } catch (\Exception $e) {
            $output = array(
                "status"  => "error",
                "message" => $e->getMessage(),
            );
        }

        $this->view->assign('value', $output);
    }

    /**
     * This function receives the Ajax request and process this request. Based on the request, it removes the requested translation unit from the translation file.
     * @param string $translationId
     * @param string $csrfToken
     * @return void
     */
    public function deleteTransaltionUnitAction(
        $translationId = "",
        $csrfToken = ""
    ) {
        $output = array(
            "status"  => "error",
            "message" => "Invalid Inputs.",
        );
        $flag     = 0;
        $errorMsg = array();
        try {
            $currentCsrfToken = $this->securityContext->getCsrfProtectionToken();
            if (trim($csrfToken) != trim($currentCsrfToken)) {
                $flag1 = 1;
            }

            if ($flag == 1) {
                $output = array(
                    "status"  => "error",
                    "message" => implode(",", $errorMsg),
                );
            } else {
                $availableSiteLanguages   = $this->translationHelperCommonSevices->getCurrentActiveSiteLanguages();
                $packageKey               = $this->translationManagementSession->getTranslationPackageKey();
                $translationsResourcePath = $this->translationHelperCommonSevices->getFlowPackageResourceTranslationPath($packageKey);
                $translationFile          = $this->translationManagementSession->getTranslationFile();

                if (empty($availableSiteLanguages) == false) {
                    $transUnitRemovalFlag = 0;
                    foreach ($availableSiteLanguages as $translationLanguage) {
                        $translationFileFullPath = trim($translationsResourcePath) . trim($translationLanguage) . "/" . trim($translationFile);
                        if ((is_file($translationFileFullPath) == true) && (file_exists($translationFileFullPath) == true)) {
                            $transUnitRemovalResult = $this->translationHelperCommonSevices->removeTranslationUnitFromCurrentTranslationFile($translationFileFullPath, $translationId);
                            if ($transUnitRemovalResult == false) {
                                $transUnitRemovalFlag = 1;
                            }
                        }
                    }

                    if ($transUnitRemovalFlag == 0) {
                        $output = array(
                            "status"  => "success",
                            "message" => $this->translationHelperCommonSevices->getDataSavedSuccessfullyMsg("en"),
                        );
                    } else {
                        $output = array(
                            "status"  => "error",
                            "message" => "Some problem in translation unit removal process",
                        );
                    }
                }
            }

        } catch (\Exception $e) {
            $output = array(
                "status"  => "error",
                "message" => $e->getMessage(),
            );
        }
        $this->view->assign('value', $output);
    }

    /**
     * This function receives the Ajax request and process this request. Based on the request, it adds translation unit to the translation file.
     * @param string $translationId
     * @param string $csrfToken
     * @param array $translationUnitLabels
     * @param array $translationUnitLanguages
     * @param array $translationUnitCDATASections
     * @return void
     */
    public function addTransaltionUnitAction(
        $translationId = "",
        $csrfToken = "",
        $translationUnitLabels = array(),
        $translationUnitLanguages = array(),
        $translationUnitCDATASections = array()
    ) {
        $output = array(
            "status"  => "error",
            "message" => "Invalid Inputs.",
        );
        $flag     = 0;
        $errorMsg = array();
        try {
            $packageKey               = $this->translationManagementSession->getTranslationPackageKey();
            $translationsResourcePath = $this->translationHelperCommonSevices->getFlowPackageResourceTranslationPath($packageKey);
            $translationFile          = $this->translationManagementSession->getTranslationFile();
            $duplicateTranslationId   = 0;

            if (empty($translationUnitLanguages) == true) {
                $errorMsg[] = "Empty translation language input.";
                $flag       = 1;
            } else {
                $translationUnitValidityCheckRegExp = "/^[a-zA-Z]([a-zA-Z0-9_\.]*)[a-zA-Z0-9]$/i";
                if (preg_match($translationUnitValidityCheckRegExp, $translationId) != 1) {
                    $errorMsg[] = "Incorrect translation unit Id.";
                    $flag       = 1;
                } else {
                    foreach ($translationUnitLanguages as $translationUnitLanguage) {
                        $translationFileFullPath = trim($translationsResourcePath) . trim($translationUnitLanguage) . "/" . trim($translationFile);
                        if ((is_file($translationFileFullPath) == true) && (file_exists($translationFileFullPath) == true)) {
                            $duplicateTranslationUnit = $this->translationHelperCommonSevices->checkGivenTranslationIdExists($translationFileFullPath, $translationId);
                            if (empty($duplicateTranslationUnit) == false) {
                                $duplicateTranslationId = 1;
                            }
                        }
                    }
                    if ($duplicateTranslationId == 1) {
                        $errorMsg[] = "Duplicate translation unit.";
                        $flag       = 1;
                    } else {
                      foreach ($translationUnitLanguages as $translationUnitLanguagekey => $translationUnitLanguage) {
                          $translationFileFullPath = trim($translationsResourcePath) . trim($translationUnitLanguage) . "/" . trim($translationFile);
                          if ((is_file($translationFileFullPath) == true) && (file_exists($translationFileFullPath) == true)) {
                              $translationLabel               = "";
                              $translationCDATAContentChecker = 0;
                              if (isset($translationUnitLabels[$translationUnitLanguagekey]) == true) {
                                  $translationLabel = $translationUnitLabels[$translationUnitLanguagekey];
                              }
                              if (isset($translationUnitCDATASections[$translationUnitLanguagekey]) == true) {
                                  $translationCDATAContentChecker = $translationUnitCDATASections[$translationUnitLanguagekey];
                              }
                              $translationUnitCommonValidationResult = $this->translationHelperCommonSevices->performCommonTranslationLabelValidation($translationLabel, $translationCDATAContentChecker, $translationUnitLanguage);
                              if( empty($translationUnitCommonValidationResult) == false ) {
                                $errorMsg[] = trim($translationUnitCommonValidationResult);
                                $flag       = 1;
                              }
                          }
                      }
                    }
                }
            }
            if ($flag == 1) {
                $output = array(
                    "status"  => "error",
                    "message" => implode(",", $errorMsg),
                );
            } else {
                $translationUnitAdditionSuccess = true;
                foreach ($translationUnitLanguages as $translationUnitLanguagekey => $translationUnitLanguage) {
                    $translationFileFullPath = trim($translationsResourcePath) . trim($translationUnitLanguage) . "/" . trim($translationFile);
                    if ((is_file($translationFileFullPath) == true) && (file_exists($translationFileFullPath) == true)) {
                        $translationLabel               = "";
                        $translationCDATAContentChecker = 0;
                        if (isset($translationUnitLabels[$translationUnitLanguagekey]) == true) {
                            $translationLabel = $translationUnitLabels[$translationUnitLanguagekey];
                        }
                        if (isset($translationUnitCDATASections[$translationUnitLanguagekey]) == true) {
                            $translationCDATAContentChecker = $translationUnitCDATASections[$translationUnitLanguagekey];
                        }
                        $translationUnitAdditionSuccess = $this->translationHelperCommonSevices->addNewTranslationUnitToCurrentTranslationFile($translationFileFullPath, $translationId, $translationLabel, $translationCDATAContentChecker);
                    }
                }

                if ($translationUnitAdditionSuccess == true) {
                    $output = array(
                        "status"  => "success",
                        "message" => $this->translationHelperCommonSevices->getDataSavedSuccessfullyMsg("en"),
                    );
                } else {
                    $output = array(
                        "status"  => "error",
                        "message" => "Some problem in translation unit addition process",
                    );
                }

            }

        } catch (\Exception $e) {
            $output = array(
                "status"  => "error",
                "message" => $e->getMessage(),
            );
        }
        $this->view->assign('value', $output);
    }
}
