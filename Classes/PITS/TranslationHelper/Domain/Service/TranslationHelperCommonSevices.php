<?php
namespace PITS\TranslationHelper\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "GVB.App".               *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class TranslationHelperCommonSevices
{

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Service\ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSourceInterface;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Package\PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * @Flow\Inject
     * @var \PITS\TranslationHelper\Domain\Session\TranslationManagement
     */
    protected $translationManagementSession;

    /**
     * This variable is used for getTranslatedFilesFromDirectory function
     * @var string
     */
    protected $parentFolderName = "";

    /**
     * This variable is used to store a list of currently available translation file path.
     * @var array
     */
    protected $currentlyAvailableTranslationFiles = array();

    /**
     * It is used for getting the current using language in the website
     * @return string
     */
    public function getLocaleIdentifier($controllerContext = null)
    {
        $curentLanguage = 'en';
        if (empty($controllerContext) == false) {
            $requestInternalArguments = $controllerContext->getRequest()->getInternalArguments();
            if (empty($requestInternalArguments) == false) {
                $internalArgumentNode = $requestInternalArguments['__node'];
                $targetDimension      = $internalArgumentNode->getContext()->getTargetDimensions();
                $curentLanguage       = $targetDimension["language"];
            }
        }
        return $curentLanguage;

    }

    /**
     * This function is used for getting the current using language identifiers
     * @return mixed
     */
    public function getCurrentActiveSiteLanguages()
    {
        $contentDimensionPresetsConfigurations = $this->contentDimensionPresetSourceInterface->getAllPresets();
        $languageIdentifiers                   = array();
        if (isset($contentDimensionPresetsConfigurations["language"]["presets"]) == true) {
            $languagePresets = $contentDimensionPresetsConfigurations["language"]["presets"];
            if ((empty($languagePresets) == false) && (is_array($languagePresets) == true)) {
                $languageIdentifiers = array_keys($languagePresets);
            }
        }
        return $languageIdentifiers;
    }

    /**
     * This function is used for getting the list of active flow packages
     * @return mixed
     */
    public function getActivePackagesForTranslation()
    {
        $activeFinalPackages = array();
        $activePackages      = $this->packageManager->getActivePackages();
        if ((empty($activePackages) == false) && (is_array($activePackages) == true)) {
            $activeFinalPackages = array_keys($activePackages);
            $activeFinalPackages = array_filter($activeFinalPackages, function ($packageKey) {
                $packageTranslationResourcePath = $this->getFlowPackageResourceTranslationPath($packageKey);
                return file_exists($packageTranslationResourcePath);
            });
        }
        return $activeFinalPackages;
    }

    /**
     * This function is used for getting the relative resource folder path of the given flow package
     * @param string $packageKey
     * @return string
     */
    public function getFlowPackageResourceTranslationPath(
        $packageKey = "none"
    ) {
        $packageResourcePath = "";
        $isActivePackages    = $this->packageManager->isPackageActive($packageKey);
        if ($isActivePackages == true) {
            $packageObject = $this->packageManager->getPackage($packageKey);
            if (empty($packageObject) == false) {
                $packageResourcePath = $packageObject->getResourcesPath();
                $packageResourcePath = $packageResourcePath . "Private/Translations/";
            }
        }
        return $packageResourcePath;
    }

    /**
     * This function is used for getting the translated message for corresponding translation unit ID.
     * @param string  $localeIdentifier
     * @param string $translationId
     * @return string
     */
    public function getCorrectTranslationLabelFromTranslationUnitId(
        $translationId = "",
        $localeIdentifier = 'en'
    ) {
        $module           = $this->getPrefixFileName();
        $packageKey       = $this->translationManagementSession->getTranslationPackageKey();
        $locale           = new \TYPO3\Flow\I18n\Locale($localeIdentifier);
        $translationLabel = $this->translator->translateById($translationId, [], null, $locale, trim($module), trim($packageKey));
        return $translationLabel;
    }

    /**
     * This function is used for getting the translated message of the data saved successfully message
     * @param string  $localeIdentifier
     * @return string
     */
    public function getDataSavedSuccessfullyMsg($localeIdentifier = 'en')
    {
        $locale                 = new \TYPO3\Flow\I18n\Locale($localeIdentifier);
        $validationErrorMessage = $this->translator->translateById("dataWasSavedSuccessfully", [], null, $locale, "Main", "PITS.TranslationHelper");
        return $validationErrorMessage;
    }

    /**
     * This function is used for getting the translated message of the data deleted successfully message
     * @param string  $localeIdentifier
     * @return string
     */
    public function getDataDeletedSuccessfullyMsg($localeIdentifier = 'en')
    {
        $locale                 = new \TYPO3\Flow\I18n\Locale($localeIdentifier);
        $validationErrorMessage = $this->translator->translateById("dataWasDeletedSuccessfully", [], null, $locale, "Main", "PITS.TranslationHelper");
        return $validationErrorMessage;
    }

    /**
     * This function is used for getting the list of available translated files in the given package
     * @param string $parentFolderName
     * @return array
     */
    public function getAllTranslationFilesList($parentFolderName = "")
    {
        $finalTranslatedFiles     = array();
        $this->parentFolderName   = trim($parentFolderName);
        $packageKey               = $this->translationManagementSession->getTranslationPackageKey();
        $translationsResourcePath = $this->getFlowPackageResourceTranslationPath($packageKey);
        $translatedLanguages      = $this->getCurrentActiveSiteLanguages();

        // List all available translation files in a package
        $availableTranslationPackageLanguages = $this->getAllAvailableTranslationLanguagesFromTranslationPackage($translationsResourcePath);
        foreach ($availableTranslationPackageLanguages as $translatedLanguage) {
            $translationEachLanguageDirectory = $translationsResourcePath . trim($translatedLanguage) . "/";
            $this->getTranslatedFilesFromDirectory($translationEachLanguageDirectory, $finalTranslatedFiles);
        }

        $finalTranslatedFiles = array_unique($finalTranslatedFiles);
        return $finalTranslatedFiles;
    }

    /**
     * This function gets the list of languages from current translation package
     * @param string $packageDirectoryFolderPath
     * @return mixed
     */
    public function getAllAvailableTranslationLanguagesFromTranslationPackage(
        $packageDirectoryFolderPath = ""
    ) {
        $availableLanguages = array();
        try {
            if (empty($packageDirectoryFolderPath) == false) {
                if ((is_dir($packageDirectoryFolderPath) == true) && (file_exists($packageDirectoryFolderPath) == true)) {
                    $directoryPointer = dir($packageDirectoryFolderPath);
                    if ($directoryPointer !== false) {
                        while (($directoryFile = $directoryPointer->read()) !== false) {
                            $regExp = "/^\.+$/i";
                            if (preg_match($regExp, trim($directoryFile), $matches) == false) {
                                $directoryFilePath = trim($packageDirectoryFolderPath) . trim($directoryFile) . "/";
                                if (is_dir($directoryFilePath) == true) {
                                    $availableLanguages[] = trim($directoryFile);
                                }
                            }
                        }
                        $directoryPointer->close();
                    }
                }
                \clearstatcache();
            }
        } catch (\Exception $e) {
          // \TYPO3\Flow\var_dump($e->getMessage());
          // exit;
          $availableLanguages = array();
        }
        return $availableLanguages;
    }

    /**
     * This function gets the list of translated files from directory
     * @param string $directory
     * @return void
     */
    private function getTranslatedFilesFromDirectory(
        $directory = "",
        &$finalTranslatedFiles = null
    ) {
        try {
            if ((is_dir($directory) == true) && (file_exists($directory) == true)) {
                $directoryPointer = dir($directory);
                if ($directoryPointer !== false) {
                    while (($directoryFile = $directoryPointer->read()) !== false) {
                        $regExp = "/^\.+$/i";
                        if (preg_match($regExp, trim($directoryFile), $matches) == false) {
                            $directoryFilePath = trim($directory) . trim($directoryFile) . "/";
                            if (is_dir($directoryFilePath) == true) {
                                $this->parentFolderName = trim($directoryFile) . "/";
                                $this->getTranslatedFilesFromDirectory($directoryFilePath, $finalTranslatedFiles);
                                $this->parentFolderName = "";
                            } else {
                                $finalTranslatedFiles[] = $this->parentFolderName . trim($directoryFile);
                            }
                        }
                    }
                    $directoryPointer->close();
                }
            }
            \clearstatcache();
        } catch (\Exception $e) {
            // \TYPO3\Flow\var_dump($e->getMessage());
            // exit;
        }

    }

    /**
     * This function is used for checking whether the translation files exist or not. If the translation file is not exist, then the corresponding translation file is created.
     * @return void
     */
    public function checkTranslationFilesExists()
    {
        $packageKey                               = $this->translationManagementSession->getTranslationPackageKey();
        $translationsResourcePath                 = $this->getFlowPackageResourceTranslationPath($packageKey);
        //$translatedLanguages                    = $this->getCurrentActiveSiteLanguages();
        $translatedLanguages                      = array_merge_recursive($this->getCurrentActiveSiteLanguages(), $this->getAllAvailableTranslationLanguagesFromTranslationPackage($translationsResourcePath));
        $translatedLanguages                      = array_unique($translatedLanguages);
        $translationFile                          = $this->translationManagementSession->getTranslationFile();
        $availableTranslationFile                 = "";
        $this->currentlyAvailableTranslationFiles = array();
        try {

            if (sizeof($translatedLanguages) > 0) {
                foreach ($translatedLanguages as $translatedLanguage) {
                    //Getting correct directory path
                    $translationsSourceFiles           = $this->getPrefixFileName();
                    $translationFileDirectorySmallPath = "";
                    if (empty($translationsSourceFiles) == false) {
                        $translationsSourceFilesParts = explode("/", trim($translationsSourceFiles));
                        if (empty($translationsSourceFilesParts) == false) {
                            array_pop($translationsSourceFilesParts);
                            $translationFileDirectorySmallPath = implode("/", $translationsSourceFilesParts);
                        }
                    }
                    $translationFileDirectory = trim($translationsResourcePath) . trim($translatedLanguage) . "/";
                    $translationFilePath      = trim($translationFileDirectory) . trim($translationFile);
                    $translationFileDirectory = trim($translationFileDirectory) . trim($translationFileDirectorySmallPath);

                    if (file_exists($translationFilePath) == false) {
                        if (file_exists(trim($translationFileDirectory)) == false) {
                            \mkdir($translationFileDirectory, 0777, true);
                        }
                        if (file_exists(trim($translationFilePath)) == false) {
                            \touch($translationFilePath);
                        }
                        if ((file_exists(trim($translationFilePath)) == true) && (is_file(trim($translationFilePath)) == true)) {
                            $this->createEmptyTranslationFile($translationFilePath, $translatedLanguage);
                        }
                    } else if (filesize($translationFilePath) <= 0) {
                        $this->createEmptyTranslationFile($translationFilePath, $translatedLanguage);
                    }
                    $this->currentlyAvailableTranslationFiles[] = trim($translationFilePath);

                }

            }
            \clearstatcache();
        } catch (\Exception $e) {
            // \TYPO3\Flow\var_dump($e->getMessage());
            // exit;
        }
    }

    /**
     * This function is used for creating an empty translation file.
     * @param string $translationFilePath
     * @param string $translatedLanguage
     * @return void
     */
    private function createEmptyTranslationFile(
        $translationFilePath = "",
        $translatedLanguage = "en"
    ) {
        try {
            $packageKey = $this->translationManagementSession->getTranslationPackageKey();
            if ((file_exists($translationFilePath) == true) && (is_file($translationFilePath) == true)) {
                $emptyTranslationFileTemplate = '<?xml version="1.0"?>' . PHP_EOL . '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">' . PHP_EOL . ' <file original="" product-name="' . trim($packageKey) . '" source-language="' . trim($translatedLanguage) . '" datatype="plaintext">' . PHP_EOL . '  <body></body>' . PHP_EOL . ' </file>' . PHP_EOL . '</xliff>';
                \file_put_contents($translationFilePath, $emptyTranslationFileTemplate);
                \clearstatcache();
            }

        } catch (\Exception $e) {
            // \TYPO3\Flow\var_dump($e->getMessage());
            // exit;
        }

    }

    /**
     * This function is used to get all unique translation IDs from giving package translation package.
     * @return mixed
     */
    public function getUniqueTranslationIdsFromTranslationFile()
    {
        $uniqueTranslationIds = array();
        try {

            if (empty($this->currentlyAvailableTranslationFiles) == false) {
                foreach ($this->currentlyAvailableTranslationFiles as $currentlyAvailableTranslationFile) {
                    $this->getTranslationIdsFromTranslationFile($currentlyAvailableTranslationFile, $uniqueTranslationIds);
                }
            }
            $uniqueTranslationIds = array_unique($uniqueTranslationIds);
        } catch (\Exception $e) {
            // \TYPO3\Flow\var_dump($e->getMessage());
            // exit;
        }
        return $uniqueTranslationIds;
    }

    /**
     * This function is used to get all unique translation IDs from a single Translation file.
     * @param string $translationFile
     * @return mixed
     */
    public function getUniqueTranslationIdsFromSingleTranslationFile(
        $translationFile = ""
    ) {
        $uniqueTranslationIds = array();
        try {

            if ((empty($translationFile) == false) && (is_file($translationFile) == true) && (file_exists($translationFile) == true)) {
                $this->getTranslationIdsFromTranslationFile($translationFile, $uniqueTranslationIds);
            }

        } catch (\Exception $e) {
            unset($uniqueTranslationIds);
        }
        $uniqueTranslationIds = array_unique($uniqueTranslationIds);
        return $uniqueTranslationIds;
    }

    /**
     * This function is used to get all translation IDs from giving translation file.
     * @param string $translationFile
     * @return mixed
     */
    public function getTranslationIdsFromTranslationFile(
        $translationFile = "",
        &$uniqueTranslationIds = null
    ) {
        try {
            if ((file_exists($translationFile) == true) && (is_file($translationFile) == true)) {
                $translationFileSize = filesize($translationFile);
                if ($translationFileSize > 0) {
                    $translationXMLPointer = new \DOMDocument("1.0");
                    // let's have a nice output
                    $translationXMLPointer->preserveWhiteSpace = false;
                    $translationXMLPointer->formatOutput       = true;
                    $translationXMLPointer->encoding= "UTF-8";
                    $translationXMLPointer->resolveExternals= TRUE;

                    $translationXMLPointer->load($translationFile, LIBXML_NOENT);

                    $results = $translationXMLPointer->documentElement->getElementsByTagName("trans-unit");
                    if (empty($results) == false) {
                        foreach ($results as $result) {
                            if ($result->hasAttribute("id") == true) {
                                $uniqueTranslationIds[] = $result->getAttribute("id");
                            }
                        }
                    }
                    $translationXMLPointer->save($translationFile);
                    $translationXMLPointer = null;
                }
            }

        } catch (\Exception $e) {
            // \TYPO3\Flow\var_dump($e->getMessage());
            // exit;
        }

    }

    /**
     * This function is used for extracting fileName (removing extension part) Eg: Main.xlf to Main
     * @return string
     */
    public function getPrefixFileName()
    {
        $translationFileName = $this->translationManagementSession->getTranslationFile();
        $prefixFileName      = "";
        if (empty($translationFileName) == false) {
            $prefixFileNameParts = explode(".xlf", trim($translationFileName));
            if (empty($prefixFileNameParts) == false) {
                $prefixFileName = array_shift($prefixFileNameParts);
            }
        }
        return $prefixFileName;
    }

    /**
     * This function is used for performing ADD, REMOVE operations in the translation file.
     * @param string $translationFile
     * @param string $translationId
     * @param string $translationLabel
     * @param integer $translationCDATAContentChecker
     * @return mixed
     */
    public function performCURDOpertionsOnTranslationFiles(
        $translationFile = "",
        $translationId = "",
        $translationLabel = "",
        $translationCDATAContentChecker = 0
    ) {
        try {
            $output = array(
                "status"  => "success",
                "message" => $this->getDataSavedSuccessfullyMsg("en"),
            );

            if ((empty($translationFile) == false) && (is_file($translationFile) == true) && (file_exists($translationFile) == true)) {

                $translationIdInstance = $this->checkGivenTranslationIdExists($translationFile, $translationId);
                if (empty($translationIdInstance) == true) {
                    $addNewTranslationUnitResult = $this->addNewTranslationUnitToCurrentTranslationFile($translationFile, $translationId, $translationLabel, $translationCDATAContentChecker);
                    if ($addNewTranslationUnitResult == false) {
                        $output = array(
                            "status"  => "error",
                            "message" => "Cannot add a new translation instance",
                        );
                    }
                } else {
                    $removeTranslationUnitResult = $this->removeTranslationUnitFromCurrentTranslationFile($translationFile, $translationId);
                    if ($removeTranslationUnitResult == false) {
                        $output = array(
                            "status"  => "error",
                            "message" => "Cannot remove selected translation unit",
                        );
                    } else {
                        $addNewTranslationUnitResult = $this->addNewTranslationUnitToCurrentTranslationFile($translationFile, $translationId, $translationLabel, $translationCDATAContentChecker);
                        if ($addNewTranslationUnitResult == false) {
                            $output = array(
                                "status"  => "error",
                                "message" => "Cannot add a new translation instance",
                            );
                        }
                    }
                }

            }

        } catch (\Exception $e) {
            $output = array(
                "status"  => "error",
                "message" => $e->getMessage(),
            );
        }
        return $output;
    }

    /**
     * This function is used for checking whether the given translation ID is exist or not.
     * @param string $translationFile
     * @param string $translationId
     * @return mixed
     */
    public function checkGivenTranslationIdExists(
        $translationFile = "",
        $translationId = ""
    ) {
        $bodyTagElement          = null;
        $translationUnitIdRecord = null;
        try {
            $domXmlPointer = new \DOMDocument("1.0");
            // let's have a nice output
            $domXmlPointer->preserveWhiteSpace = false;
            $domXmlPointer->formatOutput       = true;
            $domXmlPointer->encoding= "UTF-8";
            $domXmlPointer->resolveExternals= TRUE;

            $domXmlPointer->load($translationFile, LIBXML_NOENT);

            if ((empty($domXmlPointer) == false) && (empty($translationId) == false)) {
                $bodyTagElements = $domXmlPointer->getElementsByTagName("body");
                if (empty($bodyTagElements) == false) {
                    $bodyTagElement = $bodyTagElements->item(0);
                }
            }
            if (empty($bodyTagElement) == false) {
                $transUnitElements = $bodyTagElement->getElementsByTagName("trans-unit");
                if (empty($transUnitElements) == false) {
                    foreach ($transUnitElements as $transUnitElement) {
                        $transUnitElement->setIdAttribute("id", true);
                    }
                    // Get the id value
                    $translationUnitIdRecord = $domXmlPointer->getElementById($translationId);
                    if (empty($translationUnitIdRecord) == true) {
                        $translationUnitIdRecord = null;
                    }
                }
                $domXmlPointer->save($translationFile);
            }
            $domXmlPointer = null;
        } catch (\Exception $e) {

        }
        return $translationUnitIdRecord;
    }

    /**
     * This function is used for adding a new translation unit to the current translation file.
     * @param string $translationFile
     * @param string $translationId
     * @param string $translationLabel
     * @param integer $translationCDATAContentChecker
     * @return mixed
     */
    public function addNewTranslationUnitToCurrentTranslationFile(
        $translationFile = "",
        $translationId = "",
        $translationLabel = "",
        $translationCDATAContentChecker = 0
    ) {
        $output         = true;
        $bodyTagElement = null;
        try {
            $domXmlPointer = new \DOMDocument("1.0");
            // let's have a nice output
            $domXmlPointer->preserveWhiteSpace = false;
            $domXmlPointer->formatOutput       = true;
            $domXmlPointer->encoding= "UTF-8";
            $domXmlPointer->resolveExternals= TRUE;

            $domXmlPointer->load($translationFile, LIBXML_NOENT);

            if ((empty($domXmlPointer) == false) && (empty($translationId) == false)) {
                $bodyTagElements = $domXmlPointer->getElementsByTagName("body");
                if (empty($bodyTagElements) == false) {
                    $bodyTagElement = $bodyTagElements->item(0);
                }
            }
            if (empty($bodyTagElement) == false) {
                $newTransUnitElement = $domXmlPointer->createElement("trans-unit");
                $newTransUnitElement->setAttribute("id", trim($translationId));
                $newTransUnitElement->setAttribute("xml:space", "preserve");

                //$translationLabel = htmlentities($translationLabel,ENT_QUOTES,"UTF-8");

                // Check whether the given translation label is CDATA or not
                if ($translationCDATAContentChecker == 1) {
                    $newSourceTagForUnitElement      = $domXmlPointer->createElement("target");
                    $newSourceTagForUnitCDATASection = $domXmlPointer->createCDATASection(trim($translationLabel));
                    $newSourceTagForUnitElement->appendChild($newSourceTagForUnitCDATASection);
                } else {
                    $newSourceTagForUnitElement = $domXmlPointer->createElement("target", trim($translationLabel));
                }
                $newTransUnitElement->appendChild($newSourceTagForUnitElement);

                $bodyTagElement->appendChild($newTransUnitElement);
                $domXmlPointer->save($translationFile);
            } else {
                $output = false;
            }
            $domXmlPointer = null;
        } catch (\Exception $e) {
          $output = false;
        }
        return $output;
    }

    /**
     * This function is used for removing a selected translation unit from the current translation file.
     * @param string $translationFile
     * @param string $translationId
     * @return mixed
     */
    public function removeTranslationUnitFromCurrentTranslationFile(
        $translationFile = "",
        $translationId = ""
    ) {
        $output         = true;
        $bodyTagElement = null;
        try {
            $domXmlPointer = new \DOMDocument("1.0");
            // let's have a nice output
            $domXmlPointer->preserveWhiteSpace = false;
            $domXmlPointer->formatOutput       = true;
            $domXmlPointer->encoding= "UTF-8";
            $domXmlPointer->resolveExternals= TRUE;

            $domXmlPointer->load($translationFile, LIBXML_NOENT);

            if ((empty($domXmlPointer) == false) && (empty($translationId) == false)) {
                $bodyTagElements = $domXmlPointer->getElementsByTagName("body");
                if (empty($bodyTagElements) == false) {
                    $bodyTagElement = $bodyTagElements->item(0);
                }
            }

            if (empty($bodyTagElement) == false) {
                $transUnitElements = $domXmlPointer->getElementsByTagName("trans-unit");
                if (empty($transUnitElements) == false) {
                    foreach ($transUnitElements as $transUnitElement) {
                        $transUnitElement->setIdAttribute("id", true);
                    }

                    // Get the id value
                    $translationUnitIdRecord = $domXmlPointer->getElementById($translationId);
                    if (empty($translationUnitIdRecord) == false) {
                        $oldChild = $bodyTagElement->removeChild($translationUnitIdRecord);
                        if (empty($oldChild) == true) {
                            $output = false;
                        }
                    }
                }
                $domXmlPointer->save($translationFile);
            }
            $domXmlPointer = null;
        } catch (\Exception $e) {
            $output = false;
        }
        return $output;
    }

    /**
     * This function is used for retrieving the translation node type for giving translation ID.
     * @param string $translationFileFullPath
     * @param string $translationlabelId
     * @return string
     */
    public function getTranlationNodeTypeFromCurrentTranslationId(
        $translationFileFullPath = "",
        $translationlabelId = ""
    ) {
        $output                  = 0;
        $bodyTagElement          = null;
        $translationUnitIdRecord = null;
        try {

            $domXmlPointer = new \DOMDocument("1.0");
            // let's have a nice output
            $domXmlPointer->preserveWhiteSpace = false;
            $domXmlPointer->formatOutput       = true;
            $domXmlPointer->encoding= "UTF-8";
            $domXmlPointer->resolveExternals= TRUE;

            $domXmlPointer->load($translationFileFullPath, LIBXML_NOENT);

            if ((empty($domXmlPointer) == false) && (empty($translationlabelId) == false)) {
                $bodyTagElements = $domXmlPointer->getElementsByTagName("body");
                if (empty($bodyTagElements) == false) {
                    $bodyTagElement = $bodyTagElements->item(0);
                }
            }
            if (empty($bodyTagElement) == false) {
                $transUnitElements = $bodyTagElement->getElementsByTagName("trans-unit");
                if (empty($transUnitElements) == false) {
                    foreach ($transUnitElements as $transUnitElement) {
                        $transUnitElement->setIdAttribute("id", true);
                    }
                    // Get the id value
                    $translationUnitIdRecord = $domXmlPointer->getElementById($translationlabelId);
                    if (empty($translationUnitIdRecord) == false) {
                        $translationIdSources = $translationUnitIdRecord->getElementsByTagName("target");
                        if (empty($translationIdSources) == false) {
                            $translationIdSourcesTargetTag = $translationIdSources->item(0);
                            if (empty($translationIdSourcesTargetTag) == false) {
                                $translationIdSourcesTargetTagHasChild = $translationIdSourcesTargetTag->hasChildNodes();
                                if ($translationIdSourcesTargetTagHasChild == true) {
                                    $translationIdSourcesTargetTagChild = $translationIdSourcesTargetTag->firstChild;
                                    if (empty($translationIdSourcesTargetTagChild) == false) {
                                        $output = $translationIdSourcesTargetTagChild->nodeType;
                                    }
                                }
                            }
                        }
                    }
                }
                $domXmlPointer->save($translationFileFullPath);
            }
            $domXmlPointer = null;

        } catch (Exception $e) {
            $output = 0;
        }
        return $output;
    }

    /**
    * @param string $translationLabel
    * @param integer $translationCDATAContentChecker
    * @param string $translationUnitLanguagekey
    * @return mixed
    */
    public function performCommonTranslationLabelValidation(
    $translationLabel = "",
    $translationCDATAContentChecker = 0,
    $translationUnitLanguagekey = ""
    ) {
      $flag     = 0;
      $errorMsg = array();

      if( $translationCDATAContentChecker == 0 ) {
        if( preg_match("/[a-zA-Z0-9\s\.]*/i", $translationLabel,$translationLabelRegExpCheckerWithoutCDATAMatches) != 1) {
          $errorMsg[] = "Invalid language (".trim($translationUnitLanguagekey).") translation Label.Only allow english letters, numbers,dot,and whitespace.";
          $flag = 1;
        }
        else {
          if( ( ctype_alnum($translationLabel) == false ) && ( empty($translationLabel) == false ) ) {
            $errorMsg[] ="Invalid language (".trim($translationUnitLanguagekey).")  translation Label.Only allow english letters, numbers,dot,and whitespace.";
            $flag = 1;
          }
        }
      }
      if( $flag == 1) {
        return implode(",",$errorMsg);
      }
      else {
        return "";
      }

    }

}
