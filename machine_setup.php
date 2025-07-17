<?php
// machine_setup.php — UI page for Machine Config (wrapped by FPP header/footer)

// Read and decode your overlay models JSON as a fallback
$configPath = '/home/fpp/media/config/model-overlays.json';
$modelFile  = @file_get_contents($configPath);
$staticModels = $modelFile
    ? (json_decode($modelFile, true)['models'] ?? [])
    : [];
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
    <label for="modelWidth">Width:</label>
    <span id="modelWidth">–</span>
  </div>
  <div class="field">
    <label for="modelHeight">Height:</label>
    <span id="modelHeight">–</span>
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
  <button id="previewBtn">Apply &amp; Preview</button>

  <script>
  $(function(){
    // static fallback models from PHP
    var staticModels = <?php echo json_encode($staticModels, JSON_HEX_TAG); ?>;
    var modelsCfg = [];

    // populate <select> from array of names
    function populateDropdown(names) {
      var sel = $('#modelSel').empty();
      if (!names.length) {
        sel.append($('<option disabled>').text('No models defined'));
      } else {
        names.forEach(function(n){
          sel.append($('<option>').val(n).text(n));
        });
      }
      sel.trigger('change');
    }

    // try API then static
    function loadModels() {
      $.getJSON('/api/models')
        .done(function(apiArray){
          modelsCfg = apiArray;
          populateDropdown(apiArray.map(m=>m.Name));
        })
        .fail(function(){
          modelsCfg = staticModels;
          populateDropdown(staticModels.map(m=>m.Name));
        });
    }

    // on model change: compute dims & resize canvas
    function updateModelInfo(name) {
      var m = modelsCfg.find(x=>x.Name===name);
      if (!m) return;
      var width  = m.StringCount * m.StrandsPerString;
      var pixels = m.ChannelCount / m.ChannelCountPerNode;
      var height = pixels / width;
      $('#modelWidth').text(width + ' px');
      $('#modelHeight').text(height + ' px');
      $('#previewCanvas').attr({ width: width, height: height });
    }

    // hook up activate/deactivate
    $('#activateBtn').click(function(){
      var name = $('#modelSel').val();
      if (name) {
        $.post('/api/models/' + encodeURIComponent(name) + '/activate');
      }
    });
    $('#deactivateBtn').click(function(){
      $.post('/api/models/deactivate');
    });

    // **NEW**: preview draws black background + white text, then activates the model
    $('#previewBtn').click(function(){
      var name   = $('#modelSel').val() || '';
      var canvas = document.getElementById('previewCanvas');
      var ctx    = canvas.getContext('2d');

      // black background
      ctx.fillStyle = '#000000';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // draw your four lines in chosen color
      ctx.fillStyle = $('#color').val() || '#FFFFFF';
      ctx.font      = '12px sans-serif';
      var y = 14;
      ['line1','line2','line3','line4'].forEach(function(id){
        ctx.fillText($('#'+id).val()||'', 0, y);
        y += 14;
      });

      // compose overlay hook URL with all params
      var params = {
        preview: 1,
        model:   name,
        color:   $('#color').val() || '#FFFFFF'
      };
      ['line1','line2','line3','line4'].forEach(function(id){
        params[id] = $('#'+id).val()||'';
      });

      // fire the overlay hook, then activate that model
      $.get('/plugin/machine/overlay?' + $.param(params))
       .always(function(){
         if (name) {
           $.post('/api/models/' + encodeURIComponent(name) + '/activate');
         }
       });
    });

    // wire change + initial load
    $('#modelSel').change(function(){
      updateModelInfo($(this).val());
    });

    loadModels();
  });
  </script>
</body>
</html>
