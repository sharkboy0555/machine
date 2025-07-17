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
    #previewCanvas {
      border: 1px solid #888;
      margin-top: 12px;
      display: block;
      width: 320px;    /* 64px × 5 */
      height: 160px;   /* 32px × 5 */
      image-rendering: pixelated;
    }
    .line-input { display: inline-block; width: 200px; }
    .size-input { width: 60px; margin-left: 8px; }
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
    <label>Width:</label><span id="modelWidth">–</span>
  </div>
  <div class="field">
    <label>Height:</label><span id="modelHeight">–</span>
  </div>
  <div class="field">
    <button id="activateBtn">Activate (Override)</button>
    <button id="overlayBtn">Activate (Overlay)</button>
    <button id="deactivateBtn">Deactivate</button>
  </div>

  <h2>Overlay Preview</h2>
  <canvas id="previewCanvas"></canvas>

  <h2>Manual Text Entry</h2>
  <div class="field">
    <label for="line1">Line 1:</label>
    <input id="line1" class="line-input" type="text">
    <input id="size1"  class="size-input" type="number" min="6" max="72" value="12" title="Font Size">
  </div>
  <div class="field">
    <label for="line2">Line 2:</label>
    <input id="line2" class="line-input" type="text">
    <input id="size2"  class="size-input" type="number" min="6" max="72" value="12" title="Font Size">
  </div>
  <div class="field">
    <label for="line3">Line 3:</label>
    <input id="line3" class="line-input" type="text">
    <input id="size3"  class="size-input" type="number" min="6" max="72" value="12" title="Font Size">
  </div>
  <div class="field">
    <label for="line4">Line 4:</label>
    <input id="line4" class="line-input" type="text">
    <input id="size4"  class="size-input" type="number" min="6" max="72" value="12" title="Font Size">
  </div>
  <div class="field">
    <label for="color">Color:</label>
    <input id="color" type="color" value="#FFFFFF">
  </div>
  <button id="sendBtn">Send Text</button>

  <script>
  $(function(){
    var staticModels = <?php echo json_encode($staticModels, JSON_HEX_TAG); ?>;
    var modelsCfg = [];

    function populateDropdown(models) {
      var sel = $('#modelSel').empty();
      if (!models.length) {
        sel.append($('<option disabled>').text('No models defined'));
      } else {
        models.forEach(function(m){
          var name = (typeof m === 'string' ? m : m.Name);
          sel.append($('<option>').val(name).text(name));
        });
      }
      sel.trigger('change');
    }

    function loadModels() {
      $.getJSON('/api/overlays/models')
        .done(function(list){
          modelsCfg = list.map(function(n){ return { Name: n }; });
          populateDropdown(list);
        })
        .fail(function(){
          modelsCfg = staticModels;
          populateDropdown(staticModels);
        });
    }

    function updateModelInfo(name) {
      if (!name) return;
      $.getJSON('/api/overlays/model/' + encodeURIComponent(name))
        .done(function(info){
          var width  = info.StringCount * info.StrandsPerString;
          var height = (info.ChannelCount / info.ChannelCountPerNode) / width;
          $('#modelWidth').text(width + ' px');
          $('#modelHeight').text(height + ' px');
          $('#previewCanvas').attr({ width: width, height: height });
        })
        .fail(function(){
          var m = modelsCfg.find(x=>x.Name===name);
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
    $('#overlayBtn').click(function(){
      var name = $('#modelSel').val();
      if (!name) return;
      $.ajax({
        url: '/api/overlays/model/' + encodeURIComponent(name) + '/state',
        type: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify({ State: 2 })
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

    $('#sendBtn').click(function(){
      var name = $('#modelSel').val();
      if (!name) return alert('Please select a model.');

      // collect and join lines
      var lines = ['line1','line2','line3','line4']
        .map(id=>$('#'+id).val().trim());
      var message = lines.filter(l=>l).join('\n');
      if (!message) return alert('Enter at least one line of text.');

      // use Line 1’s size input as FontSize
      var fontSize = parseInt($('#size1').val(),10) || 12;

      var payload = {
        Message         : message,
        Position        : 'Center',
        Font            : 'FreeSans',
        FontSize        : fontSize,
        AntiAlias       : false,
        PixelsPerSecond : 0,
        Color           : $('#color').val(),
        AutoEnable      : false
      };

      console.log('PUT →','/api/overlays/model/'+name+'/text', payload);

      $.ajax({
        url         : '/api/overlays/model/' + encodeURIComponent(name) + '/text',
        type        : 'PUT',
        contentType : 'application/json',
        data        : JSON.stringify(payload),
        success     : function(){
          // then re-enable in override mode
          $.ajax({
            url         : '/api/overlays/model/' + encodeURIComponent(name) + '/state',
            type        : 'PUT',
            contentType: 'application/json',
            data       : JSON.stringify({ State: 1 })
          });
        },
        error       : function(xhr){
          console.error('Text PUT error', xhr.status, xhr.responseText);
          alert('Failed to send text: ' + xhr.statusText);
        }
      });
    });

    loadModels();
  });
  </script>
</body>
</html>
