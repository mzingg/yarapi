<?php
class DoctrineModule extends Module {

	private $oEntityManager;

	public function __construct(Module & $oModule) {
		if (is_null($oModule))
			throw new Exception('Module passed to the DoctrineModule cannot be null');

		// Call the parent constructor to initialize module correctly
		parent::__construct($oModule->getName(), $oModule->getPath());

		if (!$this->isDoctrineEnabled()) return;

		// TODO: Implement static EntityManager cache
		$oInstallationState = InstallationState::getInstance();
		$oConfig = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(array($this->getModelsPath()), $oInstallationState->getEnvironment() == 'dev');
		$this->oEntityManager = \Doctrine\ORM\EntityManager::create($this->getDatabaseConfiguration(), $oConfig);
	}

	public static function dropDatabases() {
		Doctrine::dropDatabases();
		return true;
	}

	public static function createDatabases() {
		Doctrine::createDatabases();
		return true;
	}

	function createTablesFromModels() {
		$oSchemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->oEntityManager);
		$oMetaData = $this->oEntityManager->getMetadataFactory()->getAllMetadata();

		$oSchemaTool->createSchema($oMetaData);
		return true;
	}

	function readFixtures() {
		if (!$this->isDoctrineEnabled())
			throw new Exception('This module [%s] is not enabled for doctrine model generation (schema.yml file is missing).');

		Doctrine_Core::loadData($this->getFixturesPath());
	}

	function getDatabaseConfiguration() {
		return array(
				'driver'   => 'pdo_mysql',
				'user'     => 'root',
				'password' => '',
				'host'		 => 'localhost',
				'dbname'   => 'wow_ah_analyzer',
				'charset'  => 'UTF-8'
		);
	}

	public function getEntityManager() {
		return $this->oEntityManager;
	}

	public function getModelsPath() {
		return $this->getPath() . '/classes';
	}

	public function getGeneratedModelsPath() {
		return $this->getModelsPath() . '/generated';
	}

	public function getFixturesPath() {
		return $this->getPath() . '/fixtures';
	}

	public function schemaExists() {
		return is_dir($this->getModelsPath()) && is_dir($this->getGeneratedModelsPath());
	}

	public function isDoctrineEnabled() {
		return is_dir($this->getModelsPath());
	}



}