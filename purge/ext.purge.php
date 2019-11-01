<?php

class Purge_ext
{
    public function __construct()
    {
        $this->version = ee('Addon')->get('purge')->getVersion();
    }

    /**
     * Activate extension
     */
    public function activate_extension()
    {
        $hooks = array(
            'after_channel_entry_save'   => 'sendPurgeRequest',
            'after_channel_entry_delete' => 'sendPurgeRequest',
            'cp_custom_menu'             => 'cpCustomMenu'
        );

        foreach ($hooks as $hook => $method) {
            ee('Model')->make('Extension', [
                'class'    => __CLASS__,
                'method'   => $method,
                'hook'     => $hook,
                'settings' => [],
                'version'  => $this->version,
                'enabled'  => 'y'
            ])->save();
        }
    }

    /**
     * Sends purge request to Varnish when registered EE hooks are fired
     */
    public function sendPurgeRequest($entry, $values)
    {
        $rules = ee('Model')->get('purge:Rule')->all();

        // No rules configured at all? Purge everything
        if (! $rules->count()) {
            ee('purge:Varnish')->purge();
        }

        $rules = $rules->filter(function ($rule) use ($entry) {
            return $rule->channel_id == $entry->Channel->getId();
        });

        // If rules exist but none for this channel, bail out
        if (! $rules->count()) {
            return;
        }

        foreach ($rules as $rule) {
            ee('purge:Varnish')->purgeEntryWithRule($entry, $rule);
        }
    }

    /**
     * cp_custom_menu hook handler, add Purge as a possible menu item for Menu Sets
     */
    public function cpCustomMenu($menu)
    {
        $menu->addItem(
            ee('Addon')->get('purge')->getName(),
            ee('CP/URL')->make('addons/settings/purge')
        );
    }

    /**
     * Disable extension
     */
    public function disable_extension()
    {
        ee('Model')->get('Extension')
            ->filter('class', __CLASS__)
            ->delete();
    }

    /**
     * Update extension
     */
    public function update_extension($current = '')
    {
        if ($current == '' or $current == $this->version) {
            return false;
        }
    }
}

// EOF
