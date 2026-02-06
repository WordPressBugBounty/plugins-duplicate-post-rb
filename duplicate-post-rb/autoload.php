<?php
/* 
*      RB Duplicate Post     
*      Version: 1.5.8
*      By RbPlugin
*
*      Contact: https://robosoft.co 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Autoloader function to dynamically include class files based on namespaces.
 *
 * @param string $class The fully qualified class name (including namespace).
 */
function autoloadRBDuplicatePostClasses($class) {

    // Check if the class belongs to the Robosoft namespace
  if (strpos($class, 'rbDuplicatePost\\') !== 0) {
      // If not, skip processing this class
      return;
  }

  // Replace backslashes (\) with directory separators (/ or \ depending on the OS)
  $classPath = str_replace('\\', '/', $class);

  // Remove the "rbsDuplicatePost\src" prefix from the path if it exists
  $classPath = preg_replace('/^rbDuplicatePost\//', '', $classPath);

  // Build the full file path using the new constant ROBO_GALLERY_BASE_DIR
  $filePath = RB_DUPLICATE_POST_PATH  . '/src/' . $classPath . '.php';

  // Check if the file exists and include it
  if (file_exists($filePath)) {
      require_once $filePath;
  } else {
      throw new Exception("rbDuplicatePost :: Class file not found: " . $filePath);
  }
}

// Register the autoloader function
spl_autoload_register('autoloadRBDuplicatePostClasses');