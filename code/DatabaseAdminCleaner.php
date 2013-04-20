<?php
class DatabaseAdminCleaner extends Extension {
	public static $allowed_actions = array(
		"scrub",
		"FormDeleteCruft"
	);
	
	public function scrub($request) {
		if(!Director::is_cli()) {
			$renderer = DebugView::create();
			$renderer->writeHeader();
			$renderer->writeInfo("Environment Builder", Director::absoluteBaseURL());
			echo "<div class=\"scrub\">";
		}
		
		$cruft = array();
		
		$tableList = DB::getConn()->tableList();
	
		$dataClasses = ClassInfo::subclassesFor('DataObject');
		array_shift($dataClasses);
		
		foreach($tableList as $table) {
			if(!in_array($table, $dataClasses))
				$cruftTables[$table] = array(
					"DataClass" => $table,
					"WholeTable" => true
				);
		}
		
		foreach($dataClasses as $dataClass) {
			if(class_exists($dataClass)) {
				$SNG = singleton($dataClass);
				if(!($SNG instanceof TestOnly)) {
					$classCruft = $SNG->Cruft();
					
					if(!empty($classCruft))
						$cruft[] = $classCruft;
					
					foreach($SNG->many_many() as $relationship => $childClass) {
						unset($cruftTables["{$dataClass}_{$relationship}"]);
					}
				}
				
				if($SNG->hasExtension("Versioned")) {
					unset($cruftTables["{$dataClass}_versions"]);
					unset($cruftTables["{$dataClass}_Live"]);
					unset($cruftTables["{$dataClass}_Stage"]);
				}
			}
		}
		
		$cruft = array_merge(array_values($cruftTables), $cruft);
		
		$form = $this->FormDeleteCruft();
		$form->setFormAction("/DatabaseAdmin/FormDeleteCruft");
		$fields = $form->Fields();
		foreach($cruft as $classCruft) {
			$group = new CompositeField(array(
				new HeaderField($classCruft["DataClass"])
			));
			if(!empty($classCruft["WholeTable"]) && $classCruft["WholeTable"]) {
				$group->push(new CompositeField(array(
					new CheckboxField("DeleteSpec[{$classCruft["DataClass"]}][WholeTable]", "Whole {$classCruft["DataClass"]} Table")
				)));
			}
			if(!empty($classCruft["Fields"])) {
				$fieldsGroup = new CompositeField(array(
					new HeaderField("DeleteSpec[{$classCruft["DataClass"]}][Fields]", "Fields", 3)
				));
				foreach($classCruft["Fields"] as $fieldName => $field) {
					$fieldsGroup->push(new CheckboxField("DeleteSpec[{$classCruft["DataClass"]}][Fields][{$fieldName}]", "{$fieldName} ({$field["Type"]})"));
				}
				$group->push($fieldsGroup);
			}
			
			if(!empty($classCruft["Indexes"])) {
				$indexesGroup = new CompositeField(array(
					new HeaderField("DeleteSpec[{$classCruft["DataClass"]}][Indexes]", "Indexes", 3)
				));
				foreach($classCruft["Indexes"] as $indexName => $index) {
					$indexesGroup->push(new CheckboxField("DeleteSpec[{$classCruft["DataClass"]}][Indexes][{$indexName}]", "{$indexName} ({$index["Column_name"]})"));
				}
				$group->push($indexesGroup);
			}
			
			if(!empty($classCruft["ManyManyFields"]) || !empty($classCruft["ManyManyIndexes"])) {
				$relationships = array_unique(array_merge(
					array_keys(
						isset($classCruft["ManyManyFields"])?$classCruft["ManyManyFields"]:array()
					),
					array_keys(
						isset($classCruft["ManyManyIndexes"])?$classCruft["ManyManyIndexes"]:array()
					)
				));
				
				foreach($relationships as $relationship) {
					$manyManyGroup = new CompositeField(array(
						new HeaderField("DeleteSpec[{$classCruft["DataClass"]}][ManyMany][{$relationship}]", "Many-Many: {$relationship}", 4)
					));
					
					if(!empty($classCruft["ManyManyFields"][$relationship])) {
						$manyManyGroup->push($fieldsGroup = new CompositeField(array(
							new HeaderField("DeleteSpec[{$classCruft["DataClass"]}][ManyMany][{$relationship}][Fields]", "Fields", 5)
						)));
						foreach($classCruft["ManyManyFields"][$relationship] as $fieldName => $field) {
							$fieldsGroup->push(new CheckboxField("DeleteSpec[{$classCruft["DataClass"]}][ManyMany][{$relationship}][Fields][{$fieldName}]", "{$fieldName} ({$field["Type"]})"));
						}
					}
					
					if(!empty($classCruft["ManyManyIndexes"][$relationship])) {
						$manyManyGroup->push($fieldsGroup = new CompositeField(array(
							new HeaderField("DeleteSpec[{$classCruft["DataClass"]}][ManyMany][{$relationship}][Indexes]", "Indexes", 5)
						)));
						foreach($classCruft["ManyManyIndexes"][$relationship] as $indexName => $index) {
							$fieldsGroup->push(new CheckboxField("DeleteSpec[{$classCruft["DataClass"]}][ManyMany][{$relationship}][Indexes][{$indexName}]", "{$indexName} ({$index["Column_name"]})"));
						}
					}
					
					$group->push($manyManyGroup);
				}
			}
			
			$fields->push($group);
		}
		
		echo $this->owner->renderWith(array("DatabaseAdminCleaner", "ContentController"), array(
			"FormDeleteCruft" => $form
		));
		
		if(!Director::is_cli()) {
			echo "</div>";
			$renderer->writeFooter();
		}
	}
	
	public function FormDeleteCruft($request = null) {
		$fields = new FieldList();
		
		$actions = new FieldList(
			new FormAction("ActionDeleteCruft", "Delete Cruft")
		);
		
		return new Form($this->owner, __FUNCTION__, $fields, $actions);
	}
	
	public function ActionDeleteCruft($data, $form) {
		if(!Director::is_cli()) {
			$renderer = DebugView::create();
			$renderer->writeHeader();
			$renderer->writeInfo("Environment Builder", Director::absoluteBaseURL());
			echo "<div class=\"scrub\">";
		}
		
		if(!empty($data["DeleteSpec"]))
			foreach($data["DeleteSpec"] as $table => $spec) {
				if(!empty($spec["WholeTable"]) && $spec["WholeTable"] === "1") {
					$this->deleteTable($table);
					continue;
				}
				if(!empty($spec["Fields"]))
					foreach($spec["Fields"] as $fieldName => $delete) {
						if($delete !== "1")
							continue;
						
						$this->deleteField($table, $fieldName);
					}
				
				if(!empty($spec["Indexes"]))
					foreach($spec["Indexes"] as $indexName => $delete) {
						if($delete !== "1")
							continue;
						
						$this->deleteIndex($table, $indexName);
					}
				
				if(!empty($spec["ManyMany"]))
					foreach($spec["ManyMany"] as $relationship => $manyManySpec) {
						if(!empty($manyManySpec["Fields"]))
							foreach($manyManySpec["Fields"] as $fieldName => $delete) {
								if($delete !== "1")
									continue;
								
								$this->deleteField("{$table}_{$relationship}", $fieldName);
							}
							
						if(!empty($manyManySpec["Indexes"]))
							foreach($manyManySpec["Indexes"] as $indexName => $delete) {
								if($delete !== "1")
									continue;
					
								$this->deleteIndex("{$table}_{$relationship}", $indexName);
							}
					}
			}
		
		if(!Director::is_cli()) {
			echo "</div>";
			$renderer->writeFooter();
		}
	}
	
	public function deleteTable($table) {
		Debug::message("Deleting Table: {$table}");
		
		$conn = DB::getConn();
		
		switch(true) {
			case $conn instanceof MySQLDatabase:
				$conn->query("DROP TABLE `{$table}`");
				break;
		}
	}
	
	public function deleteField($table, $field) {
		Debug::message("Deleting Field: {$table}, {$field}");
		
		$conn = DB::getConn();
		
		switch(true) {
			case $conn instanceof MySQLDatabase:
				$conn->query("ALTER TABLE `{$table}` DROP COLUMN `{$field}`");
				break;
		}
	}
	
	public function deleteIndex($table, $index) {
		Debug::message("Deleting Index: {$table}, {$index}");
		
		$conn = DB::getConn();
		
		switch(true) {
			case $conn instanceof MySQLDatabase:
				$conn->query("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
				break;
		}
	}
}

class DevelopmentAdminCleaner extends Extension {
	public static $allowed_actions = array(
		"scrub"
	);
	
	public function scrub($request) {
		return $this->owner->redirect("DatabaseAdmin/scrub");
	}
}
