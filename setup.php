<?php
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}
/**
 * Name:        Public & User Holidays
 * Directory:   holiday
 * Version:     2.0
 * Type:        user
 * UI Name:     Holiday
 * UI Icon:     ?
 * aligned with w2p module guide - note, module name is singular as referenced in w2p_utilities_date
 */
 
$config = array();
$config['mod_name'] 				= 'Holiday';                            // the module name
$config['mod_version'] 				= '2.1';                                // this release version
$config['mod_directory'] 			= 'holiday';                            // the module path
$config['mod_setup_class'] 			= 'CSetupHolidays';                     // the name of the setup class
$config['mod_type']					= 'user';                               // 'core' for modules distributed with w2p itself, 'user' for addon modules
$config['mod_ui_name'] 				= $config['mod_name'];                  // the name that is shown in the main menu of the User Interface
$config['mod_ui_icon'] 				= 'myevo-appointments.png';             // name of a related icon
$config['mod_description'] 			= 'A module for setting working time';  // some description of the module
$config['mod_config'] 				= false;                                // show 'configure' link in viewmods
$config['mod_main_class'] 			= 'CHoliday';
$config['permissions_item_table'] 	= 'holiday';
$config['permissions_item_label'] 	= 'holiday_description';
$config['permissions_item_field'] 	= 'holiday_id';
$config['requirements']             = array(
		array('require' => 'web2project',   'comparator' => '>=', 'version' => '3')
    );                                    // don't install if less than v3

if (@$a == 'setup') {
    echo w2PshowModuleConfig( $config );
}

class CSetupHolidays extends w2p_System_Setup {
    public function install()
    {
        $result = $this->_meetsRequirements();
        if (!$result) {
            return false;
        }

        $q = $this->_getQuery();
        $q->createTable('holiday');
        $sql = '(
            `holiday_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `holiday_user` int(10) NOT NULL DEFAULT \'0\',
            `holiday_type` int(10) NOT NULL DEFAULT \'0\',
            `holiday_annual` int(10) NOT NULL DEFAULT \'0\',
            `holiday_start_date` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
            `holiday_end_date` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\',
            `holiday_description` text,
            PRIMARY KEY (`holiday_id`),
            KEY `holiday_start_end_date` (`holiday_start_date`,`holiday_end_date`),
            KEY `holiday_type` (`holiday_type`),
            KEY `holiday_user` (`holiday_user`)
            ) 
            ENGINE=MyISAM CHARACTER SET=utf8 COLLATE=utf8_general_ci';
        $q->createDefinition($sql);
        $q->exec();
        $q->clear();

        // Create settings table
        $q->createTable('holiday_settings');
        $sql = '(
            `holiday_manual` int(10) NOT NULL default \'0\',
            `holiday_auto` int(10) NOT NULL default \'0\',
            `holiday_driver` int(10) NOT NULL default \'-1\',
            `holiday_filter` int(10) NOT NULL default \'-1\',
            UNIQUE KEY `holiday_manual` (holiday_manual),
            UNIQUE KEY `holiday_auto` (holiday_auto),
            UNIQUE KEY `holiday_driver` (holiday_driver),
            UNIQUE KEY `holiday_filter` (holiday_filter)
            ) 
            ENGINE=MyISAM CHARACTER SET=utf8 COLLATE=utf8_general_ci';
        $q->createDefinition($sql);
        $q->exec();
        $q->clear();

        // Set default settings
        $q->addTable('holiday_settings');
        $q->addInsert('holiday_manual', 0);
        $q->addInsert('holiday_auto', 0);
        $q->addInsert('holiday_driver', -1);
        $q->addInsert('holiday_filter', -1);
        $q->exec();

        $i = 0;
        $user_holiday_types = array('annual leave', 'sick leave', 'unpaid leave', 'special leave', 'training', 'other holiday');
        foreach ($user_holiday_types as $user_holiday_type) {
            $q->clear();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1); // select list
            $q->addInsert('sysval_title', 'UserHolidayType');
            $q->addInsert('sysval_value', $user_holiday_type);
            $q->addInsert('sysval_value_id', $i++);
            $q->exec();
        }
        return parent::install();
	}
        
    /**
    * not sure that this function has any relevance
    * look at deleting
    */
    
    public function upgrade($old_version)
    {
        switch ($old_version) {
            case '0.1':
                // There is no way to change the name of database field with w2p_Database_Query().
                db_exec("ALTER TABLE holiday CHANGE holiday_white holiday_type int(10) NOT NULL DEFAULT '0'");
                if (db_error()) {
                    return false;
                }

                $q = new w2p_Database_Query();
                $q->alterTable('holiday');
                $q->createDefinition('index holiday_start_end_date (holiday_start_date, holiday_end_date)');
                $q->exec();
                $q->clear();

                $q->alterTable('holiday');
                $q->createDefinition('index holiday_start_end_date (holiday_start_date, holiday_end_date)');
                $q->exec();
                $q->clear();

                $q->alterTable('holiday');
                $q->createDefinition('index holiday_user (holiday_user)');
                $q->exec();
                $q->clear();

                $q->alterTable('holiday');
                $q->createDefinition('index holiday_type (holiday_type)');
                $q->exec();
                $q->clear();
                
            case '2.0':    //previous running version with NZ dates
            	
            case '2.1':    //current version
            default:
                	//do nothing
        }
        return true;
    }
    public function remove()
    {
//        global $AppUI;
        $q = $this->_getQuery();
        $q->dropTable('holiday');
        $q->exec();
        $q->clear();

        $q->dropTable('holiday_settings');
        $q->exec();
        $q->clear();

        $q->setDelete('sysvals');
        $q->addWhere("sysval_title = 'UserHolidayType'");
        $q->exec();

        return parent::remove();
    }
}