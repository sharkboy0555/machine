<?php
// machine_setup.php — UI page for Machine Config (wrapped by FPP header/footer)
?>
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

  <h2>Overlay Model</h2>
  <div class="field">
    <label for="modelSel">Model:</label>
    <select id="modelSel"></select>
  </div>
  <div class="field">
    <label for="modelWidth">Width:</label><span id="modelWidth">–</span>
  </div>
  <div class="field">
    <label for="modelHeight">Height:</label><span id="modelHeight">–</span>
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

  <script>
    // On page load, fetch available overlay models
    $(function() {
      $.getJSON('/rest/overlay/models', function(models) {
        var sel = $('#modelSel').empty();
        if (!models.length) {
          sel.append($('<option disabled>').text('No models defined'));
        } else {
          $.each(models, function(i, m) {
            sel.append($('<option>').val(m).text(m));
          });
          sel.trigger('change');
        }
      }).fail(function() {
        $('#modelSel').empty().append($('<option disabled>').text('Error loading models'));
      });

      // Load saved manual settings
      $.getJSON('/plugin/machine/settings', function(data) {
        $.each(['line1','line2','line3','line4'], function(i, id) {
          $('#' + id).val(data[id] || '');
        });
        $('#color').val(data.color || '#FFFFFF');
      });
    });

    // When user selects a model, fetch its metadata
    $('#modelSel').on('change', function() {
      var model = $(this).val();
      if (!model) return;
      $.getJSON('/rest/overlay/models/' + model, function(info) {
        $('#modelWidth').text(info.pixelCountX + ' px');
        $('#modelHeight').text(info.pixelCountY + ' px');
        $('#previewCanvas')
          .attr('width', info.pixelCountX)
          .attr('height', info.pixelCountY);
      });
    });

    // Activate and deactivate overlay model
    $('#activateBtn').on('click', function() {
      var model = $('#modelSel').val();
      if (model) {
        $.post('/rest/overlay/models/' + model + '/activate');
      }
    });

    $('#deactivateBtn').on('click', function() {
      $.post('/rest/overlay/models/deactivate');
    });

    // Draw manual data onto canvas and send preview overlay
    $('#saveBtn').on('click', function() {
      var canvas = document.getElementById('previewCanvas');
      var ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = $('#color').val();
      ctx.font = '12px sans-serif';
      var y = 14;
      $.each(['line1','line2','line3','line4'], function(i, id) {
        ctx.fillText($('#' + id).val() || '', 0, y);
        y += 14;
      });
      var dataURL = canvas.toDataURL().split(',')[1];
      var model = $('#modelSel').val();
      $.ajax({
        url: '/plugin/machine/overlay?preview=1',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ model: model, data: dataURL })
      });
    });
  </script>
</body>
</html>
