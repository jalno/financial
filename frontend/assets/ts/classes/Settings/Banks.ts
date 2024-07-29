import "@jalno/translator";
import "bootstrap";
import Accounts from "./Banks/Accounts";
import Add from "./Banks/Add";
import Delete from "./Banks/Delete";
import Edit from "./Banks/Edit";

export enum Status {
	Active = 1,
	Deactive = 2,
}

export interface IBank {
	id: number;
	title: string;
	status: Status;
}

export default class Banks {
	public static initIfNeeded() {
		const $body = $("body.settings-banks");
		Banks.$table = $("table.table-banks", $body);
		if (Banks.$table.length) {
			Banks.$alert = $(".alert.alert-notfound", $body);
			Banks.canEdit = Banks.$table.data("canedit");
			Banks.canDelete = Banks.$table.data("candelete");
			Add.initIfNeeded();
			if (Banks.canEdit) {
				Edit.run($("tbody tr", Banks.$table));
			}
			if (Banks.canDelete) {
				Delete.run($("tbody tr", Banks.$table));
			}
		}
		Accounts.initIfNeeded();
	}
	public static prependBank(bank: IBank) {
		const tr = `<tr>
			<td class="center">${bank.id}</td>
			<td>${bank.title}</td>
			<td>${Banks.getStatus(bank.status)}</td>
			${Banks.generateButtons()}
		</tr>`;
		if ($("tbody tr", Banks.$table).length === 25) {
			$("tbody tr", Banks.$table).last().remove();
		}
		const $tr = $(tr).prependTo($("tbody", Banks.$table));
		$tr.data("bank", bank);
		Banks.runTooltips($tr);
		Banks.$table.show();
		Banks.$alert.hide();
		if (Banks.canEdit) {
			Edit.run($tr);
		}
		if (Banks.canDelete) {
			Delete.run($tr);
		}
	}
	public static removeBank($tr: JQuery) {
		$tr.remove();
		if (!$("tbody tr", Banks.$table).length) {
			Banks.$table.hide();
			Banks.$alert.show();
		}
	}
	public static getStatus(status: Status): string {
		switch (status) {
			case (Status.Active):
				return `<span class="label label-success">${t("packages.financial.bank.status.Active")}</span>`;
			case (Status.Deactive):
				return `<span class="label label-danger">${t("packages.financial.bank.status.Deactive")}</span>`;
		}
	}
	public static generateButtons(): string {
		let $btns = "";
		if (!Banks.canEdit && !Banks.canDelete) {
			return $btns;
		}
		$btns = `<td class="center">
		<div class="visible-md visible-lg hidden-sm hidden-xs">`;
		if (Banks.canEdit) {
			$btns += `<a href="#" class="btn btn-xs btn-warning tooltips" data-action="edit" title="${t("packages.financial.edit")}"><i class="fa fa-edit"></i></a>`;
		}
		if (Banks.canDelete) {
			$btns += `<a href="#" class="btn btn-xs btn-bricky tooltips" data-action="delete" title="${t("packages.financial.delete")}"><i class="fa fa-times"></i></a>`;
		}
		$btns += `</div>
		<div class="visible-xs visible-sm hidden-md hidden-lg">
			<div class="btn-group"><a class="btn btn-primary dropdown-toggle btn-sm" data-toggle="dropdown" href="#"><i class="fa fa-cog"></i> <span class="caret"></span></a>
				<ul role="menu" class="dropdown-menu pull-right">`;
		if (Banks.canEdit) {
			$btns += `<li><a tabindex="-1" href="#" data-action="edit"><i class="fa fa-edit"></i> ${t("packages.financial.edit")}</a></li>`;
		}
		if (Banks.canDelete) {
			$btns += `<li><a tabindex="-1" href="#" data-action="delete"><i class="fa fa-times"></i> ${t("packages.financial.delete")}</a></li>`;
		}
		$btns += `</ul>
			</div>
		</div>
	</td>`;
		return $btns;
	}
	protected static $table: JQuery;
	protected static $alert: JQuery;
	protected static canEdit = false;
	protected static canDelete = false;
	protected static runTooltips($container: JQuery) {
		$(".tooltips", $container).tooltip();
	}
	public static get table() {
		return Banks.$table;
	}
	public static get alert() {
		return Banks.$alert;
	}
}
