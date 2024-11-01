var yg_wpp_pt={},yg_wpp_page,yg_wpp_purch={},yg_wpp_product,yg_wpp_tx={},yg_wpp_post,yg_wpp_sr={},yg_wpp_totalpurch,yg_wpp_ordernum=[],yg_wpp_area_code,yg_wpp_city,yg_wpp_region_code,yg_wpp_region_name,yg_wpp_country_code,yg_wpp_country_name,yg_wpp_latitude,yg_wpp_longitude,yg_wpp_browser,yg_wpp_os;
function getDistance(lat1,lon1,lat2,lon2,unit){
	var R = (unit=='km')?6371:3959;
	var dLat = (lat2-lat1)*Math.PI/180;  
	var dLon = (lon2-lon1)*Math.PI/180;   
	var a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLon/2) * Math.sin(dLon/2);   
	var c = 2 * Math.asin(Math.sqrt(a));   
	var d = R * c;
	return d;
}
function checkHasKys(obj,objNeedl){
	var hasKey = false;
	var keys = objNeedl.split(',');
	jQuery.each(keys,function(key,val){
		if(val in obj){
			hasKey = true;
			return false;
		}
	});
	return hasKey;
}
(function($){
	var promises = [],
	bnnrpromises = [],
	popUpShown = false;
	function getAjxBnnrs(){
		if(ssnSet && lclSet){
			$('.wppdiv.wppajaxload').each(function(){
				console.log($(this).attr('id-data'));
				console.log(window['showBn'+$(this).attr('id-data')]());
			});
		}
	}
	function wppShowPop(wpppu){
		var puid = wpppu.data('id'),
		wpppubg = $('.wpp-popup-bg[data-id="'+puid+'"]');
		console.log(wpppu.outerWidth());
		//wpppu.css('margin-left',0 - wpppu.outerWidth(true)/2);
		wpppu.prepend('<div class="wppbnrclosediv"><span class="wppbnrclose"><span class="clAfter"></span><span class="clBefore"></span></span></div>');
		wpppubg.show();
		console.log(wpppu.outerWidth());
		wpppu.css('margin-left',0 - wpppu.outerWidth()/2);
		console.log(wpppu.outerWidth());
		wpppu.fadeIn(800);
		$('.wppbnrclose .clAfter,.wppbnrclose .clBefore').css('background-color',wpppu.css('border-color'));
		wpppu.find('.wppbnrclose').click(function(){
			wpppubg.hide();
			wpppu.hide();
		});
		wpppu.find('.wppbnrclose').hover(function(){
			$(this).find('.clAfter,.clBefore').css('background-color',wpppu.css('color'))
		}, function() {
			$(this).find('.clAfter,.clBefore').css('background-color',wpppu.css('border-color'))
		});
		wppSetCookie('wppPuNoShow'+puid,'no show',parseInt(ygwpp.pu_cookie));
	}
	function wppShowInlinePop(){
		if($('.wppdiv.wppajaxload.wpp-popup-bnnr').length==0 && !popUpShown){
			$('.wppdiv.wpp-popup-bnnr').each(function(){
				if(!popUpShown && !wppGetCookie('wppPuNoShow'+$(this).data('id'))){
					wppShowPop($(this));
					popUpShown = true;
					return false;
				}
			});
		}
	}
	function wppGetCookie(name){
		var hasCk = false,
		name = name+"=",
		cks = document.cookie.split(';');
		for(var i=0; i<cks.length; i++){
			var c = $.trim(cks[i]);
			if (c.indexOf(name) == 0){
				return c.substring(name.length,c.length);
			}
		}
		return hasCk;	
	}
	function wppSetCookie(name,val,exdays){
		var now = (new Date()).getTime(),
		exDate = (new Date()).setTime(now+(exdays*24*60*60*1000)),
		expires = (new Date(exDate)).toUTCString();
		document.cookie = name+'='+val+'; '+(exdays != 0 ? 'expires='+expires+'; ' : '')+'path=/';				
	}
	$(document).ready(function() {
		var lclObj = ('undefined' != typeof localStorage.ygwpp)?JSON.parse(localStorage.getItem('ygwpp')):{};
		if('undefined' != typeof ygwpp.type && 'undefined' != typeof ygwpp.page_id){
			lclObj[ygwpp.type] = lclObj[ygwpp.type] || {};
			lclObj[ygwpp.type][ygwpp.page_id] = ('undefined' != typeof lclObj[ygwpp.type][ygwpp.page_id])?parseInt(lclObj[ygwpp.type][ygwpp.page_id])+1:1;

			if('undefined' != typeof ygwpp.ptype)
				lclObj[ygwpp.ptype] = ('undefined' != typeof lclObj[ygwpp.ptype])?parseInt(lclObj[ygwpp.ptype])+1:1;
			var newOrder = ('undefined' != typeof ygwpp.ordernum && ('undefined' == typeof lclObj['ordernum'] || lclObj['ordernum'].indexOf(ygwpp.ordernum)==-1));
			if('undefined' != typeof ygwpp.purch && newOrder){
				$.each(ygwpp.purch,function(key,val){
					lclObj['purch'] = lclObj['purch'] || {};
					lclObj['purch'][key] = ('undefined' != typeof lclObj['purch'][key])?parseInt(lclObj['purch'][key])+parseInt(val):parseInt(val);
				});
				if('undefined' != typeof ygwpp.totalpurch)
					lclObj['totalpurch'] = ('undefined' != typeof lclObj['totalpurch'])?parseFloat(lclObj['totalpurch'])+parseFloat(ygwpp.totalpurch):parseFloat(ygwpp.totalpurch);
				lclObj['ordernum'] = lclObj['ordernum'] || [];
				lclObj['ordernum'].push(ygwpp.ordernum);
			}

			localStorage.setItem('ygwpp',JSON.stringify(lclObj));
			var ssnSet = $.each(lclObj,function(key,val){
				if('string' == typeof val)
					val = val.toLowerCase();
				window['yg_wpp_'+key] = val;
			});
			promises.push(ssnSet);
		}
		if('undefined' == typeof sessionStorage.ygwppssn){
			ssnObj={
				ip:ygwpp.ip,
				os:ygwpp.os,
				browser:ygwpp.browser
			};
			if('undefined' != typeof ygwpp.ip){
				var lclSet = $.ajax({
					url:ygwpp.ajx,
					data:{
						ip:ygwpp.ip,
						_wpnonce:ygwpp.nn,
						action:'yg_loc_data'
					},
					type:"POST",
					dataType:"json",
					success:function(data){
						ssnObj['area_code'] = yg_wpp_area_code = data.geoplugin_areaCode;
						ssnObj['city'] = yg_wpp_city = data.geoplugin_city;
						ssnObj['region_code'] = data.geoplugin_regionCode;
						ssnObj['region_name'] = data.geoplugin_regionName;
						ssnObj['country_code'] = data.geoplugin_countryCode;
						ssnObj['country_name'] = data.geoplugin_countryName;
						ssnObj['latitude'] = data.geoplugin_latitude;
						ssnObj['longitude'] = data.geoplugin_longitude;
						sessionStorage.setItem('ygwppssn',JSON.stringify(ssnObj));
					}
				});
				promises.push(lclSet);
			}
		}else{
			yg_wpp_os = ygwpp.os.toLowerCase();
			yg_wpp_browser = ygwpp.browser.toLowerCase();
			var ssnStrObj = JSON.parse(sessionStorage.getItem('ygwppssn'));
			var lclSet = $.each(ssnStrObj,function(key,val){
				window['yg_wpp_'+key] = val.toLowerCase();
			});
			promises.push(lclSet);
		}
		$.when.apply($, promises).done(function(){
			$('.wppdiv.wppajaxload').each(function(){
				var curDiv = $(this);
				if(window['showBn'+curDiv.data('id')]()){
					var bnnrSet = $.ajax({
						url:ygwpp.ajx,
						data:{
							id:curDiv.data('id'),
							_wpnonce:ygwpp.nn,
							action:'yg_display_bnnr'
						},
						type:"POST",
						dataType:"json",
						success:function(data){
							curDiv.attr('style',data.style);
							curDiv.html(data.cntnt);
							console.log(curDiv.data('id'));
							if(curDiv.hasClass('wpp-popup-bnnr')){
								if(!popUpShown && !wppGetCookie('wppPuNoShow'+curDiv.data('id'))){
									wppShowPop(curDiv);
									popUpShown = true;
								}else{
									curDiv.hide();
									curDiv.removeClass('wpp-popup-bnnr').addClass('wpp-popup-bnnr-notshow');
									wppShowInlinePop();
								}
							}
						}
					});
					bnnrpromises.push(bnnrSet);
				}else if(curDiv.hasClass('wpp-popup-bnnr')){
					curDiv.removeClass('wpp-popup-bnnr').addClass('wpp-popup-bnnr-notshow');
				}
			});
		});
		if($('#wpp-top-banner').length)
			$.when.apply($, bnnrpromises).done(function(){$('body').css('padding-top',$('#wpp-top-banner').outerHeight());$('html').css('margin-top',0);});
		wppShowInlinePop();
	});
})(jQuery);