/// <reference path="jquery.financialUserAutoComplete.d.ts"/>

import "jquery-ui/ui/widgets/autocomplete.js";
import {Router, webuilder} from "webuilder";
export interface IUser {
	id: number;
	name: string;
	lastname: string;
	email: string;
	cellphone: string;
	credit: number;
	currency: string;
}
interface ISearchResponse extends webuilder.AjaxResponse {
	items: IUser[];
}
$.fn.financialUserAutoComplete = function() {
	function select(event, ui): boolean {
		const $form = $(this).parents("form");
		let name = $(this).attr("name");
		name = name.substr(0, name.length - 5);
		$(this).val(ui.item.name + (ui.item.lastname ? " " + ui.item.lastname : ""));
		$(`input[name="${name}"]`, $form).val(ui.item.id).trigger("change");
		$(this).trigger("financialUserAutoComplete.select", [ui.item, event.type]);
		return false;
	}
	function unselect() {
		if ($(this).val() === "") {
			const $form = $(this).parents("form");
			let name = $(this).attr("name");
			name = name.substr(0, name.length - 5);
			$("input[name=" + name + "]", $form).val("");
			$(this).trigger("financialUserAutoComplete.unselect");
		}
	}
	$(this).autocomplete({
		source: ( request, response ) => {
			$.ajax({
				url: Router.url("userpanel/financial/users"),
				dataType: "json",
				data: {
					ajax: 1,
					word: request.term,
				},
				success: ( data: ISearchResponse) => {
					if (data.status) {
						response( data.items );
					}
				},
			});
		},
		select: select,
		focus: select,
		change: unselect,
		close: unselect,
		create: function() {
			$(this).data("ui-autocomplete")._renderItem = ( ul, item: IUser ) => {
				return $( "<li>" )
					.append( "<strong>" + item.name + (item.lastname ? " " + item.lastname : "") + "</strong><small class=\"ltr\">" + item.email + "</small><small class=\"ltr\">" + item.cellphone + "</small>" )
					.appendTo( ul );
			};
		},
	});
};
