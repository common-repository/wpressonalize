(function($){
	var conBlock = '';
	function build_con_block(data){
		$.each(data,function(key,con){
			if('undefined' != typeof con['type'] && con['type']=='block'){
				conBlockHdr = get_con_header(con);
				conBlock.append(conBlockHdr);
			}
		});
	}
	function get_con_header(data){
		var slct1 = '',slct2 = '';
		if(data['operator']=='or' || data['operator']=='')
			slct1 = ' selected="selected"';
		else
			slct2 = ' selected="selected"';
		var s = $('<select/>');
		s.attr('name','operator')
		s.append('<option value="or"'+slct1+'>any</option>');
		s.append('<option value="and"'+slct2+'>all</option>');
		return 'If '+s+' of these conditions are true'
	}
	function addRowSlct(name){
		var d = $('<div/>');
		d.attr('class','row_div');
		var s = $('<select/>');
		s.attr('name','row_type');
		s.attr('class','row_type');
		s.append('<option value="">Add...</option>');
		if('undefined' != typeof wpp_js.con_options){
			$.each(wpp_js.con_options,function(key,val){
				s.append('<option value="'+key+'">'+val+'</option>');
			});
		}
		d.append(s);
		return d;
	}
	function addConRow(which,plus){
		var blocks = (!isNaN(parseInt(plus.parents('.block').children('.tp-new').length)))?parseInt(plus.parents('.block').children('.tp-new').length):0;
		var conds = (!isNaN(parseInt(plus.parents('.block').first().children('.rowdiv').length)))?parseInt(plus.parents('.block').first().children('.rowdiv').length):0;
		var indxDate = (!isNaN(parseInt(plus.parents('.block').first().children('.rowdiv').last().attr('indx-data'))+1))?parseInt(plus.parents('.block').first().children('.rowdiv').last().attr('indx-data'))+1:0;
		var index = Math.max(blocks+conds,indxDate);
		if(isNaN(index))
			index=0;
		if(which=='block')
			addConRowBlock(plus,index);
		else if('undefined' != typeof wpp_js.con_sub_options && 'undefined' != typeof wpp_js.con_sub_options[which])
			addConRowLoc(plus,index,which);
	}
	function addConRowBlock(plus,index){
		var wrp = $('<div/>');
		wrp.attr('class','tp-new');

		var rmv = $('<div/>');
		rmv.attr('class','remove_row');
		rmv.text('x');
		
		var d = $('<div/>');
		d.attr('class','block rowdiv');
		d.attr('indx-data',index);

		var i = $('<input/>');
		i.attr('type','hidden');
		i.attr('name',plus.attr('name-data')+'['+index+'][type]');
		i.attr('value','operator');

		var s = $('<select/>');
		s.attr('name',plus.attr('name-data')+'['+index+'][val]');
		s.append('<option value="or">any</option>');
		s.append('<option value="and">all</option>');

		var dp = $('<div/>');
		dp.attr('class','add_row');
		dp.attr('name-data',plus.attr('name-data')+'['+index+']');
		dp.text('+');

		wrp.append(i);
		wrp.append('If ');
		wrp.append(s);
		wrp.append(' of these conditions are true ');
		wrp.append(rmv);
		d.append(dp);
		wrp.append(d);
		plus.before(wrp);
	}
	function addConRowLoc(plus,index,type){
		var d = $('<div/>');
		d.attr('class','rowdiv tp-'+type);
		d.attr('indx-data',index);
		var i = $('<input/>');
		i.attr('type','hidden');
		i.attr('name',plus.attr('name-data')+'['+index+'][type]');
		i.attr('value',type);
		var s = $('<select/>');
		s.attr('name',plus.attr('name-data')+'['+index+'][desc]');
		s.attr('class','con_desc_dd');
		var firstkey = '';
		var j=0;
		$.each(wpp_js.con_sub_options[type],function(key,val){
			s.append('<option value="'+key+'">'+val['desc']+'</option>');
			if(j==0)
				firstkey=key;
			j++;
		});

		var dr = $('<div class="remove_row">x</div>');

		d.append(i);
		d.append('Visitor ');
		d.append(s);
		var j=0;
		$.each(wpp_js.con_sub_options[type][firstkey]['placeholder'],function(k,value){
			var valname = (j==0)?'val':'val'+j;
			var iv = $('<input/>');
			iv.attr('type','text');
			iv.attr('name',plus.attr('name-data')+'['+index+']['+valname+']');
			iv.attr('value','');
			iv.attr('placeholder',value);
			d.append(iv);
			j++;
		});
		
		d.append(dr);
		plus.before(d);
	}
	function addComment(val,id){
		switch(val){
			case 'mn':
				$('#pos_select_comment').html('copy and paste this line in your template <strong>&lt;?php if (method_exists(\'wpressonalize\',\'displayBnners\')) echo wpressonalize::displayManualBnners('+id+'); ?&gt;</strong>');
				return;
			case 'sc':
				$('#pos_select_comment').html('copy and paste this line in your post <strong>[wpp_block id="'+id+'"]</strong>');
				return;
			default:
				$('#pos_select_comment').html('');
				return;
		}
	}
	$(document).ready(function() {
		if($('#wppb_onpost').length && $('#wppb_onpost').hasClass('wpp_init')){
			var data = {
				ids:$('#wppb_onpost_old').val(),
				_wpnonce:$('#yg_postslct').attr('flt_post_nonce'),
				action:'yg_wpp_pos_get_ttls'
			}
			if($('#wppb_onpost_old').val()!=''){
				$.ajax({
					url:ajaxurl,
					data:data,
					type:"POST",
					dataType:"json",
					success:function(data){
						var dd = $('#wppb_onpost');
						$.each(data,function(key,val){
							dd.find('option[value='+key+']').text(val);
						});
						dd.SumoSelect({csvDispCount:3,search:true,searchText:'Search'});
					}
				});
			}
		}
		if($('#wppb_onpage').length && $('#wppb_onpage').hasClass('wpp_init')){
			var data = {
				ids:$('#wppb_onpage_old').val(),
				_wpnonce:$('#yg_pageslct').attr('flt_page_nonce'),
				action:'yg_wpp_pos_get_ttls'
			}
			if($('#wppb_onpage_old').val()!=''){
				$.ajax({
					url:ajaxurl,
					data:data,
					type:"POST",
					dataType:"json",
					success:function(data){console.log(data);
						var dd = $('#wppb_onpage');
						$.each(data,function(key,val){
							dd.find('option[value='+key+']').text(val);
						});
						dd.SumoSelect({csvDispCount:3,search:true,searchText:'Search'});
					}
				});
			}
		}
		if($('#wppb_ontax').length && $('#wppb_ontax').hasClass('wpp_init')){
			var data = {
				ids:$('#wppb_ontax_old').val(),
				_wpnonce:$('#yg_taxslct').attr('flt_tax_nonce'),
				action:'yg_wpp_pos_get_tax'
			}
			if($('#wppb_ontax_old').val()!=''){
				$.ajax({
					url:ajaxurl,
					data:data,
					type:"POST",
					dataType:"json",
					success:function(data){
						var dd = $('#wppb_ontax');
						$.each(data,function(key,val){
							dd.find('option[value='+key+']').text(val);
						});
						dd.SumoSelect({csvDispCount:3,search:true,searchText:'Search'});
					}
				});
			}
		}

        $('#yg_postslct').click(function(e){
        	e.preventDefault();
        	if(!$('#wppb_onpost').length)
        		$('.wpp_loc_set.set_post').append('<select name="wppb_onpost[]" id="wppb_onpost" class="wpp_loading search-box SumoUnder sumo" multiple="multiple" placeholder="Select post page"></select>');
        	var dd = $('#wppb_onpost');
        	var data = {};
        	if($('#yg_postslct_type').val())
        		data['typ'] = $('#yg_postslct_type').val();
        	if($('#yg_postslct_cat').val())
        		data['cat'] = $('#yg_postslct_cat').val();
        	if($('#yg_postslct_ecat').val())
        		data['ecat'] = $('#yg_postslct_ecat').val();
        	if($('#yg_postslct_ttl').val())
        		data['ttl'] = $('#yg_postslct_ttl').val();
        	if($('#wppb_onpost_old').length)
        		data['exc'] = $('#wppb_onpost_old').val();
        	data['_wpnonce'] = $(this).attr('flt_post_nonce');
        	data['action'] = 'yg_wpp_pos_get_pst';
        	$.ajax({
				url:ajaxurl,
				data:data,
				type:"POST",
				dataType:"json",
				success:function(data){
					if('undefined' != typeof dd[0].sumo)
						dd[0].sumo.unload();
					dd.find('option').not(':selected').remove();
					dd.SumoSelect({csvDispCount:3,search:true,searchText:'Search',selectAll:true});
					$.each(data,function(key,val){
						//dd.append('<option value="'+key+'">'+val+'</option>');
						dd[0].sumo.add(key,val,dd.find('option').length);
					})
					dd.removeClass('wpp_loading');
				}
			});
        });
		$('#yg_pageslct').click(function(e){
        	e.preventDefault();
        	if(!$('#wppb_onpage').length)
        		$('.wpp_loc_set.set_page').append('<select name="wppb_onpage[]" id="wppb_onpage" class="wpp_loading search-box SumoUnder sumo" multiple="multiple" placeholder="Select page"></select>');
        	var dd = $('#wppb_onpage');
        	var data = {
        		typ:['page'],
        		eafter:'post',
        		_wpnonce:$(this).attr('flt_page_nonce'),
        		action:'yg_wpp_pos_get_pst'
        	};
        	if($('#wppb_onpage_old').length)
        		data['exc'] = $('#wppb_onpage_old').val();
        	$.ajax({
				url:ajaxurl,
				data:data,
				type:"POST",
				dataType:"json",
				success:function(data){
					if('undefined' != typeof dd[0].sumo)
						dd[0].sumo.unload();
					dd.find('option').not(':selected').remove();
					dd.SumoSelect({csvDispCount:3,search:true,searchText:'Search',selectAll:true});
					$.each(data,function(key,val){
						//dd.append('<option value="'+key+'">'+val+'</option>');
						dd[0].sumo.add(key,val,dd.find('option').length);
					})
					dd.removeClass('wpp_loading');
				}
			});
        });
		$('#yg_taxslct').click(function(e){
        	e.preventDefault();
        	if(!$('#wppb_ontax').length)
        		$('.wpp_loc_set.set_tax').append('<select name="wppb_ontax[]" id="wppb_ontax" class="wpp_loading search-box SumoUnder sumo" multiple="multiple" placeholder="Select taxonomy"></select>');
        	var dd = $('#wppb_ontax');
        	var data = {
        		_wpnonce:$(this).attr('flt_tax_nonce'),
        		action:'yg_wpp_pos_get_tax'
        	};
        	if($('#yg_txslct').val())
        		data['taxs'] = $('#yg_txslct').val();
        	if($('#wppb_ontax_old').length)
        		data['exc'] = [$('#wppb_ontax_old').val()];
        	$.ajax({
				url:ajaxurl,
				data:data,
				type:"POST",
				dataType:"json",
				success:function(data){
					if('undefined' != typeof dd[0].sumo)
						dd[0].sumo.unload();
					dd.find('option').not(':selected').remove();
					dd.SumoSelect({csvDispCount:3,search:true,searchText:'Search',selectAll:true});
					$.each(data,function(key,val){
						//dd.append('<option value="'+key+'">'+val+'</option>');
						dd[0].sumo.add(key,val,dd.find('option').length);
					})
					dd.removeClass('wpp_loading');
				}
			});
        });
		$('#wpp_con_meta').on('click','.add_row',function(){
			var slct = addRowSlct();
			$(this).before([slct]);
			$(this).hide();
		});
		$('#wpp_con_meta').on('click','.remove_row',function(){
			$(this).parent().remove();
		});
		$('#wpp_con_meta').on('change','.row_type',function(){
			var plus = $(this).parent().siblings('.add_row');
			addConRow($(this).val(),plus);
			$(this).hide();
			plus.show();
		});
		$('#wpp_con_meta').on('change','.con_desc_dd',function(){
			var type = $(this).prev().val();
			var name = $(this).attr('name').replace('[desc]','');
			var consub = $(this).val();
			var closeBtn = $(this).parent().find('.remove_row');
			$(this).siblings('input[type="text"]').remove();
			console.log(wpp_js.con_sub_options[type][consub]);
			var j=0;
			$.each(wpp_js.con_sub_options[type][consub]['placeholder'],function(k,value){
				var _name = (j==0)?name+'[val]':name+'[val'+j+']';
				var iv = $('<input/>');
				iv.attr('type','text');
				iv.attr('name',_name);
				iv.attr('value','');
				iv.attr('placeholder',value);
				closeBtn.before(iv);
				j++;
			});
		});
		addComment($('#pos_select').val(),$('#pos_select').attr('id-data'));
		$('#pos_select').change(function(){
			addComment($(this).val(),$(this).attr('id-data'));
		});
		if($('input[name="wppt_testmode"]').length && $('input[name="wppt_testmode"]').val()=='yes' && $('input[name="wppt_testmode"]').prop('checked'))
			$('body').addClass('bnnrtest');
		$('input[name="wppt_testmode"]').change(function(){
			$('body').toggleClass('bnnrtest');
		});
		if($('#wpp-allpost').prop('checked')){
			$('.wpp-allpost-wrap').toggle();
			$('#wppb_onpost').prop('disabled',$('#wpp-allpost').prop('checked'));
		}
		$('#wpp-allpost').change(function(){
			$('.wpp-allpost-wrap').toggle();
			$('#wppb_onpost').prop('disabled',$('#wpp-allpost').prop('checked'));
		});
		if($('#wpp-allpage').prop('checked')){
			$('.wpp-allpage-wrap').toggle();
			$('#wppb_onpage').prop('disabled',$('#wpp-allpage').prop('checked'));
		}
		$('#wpp-allpage').change(function(){
			$('.wpp-allpage-wrap').toggle();
			$('#wppb_onpage').prop('disabled',$('#wpp-allpage').prop('checked'));
		});
		if($('#pos_select').val()=='pu')
			$('.wpp-popup-only').show();
		else
			$('.wpp-popup-only').hide();
		$('#pos_select').change(function(){
			if($(this).val()=='pu')
				$('.wpp-popup-only').show();
			else
				$('.wpp-popup-only').hide();
		})
		$('#wpp-allpage').change(function(){
			$('.wpp-allpage-wrap').toggle();
			$('#wppb_onpage').prop('disabled',$('#wpp-allpage').prop('checked'));
		});
		$('.slider-field').each(function(){
			var sldr = $(this),
			val = $('#'+$(this).attr('txt-fld')).val();
			sldr.slider({
	            range: "min",
	            value: val,
	            min: 1,
	            max: 100,
	            slide: function(event, ui){
	                $('#'+$(this).attr('txt-fld')).val(ui.value);
	            }
	        })
        });
    });
})(jQuery);