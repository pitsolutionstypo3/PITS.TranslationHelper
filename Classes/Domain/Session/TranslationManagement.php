<?php

namespace PITS\TranslationHelper\Domain\Session;

// This file is part of the Pits.TranslationHelper package.

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("session")
 */
class TranslationManagement
{
    /**
     * This session variable is used for storing Translation Package Key
     *
     * @var string
     */
    protected $packageKey = "";

    /**
     * This session variable is used for storing translationFile
     *
     * @var string
     */
    protected $file = "";

    /**
     * Gets the value of $translationPackageKey
     *
     * @Flow\Session(autoStart = TRUE)
     *
     * @return string
     */
    public function getPackageKey()
    {
        return $this->packageKey;
    }

    /**
     * Sets the value of $packageKey
     *
     * @Flow\Session(autoStart = TRUE)
     *
     * @param string $packageKey
     *
     * @return void
     */
    public function setpackageKey($packageKey = "")
    {
        $this->packageKey = $packageKey;
    }

    /**
     * Gets the value of $file
     *
     * @Flow\Session(autoStart = TRUE)
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sets the value of $file
     *
     * @Flow\Session(autoStart = TRUE)
     *
     * @param string $file
     *
     * @return void
     */
    public function setFile($file = "")
    {
        $this->file = $file;
    }
}
