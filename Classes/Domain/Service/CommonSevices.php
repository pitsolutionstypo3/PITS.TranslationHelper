<?php

namespace PITS\TranslationHelper\Domain\Service;

// This script belongs to the TYPO3 Flow package "GVB.App".

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class CommonSevices
{
    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionService;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Package\PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $translator;

    /**
     * @Flow\Inject
     * @var \PITS\TranslationHelper\Domain\Session\TranslationManagement
     */
    protected $session;

    /**
     * This variable is used for getTranslatedFilesFromDirectory function
     * @var string
     */
    protected $parentFolderName = "";

    /**
     * This variable is used to store a list of currently available translation file path.
     * @var array
     */
    protected $translationFiles = [];

    /**
     * It is used for getting the current using language in the website
     * @param null|mixed $controllerContext
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
     *
     * @return mixed
     */
    public function getLanguages()
    {
        $configurations = $this->contentDimensionService->getAllPresets();
        if (isset($configurations["language"]["presets"])
            && !empty($configurations["language"]["presets"])
            && is_array($configurations["language"]["presets"])) {
            return array_keys($configurations["language"]["presets"]);
        }

        return [];
    }

    /**
     * This function is used for getting the list of active flow packages
     *
     * @return mixed
     */
    public function getPackages()
    {
        $packages      = $this->packageManager->getActivePackages();
        if (!empty($packages) && is_array($packages)) {
            return array_filter(array_keys($packages), function ($Key) {
                $file = $this->getPackagePath($Key);

                return file_exists($file);
            });
        }

        return [];
    }

    /**
     * This function is used for getting the relative resource folder path of the given flow package
     *
     * @param string $key
     *
     * @return string
     */
    public function getPackagePath($key = "none")
    {
        if ($this->packageManager->isPackageActive($key) && !empty($this->packageManager->getPackage($key))) {
            return $this->packageManager->getPackage($key)->getResourcesPath() . "Private/Translations/";
        }

        return '';
    }

    /**
     * This function is used for getting the translated message for corresponding translation unit ID.
     *
     * @param string $translationId
     * @param string  $localeIdentifier
     *
     * @return string
     */
    public function getCorrectTranslationLabelFromTranslationUnitId(
        $translationId = "",
        $localeIdentifier = 'en'
    ) {
        $module           = $this->getPrefixFileName();
        $packageKey       = $this->session->getPackageKey();
        $locale           = new \Neos\Flow\I18n\Locale($localeIdentifier);
        $translationLabel = $this->translator->translateById($translationId, [], null, $locale, trim($module), trim($packageKey));

        return $translationLabel;
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
        $packageKey               = $this->session->getPackageKey();
        $translationsResourcePath = $this->getPackagePath($packageKey);
        $translatedLanguages      = $this->getLanguages();

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
            // \Neos\Flow\var_dump($e->getMessage());
            // exit;
            $availableLanguages = array();
        }

        return $availableLanguages;
    }

    /**
     * This function is used for checking whether the translation files exist or not. If the translation file is not exist, then the corresponding translation file is created.
     * @return void
     */
    public function checkTranslationFilesExists()
    {
        $packageKey                               = $this->session->getPackageKey();
        $translationsResourcePath                 = $this->getPackagePath($packageKey);
        //$translatedLanguages                    = $this->getLanguages();
        $translatedLanguages                      = array_merge_recursive($this->getLanguages(), $this->getAllAvailableTranslationLanguagesFromTranslationPackage($translationsResourcePath));
        $translatedLanguages                      = array_unique($translatedLanguages);
        $translationFile                          = $this->session->getFile();
        $availableTranslationFile                 = "";
        $this->translationFiles                   = [];
        
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
                    } elseif (filesize($translationFilePath) <= 0) {
                        $this->createEmptyTranslationFile($translationFilePath, $translatedLanguage);
                    }
                    $this->translationFiles[] = trim($translationFilePath);
                }
            }
            \clearstatcache();
        } catch (\Exception $e) {
            // \Neos\Flow\var_dump($e->getMessage());
            // exit;
        }
    }

    /**
     * This function is used to get all unique translation IDs from giving package translation package.
     * @return mixed
     */
    public function getUniqueTranslationIdsFromTranslationFile()
    {
        $translationIds = [];

        try {
            if (empty($this->translationFiles) == false) {
                foreach ($this->translationFiles as $translationFile) {
                    $this->getTranslationIdsFromTranslationFile($translationFile, $translationIds);
                }
            }
            $translationIds = array_unique($translationIds);
        } catch (\Exception $e) {
        }

        return $translationIds;
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
                    $translationXMLPointer->encoding           = "UTF-8";
                    $translationXMLPointer->resolveExternals   = true;

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
            // \Neos\Flow\var_dump($e->getMessage());
            // exit;
        }
    }

    /**
     * This function is used for extracting fileName (removing extension part) Eg: Main.xlf to Main
     * @return string
     */
    public function getPrefixFileName()
    {
        $translationFileName = $this->session->getFile();
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
     *
     * @param string $filePath Translation File
     * @param string $id Translation Id
     * @param string $label Translation Label
     * @param integer $cdataChecker Translation CDATA Content Checker
     * @param integer $encodingChecker Translation Unit Encoding Decision Checker
     *
     * @return mixed
     */
    public function performOpertions($filePath = "", $id = "", $label = "", $cdataChecker = 0, $encodingChecker = 0)
    {
        try {
            $output = array(
                "status"  => "success",
                "message" => $this->getTransaltionMessage('dataWasSavedSuccessfully'),
            );

            if ((empty($filePath) == false) && (is_file($filePath) == true) && (file_exists($filePath) == true)) {
                $translationIdInstance = $this->checkGivenTranslationIdExists($filePath, $id);
                if (empty($translationIdInstance) == true) {
                    $addNewTranslationUnitResult = $this->addNewTranslationUnitToCurrentTranslationFile($filePath, $id, $label, $cdataChecker, $encodingChecker);
                    if ($addNewTranslationUnitResult == false) {
                        $output = array(
                            "status"  => "error",
                            "message" => "Cannot add a new translation instance",
                        );
                    }
                } else {
                    $removeTranslationUnitResult = $this->removeTranslationUnit($filePath, $id);
                    if ($removeTranslationUnitResult == false) {
                        $output = array(
                            "status"  => "error",
                            "message" => "Cannot remove selected translation unit",
                        );
                    } else {
                        $addNewTranslationUnitResult = $this->addNewTranslationUnitToCurrentTranslationFile($filePath, $id, $label, $cdataChecker, $encodingChecker);
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
            $domXmlPointer->encoding           = "UTF-8";
            $domXmlPointer->resolveExternals   = true;

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
     * @param integer $translationUnitEncodingDecisionChecker
     * @return mixed
     */
    public function addNewTranslationUnitToCurrentTranslationFile(
        $translationFile = "",
        $translationId = "",
        $translationLabel = "",
        $translationCDATAContentChecker = 0,
        $translationUnitEncodingDecisionChecker = 0
    ) {
        $output         = true;
        $bodyTagElement = null;

        try {
            $domXmlPointer = new \DOMDocument("1.0");
            // let's have a nice output
            $domXmlPointer->preserveWhiteSpace = false;
            $domXmlPointer->formatOutput       = true;
            $domXmlPointer->encoding           = "UTF-8";
            $domXmlPointer->resolveExternals   = true;

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

                if ($translationUnitEncodingDecisionChecker == 1) {
                    $translationLabel = htmlentities($translationLabel, ENT_QUOTES, "UTF-8");
                }

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
     *
     * @param string $file
     * @param string $id
     *
     * @return mixed
     */
    public function removeTranslationUnit($file = "", $id = "")
    {
        $pointer  =  $this->getDOMXMLPointer($file);
        $bodyTags = $this->getBodyTagElement($pointer, $id);
        if (!empty($bodyTags)) {
            $this->setIdAttrTransUnit($file, $id);
            $record = $pointer->getElementById($id);
            if (empty($record) || empty($bodyTags->removeChild($record))) {
                return false;
            }
        }
        $pointer->save($file);
        $pointer = null;
     
        return true;
    }

    /**
     * This function is used for retrieving the translation node type for giving translation ID.
     *
     * @param string $file Translation full path
     * @param string $id Translation label Id
     *
     * @return string
     */
    public function getTranlationNodeType($file = "", $id = "")
    {
        $pointer =  $this->getDOMXMLPointer($file);
                
        if (!empty($this->setIdAttrTransUnit($file, $id))) {
            return $this->getNodeType($pointer, $id);
        }
        
        $pointer->save($file);
                           
        return 0;
    }

    /**
    * This function performs validation of translation label
    *
    * @param string $label Translation Label
    * @param integer $cdataChecker Translation CDATA Content Checker
    * @param string $language Translation Unit Language key
    * @param integer $encodingChecker Translation Unit Encoding Decision
    *
    * @return mixed
    */
    public function validateTranslationLabel($label = '', $language = '')
    {
        $file      = $this->getTranslationFileFullPath($language);
                         
        if (empty($this->getLanguages()) || !in_array(trim($language), $this->getLanguages())) {
            return $this->getTransaltionMessage('invalidLanguage');
        } elseif (!is_file($file) || !file_exists($file)) {
            return $this->getTransaltionMessage('transFileNotExist');
        } elseif (empty($label) || !preg_match("/[a-zA-Z0-9\s\.]*/i", $label)) {
            return $this->getTransaltionMessage('invalidLanguageLabel', ['language' => $language]);
        }
        
        return false;
    }
    
    /**
     * This function is used for getting the correct translation message
     *
     * @param string $id
     * @param array $arguments
     *
     * @return string
     */
    public function getTransaltionMessage($id = '', $arguments = [])
    {
        $locale     = new \Neos\Flow\I18n\Locale('en');
        
        return $this->translator->translateById($id, $arguments, null, $locale, "Main", "PITS.TranslationHelper");
    }
    
    /**
     * This function is used for getting the full path of translation file
     *
     * @param type $language
     *
     * @return string
     */
    public function getTranslationFileFullPath($language = '')
    {
        $packageKey    = $this->session->getPackageKey();
        $resourcePath  = $this->getPackagePath($packageKey);
        $file          = $this->session->getFile();
        
        return trim($resourcePath) . trim($language)."/" . trim($file);
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
            // \Neos\Flow\var_dump($e->getMessage());
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
            $packageKey = $this->session->getPackageKey();
            if ((file_exists($translationFilePath) == true) && (is_file($translationFilePath) == true)) {
                $emptyTranslationFileTemplate = '<?xml version="1.0"?>' . PHP_EOL . '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">' . PHP_EOL . ' <file original="" product-name="' . trim($packageKey) . '" source-language="' . trim($translatedLanguage) . '" datatype="plaintext">' . PHP_EOL . '  <body></body>' . PHP_EOL . ' </file>' . PHP_EOL . '</xliff>';
                \file_put_contents($translationFilePath, $emptyTranslationFileTemplate);
                \clearstatcache();
            }
        } catch (\Exception $e) {
        }
    }
    
    /**
     * This function is used for getting common DOMXMLpointer
     *
     * @param string $file
     *
     * @return mixed
     */
    private function getDOMXMLPointer($file = '')
    {
        $domXmlPointer                     = new \DOMDocument("1.0");
        $domXmlPointer->preserveWhiteSpace = false;
        $domXmlPointer->formatOutput       = true;
        $domXmlPointer->encoding           = "UTF-8";
        $domXmlPointer->resolveExternals   = true;
        $domXmlPointer->load($file, LIBXML_NOENT);
        
        return $domXmlPointer;
    }
    
    /**
     * This function is used for getting Body XLFF element
     *
     * @param object $domXmlPointer
     * @param string $id Translation label Id
     *
     * @return mixed
     */
    private function getBodyTagElement($domXmlPointer = null, $id = '')
    {
        if (!empty($domXmlPointer) && !empty($id)) {
            $bodyTagElements = $domXmlPointer->getElementsByTagName("body");
            if (!empty($bodyTagElements)) {
                return $bodyTagElements->item(0);
            }
        }
        
        return null;
    }
    
    /**
     * This function is used for setting id attribute for trans-unit elements
     *
     * @param string $id Translation label Id
     * @param mixed $file
     *
     * @return mixed
     */
    private function setIdAttrTransUnit($file = '', $id = '')
    {
        $pointer        =  $this->getDOMXMLPointer($file); //DOMXML pointer
        $bodyTagElement = $this->getBodyTagElement($pointer, $id);
        $elements       = null; // trans-unit xml elements
        
        if (!empty($bodyTagElement)) {
            $elements = $bodyTagElement->getElementsByTagName("trans-unit");
            foreach ($elements as $element) {
                $element->setIdAttribute("id", true);
            }
        }
        
        return $elements;
    }
    
    /**
     * This function is used for getting NodeType for a trans-unit element
     *
     * @param object $pointer
     * @param string $id Translation label Id
     *
     * @return mixed
     */
    private function getNodeType($pointer = null, $id = '')
    {
        $transUnitRecord = $pointer->getElementById($id);
        if (!empty($transUnitRecord)) {
            $transUnitSources = $transUnitRecord->getElementsByTagName("target");
            if (!empty($transUnitSources)) {
                $targetTag = $transUnitSources->item(0);
                if (!empty($targetTag) && $targetTag->hasChildNodes() && !empty($targetTag->firstChild)) {
                    return  $targetTag->firstChild->nodeType;
                }
            }
        }
        
        return 0;
    }
}
