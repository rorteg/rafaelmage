function validaCPF(cpf,pType) {
	if (Validation.get('IsEmpty').test(cpf)) {
        return false;
    } 

	var valid = true;
    var cpf = cpf.replace(/[.\//-]/g,'');

    if(cpf.length != 11 || cpf == "00000000000" || cpf == "11111111111" || cpf == "22222222222" || cpf == "33333333333" || cpf == "44444444444" || cpf == "55555555555" || cpf == "66666666666" || cpf == "77777777777" || cpf == "88888888888" || cpf == "99999999999")
    valid = false;
    add = 0;
    for (i=0; i < 9; i ++)
    add += parseInt(cpf.charAt(i)) * (10 - i);
    rev = 11 - (add % 11);
    if (rev == 10 || rev == 11)
    rev = 0;
    if (rev != parseInt(cpf.charAt(9)))
    valid = false;
    add = 0;
    for (i = 0; i < 10; i ++)
    add += parseInt(cpf.charAt(i)) * (11 - i);
    rev = 11 - (add % 11);
    if (rev == 10 || rev == 11)
    rev = 0;
    if (rev != parseInt(cpf.charAt(10)))
    valid = false;

    if(valid) {
        return true;
    }        

	if (cpf.length >= 14) {
		if ( cpf.substring(12,14) == checkCNPJ( cpf.substring(0,12) ) ) {
			return true;
		}
	}

    return false;
}

function checkCNPJ(vCNPJ) {
	var mControle = "";
	var aTabCNPJ = new Array(5,4,3,2,9,8,7,6,5,4,3,2);
	for (i = 1 ; i <= 2 ; i++) {
		mSoma = 0;
		for (j = 0 ; j < vCNPJ.length ; j++)
		mSoma = mSoma + (vCNPJ.substring(j,j+1) * aTabCNPJ[j]);
		if (i == 2 ) mSoma = mSoma + ( 2 * mDigito );
		mDigito = ( mSoma * 10 ) % 11;
		if (mDigito == 10 ) mDigito = 0;
		mControle1 = mControle ;
		mControle = mDigito;
		aTabCNPJ = new Array(6,5,4,3,2,9,8,7,6,5,4,3);
	}

	return( (mControle1 * 10) + mControle );
}

Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator) {
    var n = this,
    decPlaces = isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces,
    decSeparator = decSeparator == undefined ? "." : decSeparator,
    thouSeparator = thouSeparator == undefined ? "," : thouSeparator,
    sign = n < 0 ? "-" : "",
    i = parseFloat(n = Math.abs(+n || 0).toFixed(decPlaces)) + "",
    j = (j = i.length) > 3 ? j % 3 : 0;
    return sign + (j ? i.substr(0, j) + thouSeparator : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator) + (decPlaces ? decSeparator + Math.abs(n - i).toFixed(decPlaces).slice(2) : "");
};

function remove_characters(event) {
    /* Allow: backspace, delete, tab, escape, and enter */
    if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 27 || event.keyCode == 13 || 
         /* Allow: Ctrl+A */
        (event.keyCode == 65 && event.ctrlKey === true) || 
         /* Allow: home, end, left, right */
        (event.keyCode >= 35 && event.keyCode <= 39)) {
            /* let it happen, don't do anything */
            return;
    } else {
        /* Ensure that it is a number and stop the keypress */
        if (event.shiftKey || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
            event.preventDefault(); 
        }   
    }
}

function remove_special_characters(event) {
	/* Allow: backspace, delete, tab, escape, comma, enter and decimal point */
    if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 27 || event.keyCode == 188 || event.keyCode == 13 || event.keyCode == 110 || 
         /* Allow: Ctrl+A */
        (event.keyCode == 65 && event.ctrlKey === true) || 
         /* Allow: home, end, left, right */
        (event.keyCode >= 35 && event.keyCode <= 39)) {
            /* let it happen, don't do anything */
            return;
    } else {
        /* Ensure that it is a number and stop the keypress */
        if (event.shiftKey || (event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
            event.preventDefault(); 
        }   
    }
}

if(Validation) {  
	Validation.add('validar_cpf', 'O CPF ou CNPJ informado \xE9 inválido', function(v){return validaCPF(v,0);});

    /**
	 * Hash with credit card types which can be simply extended in payment modules
	 * 0 - regexp for card number
	 * 1 - regexp for cvn
	 * 2 - check or not credit card number trough Luhn algorithm by
	 *     function validateCreditCard which you can find above in this file
	 */
	Validation.creditCartTypes = $H({
	    'VI': [new RegExp('^4[0-9]{12}([0-9]{3})?$'), new RegExp('^[0-9]{3}$'), true],
	    'MC': [new RegExp('^5[1-5][0-9]{14}$'), new RegExp('^[0-9]{3}$'), true],
	    'AE': [new RegExp('^3[47][0-9]{13}$'), new RegExp('^[0-9]{4}$'), true],
	    'DI': [false, new RegExp('^[0-9]{3}$'), true],
	    'OT': [false, new RegExp('^([0-9]{3}|[0-9]{4})?$'), false],
	    'EL': [false, new RegExp('^([0-9]{3})?$'), true],
	    'HI': [false, new RegExp('^([0-9]{3})?$'), false]
	});

	Validation.add('check_values', 'Confira os valores a passar em cada cartão', function(){return check_values();});

    Validation.add('validate-cc-exp-cus', 'Data de expiração do Cartão incorreta', function(v,elm){return verify_cc_expiration_date(v,elm);});
}

function verify_cc_expiration_date(v,elm) {
	var ccExpMonth   = v;
    var ccExpYear    = $(elm.id.substr(0,elm.id.indexOf('_expirationMonth')) + '_expirationYear').value;
    var currentTime  = new Date();
    var currentMonth = currentTime.getMonth() + 1;
    var currentYear  = currentTime.getFullYear();
    if (ccExpMonth < currentMonth && ccExpYear == currentYear) {
        return false;
    }
    return true;
}

function token_or_not(num,c,active) {
	var type = $$('input[name="payment\\[method\\]"]:checked').first().value;

	if( document.getElementById(type+'_token_'+num+'_'+c).value == 'new' ) {
		// Remove disable fields
		$(type+'_'+num+'_'+c+'_cc_type').enable();
		$(type+'_'+num+'_'+c+'_cc_number').enable();
		$(type+'_cc_holder_name_'+num+'_'+c).enable();
		$(type+'_expirationMonth_'+num+'_'+c).enable();
		$(type+'_expirationYear_'+num+'_'+c).enable();
		$(type+'_cc_cid_'+num+'_'+c).enable();

		if(document.getElementById(type+'_new_credito_parcelamento_'+num+'_'+c)!= null) {
			$(type+'_new_credito_parcelamento_'+num+'_'+c).enable();
		}
		
		if(document.getElementById(type+'_new_value_'+num+'_'+c)!= null) {
			$(type+'_new_value_'+num+'_'+c).enable();
		}
		
		// Show new credit card fields
		$(type+'_new_credit_card_'+num+'_'+c).show();

		if(active == 1) {
			$('parcelamento_'+num+'_'+c).hide();
		}
		
		if(document.getElementById('value_'+num+'_'+c)!= null) {
			$('value_'+num+'_'+c).hide();
		}
	} else {
		// Disable fields
		$(type+'_'+num+'_'+c+'_cc_type').disable();
		$(type+'_'+num+'_'+c+'_cc_number').disable();
		$(type+'_cc_holder_name_'+num+'_'+c).disable();
		$(type+'_expirationMonth_'+num+'_'+c).disable();
		$(type+'_expirationYear_'+num+'_'+c).disable();
		$(type+'_cc_cid_'+num+'_'+c).disable();
		$(type+'_new_credito_parcelamento_'+num+'_'+c).disable();

		if(document.getElementById(type+'_new_value_'+num+'_'+c)!= null) {
			$(type+'_new_value_'+num+'_'+c).disable();
		}

		// Hide new credit card fields
		$(type+'_new_credit_card_'+num+'_'+c).hide();

		if(active == 1) {
			$('parcelamento_'+num+'_'+c).show();
		}
		
		if(document.getElementById('value_'+num+'_'+c)!= null) {
			$('value_'+num+'_'+c).show();
		}
	}
}

function cc_cid(field, num, c) {
	var type = $$('input[name="payment\\[method\\]"]:checked').first().value;
	var cc_cid = document.getElementById(type+'_cc_cid_'+num+'_'+c);

	if(field.value == 'AE') {
		cc_cid.removeClassName('minimum-length-3');
		cc_cid.removeClassName('maximum-length-3');
		cc_cid.addClassName('minimum-length-4');
		cc_cid.addClassName('maximum-length-4');
	} else {
		cc_cid.removeClassName('minimum-length-4');
		cc_cid.removeClassName('maximum-length-4');
		cc_cid.addClassName('minimum-length-3');
		cc_cid.addClassName('maximum-length-3');
	}
}

function hide_methods(dont_hide) {
	if(document.getElementById('1CreditCardsOneInstallment')!= null && dont_hide != '1CreditCardsOneInstallment'){
		document.getElementById('1CreditCardsOneInstallment').style.display='none';
	}

	if(document.getElementById('1CreditCards')!= null && dont_hide != '1CreditCards'){
		document.getElementById('1CreditCards').style.display='none';
	}

	if(document.getElementById('2CreditCards')!= null && dont_hide != '2CreditCards'){
		document.getElementById('2CreditCards').style.display='none';
	}
	
	if(document.getElementById('3CreditCards')!= null && dont_hide != '3CreditCards'){
		document.getElementById('3CreditCards').style.display='none';
	}

	if(document.getElementById('4CreditCards')!= null && dont_hide != '4CreditCards'){
		document.getElementById('4CreditCards').style.display='none';
	}
	
	if(document.getElementById('5CreditCards')!= null && dont_hide != '5CreditCards'){
		document.getElementById('5CreditCards').style.display='none';
	}

	if(document.getElementById('BoletoBancario')!= null && dont_hide != 'BoletoBancario'){
		document.getElementById('BoletoBancario').style.display='none';
	}	

	$(dont_hide).show();
}

function hide_methods_admin(dont_hide) {
	if(document.getElementById('1CreditCardsOneInstallment')!= null && dont_hide != '1CreditCardsOneInstallment'){
		document.getElementById('1CreditCardsOneInstallment').style.display='none';
	}
	
	if(document.getElementById('1CreditCards')!= null && dont_hide != '1CreditCards'){
		document.getElementById('1CreditCards').style.display='none';
	}

	if(document.getElementById('2CreditCards')!= null && dont_hide != '2CreditCards'){
		document.getElementById('2CreditCards').style.display='none';
	}
	
	if(document.getElementById('3CreditCards')!= null && dont_hide != '3CreditCards'){
		document.getElementById('3CreditCards').style.display='none';
	}

	if(document.getElementById('4CreditCards')!= null && dont_hide != '4CreditCards'){
		document.getElementById('4CreditCards').style.display='none';
	}
	
	if(document.getElementById('5CreditCards')!= null && dont_hide != '5CreditCards'){
		document.getElementById('5CreditCards').style.display='none';
	}

	if(document.getElementById('BoletoBancario')!= null && dont_hide != 'BoletoBancario'){
		document.getElementById('BoletoBancario').style.display='none';
	}	

	$(dont_hide).show();
}

function calculateInstallmentValue(field, num, c, url) {
	var type = $$('input[name="payment\\[method\\]"]:checked').first().value;
	var total = $('baseGrandTotal').value;
	var total_oc = parseFloat(total.replace(',','.'));
	var field_id = type + '_credito_parcelamento_' + num + '_' + c;
	var field_id_new = type + '_new_credito_parcelamento_' + num + '_' + c;
	var rest = '';
	var response = '';
	var vfield = field.value;
	var vfield_oc = parseFloat(vfield.replace(',', '.'));
	
	if(vfield_oc >= total_oc) {
		vfield_oc = total_oc - (total_oc - 0.01);
	}

	if(parseFloat(vfield_oc)) {
		installments(field_id, field_id_new, num, c, vfield_oc, url);

		/* If more than 2 decimals we reduce to 2 */
		$(field).value = (vfield_oc.toFixed(2)).replace('.',',');

		/* If two Credit Cards we can deduct second credit card installments */
		if(type == 'mundipagg_twocreditcards' && num == 2) {
			new_value_oc = (total.replace(',', '.') - vfield_oc).toFixed(2);
			new_value =  String(new_value_oc).replace('.',',');
			
			if(c != 2) {
				if( typeof($$('#mundipagg_twocreditcards_value_2_2')[0]) != 'undefined') {
					$$('#mundipagg_twocreditcards_value_2_2')[0].value = new_value;
				}

				$$('#mundipagg_twocreditcards_new_value_2_2')[0].value = new_value;

				installments('mundipagg_twocreditcards_credito_parcelamento_2_2', 'mundipagg_twocreditcards_new_credito_parcelamento_2_2', num, c, new_value_oc, url);
			}
			
			if(c != 1) {
				if( typeof($$('#mundipagg_twocreditcards_value_2_1')[0]) != 'undefined') {
					$$('#mundipagg_twocreditcards_value_2_1')[0].value = new_value;
				}

				$$('#mundipagg_twocreditcards_new_value_2_1')[0].value = new_value;

				installments('mundipagg_twocreditcards_credito_parcelamento_2_1', 'mundipagg_twocreditcards_new_credito_parcelamento_2_1', num, c, new_value_oc, url);
			}
		}
	}
}

function installments(field, field_new, num, c, val, url) {
	if(!isNaN(parseFloat(val)) && isFinite(val) && val > 0) {
		new Ajax.Request(url + 'mundipagg/standard/installments', {
			method: 'post',
			parameters: {val: val},
			onSuccess: function(response) {
				if (200 == response.status){
					var result = eval("(" + response.responseText + ")");

					var installments = result.qtdParcelasMax;
					var currencySymbol = result.currencySymbol;

					if(installments != null) {
						if(document.getElementById(field) != null) {
							document.getElementById(field).options.length = 0;
						}

						if(document.getElementById(field_new) != null) {
							document.getElementById(field_new).options.length = 0;
						}

						for(var i = 1;i<=installments;i++) {
							var amount = val / i;
							amount = (amount.toFixed(2)).replace('.',',');

							if(i == 1) {
								var label = i + 'x de ' + currencySymbol + amount;
							} else {
								var label = i + 'x de ' + currencySymbol + amount + " sem juros";
							}
							
							if(document.getElementById(field) != null) {
								$(field).options[$(field).options.length] = new Option(label, i);
					    	}

					    	if(document.getElementById(field_new) != null) {
					    		$(field_new).options[$(field_new).options.length] = new Option(label, i);
					    	}
					   	}
					} else {
						if(document.getElementById(field) != null) {
							document.getElementById(field).options.length = 0;
						}
					}
			    }
			},
			onFailure: function(response) {
				alert('Por favor tente novamente!');
			}
		});
	}
}

function check_values() {
	var method = $$('input[name="payment\\[method\\]"]:checked').first().value;
	var type = $$('#mundipagg_type:enabled')[0].value;
	var num = type[0].substring(0, 1);
	var total = ($('baseGrandTotal').value).replace(',','.');
	var total_fields = 0.00;
	var total_fields_new = 0.00;

	for(var i=1;i<=num;i++){
		if(document.getElementById(method+'_value_'+ num +'_'+ i) != null) {
			var fieldv = ($(method+'_value_'+ num +'_'+ i).value).replace(',','.');
			
			total_fields = parseFloat(fieldv) + parseFloat(total_fields);
		}

		if(document.getElementById(method+'_new_value_'+ num +'_'+ i) != null) {
			var fieldv_new = ($(method+'_new_value_'+ num +'_'+ i).value).replace(',','.');

			total_fields_new = parseFloat(fieldv_new) + parseFloat(total_fields_new);	
		}
	}

	if( (Math.abs(total - total_fields) < 0.000001) &&  (Math.abs(total - total_fields_new) < 0.000001) ) {
		return false;
	}
	
	return true;
}

function setCcType(field, code, num, c, issuer)
{
	$$('#' + code + '_' + num + '_' + c + '_cc_type')[0].value = issuer;
	$(code + '_' + num + '_' + c + '_credito_instituicao_' + issuer).checked=true

	field = $(code + '_' + num + '_' + c + '_credito_instituicao_' + issuer);
	
	cc_cid(field, num, c)
}

function checkInstallments(field, url)
{	
	if ($('onestepcheckout-form') == null) {
		params = $('co-payment-form').serialize(true);
	} else {
		params = $('onestepcheckout-form').serialize(true);
	}

	new Ajax.Request(url + 'checkout/onepage/savePayment', {
		method: 'post',
		parameters: params,
		onSuccess: function(response) {
			if (200 == response.status){
				var result = eval("(" + response.responseText + ")");

				new Ajax.Request(url + 'mundipagg/standard/val', {
					method: 'post',
					parameters: params,
					onSuccess: function(response) {
						if (200 == response.status){
							var result = eval("(" + response.responseText + ")");

							$$('tr.grand-total td.value2 span.price')[0].update(result.grandTotal);
						}
					}
				});
			}
		},
		onFailure: function(response) {
			console.log('failed');
		},
		onComplete: function(response) {
			
		}
	});
}
