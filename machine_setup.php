<?php
// machine_setup.php — UI page for Machine Config (wrapped by FPP header/footer)
?>
<style>
  .field { margin-bottom: 12px; }
  label { display: inline-block; width: 120px; vertical-align: top; }
  select, input { width: 180px; }
  button { padding: 6px 10px; margin-right: 8px; }
</style>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Machine Status Configuration</title>
  <script src="/js/jquery.min.js"></script>
  <style>
    body { font-family: sans-serif; padding: 15px; }
    h1, h2 { margin-top: 20px; }
    .field { margin-bottom: 12px; }
    label { display: inline-block; width: 120px; vertical-align: top; }
    select, input { width: 180px; }
    button { padding: 6px 10px; margin-right: 8px; }
    #previewCanvas { border: 1px solid #888; margin-top: 12px; display: block; }
  </style>
</head>
<body>
  <h1>Machine Status Configuration</h1>

<h1>Machine Status Configuration</h1>
  <h2>Overlay Model</h2>
  <div class="field">
    <label for="modelSel">Model:</label>
    <select id="modelSel"><option>Loading...</option></select>
  </div>
  <div class="field">
    <label>Width:</label><span id="modelWidth">–</span>
  </div>
  <div class="field">
    <label>Height:</label><span id="modelHeight">–</span>
  </div>
  <div class="field">
    <button id="activateBtn">Activate</button>
    <button id="deactivateBtn">Deactivate</button>
  </div>

<h2>Overlay Model</h2>
<div class="field">
  <label for="modelSel">Model:</label>
  <select id="modelSel"><option>Loading...</option></select>
</div>
<div class="field">
  <label>Width:</label><span id="modelWidth">–</span>
</div>
<div class="field">
  <label>Height:</label><span id="modelHeight">–</span>
</div>
<div class="field">
  <button id="activateBtn">Activate</button>
  <button id="deactivateBtn">Deactivate</button>
</div>
  <h2>Overlay Preview</h2>
  <canvas id="previewCanvas"></canvas>

<h2>Manual Data Preview</h2>
<div class="field"><label for="line1">Line 1:</label><input id="line1" type="text"></div>
<div class="field"><label for="line2">Line 2:</label><input id="line2" type="text"></div>
<div class="field"><label for="line3">Line 3:</label><input id="line3" type="text"></div>
<div class="field"><label for="line4">Line 4:</label><input id="line4" type="text"></div>
<div class="field"><label for="color">Color:</label><input id="color" type="color" value="#FFFFFF"></div>
<button id="saveBtn">Apply &amp; Preview</button>
  <h2>Manual Data Preview</h2>
  <div class="field"><label for="line1">Line 1:</label><input id="line1" type="text"></div>
  <div class="field"><label for="line2">Line 2:</label><input id="line2" type="text"></div>
  <div class="field"><label for="line3">Line 3:</label><input id="line3" type="text"></div>
  <div class="field"><label for="line4">Line 4:</label><input id="line4" type="text"></div>
  <div class="field"><label for="color">Color:</label><input id="color" type="color" value="#FFFFFF"></div>
  <button id="saveBtn">Apply &amp; Preview</button>

<script>
// Load models from REST or fallback to static JSON
function loadModels() {
  $.getJSON('/api/overlays/models')
    .done(function(models) {
      populateModels(models.map(m => m.Name));
    })
    .fail(function() {
      $.getJSON('/media/config/model-overlays.json')
        .done(function(cfg) {
          populateModels(cfg.models.map(m => m.Name));
  <script>
    $(function() {
      // Load models
      $.getJSON('/rest/overlay/models')
        .done(function(models) {
          var sel = $('#modelSel').empty();
          if (!models.length) {
            sel.append($('<option disabled>').text('No models defined'));
          } else {
            $.each(models, function(i, m) {
              sel.append($('<option>').val(m).text(m));
            });
          }
          sel.trigger('change');
        })
        .fail(function() {
          $('#modelSel').empty().append(
            $('<option disabled>').text('Error loading models')
          );
        });
    });
}

function populateModels(names) {
  var sel = $('#modelSel').empty();
  if (!names.length) {
    sel.append($('<option disabled>').text('No models defined'));
  } else {
    names.forEach(function(n) {
      sel.append($('<option>').val(n).text(n));
      // Load settings
      $.getJSON('/plugin/machine/settings', function(data) {
        ['line1','line2','line3','line4'].forEach(function(id) {
          $('#' + id).val(data[id] || '');
        });
        $('#color').val(data.color || '#FFFFFF');
      });
    });
    sel.trigger('change');
  }
}

// Display model metadata: REST then static JSON fallback
$('#modelSel').on('change', function() {
  var model = $(this).val();
  if (!model) return;
  $.getJSON('/api/overlays/models/' + encodeURIComponent(model))
    .done(function(info) {
      $('#modelWidth').text(info.pixelCountX + ' px');
      $('#modelHeight').text(info.pixelCountY + ' px');
    })
    .fail(function() {
      $.getJSON('/media/config/model-overlays.json')
        .done(function(cfg) {
          var m = cfg.models.find(x => x.Name === model);
          if (m) {
            var width = m.StringCount * m.StrandsPerString;
            var totalPixels = m.ChannelCount / m.ChannelCountPerNode;
            var height = totalPixels / width;
            $('#modelWidth').text(width + ' px');
            $('#modelHeight').text(height + ' px');
          }
    // On model change
    $('#modelSel').on('change', function() {
      var model = $(this).val();
      if (!model) return;
      $.getJSON('/rest/overlay/models/' + encodeURIComponent(model))
        .done(function(info) {
          $('#modelWidth').text(info.pixelCountX + ' px');
          $('#modelHeight').text(info.pixelCountY + ' px');
          $('#previewCanvas').attr({
            width: info.pixelCountX,
            height: info.pixelCountY
          });
        });
    });
});

// Activate/deactivate model via REST
$('#activateBtn').on('click', function() {
  var model = $('#modelSel').val();
  if (model) {
    $.post('/api/overlays/models/' + encodeURIComponent(model) + '/activate');
  }
});
$('#deactivateBtn').on('click', function() {
  $.post('/api/overlays/models/deactivate');
});

// Load saved manual settings
function loadSettings() {
  $.getJSON('/plugin/machine/settings', function(data) {
    ['line1','line2','line3','line4'].forEach(id => {
      $('#' + id).val(data[id] || '');
    // Activate/deactivate
    $('#activateBtn').on('click', function() {
      var model = $('#modelSel').val();
      if (model) {
        $.post('/rest/overlay/models/' + encodeURIComponent(model) + '/activate');
      }
    });
    $('#deactivateBtn').on('click', function() {
      $.post('/rest/overlay/models/deactivate');
    });
    $('#color').val(data.color || '#FFFFFF');
  });
}

// Save settings and trigger overlay preview via GET
function saveAndPreview() {
  var payload = {};
  ['line1','line2','line3','line4'].forEach(id => {
    payload[id] = $('#' + id).val();
  });
  payload.color = $('#color').val();
  $.ajax({
    url: '/plugin/machine/settings',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(payload)
  }).always(function() {
    // Trigger overlay() hook via GET preview
    $.get('/plugin/machine/overlay?preview=1');
  });
}

// Initialize page
$(function() {
  loadModels();
  loadSettings();
  $('#saveBtn').on('click', saveAndPreview);
});
</script>
    // Preview manual settings
    $('#saveBtn').on('click', function() {
      var payload = {};
      ['line1','line2','line3','line4'].forEach(function(id) {
        payload[id] = $('#' + id).val();
      });
      payload.color = $('#color').val();
      $.ajax({
        url: '/plugin/machine/settings',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload)
      }).always(function() {
        $.get('/plugin/machine/overlay?preview=1');
      });
    });
  </script>
</body>
</html>
