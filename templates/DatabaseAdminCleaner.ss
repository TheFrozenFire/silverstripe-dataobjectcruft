<style type="text/css">
	#Form_FormDeleteCruft .composite .composite {
		margin-left: 1em;
		padding: 0.5em;
		border-bottom: 2px dashed #CCC;
	}
	#Form_FormDeleteCruft .composite .composite:last-child {
		border-bottom: none;
	}
	
	#Form_FormDeleteCruft .Actions {
		padding: 1em;
	}
</style>
<script type="text/javascript" src="/framework/thirdparty/jquery/jquery.js"></script>
<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			$("#Form_FormDeleteCruft .Actions").append('<button name="select-all">Select All</button><button name="unselect-all">Unselect All</button>');
			
			$("#Form_FormDeleteCruft button[name=select-all]").click(function(event) {
				event.preventDefault();
				$("input[type=checkbox]", $(this).closest("form")).prop("checked", true);
			});
			
			$("#Form_FormDeleteCruft button[name=unselect-all]").click(function(event) {
				event.preventDefault();
				$("input[type=checkbox]", $(this).closest("form")).prop("checked", false);
			});
		});
	}) (jQuery);
</script>
<h1>Schema Scrubber</h1>
<p>
	Click the checkbox next to all tables/fields/indexes which you want deleted.
</p>
$FormDeleteCruft
