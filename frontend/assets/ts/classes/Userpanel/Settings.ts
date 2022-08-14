import * as $ from 'jquery';
import Transaction from '../Transaction';

export default class Settings
{
	public static initIfNeeded()
	{
		Settings.$input = $('input[name="financial_checkout_limits[price]"');

		if (Settings.$input.length) {
			Settings.init();
		}
	}

	private static $input: JQuery;

	private static init()
	{
		Settings.runNumberFormatListener();
	}

	private static runNumberFormatListener() {
		Settings.$input.on("keyup", function(e) {
			let val = Transaction.deFormatNumber($(this).val() as string);
			const isDot = e.key === '.';
			const number = parseInt(val, 10);
			if (isNaN(number)) {
				$(this).val(isDot ? "0." : "");
				return;
			}
			val = Transaction.formatFloatNumber(parseFloat(val));
			if (isDot) {
				val += ".";
			}
			$(this).val(val);
		}).trigger('keyup');
		Settings.$input.parents('form').on('submit', () => {
			Settings.$input.val(Transaction.deFormatNumber(Settings.$input.val() as string));
		});
	}
}
