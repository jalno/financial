import "@jalno/translator";
import "bootstrap-inputmsg";
import $ from "jquery";
import "jquery-inputlimiter";
import "../../../jquery.financialUserAutoComplete";

export default class Add {
	public static initIfNeeded() {
		Add.$form = $("body.settings-banks-accounts.banks-accounts-add form#add-banks-account");
		if (Add.$form.length) {
			Add.init();
		}
	}
	protected static $form: JQuery;
	protected static init() {
		Add.runUserAutoComplete();
		Add.checkIBAN();
	}
	protected static runUserAutoComplete() {
		const $input = $("input[name=user_name]", Add.$form);
		if (!$input.length) {
			return;
		}
		$input.financialUserAutoComplete();
	}
	protected static checkIBAN() {
		const $input = $("input[name=shaba]", Add.$form);
		$input.on("keyup", () => {
			$input.inputMsg("reset");
			let inputValueString = String($input.val());
			inputValueString = inputValueString.replace(/\s/g, "").replace(/[^a-zA-Z0-9]/g, "");
			$input.val(inputValueString);
			if (inputValueString.length < 2) {
				return;
			}
			const countryCode = inputValueString.substring(0, 2).toLowerCase();
			$input.val(countryCode.toUpperCase() + inputValueString.substring(2));
			const countries = {al: 28, ad: 24, at: 20, az: 28, bh: 22, be: 16, ba: 20, br: 29, bg: 22, cr: 21, hr: 21, cy: 28,
							   cz: 24, dk: 18, do: 28, ee: 20, fo: 18, fi: 18, fr: 27, ge: 22, de: 22, gi: 23, gr: 27, gl: 18,
							   gt: 28, hu: 28, is: 26, ie: 22, il: 23, ir: 26, it: 27, jo: 30, kz: 20, kw: 30, lv: 21, lb: 28,
							   li: 21, lt: 20, lu: 20, mk: 19, mt: 31, mr: 27, mu: 30, mc: 27, md: 24, me: 22, nl: 18, no: 15,
							   pk: 24, ps: 29, pl: 28, pt: 25, qa: 29, ro: 24, sm: 27, sa: 24, rs: 22, sk: 24, si: 19, es: 24,
							   se: 24, ch: 21, tn: 24, tr: 26, ae: 23, gb: 22, vg: 24};
			if (!countries.hasOwnProperty(countryCode)) {
				$input.inputMsg({
					type: "error",
					message: t("packages.financial.error.IBAN.start_with_country_code"),
				});
				return;
			}
			if (inputValueString.length === countries[countryCode]) {
				let twoFirstDigit = "";
				for (let i = 0, a = "A".charCodeAt(0); i < 2; i++) {
					twoFirstDigit += countryCode.toUpperCase().charCodeAt(i) - a + 10;
				}
				const val = inputValueString.substring(4) + twoFirstDigit + inputValueString.substring(2, 4);
				const divideMod = (divident, divisor) => {
					while (divident.length > 10) {
						const part = divident.substring(0, 10);
						divident = (part % divisor) +  divident.substring(10);
					}
					return divident % divisor;
				};
				if (divideMod(val, 97) !== 1) {
					$input.inputMsg({
						type: "error",
						message: t("packages.financial.error.IBAN_not_valid"),
					});
				}
			} else if (inputValueString.length > countries[countryCode]) {
				$input.inputMsg({
					type: "error",
					message: t("packages.financial.error.IBAN.exceed", {
						number: countries[countryCode],
					}),
				});
			}
		});
	}
}
