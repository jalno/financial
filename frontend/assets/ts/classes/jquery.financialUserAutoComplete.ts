/// <reference path="jquery.financialUserAutoComplete.d.ts"/>

import * as $ from "jquery";
import "jquery-ui/ui/widgets/autocomplete.js";
import {Router, webuilder} from "webuilder";
export interface IUser{
	id:number;
	name:string;
	lastname:string;
	email:string;
	cellphone:string;
	currency: string;
}
interface searchResponse extends webuilder.AjaxResponse{
	items: IUser[];
}
$.fn.financialUserAutoComplete = function(){
	function select(event, ui):boolean{
		let $form = $(this).parents('form');
		let name = $(this).attr('name');
		name = name.substr(0, name.length - 5);
		$(this).val(ui.item.name+(ui.item.lastname ? ' '+ui.item.lastname : ''));
		$(`input[name="${name}"]`, $form).val(ui.item.id).trigger('change');
		$(this).trigger("financialUserAutoComplete.select", [ui.item]);
		return false;
	}
	function unselect(){
		if($(this).val() == ""){
			let $form = $(this).parents('form');
			let name = $(this).attr('name');
			name = name.substr(0, name.length - 5);
			$('input[name='+name+']', $form).val("");
			$(this).trigger("financialUserAutoComplete.unselect");
		}
	}
	$(this).autocomplete({
		source: function( request, response ) {
			$.ajax({
				url: Router.url("userpanel/financial/users"),
				dataType: "json",
				data: {
					ajax: 1,
					word: request.term
				},
				success: function( data: searchResponse) {
					if(data.status){
						response( data.items );
					}
				}
			});
		},
		select: select,
		focus: select,
		change:unselect,
		close:unselect,
		create: function() {
			$(this).data('ui-autocomplete')._renderItem = function( ul, item:IUser ) {
				return $( "<li>" )
					.append( "<strong>" + item.name+(item.lastname ? ' '+item.lastname : '')+ "</strong><small class=\"ltr\">"+item.email+"</small><small class=\"ltr\">"+item.cellphone+"</small>" )
					.appendTo( ul );
			}
		}
	});
}