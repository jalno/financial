var GateWays = function(){
    var form = $('.create_form');
	var showGatewayFields = function(){
		$('select[name=gateway]', form).change(function(){
			var $val = $(this).val();
			$('.gatewayfields:not(.gateway-'+$val+")",form).hide();
			$('.gatewayfields.gateway-'+$val, form).show();
		}).trigger('change');
	};
    return{
        init: function(){
            showGatewayFields();
        }
    }
}();
$(function(){
    GateWays.init();
});