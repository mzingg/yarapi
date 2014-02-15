<?php
class InstallationState extends PersistentState {
   
   // Version of the yarapi core (do not change)
   private static $YARAPI_VERSION = '1.0';
   
   // Fields set through input paramters
   private $sAbsoluteInstallationDir;

   private $sSoftwareVersion;

   private $sEnvironment;
   
   // Fields set through initialisiation or setters
   private $aIncludePath;

   private $sInstalledVersion;

   /**
    *
    * @param string $absoluteInstallationDir           
    * @param string $environment           
    * @return InstallationState
    */
   public static function create($absoluteInstallationDir, $environment = 'dev') {
      $oNewInstallationState = new InstallationState($absoluteInstallationDir, $environment);
      return self::getInstance($oNewInstallationState);
   }

   /**
    *
    * @param InstallationState $oInstallationState           
    * @return InstallationState
    */
   public static function getInstance(InstallationState & $oInstallationState = null) {
      static $oRequestCachedObject = null;
      
      if (! is_null($oInstallationState)) {
         $oRequestCachedObject = $oInstallationState;
      }
      
      if (is_null($oRequestCachedObject)) {
         throw new Exception('Could not retrieve InstallationState - please call InstallationState::create() first.');
      }
      
      return $oRequestCachedObject;
   }

   /**
    * Constructor provides the following post conditions:
    * <ul>
    * <li>Checks if the passed parameters are valid - throws an Exception otherwise.
    * <li>Makes sure all required directories for operating YARAPI are existent and accessible.
    * <li>All persisted and default include directories are added to the include_path setting.
    * </ul>
    *
    * @param string $absoluteInstallationDir
    *           Absolute path to the YARAPI installation
    * @param string $softwareVersion
    *           YARAPI version string
    * @param string $environment
    *           Current environment YARAPI operates in (dev, test, prod, ...)
    * @return void
    */
   private function InstallationState($absoluteInstallationDir, $environment = 'dev') {
      // First check input parameters - these are absolutely required to be valid so we
      // throw an exception in the case they are not.
      if (! $absoluteInstallationDir || ! $environment)
         throw new Exception(sprintf('InstallationState must be initialized with valid parameters (%s, %s).', $absoluteInstallationDir, $environment));
         
         // if the installation directory path ends with a slash remove it
      $absoluteInstallationDir = $absoluteInstallationDir == '/' ? $absoluteInstallationDir : rtrim($absoluteInstallationDir, '/');
      
      // Now check if we have a valid installation directory
      if (! file_exists($absoluteInstallationDir) || ! is_dir($absoluteInstallationDir) || ! is_readable($absoluteInstallationDir))
         throw new Exception(sprintf('Installation directory %s does not exist or is not readable. This is required to operate YARAPI.'));
         
         // Set the fields of the object
      $this->sAbsoluteInstallationDir = $absoluteInstallationDir;
      $this->sEnvironment = $environment;
      
      // Make sure /var directory structure exists and is writeable (where needeed)
      $this->checkDirectory($this->getVarDirectoryPath(), true);
      $this->checkDirectory($this->getLogDirectoryPath(), true);
      $this->checkDirectory($this->getInstallationRoot() . '/core/modules');
      
      // Make sure site directory structure exists
      $this->checkDirectory($this->getSitesDirectoryPath());
      $this->checkDirectory($this->getSitesAllDirectoryPath());
      $this->checkDirectory($this->getSitesAllDirectoryPath() . '/modules');
      $this->checkDirectory($this->getSitesLibDirectoryPath());
      
      // Setup other variables (possibly from state file in /var)
      $this->initialize();
   }

   /**
    * Checks mandatory directory and tries to create it if it does not exist.
    *
    * @param string $directoryPath
    *           Absolute path to the directory to check
    * @param boolean $writable
    *           If true the function also checks if that directory is writeable. It checks only for readability otherwise.
    * @return void
    */
   private function checkDirectory($directoryPath, $writable = false) {
      // First check for valid parameters
      if (! $directoryPath)
         throw Exception('A valid directory path must be provided.');
         
         // First we try to create a directory that does not exist
      if (! file_exists($directoryPath)) {
         // Create a readable directory recursivly
         mkdir($directoryPath, $writable ? 0644 : 0444, true);
      }
      
      // Now check (again) for readability of the directory
      if (! file_exists($directoryPath) || ! is_dir($directoryPath) || ! is_readable($directoryPath))
         throw new Exception(sprintf('Mandatory directory %s does not exist or is not readable.', $directoryPath));
         
         // If paramter $writable was given, check also wether we can write in that directory
      if ($writable && ! is_writeable($directoryPath))
         throw new Exception(sprintf('Mandatory directory %s needs to be writeable.', $directoryPath));
      
      // All is well
   }

   /**
    * Initializes data like include paths or loads it from the /var
    * directory if available.
    *
    * @return void
    */
   private function initialize() {
      // Default values
      $this->sInstalledVersion = self::$YARAPI_VERSION;
      $this->aIncludePath = array();
      
      // Configure most important include paths
      $aDefaultIncludePaths = array (
               $this->getInstallationRoot () . '/core/includes', // core include files
               $this->getInstallationRoot () . '/core/lib', // core libraries
               $this->getSitesLibDirectoryPath ()  // site libraries all
    )// site libraries all
      
      
      null;
      
      $aData = $this->load();
      if ($aData) {
         if ($aData['installedVersion'])
            $this->sInstalledVersion = $aData['installedVersion'];
         if ($aData['includePath'])
            $aDefaultIncludePaths = array_merge($aDefaultIncludePaths, $aData['includePath']);
      }
      
      // Register all the collected include paths
    foreach// Register all the collected include paths
      null ( $aDefaultIncludePaths as $sIncludePath ) 
      
      {
         $this->registerIncludePath($sIncludePath, true);
      }
   }

   /**
    * Adds a specified absolute or relative (to the YARAPI root) path to the global
    * PHP include path.
    *
    * The path is stored in a local list and persisted to the /var directory by default.
    * Use the $oneTime flag to disable persistance for the specified path (only in include path
    * for this request)
    *
    * WARNING: Use with care as it affects require_once() performance.
    *
    * @param string $includePath           
    * @param boolean $absolute           
    * @return void
    */
   public function registerIncludePath($includePath, $absolute = false, $oneTime = false) {
      $sPath = $absolute ? $includePath : $this->sAbsoluteInstallationDir . $includePath;
      
      // Get the canonical representation of the path (resolves ./, links, etc.)
    $sPath// Get the canonical representation of the path (resolves ./, links, etc.)
      null = 
      
      realpath($sPath);
      
      // Just when the path does not exist
    if// Just when the path does not exist
      null (! file_exists ( $sPath ) || ! is_dir ( $sPath ) || ! is_readable ( $sPath ))
         
      
      return;
         
         // Do nothing if the path was already registerd
    if// Do nothing if the path was already registerd
      null (in_array ( $sPath, $this->aIncludePath ))
         
      
      return;
         
         // Add it to the local list for bookkeeping if the path should be persisted
    if// Add it to the local list for bookkeeping if the path should be persisted
      null (! $oneTime)
         
      
      $this->aIncludePath[] = $sPath;
         
         // Add the path to the PHP include path setting
    // Add the path to the PHP include path setting
      nullset_include_path ( get_include_path () . PATH_SEPARATOR .       // original include path; ., pear, ...
      $sPath// original include path; ., pear, ... // and the newly added entry
    )// and the newly added entry
      
      
      null;
   }

   protected function getStateIdentifier() {
      return 'installationState';
   }

   protected function getStateData() {
      $aData = array('installedVersion' => $this->sInstalledVersion, 'includePath' => $this->aIncludePath);
      
      return $aData;
   }

   protected function getStateStorageDirectory() {
      return $this->getVarDirectoryPath();
   }

   /**
    * Returns the absolute path of this YARAPI installation.
    *
    * @return string
    */
   public function getInstallationRoot() {
      return $this->sAbsoluteInstallationDir;
   }

   /**
    * Returns the environment this YARAPI installation runs in.
    *
    * @return string
    */
   public function getEnvironment() {
      return $this->sEnvironment;
   }

   /**
    * Returns the version string for the installed core.
    *
    * @return string
    */
   public function getCoreVersion() {
      return self::$YARAPI_VERSION;
   }

   /**
    * Returns the absolute path to the /var directory of this YARAPI installation.
    *
    * @return string
    */
   public function getVarDirectoryPath() {
      return $this->getInstallationRoot() . '/var';
   }

   /**
    * Returns the absolute path to the /var directory of this YARAPI installation.
    *
    * @return string
    */
   public function getLogDirectoryPath() {
      return $this->getVarDirectoryPath() . '/logs';
   }

   /**
    * Returns the absolute path to the /sites directory of this YARAPI installation.
    *
    * @return string
    */
   public function getSitesDirectoryPath() {
      return $this->getInstallationRoot() . '/sites';
   }

   /**
    * Returns the absolute path to the /sites/all directory of this YARAPI installation.
    *
    * @return string
    */
   public function getSitesAllDirectoryPath() {
      return $this->getSitesDirectoryPath() . '/all';
   }

   /**
    * Returns the absolute path to the /sites/all directory of this YARAPI installation.
    *
    * @return string
    */
   public function getSitesLibDirectoryPath() {
      return $this->getSitesAllDirectoryPath() . '/lib';
   }

}