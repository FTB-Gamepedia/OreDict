$("input[type=checkbox]").click(function(){
	flags = parseInt($("input#flags").attr("value"), 10);
	state = $(this).attr("checked") == "checked";
	value = parseInt($(this).attr("value"), 10);
	if(state){
		flags += value;
	} else {
		flags -= value;
	}
	$("input#flags").attr("value", flags);
});

$("input#form-create-new").click(function(){
	window.location = mw.util.wikiGetlink('Special:OreDictEntryManager/-1');
});
