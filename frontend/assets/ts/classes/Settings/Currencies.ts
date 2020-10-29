import "@jalno/translator";
import "bootstrap";
import "bootstrap-inputmsg";
import * as $ from "jquery";
import "jquery.growl";
import { webuilder } from "webuilder";
interface ICurrency {
	title: string;
	value: number;
}
enum Behaviours {
	CEIL = 1,
	ROUND = 2,
	FLOOR = 3,
}
export default class Currencies {
	public static init() {
		const $body = $("body");
		if ($body.hasClass("currencies-add")) {
			Currencies.$form = $(".currency-add-form", $body);
		} else if ($body.hasClass("currencies-edit")) {
			Currencies.$form = $(".currency-edit-form", $body);
		}
		Currencies.$panel = $(".panel.panel-white", Currencies.$form);
		Currencies.setEvents(Currencies.$form);
		Currencies.runChangeListener();
		Currencies.runChangebehaviourListener();
		Currencies.createChangeRatesFields();
		Currencies.runSubmitFormListener();
	}
	public static initIfNeeded() {
		const $body = $("body");
		if ($body.hasClass("financial-settings")) {
			Currencies.init();
		}
	}
	private static $form: JQuery;
	private static $panel: JQuery;
	private static deleteField($row: JQuery) {
		const $prevRow: JQuery = $row.prev();
		$($row).remove();
		Currencies.shiftIndex($prevRow);
	}
	private static setEvents($row: JQuery) {
		$(".btn-delete", $row).on("click", function(e) {
			e.preventDefault();
			Currencies.deleteField($(this).parents(".rates"));
		});
		$(".rates-currency", $row).on("change", function() {
			const $that = $(this);
			$(".panel-body .rates-currency", Currencies.$panel).each(function() {
				const $parent = $(this).parents(".form-group");
				if (!$(this).is($that) && $(this).val() === $that.val()) {
					if (!$parent.hasClass("has-error")) {
						$parent.addClass("has-error");
						$parent.append(`<span class="help-block">${t("packages.data_duplicate.currencies.rate")}</span>`);
					}
				} else {
					$parent.removeClass("has-error");
					$(".help-block", $parent).remove();
				}
			});
		}).trigger("change");
		$(".tooltips", $row).tooltip();
	}
	private static shiftIndex($row: JQuery) {
		const $rates: JQuery = $(".panel-body .rates", Currencies.$panel);
		let eq: number;
		let found: boolean = false;
		for (let i = 0; i < $rates.length && !found; i++) {
			if ($rates.eq(i).is($row)) {
				eq = i;
				found = true;
			}
		}
		if (found) {
			const name: string = $row.find(".rates-currency").attr("name");
			let index: number = parseInt(name.match(/(\d+)/)[0], 10) + 1;
			for (let i: number = eq + 1; i < $rates.length ; i++, index++) {
				$rates.eq(i).find(".rates-currency").attr("name", "rates[" + index + "][currency]");
				$rates.eq(i).find(".rates-price").attr("name", "rates[" + index + "][price]");
			}
		}
	}
	private static runChangeListener(): void {
		const $change = $("input[name=change]", Currencies.$form);
		const $roundingContainer = $(".rounding-container", Currencies.$form);
		const $roundingInputs = $("select[name=rounding-behaviour], input[name=rounding-precision]", Currencies.$form);
		const $helpbox = $(".rounding-behaviour-guidance");
		$("input[name=change-checkbox]", Currencies.$form).on("change", function() {
			const $this = $(this);
			if (!$this.data("change")) {
				$this.prop({
					checked: false,
					disabled: true,
				});
			}
			if ($this.prop("checked")) {
				$change.val("1");
				Currencies.$panel.slideDown();
				$roundingContainer.slideDown();
				$helpbox.slideDown();
				$roundingInputs.prop("disabled", false);
			} else {
				$change.val("0");
				Currencies.$panel.slideUp();
				$roundingContainer.slideUp();
				$helpbox.slideUp();
				$roundingInputs.prop("disabled", true);
			}
		}).trigger("change");
		$helpbox.removeClass("text-center");
	}
	private static runChangebehaviourListener(): void {
		const $container = $(".rounding-container", Currencies.$form);
		const $helpbox = $(".rounding-behaviour-guidance");
		$("select[name=rounding-behaviour]", $container).on("change", function() {
			const selected = parseInt($("option:selected", this).val(), 10) as Behaviours;
			switch (selected) {
				case(Behaviours.CEIL):
				$helpbox.html(t("packages.financial.currencies.rounding.behaviour.ceil.help_text"));
				break;
				case(Behaviours.ROUND):
				$helpbox.html(t("packages.financial.currencies.rounding.behaviour.round.help_text"));
				break;
				case(Behaviours.FLOOR):
				$helpbox.html(t("packages.financial.currencies.rounding.behaviour.floor.help_text"));
				break;
			}
		}).trigger("change");
	}
	private static createCurrencySelectOptions(): string {
		let options: string = "";
		const currencies: ICurrency[] = Currencies.$panel.data("currencies");
		for (const currency of currencies) {
			options += `<option value="${currency.value}">${currency.title}</option>`;
		}
		return options;
	}
	private static createChangeRatesFields(): void {
		$(".panel-tools a.btn-add", Currencies.$panel).on("click", (e) => {
			e.preventDefault();
			const currencies: ICurrency[] = Currencies.$panel.data("currencies");

			const $rates: JQuery = $(".panel-body .row", Currencies.$panel);
			if ($rates.length >= currencies.length) {
				return;
			}
			const html = `<div class="row rates">
			<div class="col-sm-5">
				<div class="form-group"><label class="control-label">${t("financial.settings.currency.price")}</label><input value="" name="rates[0][price]" class="form-control rates-price ltr" type="number" step="any"></div>
			</div>
			<div class="col-sm-5">
				<div class="form-group"><label class="control-label">${t("financial.settings.currency")}</label>
					<select name="rates[0][currency]" class="form-control rates-currency">
						${Currencies.createCurrencySelectOptions()}
					</select>
				</div>
			</div>
			<div class="col-sm-2 col-xs-12 text-center">
				<button href="#" class="btn btn-xs btn-bricky tooltips btn-delete" title="${t("delete")}" style="margin-top: 30px;">
					<i class="fa fa-times"></i>
				</button>
			</div>
		</div>`;
			const $row = $(html).appendTo($(".panel-body", Currencies.$panel));
			Currencies.setEvents($row);
			Currencies.shiftIndex($row.prev());
		});
	}
	private static runSubmitFormListener() {
		Currencies.$form.on("submit", function(e) {
			e.preventDefault();
			let $dataDuplicate: boolean = false;
			$(".rates-currency", Currencies.$panel).each(function() {
				const $this = $(this);
				$(".rates-currency", Currencies.$panel).each(function() {
					if (!$(this).is($this) && $(this).val() === $this.val()) {
						$.growl.error({
							title: t("error.fatal.title"),
							message: t("packages.data_duplicate.currencies.rates"),
						});
						$dataDuplicate = true;
						return false;
					}
				});
				return false;
			});
			if ($dataDuplicate) {
				return;
			}
			$(".has-error", Currencies.$form).each(function() {
				$(this).removeClass("has-error");
				$(".help-block", this).remove();
			});
			$(this).formAjax({
				success: (data: webuilder.AjaxResponse) => {
					$.growl.notice({
						title: t("packages.financial.success"),
						message: t("packages.financial.request.success"),
					});
					if (data.redirect) {
						window.location.href = data.redirect;
					}
				},
				error: (error: webuilder.AjaxError) => {
					if (error.error === "data_duplicate" || error.error === "data_validation") {
						const $input = $(`[name="${error.input}"]`);
						const params = {
							title: t("error.fatal.title"),
							message: t(`packages.financial.${error.error}`),
						};
						if (error.input === "rates") {
							params.message = t("packages.financial.error.currency.rates.should_add_rates");
						}
						if ($input.length) {
							$input.inputMsg(params);
						} else {
							$.growl.error(params);
						}
					} else if (error.hasOwnProperty("type") && error.type === "fatal") {
						const ErrorHtml = `
							<div class="alert alert-block alert-danger ">
								<button data-dismiss="alert" class="close" type="button">&times;</button>
								<h4 class="alert-heading"><i class="fa fa-times-circle"></i> ${t("error.fatal.title")}</h4>
								<p>${error.message}</p>
							</div>
						`;
						if (!$(".errors .currencyError").length) {
							$(".errors").append('<div class="currencyError"></div>');
						}
						$(".errors .currencyError").html(ErrorHtml);
					} else {
						$.growl.error({
							title: t("error.fatal.title"),
							message: t("packages.financial.request.error"),
						});
					}
				},
			});
		});
	}
}
