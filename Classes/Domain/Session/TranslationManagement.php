<?php
namespace PITS\TranslationHelper\Domain\Session;

/*
 * This file is part of the Pits.TranslationHelper package.
 */

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("session")
 */
class TranslationManagement
{
    /**
     * This session variable is used for storing translationPackageKey
     * @var string
     */
    protected $translationPackageKey = "";

    /**
     * This session variable is used for storing translationFile
     * @var string
     */
    protected $translationFile = "";

    /**
     * Gets the value of $translationPackageKey
     * @Flow\Session(autoStart = TRUE)
     * @return string
     */
    public function getTranslationPackageKey()
    {
        return $this->translationPackageKey;
    }

    /**
     * Sets the value of $translationPackageKey
     * @Flow\Session(autoStart = TRUE)
     * @param string $translationPackageKey
     * @return void
     */
    public function setTranslationPackageKey(
        $translationPackageKey = ""
    ) {
        $this->translationPackageKey = $translationPackageKey;
    }

    /**
     * Gets the value of $translationFile
     * @Flow\Session(autoStart = TRUE)
     * @return string
     */
    public function getTranslationFile()
    {
        return $this->translationFile;
    }

    /**
     * Sets the value of $translationFile
     * @Flow\Session(autoStart = TRUE)
     * @param string $translationFile
     * @return void
     */
    public function setTranslationFile(
        $translationFile = ""
    ) {
        $this->translationFile = $translationFile;
    }

}
