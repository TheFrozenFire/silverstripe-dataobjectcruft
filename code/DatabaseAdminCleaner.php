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
	
		$dataClasses = ClassInfo::subclassesFor('DataObject');
		array_shift($dataClasses);
		
		$cruft = array();
		foreach($dataClasses as $dataClass) {
			if(class_exists($dataClass)) {
				$SNG = singleton($dataClass);
				if(!($SNG instanceof TestOnly)) {
					$classCruft = $SNG->CruftFields();
					
					if(!empty($classCruft))
						$cruft[] = $classCruft;
				}
			}
		}
		
		$form = $this->FormDeleteCruft();
		$form->setFormAction("/DatabaseAdmin/FormDeleteCruft");
		$fields = $form->Fields();
		foreach($cruft as $classCruft) {
			$group = new CompositeField(array(
				new HeaderField($classCruft["DataClass"])
			));
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
			}
		
		if(!Director::is_cli()) {
			echo "</div>";
			$renderer->writeFooter();
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
