<?php
// machine_setup.php — UI page for Machine Config (wrapped by FPP header/footer)

// Read and decode your overlay models JSON as a fallback
$configPath   = '/home/fpp/media/config/model-overlays.json';
$modelFile    = @file_get_contents($configPath);
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
    /* scale preview up for readability */
    #previewCanvas {
      border: 1px solid #888;
      margin-top: 12px;
      display: block;
      width: 320px;    /* 64px × 5 */
      height: 160px;   /* 32px × 5 */
      image-rendering: pixelated;
    }
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
  <button id="sendBtn">Send Text</button>

  <script>
  $(function(){
    var staticModels = <?php echo json_encode($staticModels, JSON_HEX_TAG); ?>;
    var modelsCfg    = [];

    function populateDropdown(models) {
      var sel = $('#modelSel').empty();
      if (!models.length) {
        sel.append($('<option disabled>').text('No models defined'));
      } else {
        models.forEach(function(m) {
          var name = (typeof m === 'string' ? m : m.Name);
          sel.append($('<option>').val(name).text(name));
        });
      }
      sel.trigger('change');
    }

    function loadModels() {
      $.getJSON('/api/overlays/models')
        .done(function(list) {
          modelsCfg = list.map(function(name){ return { Name: name }; });
          populateDropdown(list);
        })
        .fail(function() {
          modelsCfg = staticModels;
          populateDropdown(staticModels);
        });
    }

    function updateModelInfo(name) {
      $.getJSON('/api/overlays/model/' + encodeURIComponent(name))
        .done(function(info) {
          var width  = info.StringCount * info.StrandsPerString;
          var height = (info.ChannelCount / info.ChannelCountPerNode) / width;
          $('#modelWidth').text(width + ' px');
          $('#modelHeight').text(height + ' px');
          $('#previewCanvas').attr({ width: width, height: height });
        })
        .fail(function() {
          var m = modelsCfg.find(x => x.Name === name);
          if (!m) return;
          var width  = m.StringCount * m.StrandsPerString;
          var height = (m.ChannelCount / m.ChannelCountPerNode) / width;
          $('#modelWidth').text(width + ' px');
          $('#modelHeight').text(height + ' px');
          $('#previewCanvas').attr({ width: width, height: height });
        });
    }

    $('#modelSel').change(function(){
      updateModelInfo($(this).val());
    });

    $('#activateBtn').click(function(){
      var name = $('#modelSel').val();
      if (!name) return;
      $.ajax({
        url: '/api/overlays/model/' + encodeURIComponent(name) + '/state',
        type: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify({ State: 1 })
      });
    });
    $('#deactivateBtn').click(function(){
      var name = $('#modelSel').val();
      if (!name) return;
      $.ajax({
        url: '/api/overlays/model/' + encodeURIComponent(name) + '/state',
        type: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify({ State: 0 })
      });
    });

    // NEW: Send the 4 lines as one API call, then enable overlay
    $('#sendBtn').click(function(){
      var name  = $('#modelSel').val();
      if (!name) return alert('Please select a model.');

      // build a single Message with newlines
      var lines   = ['line1','line2','line3','line4'].map(id => $('#'+id).val().trim());
      var message = lines.filter(l => l).join('\n');
      if (!message) return alert('Enter at least one line of text.');

      var payload = {
        Message         : message,
        Position        : 'Center',
        Font            : 'FreeSans',
        FontSize        : 12,
        AntiAlias       : false,
        PixelsPerSecond : 0,
        Color           : $('#color').val(),
        AutoEnable      : false
      };

      // PUT the text
      $.ajax({
        url         : '/api/overlays/model/' + encodeURIComponent(name) + '/text',
        type        : 'PUT',
        contentType : 'application/json',
        data        : JSON.stringify(payload),
        success     : function(){
          // now turn it on
          $.ajax({
            url         : '/api/overlays/model/' + encodeURIComponent(name) + '/state',
            type        : 'PUT',
            contentType : 'application/json',
            data        : JSON.stringify({ State: 1 })
          });
        },
        error       : function(xhr){
          console.error('Overlay text error:', xhr.responseText);
          alert('Failed to send text: ' + xhr.statusText);
        }
      });
    });

    loadModels();
  });
  </script>
</body>
</html>
