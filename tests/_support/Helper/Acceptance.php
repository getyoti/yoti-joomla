<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    /**
     * Find elements by selector.
     *
     * @param string $selector
     */
    public function findElements($selector)
    {
        $web_driver = $this->getModule('JoomlaBrowser');
        return $web_driver->_findElements($selector);
    }

    /**
     * Get the configured Joomla folder.
     *
     * @return string
     */
    public function getJoomlaFolder()
    {
        return rtrim($this->getModule('JoomlaBrowser')->_getConfig('joomla folder'), '/') . '/';
    }

    /**
     * Bootstrap Joomla.
     */
    public function bootstrapJoomla()
    {
        ob_start();
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
        require_once $this->getJoomlaFolder() . 'index.php';
        ob_end_clean();
    }
}
