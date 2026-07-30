/* GA4WP — export dropdown: PNG · PDF · CSV */
(function () {
  'use strict';

  var menuCounter = 0;

  /* ── Boot ── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    document.querySelectorAll('.ga4wp-box').forEach(wireBox);

    /* Close any open dropdown when clicking outside */
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.ga4wp-export-wrap')) {
        closeAllMenus();
      }
    });

    /* Watch for AJAX-injected boxes */
    new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        m.addedNodes.forEach(function (node) {
          if (node.nodeType !== 1) return;
          if (node.classList && node.classList.contains('ga4wp-box')) {
            wireBox(node);
          } else if (node.querySelectorAll) {
            node.querySelectorAll('.ga4wp-box').forEach(wireBox);
          }
        });
      });
    }).observe(document.body, { childList: true, subtree: true });
  }

  /* ── Wire one chart card ── */
  function wireBox(box) {
    if (box.dataset.ga4wpExportWired) return;
    box.dataset.ga4wpExportWired = '1';

    var headerRight = box.querySelector('.ga4wp-box-header-right');
    if (!headerRight) return;

    var canvas = box.querySelector('canvas');
    var table  = box.querySelector('.ga4wp-compare-table');
    var title  = getCardTitle(box);

    /* Build menu items */
    var items = [];
    if (canvas) {
      items.push({ icon: 'image',         label: 'PNG Image',       action: function () { downloadChartPNG(canvas, title); } });
      items.push({ icon: 'picture_as_pdf',label: 'PDF Document',    action: function () { downloadChartPDF(canvas, title); } });
    }
    if (table) {
      items.push({ icon: 'table_view',    label: 'CSV Spreadsheet', action: function () { downloadTableCSV(table, title); } });
      items.push({ icon: 'picture_as_pdf',label: 'PDF Document',    action: function () { downloadTablePDF(table, title); } });
    }
    if (!items.length) return;

    var wrap = buildDropdown(items);
    /* Insert before the info icon so it sits right-aligned inline with it */
    headerRight.insertBefore(wrap, headerRight.firstChild);
  }

  /* ── Build dropdown wrapper ── */
  function buildDropdown(items) {
    menuCounter++;
    var menuId = 'ga4wp-emenu-' + menuCounter;

    /* Wrapper — relative container so menu positions correctly */
    var wrap = document.createElement('div');
    wrap.className = 'ga4wp-export-wrap';

    /* Trigger button */
    var trigger = document.createElement('button');
    trigger.type      = 'button';
    trigger.className = 'ga4wp-export-trigger';
    trigger.setAttribute('aria-haspopup', 'true');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', menuId);
    trigger.title     = 'Export';
    trigger.innerHTML =
      '<i class="material-icons ga4wp-export-dl-icon">file_download</i>' +
      '<i class="material-icons ga4wp-export-arrow">arrow_drop_down</i>';

    /* Dropdown menu */
    var menu = document.createElement('ul');
    menu.id        = menuId;
    menu.className = 'ga4wp-export-menu';
    menu.setAttribute('role', 'menu');

    items.forEach(function (item) {
      var li  = document.createElement('li');
      li.setAttribute('role', 'none');

      var btn = document.createElement('button');
      btn.type      = 'button';
      btn.className = 'ga4wp-export-item waves-effect';
      btn.setAttribute('role', 'menuitem');
      btn.innerHTML =
        '<i class="material-icons">' + item.icon + '</i>' +
        '<span>' + item.label + '</span>';

      btn.addEventListener('click', function () {
        closeAllMenus();
        item.action();
      });

      li.appendChild(btn);
      menu.appendChild(li);
    });

    /* Toggle on trigger click */
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = wrap.classList.contains('ga4wp-export-open');
      closeAllMenus();
      if (!isOpen) {
        wrap.classList.add('ga4wp-export-open');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });

    wrap.appendChild(trigger);
    wrap.appendChild(menu);
    return wrap;
  }

  function closeAllMenus() {
    document.querySelectorAll('.ga4wp-export-wrap.ga4wp-export-open').forEach(function (w) {
      w.classList.remove('ga4wp-export-open');
      var t = w.querySelector('.ga4wp-export-trigger');
      if (t) t.setAttribute('aria-expanded', 'false');
    });
  }

  /* ══════════════════════════════════════════
     HELPERS
  ══════════════════════════════════════════ */

  function getCardTitle(box) {
    var el = box.querySelector('.ga4wp-box-title');
    if (!el) return 'TrueAna Report';
    var clone = el.cloneNode(true);
    var iconEl = clone.querySelector('.material-icons, .material-icons-round');
    if (iconEl) iconEl.remove();
    return clone.textContent.trim().replace(/\s+/g, ' ') || 'TrueAna Report';
  }

  function slugify(str) {
    return (str || 'export').replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '').toLowerCase() || 'export';
  }

  function whiteCanvas(src) {
    var off = document.createElement('canvas');
    off.width  = src.width;
    off.height = src.height;
    var ctx = off.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, off.width, off.height);
    ctx.drawImage(src, 0, 0);
    return off;
  }

  function triggerDownload(href, filename) {
    var a = document.createElement('a');
    a.href     = href;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }

  function cellText(td) {
    var clone = td.cloneNode(true);
    clone.querySelectorAll(
      '.material-icons, .material-icons-round, .material-icons-outlined,' +
      '.ga4wp-td-mini-badge, .ga4wp-badge-abs, .ga4wp-td-prev-inline'
    ).forEach(function (el) { el.remove(); });
    return clone.textContent.trim().replace(/\s+/g, ' ');
  }

  /* Read current + comparison period from the dashboard range bar */
  function getDateRanges() {
    var info = document.querySelector('.ga4wp-range-info');
    if (!info) return null;
    var strongs = info.querySelectorAll('strong');
    if (strongs.length < 2) return null;
    return {
      current: strongs[0].textContent.trim(),
      compare: strongs[1].textContent.trim(),
    };
  }

  function getJsPDF() {
    /* Prefer the UMD namespace — autoTable patches window.jspdf.jsPDF.prototype.
       window.jsPDF is a backwards-compat alias that may be a separate reference,
       so checking it first caused autoTable to appear missing. */
    if (window.jspdf && window.jspdf.jsPDF) return window.jspdf.jsPDF;
    if (window.jsPDF) return window.jsPDF;
    return null;
  }

  /* Doughnut/pie charts hide their Chart.js legend and render it as a
     separate HTML row instead (see publish_simple_doughnut_chart), so it's
     invisible to canvas-only exports. Rebuild it from the chart's own data. */
  function getLegendItems(chart) {
    if (!chart || (chart.config.type !== 'doughnut' && chart.config.type !== 'pie')) return null;
    var labels = (chart.data && chart.data.labels) || [];
    var ds = chart.data && chart.data.datasets && chart.data.datasets[0];
    if (!ds || !labels.length) return null;
    var colors = ds.backgroundColor || [];
    var values = ds.data || [];
    return labels.map(function (lbl, i) {
      return {
        label: String(lbl),
        value: values[i],
        color: Array.isArray(colors) ? colors[i] : colors,
      };
    });
  }

  function hexToRgb(hex) {
    var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex || '');
    return m ? [parseInt(m[1], 16), parseInt(m[2], 16), parseInt(m[3], 16)] : [148, 163, 184];
  }

  /* Word-wrap a single string to fit maxWidth, given a 2D context with the
     desired font already set. Returns an array of lines. */
  function wrapTextByWidth(ctx, text, maxWidth) {
    var words = String(text).split(' ');
    var lines = [];
    var cur = '';
    words.forEach(function (word) {
      var candidate = cur ? cur + ' ' + word : word;
      if (cur && ctx.measureText(candidate).width > maxWidth) {
        lines.push(cur);
        cur = word;
      } else {
        cur = candidate;
      }
    });
    if (cur) lines.push(cur);
    return lines.length ? lines : [''];
  }

  /* Lay out the "Period: X   vs   Y" line, wrapping to multiple lines
     whenever it doesn't fit within maxWidth instead of clipping. */
  function layoutDateLines(ctx, dateInfo, font, maxWidth) {
    ctx.font = font;
    var full = 'Period: ' + dateInfo.current + '   vs   ' + dateInfo.compare;
    if (ctx.measureText(full).width <= maxWidth) return [full];
    var lines = wrapTextByWidth(ctx, 'Period: ' + dateInfo.current, maxWidth);
    lines = lines.concat(wrapTextByWidth(ctx, 'vs ' + dateInfo.compare, maxWidth));
    return lines;
  }

  /* Flow-wrap legend chips (dot + "label — value") across multiple rows,
     mirroring the flex-wrap behaviour of the on-screen HTML legend.
     prefixWidth is the space reserved for the dot + gap before the text
     of each chip, so row-break decisions account for it even though the
     text width alone is what's stored for drawing. */
  function layoutLegendRows(ctx, legend, font, maxWidth, chipGap, prefixWidth) {
    ctx.font = font;
    var rows = [[]];
    var rowWidth = 0;
    legend.forEach(function (item) {
      var text = item.label + ' — ' + item.value;
      var textWidth = ctx.measureText(text).width;
      var w = prefixWidth + textWidth;
      var needed = (rows[rows.length - 1].length ? chipGap : 0) + w;
      if (rowWidth + needed > maxWidth && rows[rows.length - 1].length) {
        rows.push([]);
        rowWidth = 0;
        needed = w;
      }
      rows[rows.length - 1].push({ text: text, width: textWidth, color: item.color });
      rowWidth += needed;
    });
    return rows;
  }

  /* ══════════════════════════════════════════
     PNG
  ══════════════════════════════════════════ */
  function downloadChartPNG(canvas, title) {
    var chart = typeof Chart !== 'undefined' && Chart.getChart
      ? Chart.getChart(canvas) : null;
    if (!chart) { alert('Chart not ready — please wait a moment.'); return; }

    var dateInfo = getDateRanges();
    var legend   = getLegendItems(chart);
    var dpr      = window.devicePixelRatio || 1;
    var pad      = Math.round(10 * dpr);
    var sidePad  = Math.round(14 * dpr);
    var titleSz  = Math.round(13 * dpr);
    var dateSz   = Math.round(10 * dpr);
    var legendSz = Math.round(11 * dpr);
    var dotR     = Math.round(4 * dpr);
    var dateFont   = dateSz + 'px Arial, sans-serif';
    var legendFont = legendSz + 'px Arial, sans-serif';

    var src = chart.canvas;
    var maxWidth = src.width - sidePad * 2;

    /* Measure/wrap before sizing the canvas so nothing gets clipped */
    var measure = document.createElement('canvas').getContext('2d');
    var dateLines = dateInfo ? layoutDateLines(measure, dateInfo, dateFont, maxWidth) : [];
    var legendRows = legend
      ? layoutLegendRows(measure, legend, legendFont, maxWidth, Math.round(18 * dpr), dotR * 2 + Math.round(6 * dpr))
      : [];

    var dateLineH   = dateSz + Math.round(4 * dpr);
    var legendRowH  = legendSz + Math.round(8 * dpr);
    var dateH       = dateLines.length ? Math.round(5 * dpr) + dateLines.length * dateLineH : 0;
    var legendH     = legendRows.length ? Math.round(10 * dpr) + legendRows.length * legendRowH : 0;
    var headerH     = pad + titleSz + dateH + legendH + pad;

    var off = document.createElement('canvas');
    off.width  = src.width;
    off.height = src.height + headerH;
    var ctx = off.getContext('2d');

    /* Header background */
    ctx.fillStyle = '#F4F6FA';
    ctx.fillRect(0, 0, off.width, headerH);

    /* Chart area background */
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, headerH, off.width, src.height);

    /* Title */
    ctx.fillStyle = '#0F172A';
    ctx.font = 'bold ' + titleSz + 'px Arial, sans-serif';
    ctx.fillText(title, sidePad, pad + titleSz);

    var y = pad + titleSz;

    /* Date range (wrapped) */
    if (dateLines.length) {
      ctx.fillStyle = '#475569';
      ctx.font = dateFont;
      y += Math.round(5 * dpr);
      dateLines.forEach(function (line) {
        y += dateSz;
        ctx.fillText(line, sidePad, y);
      });
    }

    /* Legend (doughnut/pie charts hide their Chart.js legend on-screen), wrapped */
    if (legendRows.length) {
      ctx.font = legendFont;
      y += Math.round(10 * dpr);
      legendRows.forEach(function (row) {
        var lx = sidePad;
        row.forEach(function (chip) {
          ctx.fillStyle = chip.color || '#94A3B8';
          ctx.beginPath();
          ctx.arc(lx + dotR, y - dotR + Math.round(1 * dpr), dotR, 0, Math.PI * 2);
          ctx.fill();
          ctx.fillStyle = '#475569';
          var textX = lx + dotR * 2 + Math.round(6 * dpr);
          ctx.fillText(chip.text, textX, y);
          lx = textX + chip.width + Math.round(18 * dpr);
        });
        y += legendRowH;
      });
    }

    /* Divider line */
    ctx.strokeStyle = '#E4E8F2';
    ctx.lineWidth   = dpr;
    ctx.beginPath();
    ctx.moveTo(0, headerH - dpr);
    ctx.lineTo(off.width, headerH - dpr);
    ctx.stroke();

    /* Chart */
    ctx.drawImage(src, 0, headerH);

    triggerDownload(off.toDataURL('image/png'), slugify(title) + '.png');
  }

  /* ══════════════════════════════════════════
     CHART → PDF
  ══════════════════════════════════════════ */
  function downloadChartPDF(canvas, title) {
    var jsPDF = getJsPDF();
    if (!jsPDF) { alert('PDF library loading — please retry in a moment.'); return; }
    var chart = typeof Chart !== 'undefined' && Chart.getChart
      ? Chart.getChart(canvas) : null;
    if (!chart) { alert('Chart not ready — please wait a moment.'); return; }

    var off    = whiteCanvas(chart.canvas);
    var doc    = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    var margin = 14;
    var pageW  = doc.internal.pageSize.getWidth();
    var pageH  = doc.internal.pageSize.getHeight();

    var dateInfo = getDateRanges();
    var legend   = getLegendItems(chart);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(15, 23, 42);
    doc.text(title, margin, margin);

    var availW = pageW - margin * 2;
    var y = margin;

    /* Date range — wrapped instead of clipped when it doesn't fit */
    if (dateInfo) {
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8);
      doc.setTextColor(71, 85, 105);
      var full = 'Period: ' + dateInfo.current + '   |   Compared to: ' + dateInfo.compare;
      var dateLines = doc.getTextWidth(full) <= availW ? [full] : doc.splitTextToSize(full, availW);
      dateLines.forEach(function (line, i) {
        y += i === 0 ? 7 : 4;
        doc.text(line, margin, y);
      });
    }

    /* Legend (doughnut/pie charts hide their Chart.js legend on-screen),
       flow-wrapped across rows so it never runs off the page edge */
    if (legend) {
      y += 6;
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8);
      var lx = margin;
      legend.forEach(function (item) {
        var text  = item.label + ' — ' + item.value;
        var chipW = 4 + doc.getTextWidth(text);
        if (lx + chipW > pageW - margin && lx > margin) {
          lx = margin;
          y += 5;
        }
        var rgb = hexToRgb(item.color);
        doc.setFillColor(rgb[0], rgb[1], rgb[2]);
        doc.circle(lx + 1, y - 1, 1, 'F');
        doc.setTextColor(71, 85, 105);
        doc.text(text, lx + 4, y);
        lx += chipW + 8;
      });
    }

    var rulerY = y + 4;
    doc.setDrawColor(228, 232, 242);
    doc.setLineWidth(0.3);
    doc.line(margin, rulerY, pageW - margin, rulerY);

    var topY   = rulerY + 5;
    var availH = pageH - topY - margin;
    var ratio  = off.width / off.height;
    var imgW   = availW;
    var imgH   = imgW / ratio;
    if (imgH > availH) { imgH = availH; imgW = imgH * ratio; }

    doc.addImage(off.toDataURL('image/png'), 'PNG', margin, topY, imgW, imgH);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(148, 163, 184);
    doc.text('Exported by TrueAna · ' + new Date().toLocaleDateString(), margin, pageH - 6);

    doc.save(slugify(title) + '.pdf');
  }

  /* ══════════════════════════════════════════
     TABLE → CSV
  ══════════════════════════════════════════ */
  function downloadTableCSV(table, title) {
    var dateInfo = getDateRanges();
    var rows = [];

    /* Report header rows */
    rows.push('"' + title.replace(/"/g, '""') + '"');
    if (dateInfo) {
      rows.push('"Period: ' + dateInfo.current.replace(/"/g, '""') + '","vs","' + dateInfo.compare.replace(/"/g, '""') + '"');
    }
    rows.push(''); /* blank separator before data */

    /* Column headers */
    rows.push(Array.from(table.querySelectorAll('thead th')).map(function (th) {
      return '"' + th.textContent.trim().replace(/"/g, '""') + '"';
    }).join(','));

    /* Data rows */
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      rows.push(Array.from(tr.querySelectorAll('td')).map(function (td) {
        return '"' + cellText(td).replace(/"/g, '""') + '"';
      }).join(','));
    });

    var blob = new Blob(['﻿' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    var url  = URL.createObjectURL(blob);
    triggerDownload(url, slugify(title) + '.csv');
    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
  }

  /* ══════════════════════════════════════════
     TABLE → PDF
  ══════════════════════════════════════════ */
  function downloadTablePDF(table, title) {
    var jsPDF = getJsPDF();
    if (!jsPDF) { alert('PDF library loading — please retry in a moment.'); return; }

    var headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
      return th.textContent.trim();
    });
    var body = [];
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      body.push(Array.from(tr.querySelectorAll('td')).map(cellText));
    });

    var doc    = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

    /* Check on the instance — more reliable than checking the prototype directly */
    if (typeof doc.autoTable !== 'function') {
      alert('PDF table plugin loading — please retry in a moment.'); return;
    }
    var margin = 14;
    var pageW  = doc.internal.pageSize.getWidth();
    var pageH  = doc.internal.pageSize.getHeight();

    var dateInfo = getDateRanges();

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(15, 23, 42);
    doc.text(title, margin, margin);

    var rulerY = margin + (dateInfo ? 12 : 4);
    if (dateInfo) {
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8);
      doc.setTextColor(71, 85, 105);
      doc.text(
        'Period: ' + dateInfo.current + '   |   Compared to: ' + dateInfo.compare,
        margin, margin + 7
      );
    }

    doc.setDrawColor(228, 232, 242);
    doc.setLineWidth(0.3);
    doc.line(margin, rulerY, pageW - margin, rulerY);

    doc.autoTable({
      startY: rulerY + 5,
      head: [headers],
      body: body,
      margin: { left: margin, right: margin },
      styles:          { font: 'helvetica', fontSize: 8, cellPadding: 3, textColor: [71, 85, 105] },
      headStyles:      { fillColor: [37, 99, 235], textColor: 255, fontStyle: 'bold', fontSize: 8 },
      alternateRowStyles: { fillColor: [244, 246, 250] },
      didDrawPage: function () {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(148, 163, 184);
        doc.text('Exported by TrueAna · ' + new Date().toLocaleDateString(), margin, pageH - 6);
      },
    });

    doc.save(slugify(title) + '.pdf');
  }
})();
