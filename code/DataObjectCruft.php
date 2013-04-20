<?php
class DataObjectCruft extends DataExtension {
	public $manifest_fields = array();
	public $manifest_extensions = array();
	public $manifest_indexes = array();
	
	public $manifest_manyManyFields = array();
	public $manifest_manyManyIndexes = array();
	
	public $schema_fields = array();
	public $schema_extensions = array();
	public $schema_indexes = array();
	
	public $schema_manyManyFields = array();
	public $schema_manyManyIndexes = array();
	
	public function CruftFields() {
		$this->populateManifestFields();
		$this->populateSchemaFields();
		
		$cruft = array();
		
		$cruft["DataClass"] = $this->owner->class;
		$cruft["Fields"] = array_diff_key($this->schema_fields, $this->manifest_fields);
		$cruft["Indexes"] = array_diff_key($this->schema_indexes, $this->manifest_indexes);
		
		if(empty($cruft["Fields"]) && empty($cruft["Indexes"]))
			return null;
		
		return $cruft;
	}

	public function populateManifestFields() {
		$this->manifest_fields = $this->owner->database_fields($this->owner->class);
		$this->manifest_extensions = $this->owner->database_extensions($this->owner->class);
		$this->manifest_indexes = $this->owner->databaseIndexes();
		
		if($manyMany = $this->owner->uninherited('many_many', true)) {
			$extras = $this->owner->uninherited('many_many_extraFields', true);
			foreach($manyMany as $relationship => $childClass) {
				// Build field list
				$this->manifest_manyManyFields["{$this->class}_$relationship"] = array(
					"{$this->owner->class}ID" => "Int",
					(($this->owner->class == $childClass) ? "ChildID" : "{$childClass}ID") => "Int",
				);
				if(isset($extras[$relationship])) {
					$manymanyFields = array_merge($manymanyFields, $extras[$relationship]);
				}

				// Build index list
				$this->manifest_manyManyIndexes["{$this->class}_$relationship"] = array(
					"{$this->owner->class}ID" => true,
					(($this->owner->class == $childClass) ? "ChildID" : "{$childClass}ID") => true,
				);
			}
		}
		
		return $this;
	}
	
	public function populateSchemaFields() {
		$conn = DB::getConn();
		switch(true) {
			case $conn instanceof MySQLDatabase:
				if($this->hasOwnTable()) {
					$this->schema_fields = $this->getFields_MySQLDatabase($conn);
					$this->schema_indexes = $this->getIndexes_MySQLDatabase($conn);
				}
				$this->schema_extensions = $this->getExtensions_MySQLDatabase($conn);
				$this->schema_manyManyFields = $this->getManyManyFields_MySQLDatabase($conn);
				$this->schema_manyManyIndexes = $this->getManyManyIndexes_MySQLDatabase($conn);
				break;
		}
	}
	
	public function hasOwnTable() {
		return !empty($this->manifest_fields);
	}
	
	public function getFields_MySQLDatabase($conn) {
		$columns = array();
		$columnsResult = $conn->query("SHOW COLUMNS FROM `{$this->owner->class}`");
		
		if(!$columnsResult)
			return array();
		
		foreach($columnsResult as $column) {
			if($column["Field"] === "ID")
				continue;
			$fieldName = $column["Field"];
			unset($column["Field"]);
			$columns[$fieldName] = $column;
		}
		
		return $columns;
	}
	
	public function getExtensions_MySQLDatabase($conn) {
		// No need. No extensions to the database
	}
	
	public function getIndexes_MySQLDatabase($conn) {
		$indexes = array();
		$indexesResult = $conn->query("SHOW INDEX FROM `{$this->owner->class}`");
		
		if(!$indexesResult)
			return array();
			
		foreach($indexesResult as $index) {
			if($index["Key_name"] === "PRIMARY")
				continue;
			$keyName = $index["Key_name"];
			unset($index["Key_name"]);
			$indexes[$keyName] = $index;
		}
		
		return $indexes;
	}
	
	public function getManyManyFields_MySQLDatabase($conn) {
		
	}
	
	public function getManyManyIndexes_MySQLDatabase($conn) {

	}
}
