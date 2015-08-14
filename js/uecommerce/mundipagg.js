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

function selectCredcard(ele){
    var check = checkCredcardType(ele.value);

    ele.up(2).previous().select('.cc_brand_types').each(function(el) {
        el.removeClassName('active');
    });
    var id = ele.id;

    var realId = id
        .replace('mundipagg_creditcard','')
        .replace('mundipagg_twocreditcards_','')
        .replace('mundipagg_threecreditcards_','')
        .replace('mundipagg_fourcreditcards_','')
        .replace('mundipagg_fivecreditcards_','')
        .replace('_cc_number','');

    var cardType = id.replace('mundipagg_','').replace('_cc_number','').replace('_'+realId,'');
    if(check){
        var parentElement = ele.up(2).previous().select('li');

        parentElement.each(function(element){
            var inpt = element.select('input')[0];

            if( inpt.value == check){
                inpt.click();
                inpt.previous().addClassName('active');
                if(window.isInstallmentsEnabled) {


                    if (window[realId] != check) {
                        window[realId] = check;
                        window['brand_' + realId] = check;
                        selects = ele.up(3).select('select');
                        selects.each(function (select) {
                            if (select.name.indexOf('parcelamento') != -1) {
                                installmentElement = select;
                            }
                        });
                        if (window['installmentElement'] != undefined) {
                            window['select_html_' + realId] = installmentElement.innerHTML;
                            window['select_' + realId] = installmentElement;

                            if ($('mundipagg_' + cardType + '_new_value_' + realId) != undefined) {
                                if ($('mundipagg_' + cardType + '_new_value_' + realId).value == '') {
                                    updateInstallments(check, installmentElement);
                                } else {
                                    updateInstallments(check, installmentElement, $('mundipagg_' + cardType + '_new_value_' + realId).value);
                                }
                            } else {
                                updateInstallments(check, installmentElement);
                            }
                        }
                    }


                }
            }
        });

    }else{
        if (window[realId] != undefined) {
            window[realId] = undefined;
            if ($('mundipagg_'+cardType+'_new_value_' + realId) != undefined) {
                totalValue = $('mundipagg_'+cardType+'_new_value_' + realId).value;
            } else {
                totalValue = '';
            }
            if (window['select_' + realId] != undefined && totalValue == '') {
                window['select_' + realId].innerHTML = window['select_html_' + realId];
            } else {
                if (window.installmentElement !== undefined) {
                    updateInstallments(0, installmentElement, $('mundipagg_'+cardType+'_new_value_' + realId).value);
                }
            }
        }
    }



}

/**
 * Javascript method based on helper Mundipagg: https://github.com/mundipagg/mundipagg-one-php/blob/master/lib/One/Helper/CreditCardHelper.php
 * 
 * @param string cardNumber Número do cartão
 * @return string Bandeira do cartão
 */
function checkCredcardType(cardNumber){
    var flag = false;
    // Extrai somente números do cartão (Note: Not only draw numbers, letters as well).
    cardNumber = cardNumber.toString().replace(/[^0-9a-zA-Z ]/g,'');
    
    if(inArray(cardNumber.substring(0,6),['401178', '504175', '509002', '509003', '438935', '457631', '451416', '457632', '506726', '506727', '506739', '506741', '506742', '506744', '506747', '506748', '506778', '636297', '636368', '637095'])){
        flag = 'EL';
    }
    else if(cardNumber.substring(0,4) == '6011' || cardNumber.substring(0,3) == '622' || inArray(cardNumber.substring(0,2), ['64', '65'])){
        // Flag not implemented in the module yet.
        flag = 'discover';
    }
    else if(inArray(cardNumber.substring(0,3), ['301', '305']) || inArray(cardNumber.substring(0,2),['36', '38'])){
        flag = 'DI';
    }
    else if(inArray(cardNumber.substring(0,2), ['34', '37'])){
        flag = 'AE';
    }
    else if(cardNumber.substring(0,2) == '50'){
        // Flag not implemented in the module yet.
        flag = 'aura';
    }
    else if(inArray(cardNumber.substring(0,2),['38', '60'])){
        flag = 'HI';
    }
    else if(cardNumber[0] == '4'){
        flag = 'VI';
    }
    else if(cardNumber[0] == '5'){
        flag = 'MC';
    }
    
    
    console.log(flag);
    return flag;
}

/**
 * Javascript method inArray equivalent PHP
 */
function inArray(neddle, arraySearch){
    return arraySearch.filter(function(item){return item == neddle;}).length;
}

/**
 * Old function.
 */

//function checkCredcardType(cardNumber){
//    var regexVisa = /^4[0-9]{12}([0-9]{3})?$/;
//    var regexMaster = /^5[1-5][0-9]{14}/;
//    var regexAmex = /^3[47][0-9]{13}/;
//    var regexDiners = /^3(?:0[0-5]|[68][0-9])[0-9]{11}/;
//    var regexElo = /^((((636368)|(438935)|(504175)|(451416)|(636297)|(506717)|(506718)|(506733)|(506739)|(506741)|(506742)|(506743)|(506753)|(506774)|(506776)|(506778)|(509003)|(509004)|(509005)|(509006)|(509007)|(509008)|(509009)|(509013)|(509020)|(509021)|(509022)|(509023)|(509024)|(509025)|(509026)|(509027)|(509028)|(509029)|(509031)|(509033)|(509035)|(509038)|(509039)|(509040)|(509041)|(509042)|(509043)|(509044)|(509045)|(509046)|(509047)|(509048)|(509049)|(509050)|(509051)|(509052)|(509053)|(509064)|(636297)|(431274)|(506724)|(509077)|(509078)|(509079)|(509080)|(506720)|(506721)|(506724)|(506725)|(506726)|(506727)|(506728)|(506729)|(506730)|(506731)|(506732)|(506740)|(506744)|(506745)|(506746)|(506747)|(506748)|(506775)|(506777)|(509000)|(509001)|(509002)|(509066)|(509067)|(509068)|(509069)|(509072)|(509074)|(636368)|(504175)|(438935)|(401179)|(401178)|(457631)|(457632)|(457393)|(506720)|(506721)|(509076)|(509081)|(509082)|(509083)|(509085)|(509086))\d{0,10})|((5067)|(4576)|(4011))[0-9]\d{0,12})$/;
//    var regexHipercard = /^((606282[0-9]{10})|(3841[0-9]{0,15}))/;
//    //var regexDiscover = /^6(?:011|5[0-9]{2})[0-9]{12}/;
//    //var regexJCB = /^(?:2131|1800|35\d{3})\d{11}/;
//
//    var flag = false;
//    
//    if(regexVisa.test(cardNumber)){
//        flag = 'VI';
//    }
//    if(regexMaster.test(cardNumber)){
//        flag = 'MC';
//    }
//    if(regexAmex.test(cardNumber)){
//       flag = 'AE';
//    }
//    
//    if(regexDiners.test(cardNumber)){
//        flag = 'DI';
//    }
//    
//    if(regexHipercard.test(cardNumber)){
//        flag = 'HI';
//    }
//    /*if(regexDiscover.test(cardNumber)){
//        return 'discover';
//    }*/
//    /*if(regexJCB.test(cardNumber)){
//        return 'jcb';
//    }*/
//    if(regexElo.test(cardNumber)){
//        flag = 'EL';
//    }
//
//    
//
//    return flag;
//}

function updateInstallments(ccType, element ,total){

    if(window['admin_area_url'] == undefined) {
        var url = window.baseUrl + '/mundipagg/standard/installmentsandinterest';
    }else{
        var url = window['admin_area_url'];
    }

    if(!total){
        total=0;
    }
    if(window.ajax_loader_mundipagg_img != undefined){
        mundipagg_img = window.ajax_loader_mundipagg_img;
    }else{
        mundipagg_img = window.baseUrl+'/skin/frontend/base/default/images/mundipagg/ajax-loader.gif';
    }
    loading = new Element('img', {src:mundipagg_img});
    loading.addClassName('mundipagg_reload');
    element.insert({
        'after':loading
    });
    element.options.length = 0;

    var id = element.id.replace('_new_credito_parcelamento','')+'_cc_number';
    new Ajax.Request(url,{
        method:'post',
        parameters:{cctype:ccType,total:total},
        onSuccess:function(response){

            var res = JSON.parse(response.responseText);

            if(res['installments'] != undefined){

                i=0;
                for(key in res.installments){
                    element.options[i] = new Option(res.installments[key],key,false,false);
                    i++;
                }
                if(res['brand'] != undefined) {
                    window['brand_' + id.replace('mundipagg_twocreditcards_', '').replace('_cc_number', '')] = res.brand;
                }
            }else{
                window['select_'+id].innerHTML = window['select_html_'+id];
                window['brand_'+id.replace('mundipagg_twocreditcards_','').replace('_cc_number','')] = undefined;
            }
            $$('.mundipagg_reload')[0].remove();
            if($('order-billing_method') != undefined){

                var data = {};
                this.loadArea(['totals'], true, data);

            }
        }
    });

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
	Validation.add('validar_cpf', 'The taxvat is invalid', function(v){return validaCPF(v,0);});

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

	Validation.add('check_values', 'Check the values to pass on each card', function(){return check_values();});

    Validation.add('validate-cc-exp-cus', 'Expiration date of the incorrect card', function(v,elm){return verify_cc_expiration_date(v,elm);});
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

function token_or_not(num,c,field) {

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

		/*if(active == 1) {
			$('parcelamento_'+num+'_'+c).hide();
		}*/
        if($('parcelamento_'+num+'_'+c) != null) {
            $('parcelamento_' + num + '_' + c).hide();
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

        if(document.getElementById(type+'_new_credito_parcelamento_'+num+'_'+c)!= null) {
            $(type+'_new_credito_parcelamento_'+num+'_'+c).disable();
        }

		if(document.getElementById(type+'_new_value_'+num+'_'+c)!= null) {
			$(type+'_new_value_'+num+'_'+c).disable();
		}

		// Hide new credit card fields
		$(type+'_new_credit_card_'+num+'_'+c).hide();

		/*if(active == 1) {
			$('parcelamento_'+num+'_'+c).show();
		}*/
        if($('parcelamento_'+num+'_'+c) != null) {
            $('parcelamento_' + num + '_' + c).show();
        }
        
		if(document.getElementById('value_'+num+'_'+c)!= null) {
			$('value_'+num+'_'+c).show();
		}
        group = '.'+field.readAttribute('data');

        field.select('option').each(function(opt){
            if(opt.value == field.value){
                if(opt.readAttribute('data')){
                    grandTotal = 0;

                    fieldValue = field.up(3).select(group+'.check_values')[0];
                    if(fieldValue != undefined) {
                        grandTotal = fieldValue.value;
                    }
                    if(field.up(3).select(group+'.installment-token')[0] != undefined) {
                        updateInstallments(opt.readAttribute('data'), field.up(3).select(group + '.installment-token')[0], grandTotal);
                    }
                }
            }
        });
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
        if($('partial') != undefined){
            total = String(quoteBaseGrandTotal);
        }
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
		//installments(field_id, field_id_new, num, c, vfield_oc, url);
        selects = field.up(3).select('select');

        selects.each(function(select){
            if(select.name.indexOf('parcelamento') != -1){
                installmentElement = select;
            }
        });

        var id = field.id;
        var realId = id
            .replace('mundipagg_creditcard','')
            .replace('mundipagg_twocreditcards_','')
            .replace('mundipagg_threecreditcards_','')
            .replace('mundipagg_fourcreditcards_','')
            .replace('mundipagg_fivecreditcards_','')
            .replace('_cc_number','')
            .replace('value_','');
        window['brand_'+realId] = undefined;

        if($('parcelamento_'+realId) != undefined){

            installmentElement = $('parcelamento_'+realId).select('select')[0];
            field.up(3).previous().select('.tokens')[0].select('option').each(function(opt){
                if(opt.selected){
                    window['brand_'+realId] = opt.readAttribute('data');
                    //console.log(opt.readAttribute('data'));
                }
            });


        }

        var cardType = id.replace('mundipagg_','').replace('_cc_number','').replace('_'+realId,'');

        if(window['installmentElement'] != undefined) {


            window['select_html_' + realId] = installmentElement.innerHTML;
            window['select_' + realId] = installmentElement;
            if (window['brand_' + realId] != undefined) {
                updateInstallments(window['brand_' + realId], installmentElement, vfield_oc);
            } else {
                var brand = field.up(3).select('.cc_brand_types.active')[0];
                if (brand != undefined) {
                    window['brand_' + realId] = brand.next().value;
                    updateInstallments(window['brand_' + realId], installmentElement, vfield_oc);
                    console.log(window['brand_' + realId]);
                } else {
                    updateInstallments(0, installmentElement, vfield_oc);
                }

            }
        }
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
                selects = $$('#mundipagg_twocreditcards_new_value_2_2')[0].up(3).select('select');
                installmentElement = undefined;
                selects.each(function(select){
                    if(select.name.indexOf('parcelamento') != -1){
                        installmentElement = select;
                    }
                });
                if($('parcelamento_2_2') != undefined && window['installmentElement'] == undefined){
                    installmentElement = $('parcelamento_2_2').select('select')[0];

                }
                if($('parcelamento_2_2') != undefined){
                    $('mundipagg_twocreditcards_token_2_2').select('option').each(function(opt){
                        if(opt.selected){
                            window['brand_2_2'] = opt.readAttribute('data');

                        }
                    });
                }

                if(window['installmentElement'] != undefined) {
                    window['select_html_2_2'] = installmentElement.innerHTML;
                    window['select_2_2'] = installmentElement;

                    if (window['brand_2_2'] != undefined) {
                        updateInstallments(window['brand_2_2'], installmentElement, new_value);

                        if ($('parcelamento_2_2') != undefined) {

                            updateInstallments(window['brand_2_2'], $('parcelamento_2_2').select('select')[0], new_value);
                        }
                    } else {

                        updateInstallments(0, installmentElement, new_value);
                        if ($('parcelamento_2_2') != undefined) {
                            updateInstallments(0, $('parcelamento_2_2').select('select')[0], new_value);
                        }
                    }
                }

				//installments('mundipagg_twocreditcards_credito_parcelamento_2_2', 'mundipagg_twocreditcards_new_credito_parcelamento_2_2', num, c, new_value_oc, url);
			}
			
			if(c != 1) {
				if( typeof($$('#mundipagg_twocreditcards_value_2_1')[0]) != 'undefined') {
					$$('#mundipagg_twocreditcards_value_2_1')[0].value = new_value;
				}

				$$('#mundipagg_twocreditcards_new_value_2_1')[0].value = new_value;
                selects = $$('#mundipagg_twocreditcards_new_value_2_1')[0].up(3).select('select');
                installmentElement = undefined;
                selects.each(function(select){
                    if(select.name.indexOf('parcelamento') != -1){
                        installmentElement = select;
                    }
                });

                if($('parcelamento_2_1') != undefined && window['installmentElement'] == undefined){
                    installmentElement = $('parcelamento_2_1').select('select')[0];

                }
                if($('parcelamento_2_1') != undefined){
                    $('mundipagg_twocreditcards_token_2_1').select('option').each(function(opt){
                        if(opt.selected){
                            window['brand_2_1'] = opt.readAttribute('data');
                        }
                    });
                }

                if(window['installmentElement'] != undefined) {
                    window['select_html_2_1'] = installmentElement.innerHTML;
                    window['select_2_1'] = installmentElement;

                    if (window['brand_2_1'] != undefined) {
                        updateInstallments(window['brand_2_1'], installmentElement, new_value);
                        if ($('parcelamento_2_1') != undefined) {

                            updateInstallments(window['brand_2_1'], $('parcelamento_2_1').select('select')[0], new_value);
                        }
                    } else {
                        updateInstallments(0, installmentElement, new_value);
                        if ($('parcelamento_2_1') != undefined) {
                            updateInstallments(0, $('parcelamento_2_1').select('select')[0], new_value);
                        }
                    }
                }

				//installments('mundipagg_twocreditcards_credito_parcelamento_2_1', 'mundipagg_twocreditcards_new_credito_parcelamento_2_1', num, c, new_value_oc, url);
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

function setTotalInterestHtml(field){

    var container = field.up(5);
    var totalFieldElement = container.select('div')[0];
    var totalFieldValue = parseFloat(totalFieldElement.innerHTML.replace(/\s/g, "").replace('<b>ValorTotal:</b>','').replace('R$','').replace('.','').replace(',','.'));
    var containerSelects = container.select('select');
    var template = '<div class="total_juros">'+Translator.translate('Total amount with interest: USD{%%%}')+'</div>';
    var totalInterest = 0;
    containerSelects.each(function(select){
        if(select.readAttribute('id').indexOf('credito_parcelamento') != -1){
            if(window.getComputedStyle(select.up(3)).getPropertyValue('display') == 'block'){
                var selectedText = select.options[select.selectedIndex].text;
                if(selectedText.indexOf('Total') != -1){
                    realCurrentInterest = 0;
                    newTotalInterest = parseFloat(selectedText.substring(selectedText.indexOf('Total'), selectedText.length).replace('Total: R$','').replace(')','').replace(/\s/g, "").replace('.','').replace(',','.'));
                    if($(select.readAttribute('id').replace('credito_parcelamento','value')) != undefined) {
                        currentValueField = parseFloat($(select.readAttribute('id').replace('credito_parcelamento', 'value')).value.replace('.','').replace(',', '.'));
                    }else{
                        currentValueField = NaN;
                    }

                    if(!isNaN(currentValueField)){
                        realCurrentInterest = parseFloat(newTotalInterest - currentValueField );
                        totalInterest = parseFloat(totalInterest + parseFloat(realCurrentInterest));
                    }else{
                        realCurrentInterest = parseFloat(newTotalInterest - totalFieldValue);
                        totalInterest = parseFloat(totalInterest + parseFloat(realCurrentInterest));
                    }


                }
            }

        }
    });

    if(Object.keys(totalFieldElement.select('.total_juros')).length){
        totalFieldElement.select('.total_juros')[0].remove();
    }

    if(totalInterest > 0){
        var strNumber = parseFloat(totalFieldValue + totalInterest).toFixed(2);
        while (strNumber.match(/^\d{4}/)){
            strNumber = strNumber.replace(/(\d)(\d{3}(\.|$))/, '$1.$2');
        }
        strNumber = strNumber.substring(0,parseInt(strNumber.length - 3))+','+strNumber.substring(parseInt(strNumber.length - 2), strNumber.length);
        totalFieldElement.insert(template.replace('{%%%}',strNumber));
    }

}

function checkInstallments(field, url)
{

	if ($('onestepcheckout-form') == null) {
		params = $('co-payment-form').serialize(true);
	} else {
		params = $('onestepcheckout-form').serialize(true);
	}

    if(window['mundipaggTotalInterest'] == undefined){
        window.mundipaggTotalInterest = 0;
    }



    setTotalInterestHtml(field);




	new Ajax.Request(url + 'checkout/onepage/savePayment', {
		method: 'post',
		parameters: params,
		onSuccess: function(response) {
			if (200 == response.status){
				var result = eval("(" + response.responseText + ")");

                console.log(result);
				/*new Ajax.Request(url + 'mundipagg/standard/val', {
					method: 'post',
					parameters: params,
					onSuccess: function(response) {
						if (200 == response.status){
							var result = eval("(" + response.responseText + ")");
                            console.log(result);
							$$('tr.grand-total td.value2 span.price')[0].update(result.grandTotal);
						}
					}
				});*/
			}
		},
		onFailure: function(response) {
			console.log('failed');
		},
		onComplete: function(response) {
			
		}
	});
}

checkCredcardType('38897722756041');