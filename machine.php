<?php
require_once dirname(__FILE__) . '/fpp_plugin.php';

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
        // Clear existing overlay
        $this->clearOverlay();
        // Draw each line
        $lines = ['line1','line2','line3','line4'];
        $y = 0;
        foreach ($lines as $field) {
            $text  = isset($settings[$field]) ? $settings[$field] : '';
            $color = isset($settings['color']) ? $settings['color'] : '#FFFFFF';
            $this->drawText($text, 'fixed', 0, $y, $color);
            $y += 12; // vertical spacing
        }
    }
}

// Instantiate plugin
new MachinePlugin();
