<?php
// Plugin entrypoint for the 'machine' overlay
// FPPPlugin base class is provided by FPP core, no additional includes required.
// NOTE: No closing '?>' tag is needed at the end of this file (per PHP best practices).

class MachinePlugin extends FPPPlugin {
    public function __construct() {
        parent::__construct();
        $this->name = 'machine';
        $this->addHook('settings');
        $this->addHook('overlay');
        $this->addMenuEntry('Machine Config', 'status', 'www/machine.html', 1);
    }

    public function overlay($args) {
        $settings = $this->getSettings();
        $this->clearOverlay();
        $lines = ['line1','line2','line3','line4'];
        $y = 0;
        foreach ($lines as $field) {
            $text  = $settings[$field] ?? '';
            $color = $settings['color'] ?? '#FFFFFF';
            $this->drawText($text, 'fixed', 0, $y, $color);
            $y += 12;  // line height
        }
    }
}

new MachinePlugin();
