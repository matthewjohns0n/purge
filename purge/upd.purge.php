<?php

class Purge_upd
{
    public function __construct()
    {
        $this->version = ee('Addon')->get('purge')->getVersion();
    }

    /**
     * Module installer
     */
    public function install()
    {
        ee('Model')->make('Module', [
            'module_name' => ee('Addon')->get('purge')->getName(),
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n'
        ])->save();

        $fields = [
            'id' => [
                'type' => 'int',
                'constraint' => '10',
                'unsigned' => true,
                'auto_increment' => true
            ],
            'site_id' => [
                'type' => 'int',
                'constraint' => '10',
                'unsigned' => true
            ],
            'channel_id' => [
                'type' => 'int',
                'constraint' => '10',
                'unsigned' => true
            ],
            'pattern' => [
                'type' => 'varchar',
                'constraint' => '255'
            ]
        ];

        ee()->load->dbforge();
        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('id', true);
        ee()->dbforge->create_table('purge_rules');

        return true;
    }

    /**
     * Module uninstaller
     */
    public function uninstall()
    {
        ee('Model')->get('Module')
            ->filter('module_name', ee('Addon')->get('purge')->getName())
            ->delete();

        ee()->load->dbforge();
        ee()->dbforge->drop_table('purge_rules');

        return true;
    }

    /**
     * Module updater
     */
    public function update($current = '')
    {
        return true;
    }
}

// EOF
