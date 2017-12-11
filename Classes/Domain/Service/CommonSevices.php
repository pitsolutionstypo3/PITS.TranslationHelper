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
     * This variable is used for getDirectoryFiles function
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
     *
     * @param null|mixed $context controller Context
     *
     * @return string
     */
    public function getLocale($context = null)
    {
        if (!empty($context) && !empty($context->getRequest()->getInternalArguments())) {
            $node = $context->getRequest()->getInternalArguments()['__node'];

            return $node->getContext()->getTargetDimensions()["language"];
        }

        return 'en';
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
                return file_exists($this->getPackagePath($Key));
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
     * This function is used for getting the list of available translated files in the given package
     *
     * @param string $folder Parent Folder Name
     *
     * @return array
     */
    public function getFiles($folder = "")
    {
        $files                    = [];
        $this->parentFolderName   = trim($folder);
        $packageKey               = $this->session->getPackageKey();
        $file                     = $this->getPackagePath($packageKey);
        
        foreach ($this->getPackageLanguages($file) as $language) {
            $directory = $file . trim($language) . "/";
            $this->getDirectoryFiles($directory, $files);
        }

        return array_unique($files);
    }

    /**
     * This function gets the list of languages from current translation package
     *
     * @param string $folder package Directory Folder Path
     *
     * @return mixed
     */
    public function getPackageLanguages($folder = "")
    {
        $languages = [];

        if (!empty($folder) && is_dir($folder) && file_exists($folder) && ($pointer = dir($folder))) {
            while (($file = $pointer->read()) !== false) {
                if (!preg_match("/^\.+$/i", trim($file)) && is_dir(trim($folder) . trim($file) . "/")) {
                    $languages[] = trim($file);
                }
            }
            $pointer->close();
            
            \clearstatcache();
        }
       
        return $languages;
    }

    /**
     * This function is used for checking whether the translation files exist or not.
     * If the translation file is not exist, then the corresponding translation file is created.
     *
     * @return void
     */
    public function checkFilesExists()
    {
        $packagePath                  = $this->getPackagePath($this->session->getPackageKey());
        $packageLangs                 = $this->getPackageLanguages($packagePath);
        $fullLangs                    = array_merge_recursive($this->getLanguages(), $packageLangs);
        $languages                    = array_unique($fullLangs);
        $this->translationFiles       = [];
        
        foreach ($languages as $language) {
            $this->translationFiles[] = $this->createFile($language);
        }
        
        \clearstatcache();
    }

    /**
     * This function is used to get all unique translation IDs from giving package translation package.
     *
     * @return mixed
     */
    public function getTranslationIds()
    {
        $translationIds = [];
        foreach ($this->translationFiles as $translationFile) {
            $this->getTranslationFileIds($translationFile, $translationIds);
        }
       
        return  array_unique($translationIds);
    }

    /**
     * This function is used to get all translation IDs from giving translation file.
     *
     * @param string $file Translation File
     * @param mixed $uniqueTranslationIds
     *
     * @return mixed
     */
    public function getTranslationFileIds($file = "", &$uniqueTranslationIds = null)
    {
        if (file_exists($file) && is_file($file) && filesize($file) > 0) {
            $pointer = $this->getDOMXMLPointer($file);

            $results = $pointer->documentElement->getElementsByTagName("trans-unit");
            foreach ($results as $result) {
                if ($result->hasAttribute("id")) {
                    $uniqueTranslationIds[] = $result->getAttribute("id");
                }
            }
            
            $pointer->save($file);
        }
    }

    /**
     * This function is used for extracting fileName (removing extension part) Eg: Main.xlf to Main
     *
     * @return string
     */
    public function getPrefixFileName()
    {
        if (!empty($this->session->getFile())) {
            $parts = explode(".xlf", trim($this->session->getFile()));
            if (!empty($parts)) {
                return array_shift($parts);
            }
        }

        return '';
    }

    /**
     * This function is used for performing ADD, REMOVE operations in the translation file.
     *
     * @param string $file Translation File
     * @param string $id Translation Id
     * @param string $label Translation Label
     * @param integer $cdataChecker Translation CDATA Content Checker
     * @param integer $encodingChecker Translation Unit Encoding Decision Checker
     *
     * @return mixed
     */
    public function performOpertions($file = "", $id = "", $label = "", $cdataChecker = 0, $encodingChecker = 0)
    {
        if (!empty($file) && is_file($file) && file_exists($file)) {
            if (!$this->isTranslationIdExists($file, $id)) {
                if (!$this->addTranslationUnit($file, $id, $label, $cdataChecker, $encodingChecker)) {
                    return ["status"  => "error","message" => $this->getTransaltionMessage('transUnitaddProblem')];
                }
            } else {
                if (!$this->removeTranslationUnit($file, $id)) {
                    return ["status"  => "error","message" => $this->getTransaltionMessage('transUnitRemovalProblem')];
                }
                if (!$this->addTranslationUnit($file, $id, $label, $cdataChecker, $encodingChecker)) {
                    return ["status"  => "error","message" => $this->getTransaltionMessage('transUnitaddProblem')];
                }
            }
        }
                
        return ['status' => 'success', 'message' => $this->getTransaltionMessage('dataWasSavedSuccessfully')];
    }

    /**
     * This function is used for checking whether the given translation ID is exist or not.
     *
     * @param string $file
     * @param string $id
     *
     * @return mixed
     */
    public function isTranslationIdExists($file = "", $id = "")
    {
        $pointer = $this->getDOMXMLPointer($file);
            
        if (!empty($this->setIdAttrTransUnit($pointer, $id))) {
            return !empty($pointer->getElementById($id));
        }
        
        return false;
    }

    /**
     * This function is used for adding a new translation unit to the current translation file.
     *
     * @param string  $file  Translation File
     * @param string  $id    Translation Id
     * @param string  $label Translation Label
     * @param integer $cdataChecker
     * @param integer $encodingChecker
     *
     * @return mixed
     */
    public function addTranslationUnit($file = "", $id = "", $label = "", $cdataChecker = 0, $encodingChecker = 0)
    {
        $pointer        = $this->getDOMXMLPointer($file);
        $bodyTagElement = $this->getBodyTagElement($pointer, $id);
            
        if (!empty($bodyTagElement)) {
            $transUnit = $this->createTranslationElement($id, $pointer, $label, $cdataChecker, $encodingChecker);
            $bodyTagElement->appendChild($transUnit);
            $pointer->save($file);
                
            return true;
        }
        
        return false;
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
            $this->setIdAttrTransUnit($pointer, $id);
            $record = $pointer->getElementById($id);
            if (empty($record) || empty($bodyTags->removeChild($record))) {
                return false;
            }
        }
        $pointer->save($file);
        
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
                
        if (!empty($this->setIdAttrTransUnit($pointer, $id))) {
            return $this->getNodeType($pointer, $id);
        }
        
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
     * This function is used for creating a new translation unit element
     *
     * @param string $id
     * @param DOMDocument $pointer
     * @param string $label
     * @param int $cdata
     * @param int $encoding
     *
     * @return mixed
     */
    private function createTranslationElement($id = '', $pointer = null, $label = '', $cdata = 0, $encoding = 0)
    {
        $transUnit           = $pointer->createElement("trans-unit");
        $transUnit->setAttribute("id", trim($id));
        $transUnit->setAttribute("xml:space", "preserve");

        if ($encoding) {
            $label = htmlentities($label, ENT_QUOTES, "UTF-8");
        }

        // Check whether the given translation label is CDATA or not
        if ($cdata) {
            $tagElement      = $pointer->createElement("target");
            $cdataElement    = $pointer->createCDATASection(trim($label));
            $tagElement->appendChild($cdataElement);
        } else {
            $tagElement = $pointer->createElement("target", trim($label));
        }
        $transUnit->appendChild($tagElement);
                
        return $transUnit;
    }
    
    /**
     * This function is used for getting subdirectory path for translation directory
     */
    private function getSubDirPath()
    {
        if (!empty($this->getPrefixFileName())) {
            $subdirs = explode("/", trim($this->getPrefixFileName()));
            if (!empty($subdirs)) {
                array_pop($subdirs);

                return implode("/", $subdirs);
            }
        }
            
        return '';
    }
    
    /**
     * This function is used for creating a translation file
     * If it not exist
     *
     * @param string $language
     *
     * @return string
     */
    private function createFile($language = '')
    {
        $packagePath   = $this->getPackagePath($this->session->getPackageKey());
        $directory     = trim($packagePath) . trim($language) . "/";
        $file          = trim($directory) . trim($this->session->getFile());
        $directory     = trim($directory) . trim($this->getSubDirPath());
        
        if (!file_exists($file) && filesize($file) <= 0) {
            if (file_exists(trim($directory)) == false) {
                \mkdir($directory, 0777, true);
            }
            if (file_exists(trim($file)) == false) {
                \touch($file);
            }
            if ((file_exists(trim($file)) == true) && (is_file(trim($file)) == true)) {
                $this->createEmptyFile($file, $language);
            }
        }
        
        return $file;
    }
    
    /**
     * This function gets the list of translated files from directory
     *
     * @param string $directory
     *
     * @return void
     */
    private function getDirectoryFiles($directory = "", &$files = null)
    {
        if (is_dir($directory) && file_exists($directory) && ($pointer = dir($directory))) {
            while (($directoryFile = $pointer->read()) !== false) {
                if (!preg_match("/^\.+$/i", trim($directoryFile))) {
                    $directoryFilePath = trim($directory) . trim($directoryFile) . "/";
                    if (is_dir($directoryFilePath)) {
                        $this->parentFolderName = trim($directoryFile) . "/";
                        $this->getDirectoryFiles($directoryFilePath, $files);
                        $this->parentFolderName = "";
                    } else {
                        $files[] = $this->parentFolderName . trim($directoryFile);
                    }
                }
            }
            $pointer->close();
        }
        \clearstatcache();
    }

    /**
     * This function is used for creating an empty translation file.
     *
     * @param string $file Translation File Path
     * @param string $language Language
     *
     * @return void
     */
    private function createEmptyFile($file = "", $language = "en")
    {
        if (file_exists($file) && is_file($file)) {
            $template = '<?xml version="1.0"?>' . PHP_EOL
                    . '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">' . PHP_EOL
                    . ' <file original="" product-name="' . trim($this->session->getPackageKey())
                    . '" source-language="'. trim($language) . '" datatype="plaintext">' . PHP_EOL
                    . '  <body></body>' . PHP_EOL . ' </file>' . PHP_EOL . '</xliff>';
            \file_put_contents($file, $template);
            \clearstatcache();
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
     * @param DOMDocument $pointer
     * @param string $id Translation label Id
     *
     * @return mixed
     */
    private function setIdAttrTransUnit($pointer = null, $id = '')
    {
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
