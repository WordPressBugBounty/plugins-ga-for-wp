/* GA4WP — Modern UI v3 */
var ga4wpAuthWin = null;

/* Builds a diagnostic message from a failed jqXHR so AJAX failures show the
 * real HTTP status / response instead of a generic "network error" — makes
 * it possible to tell a genuine connectivity problem apart from e.g. a
 * expired nonce (403) or a PHP fatal error (500 with an error snippet). */
function ga4wpAjaxFailureMessage(jqXHR) {
  var status = jqXHR && jqXHR.status ? jqXHR.status : 0;
  var statusText = (jqXHR && jqXHR.statusText) ? jqXHR.statusText : 'unknown';
  var snippet = '';
  if (jqXHR && jqXHR.responseText) {
    snippet = jqXHR.responseText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160);
  }
  console.error('GA4WP AJAX failure', status, statusText, jqXHR && jqXHR.responseText);
  if (status === 0) {
    return 'Request could not reach the server (connection blocked or timed out).';
  }
  return 'HTTP ' + status + ' ' + statusText + (snippet ? ' — ' + snippet : '');
}

/* Measurement Protocol secret — Status page "Generate Now" / "Retry" button.
 * Available on both free and premium tiers. Bound first, before anything
 * below that could throw on pages (like Status) that lack Dashboard-only
 * elements — a synchronous error in jQuery(document).ready() further down
 * would otherwise stop the rest of this file from ever running. */
jQuery(document).on('click', '#ga4wp-generate-mp-key', function () {
  var $btn = jQuery(this);
  var isRetry = $btn.text().indexOf('Retry') !== -1;
  $btn.prop('disabled', true).html('<span class="material-icons-round ga4wp-spin-icon">sync</span> ' + (isRetry ? 'Retrying…' : 'Generating…'));
  jQuery.post(
    ga4wp_js_object.ajax_url,
    {action: 'ga4wp_generate_measurement_key', security: $btn.data('nonce')},
    function (res) {
      if (res && res.success) {
        jQuery('#ga4wp-mp-key-status').html(
          '<span class="ga4wp-status-badge ga4wp-status-badge-green">' +
          '<span class="material-icons-round">check_circle</span>Generated</span> ' +
          '<code class="ga4wp-status-code" id="ga4wp-mp-key-value">••••' + res.data.secret_last4 + '</code>' +
          '<button type="button" class="ga4wp-status-copy-btn" id="ga4wp-mp-key-copy" data-secret="' + res.data.secret + '" title="Copy secret">' +
          '<span class="material-icons-round">content_copy</span></button>'
        );
        jQuery('#ga4wp-mp-key-error').remove();
        $btn.remove();
        M.toast({html: 'Measurement Protocol secret ready!', classes: 'rounded teal', displayLength: 4000});
      } else {
        var msg = (res && res.data) ? res.data : 'Failed to generate the secret.';
        M.toast({html: 'Failed: ' + msg, classes: 'rounded red', displayLength: 7000});
        // Reload so the server-rendered hint/explanation block (with the terms-of-service
        // guidance) reflects the newly persisted error state.
        setTimeout(function () { window.location.reload(); }, 1500);
      }
    }
  ).fail(function (jqXHR) {
    $btn.prop('disabled', false).html('<span class="material-icons-round">add_circle</span> ' + (isRetry ? 'Retry' : 'Generate Now'));
    M.toast({html: ga4wpAjaxFailureMessage(jqXHR), classes: 'rounded red', displayLength: 9000});
  });
});

jQuery(document).on('click', '#ga4wp-mp-key-copy', function () {
  var $btn = jQuery(this);
  var secret = $btn.data('secret');
  var done = function () {
    var $icon = $btn.find('.material-icons-round');
    var orig = $icon.text();
    $icon.text('check');
    M.toast({html: 'Copied to clipboard!', classes: 'rounded teal', displayLength: 2500});
    setTimeout(function () { $icon.text(orig); }, 1500);
  };
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(secret).then(done).catch(function () {
      M.toast({html: 'Could not copy — please copy manually.', classes: 'rounded red', displayLength: 4000});
    });
  } else {
    var $tmp = jQuery('<textarea readonly></textarea>').val(secret).css({position: 'fixed', top: '-1000px'}).appendTo('body');
    $tmp[0].select();
    try { document.execCommand('copy'); done(); } catch (e) {
      M.toast({html: 'Could not copy — please copy manually.', classes: 'rounded red', displayLength: 4000});
    }
    $tmp.remove();
  }
});

/* Measurement Protocol secret — inline "Generate" button next to the API
 * Secret Key field on the Tracking Settings tab. Same backend action as the
 * Status page button above, but updates the input field directly instead of
 * a status badge. */
jQuery(document).on('click', '#ga4wp-generate-mp-key-inline', function () {
  var $btn = jQuery(this);
  var $input = jQuery('input[name="ga4wp_track_settings[google_measurement_api]"]');
  var origHtml = $btn.html();
  $btn.prop('disabled', true).html('<span class="material-icons-round ga4wp-spin-icon">sync</span> Generating…');
  jQuery.post(
    ga4wp_js_object.ajax_url,
    {action: 'ga4wp_generate_measurement_key', security: $btn.data('nonce')},
    function (res) {
      if (res && res.success) {
        $input.val(res.data.secret);
        if (window.M && M.updateTextFields) M.updateTextFields();
        M.toast({html: 'Measurement Protocol secret ready — remember to save your settings.', classes: 'rounded teal', displayLength: 6000});
      } else {
        var msg = (res && res.data) ? res.data : 'Failed to generate the secret.';
        M.toast({html: 'Failed: ' + msg, classes: 'rounded red', displayLength: 7000});
      }
    }
  ).fail(function (jqXHR) {
    M.toast({html: ga4wpAjaxFailureMessage(jqXHR), classes: 'rounded red', displayLength: 9000});
  }).always(function () {
    $btn.prop('disabled', false).html(origHtml);
  });
});

jQuery('.GA4WP-access-revoke').click(function () {
  var $btn = jQuery(this);
  $btn.css('pointer-events','none').html('<span class="material-icons-round ga4wp-spin-icon">sync</span> Revoking access…');
  M.toast({html:'Revoking Google access, please wait…', classes:'rounded teal', displayLength:8000});
  jQuery.post(ga4wp_js_object.ajax_url,{action:'web_ga4wp_revoke_access',security:ga4wp_js_object.revoke_access_nonce},function(){window.location.reload();});
});
jQuery('.GA4WP-un-link').click(function () {
  var $btn = jQuery(this);
  $btn.css('pointer-events','none').html('<span class="material-icons-round ga4wp-spin-icon">sync</span> Disconnecting…');
  M.toast({html:'Disconnecting Google Analytics, please wait…', classes:'rounded teal', displayLength:8000});
  jQuery.post(ga4wp_js_object.ajax_url,{action:'web_ga4wp_un_link',security:ga4wp_js_object.un_link_nonce},function(){window.location.reload();});
});

/* Auto-select the active dashboard/settings tab on page load. Registered as
 * its own early ready() callback — separate from the large one further down
 * — so it still runs even if something later in this file (or a later
 * plugin's script) throws inside a ready handler and aborts the rest of that
 * queue; same defensive pattern as the MP-key handlers above.
 * Prefers the URL hash (so a direct link/bookmark/reload opens the right
 * tab) and falls back to the last tab persisted server-side. */
jQuery(document).ready(function () {
  var hashTab = (window.location.hash || '').replace('#', '');
  var tab_id  = hashTab || ga4wp_js_object.current_tab_id;

  if (jQuery('.tabs').length) {
    jQuery('.tabs').tabs();
    jQuery('.tabs').tabs('select', tab_id);
    jQuery('.tabs a.active').trigger('click');
  }

  if (jQuery('.ga4wp-settings-tabs').length) {
    var stab = '#' + tab_id;
    var $stTarget = jQuery('.ga4wp-settings-tabs a[href="' + stab + '"]');
    if (!$stTarget.length) { $stTarget = jQuery('.ga4wp-settings-tabs a').first(); }
    $stTarget.trigger('click');
  }
});

jQuery(document).on('click', 'a[data-settab]', function(e) {
  e.preventDefault();
  var tab_id = jQuery(this).attr('href');
  jQuery('a[data-settab]').removeClass('ga4wp-tab-active');
  jQuery(this).addClass('ga4wp-tab-active');
  jQuery('.ga4wp-settings-pane').hide();
  jQuery(tab_id).show();
  jQuery(tab_id).html("<div class='ga4wp-tab-loader'><div class='preloader-wrapper small active'><div class='spinner-layer'><div class='circle-clipper left'><div class='circle'></div></div><div class='gap-patch'><div class='circle'></div></div><div class='circle-clipper right'><div class='circle'></div></div></div></div><p class='ga4wp-tab-loader-text'>Loading…</p></div>");
  jQuery.post(ga4wp_js_object.ajax_url,
    {action: 'web_ga4wp_tab_update', security: ga4wp_js_object.tab_update_nonce, tab: tab_id},
    function(data, status) {
      if (status === 'success') {
        jQuery(tab_id).html(data);
        ga4wpInitViewMoreTables();
        ga4wpApplyChartDefaults();
        check_start();
      }
    }
  );
});

jQuery('.tabs a').click(function () {
  var tab_id = jQuery(this).attr('href');
  var isRealtime = tab_id.indexOf('realtime') !== -1;
  jQuery('.ga4wp-dash-right, .ga4wp-dash-range, .ga4wp-range-info').toggle(!isRealtime);
  jQuery('.ga4wp-range-info').next('.divider').toggle(!isRealtime);
  if (tab_id.indexOf('pro') === -1) {
    var $tabClone = jQuery(this).clone();
    $tabClone.find('span[class*="material"]').remove();
    var tabLabel = $tabClone.text().trim();
    if (tabLabel && jQuery('#ga4wp-dash-title').length) {
      jQuery('#ga4wp-dash-title').text(tabLabel.charAt(0).toUpperCase() + tabLabel.slice(1) + ' Overview');
    }
    jQuery(tab_id).html("<div class='ga4wp-tab-loader'><div class='preloader-wrapper small active'><div class='spinner-layer'><div class='circle-clipper left'><div class='circle'></div></div><div class='gap-patch'><div class='circle'></div></div><div class='circle-clipper right'><div class='circle'></div></div></div></div><p class='ga4wp-tab-loader-text'>Loading…</p></div>");
    jQuery.post(ga4wp_js_object.ajax_url,
      {action:'web_ga4wp_tab_update',security:ga4wp_js_object.tab_update_nonce,tab:tab_id},
      function(data,status){
        if(status==='success'){
          jQuery(tab_id).html(data);
          ga4wpInitViewMoreTables();
          ga4wpApplyChartDefaults();
        }
      }
    );
  }
});

function ga4wpInitViewMoreTables(){
  var limit=10;
  jQuery('.ga4wp-box').each(function(){
    var $box=jQuery(this),$tbl=$box.find('table').first(),$btn=$box.find('.ga4wp-view-more-btn').first();
    if(!$tbl.length||!$btn.length)return;
    var $rows=$tbl.find('tbody tr');
    $rows.removeClass('ga4wp-hidden-row');
    $btn.hide().text('Show more rows').data('expanded',false);
    if($rows.length>limit){$rows.slice(limit).addClass('ga4wp-hidden-row');$btn.show();}
  });
}

jQuery(document).on('click','.ga4wp-view-more-btn',function(e){
  e.preventDefault();
  var limit=10,$btn=jQuery(this),$rows=$btn.closest('.ga4wp-box').find('table').first().find('tbody tr');
  var expanded=$btn.data('expanded')===true;
  if(expanded){$rows.slice(limit).addClass('ga4wp-hidden-row');$btn.text('Show more rows').data('expanded',false);}
  else{$rows.removeClass('ga4wp-hidden-row');$btn.text('Show less').data('expanded',true);}
});

function ga4wpApplyChartDefaults(){
  if(typeof Chart==='undefined')return;
  Chart.defaults.font.family="'Sora', -apple-system, sans-serif";
  Chart.defaults.font.size=11;
  Chart.defaults.color='#94A3B8';
  Chart.defaults.plugins.tooltip.backgroundColor='#FFFFFF';
  Chart.defaults.plugins.tooltip.borderColor='#E4E8F2';
  Chart.defaults.plugins.tooltip.borderWidth=1;
  Chart.defaults.plugins.tooltip.titleColor='#0F172A';
  Chart.defaults.plugins.tooltip.bodyColor='#475569';
  Chart.defaults.plugins.tooltip.padding=10;
  Chart.defaults.plugins.tooltip.cornerRadius=8;
  Chart.defaults.plugins.tooltip.boxPadding=4;
  Chart.defaults.plugins.legend.display=false;
  Chart.defaults.scale.grid.color='rgba(15,23,42,0.05)';
  Chart.defaults.scale.ticks.color='#94A3B8';
  Chart.defaults.animation.duration=900;
  Chart.defaults.animation.easing='easeInOutQuart';
}

jQuery(document).ready(function(){
  jQuery('.tooltipped').tooltip();
  jQuery('#modal1').modal();
  jQuery('#modal2').modal();
  jQuery('.tabs').tabs();
  jQuery('.collapsible').collapsible();
  ga4wpApplyChartDefaults();

  jQuery('.main-content').on('click','.ga4wp-box-title.right i,.chart-info',function(){
    jQuery(this).closest('.ga4wp-box').find('.ga4wp-box-description').first().slideToggle(150);
  });

  M.updateTextFields();
  jQuery('select').formSelect();

  jQuery('.GA4WP-authenticate').click(function(e){
    e.preventDefault();
    var $btn = jQuery(this);
    ga4wpAuthWin = window.open(ga4wp_js_object.auth_url,'GA4WP-Authentication','menubar=0,width=620,height=700,left=200,top=100');
    $btn.html('<span class="material-icons-round ga4wp-spin-icon">sync</span> Waiting for Google…').css('pointer-events','none');
    M.toast({html:'Google sign-in window opened — complete the process there to continue.', classes:'rounded teal', displayLength:10000});

    // Poll every 2 s for auth completion.
    // window.opener is unreliable after navigating through Google's cross-origin OAuth pages
    // (Google sets Cross-Origin-Opener-Policy which severs the opener reference).
    // Polling is origin-safe and works regardless of browser security policies.
    var ga4wpAuthPoll = setInterval(function(){
      jQuery.post(ga4wp_js_object.ajax_url, {
        action:   'ga4wp_check_auth_status',
        security: ga4wp_js_object.tab_update_nonce
      }, function(res){
        if (res && res.success && res.data && res.data.connected) {
          clearInterval(ga4wpAuthPoll);
          if (ga4wpAuthWin && !ga4wpAuthWin.closed) ga4wpAuthWin.close();
          window.location.reload();
        } else if (ga4wpAuthWin && ga4wpAuthWin.closed) {
          // Popup closed without completing auth — stop polling, restore button
          clearInterval(ga4wpAuthPoll);
          $btn.html('<span class="material-icons-round">link</span> Connect with Google').css('pointer-events','auto');
        }
      }, 'json').fail(function(){ /* network hiccup — keep polling */ });
    }, 2000);

    // Safety cap: stop polling after 10 minutes
    setTimeout(function(){ clearInterval(ga4wpAuthPoll); }, 600000);
  });

  check_start();
  from_to_dates();

  jQuery('#from').datepicker({selectMonths:true,closeOnSelect:true,closeOnClear:true,format:'yyyy-mm-dd',maxDate:new Date(),
    onSelect:function(selected){var dt=new Date(selected);dt.setDate(dt.getDate()+1);M.Datepicker.getInstance(document.getElementById('to')).options.minDate=dt;}
  });
  jQuery('#to').datepicker({selectMonths:true,closeOnSelect:true,closeOnClear:true,format:'yyyy-mm-dd',maxDate:new Date(),
    onSelect:function(selected){var dt=new Date(selected);dt.setDate(dt.getDate()-1);M.Datepicker.getInstance(document.getElementById('from')).options.maxDate=dt;}
  });
  // Apply initial minDate/maxDate constraints from existing saved values
  (function(){
    var fromVal=jQuery('#from').val(),toVal=jQuery('#to').val();
    if(fromVal){var p=fromVal.split('-');if(p.length===3){var d=new Date(+p[0],+p[1]-1,+p[2]+1);var ti=M.Datepicker.getInstance(document.getElementById('to'));if(ti)ti.options.minDate=d;}}
    if(toVal){var p2=toVal.split('-');if(p2.length===3){var d2=new Date(+p2[0],+p2[1]-1,+p2[2]-1);var fi=M.Datepicker.getInstance(document.getElementById('from'));if(fi)fi.options.maxDate=d2;}}
  })();
  // Block submit when To < From in Custom Range mode
  jQuery('#ga4wp-filter-form').on('submit',function(e){
    if(jQuery('#ga4wp-period-input').val()==='Custom Range'){
      var fv=jQuery('#from').val(),tv=jQuery('#to').val();
      if(fv&&tv&&new Date(fv)>new Date(tv)){
        e.preventDefault();
        M.toast({html:'"From" date must be on or before "To" date.',classes:'rounded red',displayLength:4000});
        return false;
      }
    }
  });

  var view_id=jQuery('#report_view_id').val();
  jQuery('#report_view_id').on('change',function(){
    var reg=new RegExp(view_id,'g');
    jQuery('#view_reports').html(jQuery('#view_reports').html().replace(reg,this.value));
    view_id=jQuery('#report_view_id').val();
  });
});

jQuery(document).on('click','.ga4wp-period-opt',function(){
  var $btn=jQuery(this),period=$btn.data('period');
  jQuery('.ga4wp-period-opt').removeClass('active');
  $btn.addClass('active');
  jQuery('#ga4wp-period-input').val(period);
  if(period==='Custom Range'){
    jQuery('#ga4wp-date-chip').addClass('visible');
  } else {
    jQuery('#ga4wp-date-chip').removeClass('visible');
    jQuery('#ga4wp-filter-form').submit();
  }
});
jQuery('#report_frame').on('change',function(){from_to_dates();});
function from_to_dates(){
  if(jQuery('#report_frame').val()==='Custom Range'){jQuery('.from').show();jQuery('.to').show();}
  else{jQuery('.from').hide();jQuery('.to').hide();}
}
jQuery(document).on('change',function(){check_start();});
function auth_callback(){
  if(ga4wpAuthWin && !ga4wpAuthWin.closed){ ga4wpAuthWin.close(); }
  window.location.reload();
}
function check_start(){
  var isManual=jQuery('.check_manual').is(':checked');
  if(isManual){jQuery('.auto-connect').hide();jQuery('.manual-connect').show();}
  else{jQuery('.auto-connect').show();jQuery('.manual-connect').hide();}
  jQuery('.ga4wp-auth-method-opt-auto').toggleClass('active',!isManual);
  jQuery('.ga4wp-auth-method-opt-manual').toggleClass('active',isManual);
  jQuery('.check_gdpr').is(':checked')?jQuery('.ga4wp-gdpr').show():jQuery('.ga4wp-gdpr').hide();
  var t=jQuery('.tracking-id').val(),p=jQuery('.property-id').val();
  if((t&&t.indexOf('G')>-1)||(p&&p.indexOf('G')>-1)){jQuery('.api-require').show();}
  else{jQuery('.api-require').hide();}
}
