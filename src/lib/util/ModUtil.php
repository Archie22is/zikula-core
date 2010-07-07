<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Util
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

/**
 * Module Util.
 */
class ModUtil
{
    // States
    const STATE_UNINITIALISED = 1;
    const STATE_INACTIVE = 2;
    const STATE_ACTIVE = 3;
    const STATE_MISSING = 4;
    const STATE_UPGRADED = 5;
    const STATE_NOTALLOWED = 6;
    const STATE_INVALID = -1;

    const CONFIG_MODULE = '/PNConfig';

    // Types
    const TYPE_MODULE = 2;
    const TYPE_SYSTEM = 3;
    const TYPE_CORE = 4;

    // Module dependency states
    const DEPENDENCY_REQUIRED = 1;
    const DEPENDENCY_RECOMMENDED = 2;
    const DEPENDENCY_CONFLICTS = 3;

    /**
     * Memory of object oriented modules.
     *
     * @var array
     */
    protected static $ooModules = array();

    /**
     * Module info cache.
     *
     * @var array
     */
    protected static $modinfo;

    /**
     * The initCoreVars preloads some module vars.
     *
     * Preloads module vars for a number of key modules to reduce sql statements.
     *
     * @return void
     */
    public static function initCoreVars()
    {
        global $pnmodvar;

        // don't init vars during the installer
        if (System::isInstalling()) {
            return;
        }

        // if we haven't got vars for this module yet then lets get them
        if (!isset($pnmodvar)) {
            $pnmodvar = array();
            $tables   = DBUtil::getTables();
            $col      = $tables['module_vars_column'];
            $where =   "$col[modname] = '" . self::CONFIG_MODULE ."'
                     OR $col[modname] = '" . PluginUtil::CONFIG ."'
                     OR $col[modname] = '" . EventUtil::HANDLERS ."'
                     OR $col[modname] = 'Theme'
                     OR $col[modname] = 'Blocks'
                     OR $col[modname] = 'Users'
                     OR $col[modname] = 'Settings'
                     OR $col[modname] = 'SecurityCenter'";

            $profileModule = System::getVar('profilemodule', '');
            if (!empty($profileModule) && self::available($profileModule)) {
                $where .= " OR $col[modname] = '$profileModule'";
            }

            $pnmodvars = DBUtil::selectObjectArray('module_vars', $where);
            foreach ($pnmodvars as $var) {
                if (array_key_exists($var['name'],$GLOBALS['ZConfig']['System'])) {
                    $pnmodvar[$var['modname']][$var['name']] = $GLOBALS['ZConfig']['System'][$var['name']];
                } elseif ($var['value'] == '0' || $var['value'] == '1') {
                    $pnmodvar[$var['modname']][$var['name']] = $var['value'];
                } else {
                    $pnmodvar[$var['modname']][$var['name']] = unserialize($var['value']);
                }
            }
        }
    }

    /**
     * Checks to see if a module variable is set.
     *
     * @param string $modname The name of the module.
     * @param string $name    The name of the variable.
     *
     * @return boolean True if the variable exists in the database, false if not.
     */
    public static function hasVar($modname, $name)
    {
        // define input, all numbers and booleans to strings
        $modname = isset($modname) ? ((string)$modname) : '';
        $name    = isset($name) ? ((string)$name) : '';

        // make sure we have the necessary parameters
        if (!System::varValidate($modname, 'mod') || !System::varValidate($name, 'modvar')) {
            return false;
        }

        // get all module vars for this module
        $modvars = self::getVar($modname);

        return array_key_exists($name, (array)$modvars);
    }

    /**
     * The getVar method gets a module variable.
     *
     * If the name parameter is included then method returns the
     * module variable value.
     * if the name parameter is ommitted then method returns a multi
     * dimentional array of the keys and values for the module vars.
     *
     * @param string  $modname The name of the module.
     * @param string  $name    The name of the variable.
     * @param boolean $default The value to return if the requested modvar is not set.
     *
     * @return  string|array If the name parameter is included then method returns
     *          string - module variable value
     *          if the name parameter is ommitted then method returns
     *          array - multi dimentional array of the keys
     *                  and values for the module vars.
     */
    public static function getVar($modname, $name = '', $default = false)
    {
        // if we don't know the modname then lets assume it is the current
        // active module
        if (!isset($modname)) {
            $modname = self::getName();
        }

        global $pnmodvar;

        // if we haven't got vars for this module yet then lets get them
        if (!isset($pnmodvar[$modname])) {
            $tables = DBUtil::getTables();
            $col    = $tables['module_vars_column'];
            $where  = "WHERE $col[modname] = '" . DataUtil::formatForStore($modname) . "'";
            $sort   = ' '; // this is not a mistake, it disables the default sort for DBUtil::selectFieldArray()

            $results = DBUtil::selectFieldArray('module_vars', 'value', $where, $sort, false, 'name');
            foreach ($results as $k => $v) {
                // ref #2045 vars are being stored with 0/1 unserialised.
                if (array_key_exists($k,$GLOBALS['ZConfig']['System'])) {
                    $pnmodvar[$modname][$k] = $GLOBALS['ZConfig']['System'][$k];
                } else if ($v == '0' || $v == '1') {
                    $pnmodvar[$modname][$k] = $v;
                } else {
                    $pnmodvar[$modname][$k] = unserialize($v);
                }
            }
        }

        // if they didn't pass a variable name then return every variable
        // for the specified module as an associative array.
        // array('var1' => value1, 'var2' => value2)
        if (empty($name) && array_key_exists($modname, $pnmodvar)) {
            return $pnmodvar[$modname];
        }

        // since they passed a variable name then only return the value for
        // that variable
        if (isset($pnmodvar[$modname]) && array_key_exists($name, $pnmodvar[$modname])) {
            return $pnmodvar[$modname][$name];
        }

        // we don't know the required module var but we established all known
        // module vars for this module so the requested one can't exist.
        // we return the default (which itself defaults to false)
        return $default;
    }

    /**
     * The setVar method sets a module variable.
     *
     * @param string $modname The name of the module.
     * @param string $name    The name of the variable.
     * @param string $value   The value of the variable.
     *
     * @return boolean True if successful, false otherwise.
     */
    public static function setVar($modname, $name, $value = '')
    {
        // define input, all numbers and booleans to strings
        $modname = isset($modname) ? ((string)$modname) : '';

        // validate
        if (!System::varValidate($modname, 'mod') || !isset($name)) {
            return false;
        }

        global $pnmodvar;

        $obj = array();
        $obj['value'] = serialize($value);

        if (self::hasVar($modname, $name)) {
            $tables = DBUtil::getTables();
            $cols   = $tables['module_vars_column'];
            $where  = "WHERE $cols[modname] = '" . DataUtil::formatForStore($modname) . "'
                         AND $cols[name] = '" . DataUtil::formatForStore($name) . "'";
            $res = DBUtil::updateObject($obj, 'module_vars', $where);
        } else {
            $obj['name']    = $name;
            $obj['modname'] = $modname;
            $res = DBUtil::insertObject($obj, 'module_vars');
        }

        if ($res) {
            $pnmodvar[$modname][$name] = $value;
        }

        return (bool)$res;
    }

    /**
     * The setVars method sets multiple module variables.
     *
     * @param string $modname The name of the module.
     * @param array  $vars    An associative array of varnames/varvalues.
     *
     * @return boolean True if successful, false otherwise.
     */
    public static function setVars($modname, array $vars)
    {
        $ok = true;
        foreach ($vars as $var => $value) {
            $ok = $ok && self::setVar($modname, $var, $value);
        }
        return $ok;
    }

    /**
     * The delVar method deletes a module variable.
     *
     * Delete a module variables. If the optional name parameter is not supplied all variables
     * for the module 'modname' are deleted.
     *
     * @param string $modname The name of the module.
     * @param string $name    The name of the variable (optional).
     *
     * @return boolean True if successful, false otherwise.
     */
    public static function delVar($modname, $name = '')
    {
        // define input, all numbers and booleans to strings
        $modname = isset($modname) ? ((string)$modname) : '';

        // validate
        if (!System::varValidate($modname, 'modvar')) {
            return false;
        }

        global $pnmodvar;

        $val = null;
        if (empty($name)) {
            if (array_key_exists($modname, $pnmodvar)) {
                unset($pnmodvar[$modname]);
            }
        } else {
            if (array_key_exists($name, $pnmodvar[$modname])) {
                $val = $pnmodvar[$modname][$name];
                unset($pnmodvar[$modname][$name]);
            }
        }

        $tables = DBUtil::getTables();
        $cols   = $tables['module_vars_column'];

        // check if we're deleting one module var or all module vars
        $specificvar = '';
        $name    = DataUtil::formatForStore($name);
        $modname = DataUtil::formatForStore($modname);
        if (!empty($name)) {
            $specificvar = " AND $cols[name] = '$name'";
        }

        $where = "WHERE $cols[modname] = '$modname' $specificvar";
        $res = (bool)DBUtil::deleteWhere('module_vars', $where);
        return ($val ? $val : $res);
    }

    /**
     * Get Module meta info.
     *
     * @param string $module Module name.
     *
     * @return array|boolean Module information array or false.
     */
    public static function getInfoFromName($module)
    {
        return self::getInfo(self::getIdFromName($module));
    }

    /**
     * The getIdFromName method gets module ID given its name.
     *
     * @param string $module The name of the module.
     *
     * @return integer module ID.
     */
    public static function getIdFromName($module)
    {
        // define input, all numbers and booleans to strings
        $module = (isset($module) ? strtolower((string)$module) : '');

        // validate
        if (!System::varValidate($module, 'mod')) {
            return false;
        }

        static $modid;

        if (!is_array($modid) || System::isInstalling()) {
            $modules = self::getModsTable();

            if ($modules === false) {
                return false;
            }

            foreach ($modules as $mod) {
                $mName = strtolower($mod['name']);
                $modid[$mName] = $mod['id'];
                if (isset($mod['url']) && $mod['url']) {
                    $mdName = strtolower($mod['url']);
                    $modid[$mdName] = $mod['id'];
                }
            }

            if (!isset($modid[$module])) {
                $modid[$module] = false;
                return false;
            }
        }

        if (isset($modid[$module])) {
            return $modid[$module];
        }

        return false;
    }

    /**
     * The getInfo method gets information on module.
     *
     * Return array of module information or false if core ( id = 0 ).
     *
     * @param integer $modid The module ID.
     *
     * @return array|boolean Module information array or false.
     */
    public static function getInfo($modid = 0)
    {
        // a $modid of 0 is associated with the core ( pn_blocks.mid, ... ).
        if (!is_numeric($modid)) {
            return false;
        }

        if (!is_array(self::$modinfo) || System::isInstalling()) {
            self::$modinfo = self::getModsTable();

            if (!self::$modinfo) {
                return null;
            }

            if (!isset(self::$modinfo[$modid])) {
                self::$modinfo[$modid] = false;
                return self::$modinfo[$modid];
            }
        }

        if (isset(self::$modinfo[$modid])) {
            return self::$modinfo[$modid];
        }

        return false;
    }

    /**
     * The getUserMods method gets a list of user modules.
     *
     * @deprecated
     * @see ModUtil::getModulesCapableOf()
     *
     * @return array An array of module information arrays.
     */
    public static function getUserMods()
    {
        return self::getTypeMods('user');
    }

    /**
     * The getProfileMods method gets a list of profile modules.
     *
     * @deprecated
     * @see ModUtil::getModulesCapableOf()
     *
     * @return array An array of module information arrays.
     */
    public static function getProfileMods()
    {
        return self::getTypeMods('profile');
    }

    /**
     * The getMessageMods method gets a list of message modules.
     *
     * @return array An array of module information arrays.
     */
    public static function getMessageMods()
    {
        return self::getTypeMods('message');
    }

    /**
     * The getAdminMods method gets a list of administration modules.
     *
     * @deprecated
     * @see ModUtil::getModulesCapableOf()
     *
     * @return array An array of module information arrays.
     */
    public static function getAdminMods()
    {
        return self::getTypeMods('admin');
    }

    /**
     * The getTypeMods method gets a list of modules by module type.
     *
     * @param string $capability The module type to get (either 'user' or 'admin') (optional) (default='user').
     *
     * @return array An array of module information arrays.
     */
    public static function getModulesCapableOf($capability = 'user')
    {
        static $modcache = array();

        if (!isset($modcache[$capability]) || !$modcache[$capability]) {
            $modcache[$capability] = array();
            $mods = self::getAllMods();
            foreach ($mods as $key => $mod) {
                if (isset($mod['capabilities'][$capability])) {
                    $modcache[$capability][] = $mods[$key];
                }
            }
        }

        return $modcache[$capability];
    }

    /**
     * Get mod types.
     *
     * @deprecated
     * @see ModUtil::getModulesCapableOf()
     */
    public static function getTypeMods($type = 'user')
    {
        return self::getModulesCapableOf($type);
    }

    public static function isCapable($module, $capability)
    {
        $modinfo = self::getInfoFromName($module);
        if (!$modinfo) {
            return false;
        }
        return (bool)array_key_exists($capability, $modinfo['capabilities']);
    }

    public static function getCapabilitiesOf($module)
    {
        $modules = self::getAllMods();
        if (array_key_exists($module, $modules)) {
            return $module['capabilities'];
        }
        return false;
    }

    /**
     * The getAllMods method gets a list of all modules.
     *
     * @return array An array of module information arrays.
     */
    public static function getAllMods()
    {
        static $modsarray = array();

        if (empty($modsarray)) {
            $tables  = DBUtil::getTables();
            $cols    = $tables['modules_column'];
            $where   = "WHERE $cols[state] = " . self::STATE_ACTIVE . "
                           OR $cols[name] = 'Modules'";
            $orderBy = "ORDER BY $cols[displayname]";

            $modsarray = DBUtil::selectObjectArray('modules', $where, $orderBy);
            if ($modsarray === false) {
                return false;
            }
            foreach ($modsarray as $key => $mod) {
                $capabilities = unserialize($mod['capabilities']);
                $modsarray[$key]['capabilities'] = $capabilities;
            }
        }

        return $modsarray;
    }

    /**
     * Loads database definition for a module.
     *
     * @param string  $modname   The name of the module to load database definition for.
     * @param string  $directory Directory that module is in (if known).
     * @param boolean $force     Force table information to be reloaded.
     *
     * @return boolean True if successful, false otherwise.
     */
    public static function dbInfoLoad($modname, $directory = '', $force = false)
    {
        // define input, all numbers and booleans to strings
        $modname = (isset($modname) ? strtolower((string)$modname) : '');

        // default return value
        $data = false;

        // validate
        if (!System::varValidate($modname, 'mod')) {
            return $data;
        }

        static $loaded = array();

        // check to ensure we aren't doing this twice
        if (isset($loaded[$modname]) && !$force) {
            $data = true;
            return $data;
        }

        // get the directory if we don't already have it
        if (empty($directory)) {
            // get the module info
            $modinfo = self::getInfo(self::getIdFromName($modname));
            $directory = $modinfo['directory'];

            $modpath = ($modinfo['type'] == self::TYPE_SYSTEM) ? 'system' : 'modules';
        } else {
            $modpath = is_dir("system/$directory") ? 'system' : 'modules';
        }

        // no need for pntables scan if using Doctrine
        $doctrineModelDir = "$modpath/$directory/lib/$directory/Model";
        if (is_dir($doctrineModelDir)) {
            $loaded[$modname] = true;
            return true;
        }

        // Load the database definition if required
        $files = array();
        //$files[] = "config/functions/$directory/tables.php";
        $files[] = "$modpath/$directory/tables.php";
        //$files[] = "config/functions/$directory/pntables.php";
        $files[] = "$modpath/$directory/pntables.php";

        if (Loader::loadOneFile($files)) {
            $tablefunc = $modname . '_tables';
            $tablefuncOld = $modname . '_pntables';
            if (function_exists($tablefunc)) {
                $data = call_user_func($tablefunc);
            } elseif (function_exists($tablefuncOld)) {
                $data = call_user_func($tablefuncOld);
            }

            // Generate _column automatically from _column_def if it is not present.
            foreach ($data as $key => $value) {
                $table_col = substr($key, 0, -4);
                if (substr($key, -11) == "_column_def" && !$data[$table_col]) {
                    foreach ($value as $fieldname => $def) {
                        $data[$table_col][$fieldname] = $fieldname;
                    }
                }
            }

            $GLOBALS['dbtables'] = array_merge((array)$GLOBALS['dbtables'], (array)$data);
            $loaded[$modname] = true;
        }

        // return data so we know which tables were loaded by this module
        return $data;
    }

    /**
     * Loads a module.
     *
     * @param string  $modname The name of the module.
     * @param string  $type    The type of functions to load.
     * @param boolean $force   Determines to load Module even if module isn't active.
     *
     * @return string|boolean Name of module loaded, or false on failure.
     */
    public static function load($modname, $type = 'user', $force = false)
    {
        if (strtolower(substr($type, -3)) == 'api') {
            return false;
        }
        return self::loadGeneric($modname, $type, $force);
    }

    /**
     * Load an API module.
     *
     * @param string  $modname The name of the module.
     * @param string  $type    The type of functions to load.
     * @param boolean $force   Determines to load Module even if module isn't active.
     *
     * @return string|boolean Name of module loaded, or false on failure.
     */
    public static function loadApi($modname, $type = 'user', $force = false)
    {
        return self::loadGeneric($modname, $type, $force, true);
    }

    /**
     * Load a module.
     *
     * This loads/set's up a module.  For classic style modules, it tests to see
     * if the module type files exist, admin.php, user.php etc and includes them.
     * If they do not exist, it will return false.
     *
     * Loading a module simply means making the functions/methods available
     * by loading the files and other tasks like binding any language domain.
     *
     * For OO style modules this means registering the main module autoloader,
     * and binding any language domain.
     *
     * @param string  $modname The name of the module.
     * @param string  $type    The type of functions to load.
     * @param boolean $force   Determines to load Module even if module isn't active.
     * @param boolean $api     Whether or not to load an API (or regular) module.
     *
     * @return string|boolean Name of module loaded, or false on failure.
     */
    public static function loadGeneric($modname, $type = 'user', $force = false, $api = false)
    {
        // define input, all numbers and booleans to strings
        $osapi = ($api ? 'api' : '');
        $modname = isset($modname) ? ((string)$modname) : '';
        $modtype = strtolower("$modname{$type}{$osapi}");

        static $loaded = array();

        if (!empty($loaded[$modtype])) {
            // Already loaded from somewhere else
            return $loaded[$modtype];
        }

        // this is essential to call separately and not in the condition below - drak
        $available = self::available($modname, $force);

        // check the modules state
        if (!$force && !$available && $modname != 'Modules') {
            return false;
        }

        // get the module info
        $modinfo = self::getInfo(self::getIdFromName($modname));
        // check for bad System::varValidate($modname)
        if (!$modinfo) {
            return false;
        }

        // create variables for the OS preped version of the directory
        $modpath = ($modinfo['type'] == self::TYPE_SYSTEM) ? 'system' : 'modules';

        // if class is loadable or has been loaded exit here.
        if (self::isIntialized($modname)) {
            self::_loadStyleSheets($modname, $api, $type);
            return $modname;
        }

        // is OOP module
        if (self::isOO($modname)) {
            self::initOOModule($modname);
        } else {
            $osdir   = DataUtil::formatForOS($modinfo['directory']);
            $ostype  = DataUtil::formatForOS($type);

            $cosfile = "config/functions/$osdir/pn{$ostype}{$osapi}.php";
            $mosfile = "$modpath/$osdir/pn{$ostype}{$osapi}.php";
            $mosdir  = "$modpath/$osdir/pn{$ostype}{$osapi}";

            if (file_exists($cosfile)) {
                // Load the file from config
                include_once $cosfile;
            } elseif (file_exists($mosfile)) {
                // Load the file from modules
                include_once $mosfile;
            } elseif (is_dir($mosdir)) {
            } else {
                // File does not exist
                return false;
            }
        }

        $loaded[$modtype] = $modname;

        if ($modinfo['type'] == self::TYPE_MODULE) {
            ZLanguage::bindModuleDomain($modname);
        }

        // Load database info
        self::dbInfoLoad($modname, $modinfo['directory']);

        self::_loadStyleSheets($modname, $api, $type);

        $event = new Zikula_Event('module.postloadgeneric', null, array('modinfo' => $modinfo, 'type' => $type, 'force' => $force, 'api' => $api));
        EventUtil::notify($event);

        return $modname;
    }

    /**
     * Initialise all modules.
     *
     * @return void
     */
    public static function loadAll()
    {
        $modules = self::getModsTable();
        unset($modules[0]);
        foreach ($modules as $module) {
            if (self::available($module['name'])) {
                self::loadGeneric($module['name']);
            }
        }
    }

    /**
     * Add stylesheet to the page vars.
     *
     * This makes the modulestylesheet plugin obsolete,
     * but only for non-api loads as we would pollute the stylesheets
     * not during installation as the Theme engine may not be available yet and not for system themes
     * TODO: figure out how to determine if a userapi belongs to a hook module and load the
     *       corresponding css, perhaps with a new entry in modules table?
     *
     * @param string  $modname Module name.
     * @param boolean $api     Whether or not it's a api load.
     * @param string  $type    Type.
     *
     * @return void
     */
    private static function _loadStyleSheets($modname, $api, $type)
    {
        if (!System::isInstalling() && !$api) {
            PageUtil::addVar('stylesheet', ThemeUtil::getModuleStylesheet($modname));
            if ($type == 'admin') {
                // load special admin.css for administrator backend
                PageUtil::addVar('stylesheet', ThemeUtil::getModuleStylesheet('Admin', 'admin.css'));
            }
        }
    }

    /**
     * Get module class.
     *
     * @param string  $modname Module name.
     * @param string  $type    Type.
     * @param boolean $api     Whether or not to get the api class.
     * @param boolean $force   Whether or not to force load.
     *
     * @return boolean|string Class name.
     */
    public static function getClass($modname, $type, $api = false, $force = false)
    {
        // do not cache this process - drak
        if (!self::isOO($modname)) {
            return false;
        }

        if ($api) {
            $result = self::loadApi($modname, $type);
        } else {
            $result = self::load($modname, $type);
        }

        if (!$result) {
            return false;
        }

        $modinfo = self::getInfo(self::getIDFromName($modname));

        $className = ($api) ? ucwords($modname) . '_Api_' . ucwords($type) : ucwords($modname). '_Controller_'. ucwords($type);

        // allow overriding the OO class (to override existing methods using inheritance).
        $event = new Zikula_Event('module.custom_classname', null, array('modname', 'modinfo' => $modinfo, 'type' => $type, 'api' => $api), $className);
        EventUtil::notifyUntil($event);
        if ($event->hasNotified()) {
            $className = $event->getData();
        }

        // check the modules state
        if (!$force && !self::available($modname) && $modname != 'Modules') {
            return false;
        }

        if (class_exists($className)) {
            return $className;
        }

        return false;
    }

    /**
     * Checks if module has the given controller.
     *
     * @param string $modname Module name.
     * @param string $type    Controller type.
     *
     * @return boolean
     */
    public static function hasController($modname, $type)
    {
        return (bool)self::getClass($modname, $type);
    }

    /**
     * Checks if module has the given API class.
     *
     * @param string $modname Module name.
     * @param string $type    API type.
     *
     * @return boolean
     */
    public static function hasApi($modname, $type)
    {
        return (bool)self::getClass($modname, $type, true);
    }

    /**
     * Get class object.
     *
     * @param string $className Class name.
     *
     * @throws LogicException If $className is neither a Zikula_API nor a Zikula_Controller.
     * @return object Module object.
     */
    public static function getObject($className)
    {
        if (!$className) {
            return false;
        }

        $serviceId = strtolower("module.$className");
        $sm = ServiceUtil::getManager();

        $callable = false;
        if ($sm->hasService($serviceId)) {
            $object = $sm->getService($serviceId);
        } else {
            $r = new ReflectionClass($className);
            $object = $r->newInstanceArgs(array($sm));
            try {
                if (strrpos($className, 'Api') && !$object instanceof Zikula_Api) {
                    throw new LogicException(sprintf('Api %s must inherit from Zikula_Api', $className));
                } elseif (!strrpos($className, 'Api') && !$object instanceof Zikula_Controller) {
                    throw new LogicException(sprintf('Controller %s must inherit from Zikula_Controller', $className));
                }
            } catch (LogicException $e) {
                if (System::isDevelopmentMode()) {
                    throw $e;
                } else {
                    LogUtil::registerError('A fatal error has occured which can be viewed only in development mode.', 500);
                    return false;
                }
            }
            $sm->attachService(strtolower($serviceId), $object);
        }

        return $object;
    }

    /**
     * Get info if callable.
     *
     * @param string  $modname Module name.
     * @param string  $type    Type.
     * @param string  $func    Function.
     * @param boolean $api     Whether or not this is an api call.
     * @param boolean $force   Whether or not force load.
     *
     * @return mixed
     */
    public static function getCallable($modname, $type, $func, $api = false, $force = false)
    {
        $className = self::getClass($modname, $type, $api, $force);
        if (!$className) {
            return false;
        }

        $object = self::getObject($className);
        if (is_callable(array($object, $func))) {
            return array('serviceid' => strtolower("module.$className"), 'classname' => $className, 'callable' => array($object, $func));
        }

        return false;
    }

    /**
     * Run a module function.
     *
     * @param string  $modname    The name of the module.
     * @param string  $type       The type of function to run.
     * @param string  $func       The specific function to run.
     * @param array   $args       The arguments to pass to the function.
     * @param boolean $api        Whether or not to execute an API (or regular) function.
     * @param string  $instanceof Perform instanceof checking of target class.
     *
     * @throws Zikula_Exception_NotFound If method was not found.
     * @return mixed.
     */
    public static function exec($modname, $type = 'user', $func = 'main', $args = array(), $api = false, $instanceof = null)
    {
        // define input, all numbers and booleans to strings
        $modname = isset($modname) ? ((string)$modname) : '';
        $ftype = ($api ? 'api' : '');
        $loadfunc = ($api ? 'ModUtil::loadApi' : 'ModUtil::load');

        // validate
        if (!System::varValidate($modname, 'mod')) {
            return null;
        }

        $modinfo = self::getInfo(self::getIDFromName($modname));
        $path = ($modinfo['type'] == self::TYPE_SYSTEM ? 'system' : 'modules');

        $controller = null;
        $modfunc = null;
        $loaded = call_user_func_array($loadfunc, array($modname, $type));
        if (self::isOO($modname)) {
            $result = self::getCallable($modname, $type, $func, $api);
            if ($result) {
                $modfunc = $result['callable'];
                $controller = $modfunc[0];
                if (!is_null($instanceof)) {
                    if (!$controller instanceof $instanceof) {
                        throw new InvalidArgumentException(__f('%1$s must be an instance of $2$s', array(get_class($controller), $instanceof)));
                    }
                }
                
            }
        }

        $modfunc = ($modfunc) ? $modfunc : "{$modname}_{$type}{$ftype}_{$func}";

        if ($loaded) {
            $preExecuteEvent = new Zikula_Event('module.preexecute', $controller, array('modname' => $modname, 'modfunc' => $modfunc, 'args' => $args, 'modinfo' => $modinfo, 'type' => $type, 'api' => $api));
            $postExecuteEvent = new Zikula_Event('module.postexecute', $controller, array('modname' => $modname, 'modfunc' => $modfunc, 'args' => $args, 'modinfo' => $modinfo, 'type' => $type, 'api' => $api));

            if (is_callable($modfunc)) {
                EventUtil::notify($preExecuteEvent);

                // Check $modfunc is an object instance (OO) or a function (old)
                if (is_array($modfunc)) {
                    if ($modfunc[0] instanceof Zikula_Controller) {
                        $reflection = call_user_func(array($modfunc[0], 'getReflection'));
                        $subclassOfReflection = new ReflectionClass($reflection->getParentClass());
                        if ($subclassOfReflection->hasMethod($modfunc[1])) {
                            // Don't allow front controller to access any public methods inside the controller's parents
                            throw new Zikula_Exception_NotFound();
                        }
                        $modfunc[0]->preInvokeMethod();
                    }

                    $postExecuteEvent->setData(call_user_func($modfunc, $args));
                } else {
                    $postExecuteEvent->setData($modfunc($args));
                }

                return EventUtil::notify($postExecuteEvent)->getData();
            }

            // get the theme
            if ($GLOBALS['loadstages'] & System::CORE_STAGES_THEME) {
                $theme = ThemeUtil::getInfo(ThemeUtil::getIDFromName(UserUtil::getTheme()));
                if (file_exists($file = 'themes/' . $theme['directory'] . '/functions/' . $modname . "/pn{$type}{$ftype}/$func.php")) {
                    include_once $file;
                    if (function_exists($modfunc)) {
                        EventUtil::notify($preExecuteEvent);
                        $postExecuteEvent->setData($modfunc($args));
                        return EventUtil::notify($postExecuteEvent)->getData();
                    }
                }
            }

            if (file_exists($file = "config/functions/$modname/pn{$type}{$ftype}/$func.php")) {
                include_once $file;
                if (is_callable($modfunc)) {
                    EventUtil::notify($preExecuteEvent);
                    $postExecuteEvent->setData($modfunc($args));
                    return EventUtil::notify($postExecuteEvent)->getData();
                }
            }

            if (file_exists($file = "$path/$modname/pn{$type}{$ftype}/$func.php")) {
                include_once $file;
                if (is_callable($modfunc)) {
                    EventUtil::notify($preExecuteEvent);
                    $postExecuteEvent->setData($modfunc($args));
                    return EventUtil::notify($postExecuteEvent)->getData();
                }
            }

            // try to load plugin
            // This kind of eventhandler should
            // 1. Check $event['modfunc'] to see if it should run else exit silently.
            // 2. Do something like $result = {$event['modfunc']}({$event['args'});
            // 3. Save the result $event->setData($result).
            // 4. $event->setNotify().
            // return void

            // This event means that no $type was found
            $event = new Zikula_Event('module.type_not_found', null, array('modfunc' => $modfunc, 'args' => $args, 'modinfo' => $modinfo, 'type' => $type, 'api' => $api), false);
            EventUtil::notifyUntil($event);

            if ($preExecuteEvent->hasNotified()) {
                return $preExecuteEvent->getData();
            }

            return false;
        }
    }


    /**
     * Run a module function.
     *
     * @param string  $modname    The name of the module.
     * @param string  $type       The type of function to run.
     * @param string  $func       The specific function to run.
     * @param array   $args       The arguments to pass to the function.
     * @param string  $instanceof Perform instanceof checking of target class.
     *
     * @return mixed.
     */
    public static function func($modname, $type = 'user', $func = 'main', $args = array(), $instanceof = null)
    {
        return self::exec($modname, $type, $func, $args, false, $instanceof);
    }

    /**
     * Run an module API function.
     *
     * @param string  $modname    The name of the module.
     * @param string  $type       The type of function to run.
     * @param string  $func       The specific function to run.
     * @param array   $args       The arguments to pass to the function.
     * @param string  $instanceof Perform instanceof checking of target class.
     *
     * @return mixed.
     */
    public static function apiFunc($modname, $type = 'user', $func = 'main', $args = array(), $instanceof = null)
    {
        if (empty($type)) {
            $type = 'user';
        } elseif (!System::varValidate($type, 'api')) {
            return null;
        }

        if (empty($func)) {
            $func = 'main';
        }

        return self::exec($modname, $type, $func, $args, true, $instanceof);
    }


    /**
     * Generate a module function URL.
     *
     * If the module is non-API compliant (type 1) then
     * a) $func is ignored.
     * b) $type=admin will generate admin.php?module=... and $type=user will generate index.php?name=...
     *
     * @param string       $modname      The name of the module.
     * @param string       $type         The type of function to run.
     * @param string       $func         The specific function to run.
     * @param array        $args         The array of arguments to put on the URL.
     * @param boolean|null $ssl          Set to constant null,true,false $ssl = true not $ssl = 'true'  null - leave the current status untouched,
     *                                   true - create a ssl url, false - create a non-ssl url.
     * @param string       $fragment     The framgment to target within the URL.
     * @param boolean|null $fqurl        Fully Qualified URL. True to get full URL, eg for Redirect, else gets root-relative path unless SSL.
     * @param boolean      $forcelongurl Force ModUtil::url to not create a short url even if the system is configured to do so.
     * @param boolean      $forcelang    Forcelang.
     *
     * @return sting Absolute URL for call
     */
    public static function url($modname, $type = 'user', $func = 'main', $args = array(), $ssl = null, $fragment = null, $fqurl = null, $forcelongurl = false, $forcelang=false)
    {
        // define input, all numbers and booleans to strings
        $modname = isset($modname) ? ((string)$modname) : '';

        // validate
        if (!System::varValidate($modname, 'mod')) {
            return null;
        }

        //get the module info
        $modinfo = self::getInfo(self::getIDFromName($modname));

        // set the module name to the display name if this is present
        if (isset($modinfo['url']) && !empty($modinfo['url'])) {
            $modname = rawurlencode($modinfo['url']);
        }

        // define some statics as this API is likely to be called many times
        static $entrypoint, $host, $baseuri, $https, $shorturls, $shorturlstype, $shorturlsstripentrypoint, $shorturlsdefaultmodule;

        // entry point
        if (!isset($entrypoint)) {
            $entrypoint = System::getVar('entrypoint');
        }
        // Hostname
        if (!isset($host)) {
            $host = System::serverGetVar('HTTP_HOST');
        }
        if (empty($host)) {
            return false;
        }
        // Base URI
        if (!isset($baseuri)) {
            $baseuri = System::getBaseUri();
        }
        // HTTPS Support
        if (!isset($https)) {
            $https = System::serverGetVar('HTTPS');
        }
        // use friendly url setup
        if (!isset($shorturls)) {
            $shorturls = System::getVar('shorturls');
        }
        if (!isset($shorturlstype)) {
            $shorturlstype = System::getVar('shorturlstype');
        }
        if (!isset($shorturlsstripentrypoint)) {
            $shorturlsstripentrypoint = System::getVar('shorturlsstripentrypoint');
        }
        if (!isset($shorturlsdefaultmodule)) {
            $shorturlsdefaultmodule = System::getVar('shorturlsdefaultmodule');
        }
        if (isset($args['returnpage'])) {
            $shorturls = false;
        }

        $language = ($forcelang ? $forcelang : ZLanguage::getLanguageCode());

        // Only produce full URL when HTTPS is on or $ssl is set
        $siteRoot = '';
        if ((isset($https) && $https == 'on') || $ssl != null || $fqurl == true) {
            $protocol = 'http' . (($https == 'on' && $ssl !== false) || $ssl === true ? 's' : '');
            $secureDomain = System::getVar('secure_domain');
            $siteRoot = $protocol . '://' . (($secureDomain != '') ? $secureDomain : ($host . $baseuri)) . '/';
        }

        // Only convert User URLs. Exclude links that append a theme parameter
        if ($shorturls && $shorturlstype == 0 && $type == 'user' && $forcelongurl == false) {
            if (isset($args['theme'])) {
                $theme = $args['theme'];
                unset($args['theme']);
            }
            // Module-specific Short URLs
            $url = self::apiFunc($modinfo['name'], 'user', 'encodeurl', array('modname' => $modname, 'type' => $type, 'func' => $func, 'args' => $args));
            if (empty($url)) {
                // depending on the settings, we have generic directory based short URLs:
                // [language]/[module]/[function]/[param1]/[value1]/[param2]/[value2]
                // [module]/[function]/[param1]/[value1]/[param2]/[value2]
                $vars = '';
                foreach ($args as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $k2 => $w) {
                            if (is_numeric($w) || !empty($w)) {
                                // we suppress '', but allow 0 as value (see #193)
                                $vars .= '/' . $k . '[' . $k2 . ']/' . $w; // &$k[$k2]=$w
                            }
                        }
                    } elseif (is_numeric($v) || !empty($v)) {
                        // we suppress '', but allow 0 as value (see #193)
                        $vars .= "/$k/$v"; // &$k=$v
                    }
                }
                $vars = substr($vars, 1);
                if ((!empty($func) && $func != 'main') || $vars != '') {
                    $func = "/$func/";
                } else {
                    $func = '/';
                }
                $url = $modname . $func . $vars;
            }

            if ($shorturlsdefaultmodule == $modinfo['name'] && $url != "{$modinfo['url']}/") {
                $url = str_replace("{$modinfo['url']}/", '', $url);
            }
            if (isset($theme)) {
                $url = rawurlencode($theme) . '/' . $url;
            }

            // add language param to short url
            if (ZLanguage::isRequiredLangParam() || $forcelang) {
                $url = "$language/" . $url;
            }
            if (!$shorturlsstripentrypoint) {
                $url = "$entrypoint/$url" . (!empty($query) ? '?' . $query : '');
            } else {
                $url = "$url" . (!empty($query) ? '?' . $query : '');
            }

        } else {
            // Regular URLs

            // The arguments
            $urlargs = "module=$modname";
            if ((!empty($type)) && ($type != 'user')) {
                $urlargs .= "&type=$type";
            }
            if ((!empty($func)) && ($func != 'main')) {
                $urlargs .= "&func=$func";
            }

            // add lang param to URL
            if (ZLanguage::isRequiredLangParam() || $forcelang) {
                $urlargs .= "&lang=$language";
            }

            $url = "$entrypoint?$urlargs";

            if (!is_array($args)) {
                return false;
            } else {
                foreach ($args as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $l => $w) {
                            if (is_numeric($w) || !empty($w)) {
                                // we suppress '', but allow 0 as value (see #193)
                                $url .= "&$k" . "[$l]=$w";
                            }
                        }
                    } elseif (is_numeric($v) || !empty($v)) {
                        // we suppress '', but allow 0 as value (see #193)
                        $url .= "&$k=$v";
                    }
                }
            }
        }

        if (isset($fragment)) {
            $url .= '#' . $fragment;
        }

        return $siteRoot . $url;
    }

    /**
     * Check if a module is available.
     *
     * @param string  $modname The name of the module.
     * @param boolean $force   Force.
     *
     * @return boolean True if the module is available, false if not.
     */
    public static function available($modname = null, $force = false)
    {
        // define input, all numbers and booleans to strings
        $modname = (isset($modname) ? strtolower((string)$modname) : '');

        // validate
        if (!System::varValidate($modname, 'mod')) {
            return false;
        }

        static $modstate = array();

        if (!isset($modstate[$modname]) || $force == true) {
            $modinfo = self::getInfo(self::getIDFromName($modname));
            if (isset($modinfo['state'])) {
                $modstate[$modname] = $modinfo['state'];
            }
        }

        if ($force == true) {
            $modstate[$modname] = self::STATE_ACTIVE;
        }

        if ((isset($modstate[$modname]) &&
                        $modstate[$modname] == self::STATE_ACTIVE) || (preg_match('/(modules|admin|theme|block|groups|permissions|users)/i', $modname) &&
                        (isset($modstate[$modname]) && ($modstate[$modname] == self::STATE_UPGRADED || $modstate[$modname] == self::STATE_INACTIVE)))) {
            return true;
        }

        return false;
    }

    /**
     * Get name of current top-level module.
     *
     * @return string The name of the current top-level module, false if not in a module.
     */
    public static function getName()
    {
        static $module;

        if (!isset($module)) {
            $type   = FormUtil::getPassedValue('type', null, 'GETPOST', FILTER_SANITIZE_STRING);
            $module = FormUtil::getPassedValue('module', null, 'GETPOST', FILTER_SANITIZE_STRING);

            if (empty($module)) {
                $module = System::getVar('startpage');
            }

            // the parameters may provide the module alias so lets get
            // the real name from the db
            $modinfo = self::getInfo(self::getIdFromName($module));
            if (isset($modinfo['name'])) {
                $module = $modinfo['name'];
                if ((!$type == 'init' || !$type == 'initeractiveinstaller') && !self::available($module)) {
                    $module = System::getVar('startpage');
                }
            }
        }

        return $module;
    }

    /**
     * Register a hook function.
     *
     * @param object $hookobject The hook object.
     * @param string $hookaction The hook action.
     * @param string $hookarea   The area of the hook (either 'GUI' or 'API').
     * @param string $hookmodule Name of the hook module.
     * @param string $hooktype   Name of the hook type.
     * @param string $hookfunc   Name of the hook function.
     *
     * @return boolean True if successful, false otherwise.
     */
    public static function registerHook($hookobject, $hookaction, $hookarea, $hookmodule, $hooktype, $hookfunc)
    {
        // define input, all numbers and booleans to strings
        $hookmodule = isset($hookmodule) ? ((string)$hookmodule) : '';

        // validate
        if (!System::varValidate($hookmodule, 'mod')) {
            return false;
        }

        // Insert hook
        $obj = array('object' => $hookobject, 'action' => $hookaction, 'tarea' => $hookarea, 'tmodule' => $hookmodule, 'ttype' => $hooktype, 'tfunc' => $hookfunc);

        return (bool)DBUtil::insertObject($obj, 'hooks', 'id');
    }

    /**
     * Unregister a hook function.
     *
     * @param string $hookobject The hook object.
     * @param string $hookaction The hook action.
     * @param string $hookarea   The area of the hook (either 'GUI' or 'API').
     * @param string $hookmodule Name of the hook module.
     * @param string $hooktype   Name of the hook type.
     * @param string $hookfunc   Name of the hook function.
     *
     * @return boolean True if successful, false otherwise.
     */
    public static function unregisterHook($hookobject, $hookaction, $hookarea, $hookmodule, $hooktype, $hookfunc)
    {
        // define input, all numbers and booleans to strings
        $hookmodule = isset($hookmodule) ? ((string)$hookmodule) : '';

        // validate
        if (!System::varValidate($hookmodule, 'mod')) {
            return false;
        }

        // Get database info
        $tables = DBUtil::getTables();
        $hookscolumn = $tables['hooks_column'];

        // Remove hook
        $where = "WHERE $hookscolumn[object] = '" . DataUtil::formatForStore($hookobject) . "'
                    AND $hookscolumn[action] = '" . DataUtil::formatForStore($hookaction) . "'
                    AND $hookscolumn[tarea] = '" . DataUtil::formatForStore($hookarea) . "'
                    AND $hookscolumn[tmodule] = '" . DataUtil::formatForStore($hookmodule) . "'
                    AND $hookscolumn[ttype] = '" . DataUtil::formatForStore($hooktype) . "'
                    AND $hookscolumn[tfunc] = '" . DataUtil::formatForStore($hookfunc) . "'";

        return (bool)DBUtil::deleteWhere('hooks', $where);
    }

    /**
     * Carry out hook operations for module.
     *
     * @param string  $hookobject The object the hook is called for - one of 'item', 'category' or 'module'.
     * @param string  $hookaction The action the hook is called for - one of 'new', 'create', 'modify', 'update', 'delete', 'transform', 'display', 'modifyconfig', 'updateconfig'.
     * @param integer $hookid     The id of the object the hook is called for (module-specific).
     * @param array   $extrainfo  Extra information for the hook, dependent on hookaction.
     * @param boolean $implode    Implode collapses all display hooks into a single string.
     * @param object  $subject    Object, usually the calling class as $this.
     * @param array   $args       Extra arguments.
     *
     * @return string|array String output from GUI hooks, extrainfo array for API hooks.
     */
    public static function callHooks($hookobject, $hookaction, $hookid, $extrainfo = array(), $implode = true, $subject = null, array $args = array())
    {
        static $modulehooks;

        if (!isset($hookaction)) {
            return null;
        }

        if (isset($extrainfo['module']) && (self::available($extrainfo['module']) || strtolower($hookobject) == 'module' || strtolower($extrainfo['module']) == 'zikula')) {
            $modname = $extrainfo['module'];
        } else {
            $modname = self::getName();
        }

        $lModname = strtolower($modname);
        if (!isset($modulehooks[$lModname])) {
            // Get database info
            $tables  = DBUtil::getTables();
            $cols    = $tables['hooks_column'];
            $where   = "WHERE $cols[smodule] = '" . DataUtil::formatForStore($modname) . "'";
            $orderby = "$cols[sequence] ASC";
            $hooks   = DBUtil::selectObjectArray('hooks', $where, $orderby);
            $modulehooks[$lModname] = $hooks;
        }

        $gui = false;
        $output = array();

        // Call each hook
        foreach ($modulehooks[$lModname] as $modulehook) {
            $modulehook['subject'] = $subject;
            $modulehook['args'] = $args;
            if (!isset($extrainfo['tmodule']) || (isset($extrainfo['tmodule']) && $extrainfo['tmodule'] == $modulehook['tmodule'])) {
                if (($modulehook['action'] == $hookaction) && ($modulehook['object'] == $hookobject)) {
                    if (isset($modulehook['tarea']) && $modulehook['tarea'] == 'GUI') {
                        $gui = true;
                        if (self::available($modulehook['tmodule'], $modulehook['ttype']) && self::load($modulehook['tmodule'], $modulehook['ttype'])) {
                            $hookArgs = array('objectid' => $hookid, 'extrainfo' => $extrainfo, 'modulehook' => $modulehook);
                            $output[$modulehook['tmodule']] = self::func($modulehook['tmodule'], $modulehook['ttype'], $modulehook['tfunc'], $hookArgs);
                        }
                    } else {
                        if (isset($modulehook['tmodule']) &&
                                self::available($modulehook['tmodule'], $modulehook['ttype']) &&
                                self::loadApi($modulehook['tmodule'], $modulehook['ttype'])) {
                            $hookArgs = array('objectid' => $hookid, 'extrainfo' => $extrainfo, 'modulehook' => $modulehook);
                            $extrainfo = self::apiFunc($modulehook['tmodule'], $modulehook['ttype'], $modulehook['tfunc'], $hookArgs);
                        }
                    }
                }
            }
        }

        // check what type of information we need to return
        $hookaction = strtolower($hookaction);
        if ($gui || $hookaction == 'display' || $hookaction == 'new' || $hookaction == 'modify' || $hookaction == 'modifyconfig') {
            if ($implode || empty($output)) {
                $output = implode("\n", $output);
            }

            // Events that alter $output with $event->data.
            $event = new Zikula_Event('module.postcallhooks.output', $subject, array(
                            'gui' => $gui,
                            'hookobject' => $hookobject,
                            'hookaction' => $hookaction,
                            'hookid' => $hookid,
                            'extrainfo' => $extrainfo,
                            'implode' => $implode,
                            'output' => $output,
                            'args' => $args), $output);
            EventUtil::notify($event);

            return $event->getData();
        }

        // Events that alter $extrainfo via $event->data.
        $event = new Zikula_Event('module.postcallhooks.extrainfo', $subject, array(
                        'gui' => $gui,
                        'hookobject' => $hookobject,
                        'hookaction' => $hookaction,
                        'hookid' => $hookid,
                        'extrainfo' => $extrainfo,
                        'implode' => $implode,
                        'args' => $args), $extrainfo);
        EventUtil::notify($event);

        return $event->getData();
    }

    /**
     * Determine if a module is hooked by another module.
     *
     * @param string $tmodule The target module.
     * @param string $smodule The source module - default the current top most module.
     *
     * @return boolean True if the current module is hooked by the target module, false otherwise.
     */
    public static function isHooked($tmodule, $smodule)
    {
        static $hooked = array();

        if (isset($hooked[$tmodule][$smodule])) {
            return $hooked[$tmodule][$smodule];
        }

        // define input, all numbers and booleans to strings
        $tmodule = isset($tmodule) ? ((string)$tmodule) : '';
        $smodule = isset($smodule) ? ((string)$smodule) : '';

        // validate
        if (!System::varValidate($tmodule, 'mod') || !System::varValidate($smodule, 'mod')) {
            return false;
        }

        // Get database info
        $tables = DBUtil::getTables();
        $hookscolumn = $tables['hooks_column'];

        // Get applicable hooks
        $where = "WHERE $hookscolumn[smodule] = '" . DataUtil::formatForStore($smodule) . "'
                    AND $hookscolumn[tmodule] = '" . DataUtil::formatForStore($tmodule) . "'";

        $hooked[$tmodule][$smodule] = $numitems = DBUtil::selectObjectCount('hooks', $where);
        $hooked[$tmodule][$smodule] = ($numitems > 0);

        return $hooked[$tmodule][$smodule];
    }

    /**
     * Get the base directory for a module.
     *
     * Example: If the webroot is located at
     * /var/www/html
     * and the module name is Template and is found
     * in the modules directory then this function
     * would return /var/www/html/modules/Template
     *
     * If the Template module was located in the system
     * directory then this function would return
     * /var/www/html/system/Template
     *
     * This allows you to say:
     * include(ModUtil::getBaseDir() . '/includes/private_functions.php');.
     *
     * @param string $modname Name of module to that you want the base directory of.
     *
     * @return string The path from the root directory to the specified module.
     */
    public static function getBaseDir($modname = '')
    {
        if (empty($modname)) {
            $modname = self::getName();
        }

        $path = System::getBaseUri();
        $directory = 'system/' . $modname;
        if ($path != '') {
            $path .= '/';
        }

        $url = $path . $directory;
        if (!is_dir($url)) {
            $directory = 'modules/' . $modname;
            $url = $path . $directory;
        }

        return $url;
    }

    /**
     * Gets the modules table.
     *
     * Small wrapper function to avoid duplicate sql.
     *
     * @return array An array modules table.
     */
    public static function getModsTable()
    {
        static $modstable;

        if (!isset($modstable) || System::isInstalling()) {
            $modstable = DBUtil::selectObjectArray('modules', '', '', -1, -1, 'id');
            foreach ($modstable as $mid => $module) {
                if (!isset($module['url']) || empty($module['url'])) {
                    $modstable[$mid]['url'] = $module['displayname'];
                }
            }
        }

        // add Core module (hack).
        $modstable[0] = array('id' => '0', 'name' => 'zikula', 'type' => self::TYPE_CORE, 'directory' => '', 'displayname' => 'Zikula Core v' . System::VERSION_NUM);

        return $modstable;
    }

    /**
     * Generic modules select function.
     *
     * Only modules in the module table are returned
     * which means that new/unscanned modules will not be returned.
     *
     * @param string $where The where clause to use for the select.
     * @param string $sort  The sort to use.
     *
     * @return array The resulting module object array.
     */
    public static function getModules($where='', $sort='displayname')
    {
        return DBUtil::selectObjectArray('modules', $where, $sort);
    }


    /**
     * Return an array of modules in the specified state.
     *
     * Only modules in the module table are returned
     * which means that new/unscanned modules will not be returned.
     *
     * @param constant $state The module state (optional) (defaults = active state).
     * @param string   $sort  The sort to use.
     *
     * @return array The resulting module object array.
     */
    public static function getModulesByState($state = self::STATE_ACTIVE, $sort='displayname')
    {
        $tables = DBUtil::getTables();
        $cols   = $tables['modules_column'];

        $where = "$cols[state] = $state";

        return DBUtil::selectObjectArray ('modules', $where, $sort);
    }

    /**
     * Initialize object oriented module.
     *
     * @param string $moduleName Module name.
     *
     * @return boolean
     */
    public static function initOOModule($moduleName)
    {
        if (self::isIntialized($moduleName)) {
            return true;
        }

        $modinfo = self::getInfo(self::getIdFromName($moduleName));
        if (!$modinfo) {
            return false;
        }

        $modpath = ($modinfo['type'] == self::TYPE_SYSTEM) ? 'system' : 'modules';
        $osdir   = DataUtil::formatForOS($modinfo['directory']);
        ZLoader::addAutoloader($moduleName, realpath("$modpath/$osdir/lib"));
        // load optional bootstrap
        $bootstrap = "$modpath/$osdir/bootstrap.php";
        if (file_exists($bootstrap)) {
            include_once $bootstrap;
        }

        // register any event handlers.
        // module handlers must be attached from the bootstrap.
        if (is_dir("config/EventHandlers/$osdir")) {
            EventUtil::attachCustomHandlers("config/EventHandlers/$osdir");
        }

        // load any plugins
        PluginUtil::loadPlugins("$modpath/$osdir/plugins", "ModulePlugin_{$osdir}");

        self::$ooModules[$moduleName]['initialized'] = true;
        return true;
    }

    /**
     * Checks whether a OO module is initialized.
     *
     * @param string $moduleName Module name.
     *
     * @return boolean
     */
    public static function isIntialized($moduleName)
    {
        return (self::isOO($moduleName) && self::$ooModules[$moduleName]['initialized']);
    }

    /**
     * Checks whether a module is object oriented.
     *
     * @param string $moduleName Module name.
     *
     * @return boolean
     */
    public static function isOO($moduleName)
    {
        if (!isset(self::$ooModules[$moduleName])) {
            self::$ooModules[$moduleName] = array();
            self::$ooModules[$moduleName]['initialized'] = false;
            self::$ooModules[$moduleName]['oo'] = false;
            $modinfo = self::getInfo(self::getIdFromName($moduleName));
            $modpath = ($modinfo['type'] == self::TYPE_SYSTEM) ? 'system' : 'modules';
            $osdir   = DataUtil::formatForOS($modinfo['directory']);

            if (!$modinfo) {
                return false;
            }

            if (is_dir("$modpath/$osdir/lib")) {
                self::$ooModules[$moduleName]['oo'] = true;
            }
        }

        return self::$ooModules[$moduleName]['oo'];
    }

    /**
     * Register all autoloaders for all modules.
     *
     * @internal
     *
     * @return void
     */
    public static function registerAutoloaders()
    {
        $modules = self::getModsTable();
        unset($modules[0]);
        foreach ($modules as $module) {
            $base = ($module['type'] == self::TYPE_MODULE) ? 'modules' : 'system';
            $path = "$base/$module[name]/lib";
            ZLoader::addAutoloader($module['directory'], $path);
        }
    }
}
