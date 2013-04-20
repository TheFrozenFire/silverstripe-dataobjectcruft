<?php
Config::inst()->forClass("DataObject")->extensions = array(
	"DataObjectCruft"
);

Config::inst()->forClass("DevelopmentAdmin")->extensions = array(
	"DevelopmentAdminCleaner"
);

Config::inst()->forClass("DatabaseAdmin")->extensions = array(
	"DatabaseAdminCleaner"
);
