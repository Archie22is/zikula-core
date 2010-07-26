<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Zikula
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

class Groups_Controller_Admin extends Zikula_Controller
{
    /**
     * Groups Module main administration function
     * This function is the default function, and is called whenever the
     * module is initiated without defining arguments.  As such it can
     * be used for a number of things, but most commonly it either just
     * shows the module menu and returns or calls whatever the module
     * designer feels should be the default function (often this is the
     * view() function)
     * @author Mark West
     * @return string HTML output string
     */
    public function main()
    {
        // Security check
        $any_access = false;
        // get all groups from the API
        $groups = ModUtil::apiFunc('Groups', 'user', 'getall');
        if (is_array($groups)) {
            foreach($groups as $group) {
                if (SecurityUtil::checkPermission('Groups::', $group['gid'] . '::', ACCESS_EDIT)==true) {
                    $any_access = true;
                    break;
                }
            }
        }

        if ($any_access == false) {
            // we found no groups that we are allowed to administer
            // return now
            return LogUtil::registerPermissionError();
        }

        $this->view->setCaching(false);

        // Return the output that has been generated by this function
        return $this->view->fetch('groups_admin_main.tpl');
    }

    /**
     * View all groups
     * this function creates a tabular output of all group items in the module
     * @author Mark West
     * @return string HTML output string
     */
    public function view()
    {
        // Get parameters from whatever input we need.
        $startnum = (int)FormUtil::getPassedValue('startnum', null, 'GET');

        // we need this value multiple times, so we keep it
        $itemsperpage = $this->getVar('itemsperpage');

        // get the default user group
        $defaultgroup = $this->getVar('defaultgroup');
        // get the primary admin group
        $primaryadmingroup = $this->getVar('primaryadmingroup', 2);

        // The user API function is called.
        $items = ModUtil::apiFunc('Groups', 'user', 'getall',
                array('startnum' => $startnum,
                'numitems' => $itemsperpage));


        // Setting various defines
        $typelabel = array(//'-2' => $this->__('New group in DB'),
                //'-1' => $this->__('Core'),
                '0'  => $this->__('Core'),
                '1'  => $this->__('Public'),
                '2'  => $this->__('Private'));

        $statelabel = array('0' => $this->__('Closed'),
                '1' => $this->__('Open'));

        $groups = array();
        foreach ($items as $item) {

            if (SecurityUtil::checkPermission('Groups::', $item['gid'].'::', ACCESS_READ)) {

                // Options for the item.
                $options = array();
                if (SecurityUtil::checkPermission('Groups::', $item['gid'].'::', ACCESS_EDIT)) {
                    $editurl    = ModUtil::url('Groups', 'admin', 'modify', array('gid'     => $item['gid']));
                    $deleteurl  = ModUtil::url('Groups', 'admin', 'view', array());
                    $membersurl = ModUtil::url('Groups', 'admin', 'groupmembership', array('gid'     => $item['gid']));
                    $options[] = array('url' => ModUtil::url('Groups', 'admin', 'modify', array('gid'     => $item['gid'])),
                            'title'   => $this->__('Edit'),
                            'imgfile' => 'xedit.gif');
                    if ((SecurityUtil::checkPermission('Groups::', $item['gid'].'::', ACCESS_DELETE))
                            && ($item['gid'] != $defaultgroup) && ($item['gid'] != $primaryadmingroup))
                    {
                        $deleteurl  = ModUtil::url('Groups', 'admin', 'delete', array('gid'     => $item['gid']));
                        $options[] = array('url' => ModUtil::url('Groups', 'admin', 'delete', array('gid'     => $item['gid'])),
                                'title'   => $this->__('Delete'),
                                'imgfile' => '14_layer_deletelayer.gif');
                    }
                    $options[] = array('url' => ModUtil::url('Groups', 'admin', 'groupmembership', array('gid'     => $item['gid'])),
                            'title'   => $this->__('Group membership'),
                            'imgfile' => 'agt_family.gif');
                    $nbuser = ModUtil::apiFunc('Groups', 'user', 'countgroupmembers', array('gid' => $item['gid']));
                    $groups[] = array('name'        => $item['name'],
                            'gid'         => $item['gid'],
                            'gtype'       => $item['gtype'],
                            'gtypelbl'    => $typelabel[$item['gtype']],
                            'description' => ((empty($item['description'])== false) ? $item['description'] : ''),
                            'prefix'      => $item['prefix'],
                            'state'       => $item['state'],
                            'statelbl'    => $statelabel[$item['state']],
                            'nbuser'      => (($nbuser <> false) ? $nbuser : 0),
                            'nbumax'      => $item['nbumax'],
                            'link'        => $item['link'],
                            'uidmaster'   => $item['uidmaster'],
                            'options'     => $options,
                            'editurl'     => $editurl,
                            'deleteurl'   => $deleteurl,
                            'membersurl'  => $membersurl);
                }
            }
        }

        if (count($groups) == 0) {
            // groups array is empty
            return LogUtil::registerPermissionError();
        }

        // The admin API function is called. This fetch the pending applications if any.
        // permission check for the group is done in this function
        $users = ModUtil::apiFunc('Groups', 'admin', 'getapplications',
                array('startnum' => $startnum,
                'numitems' => $itemsperpage));

        $this->view->setCaching(false);

        $this->view->assign('groups',       $groups)
                   ->assign('grouptypes',   $typelabel)
                   ->assign('states',       $statelabel)
                   ->assign('useritems',    $users)
                   ->assign('defaultgroup', $defaultgroup)
                   ->assign('primaryadmingroup', $primaryadmingroup);

        // Assign the values for the smarty plugin to produce a pager
        $this->view->assign('pager', array('numitems'     => ModUtil::apiFunc('Groups', 'admin', 'countitems'),
                                           'itemsperpage' => $itemsperpage));

        // Return the output that has been generated by this function
        return $this->view->fetch('groups_admin_view.tpl');
    }

    /**
     * Add a new group
     * This is a standard function that is called whenever an administrator
     * wishes to create a new group
     * @author Mark West
     * @return string HTML output string
     */
    public function newgroup()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Groups::', '::', ACCESS_ADD)) {
            return LogUtil::registerPermissionError();
        }

        $this->view->setCaching(false);

        // Setting various defines
        $grouptype = array(
            '0' => $this->__('Core'),
            '1'  => $this->__('Public'),
            '2'  => $this->__('Private'));

        $groupstate = array(
            '0' => $this->__('Closed'),
            '1' => $this->__('Open'));

        $this->view->assign('grouptype',  $grouptype)
                   ->assign('groupstate', $groupstate);

        // Return the output that has been generated by this function
        return $this->view->fetch('groups_admin_new.tpl');
    }

    /**
     * This is a standard function that is called with the results of the
     * form supplied by groups admin_new() to create a new group
     * @author Mark West
     * @param string 'name' the name of the group to be created
     * @return bool true if group created succesfully, false otherwise
     */
    public function create($args)
    {
        // Get parameters from whatever input we need.
        $name = FormUtil::getPassedValue('name', isset($args['name']) ? $args['name'] : null, 'POST');
        $gtype = FormUtil::getPassedValue('gtype', isset($args['gtype']) ? $args['gtype'] : null, 'POST');
        $state = FormUtil::getPassedValue('state', isset($args['state']) ? $args['state'] : null, 'POST');
        $nbumax = FormUtil::getPassedValue('nbumax', isset($args['nbumax']) ? $args['nbumax'] : null, 'POST');
        $description = FormUtil::getPassedValue('description', isset($args['description']) ? $args['description'] : null, 'POST');

        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Groups', 'admin', 'view'));
        }

        // The API function is called.
        $check = ModUtil::apiFunc('Groups', 'admin', 'getgidbyname',
                array('name' => $name));

        if ($check != false) {
            // Group already exists
            LogUtil::registerError($this->__('Error! There is already a group with that name.'));
        } else {
            $gid = ModUtil::apiFunc('Groups', 'admin', 'create',
                    array('name'        => $name,
                    'gtype'       => $gtype,
                    'state'       => $state,
                    'nbumax'      => $nbumax,
                    'description' => $description));

            // The return value of the function is checked here
            if ($gid != false) {
                // Success
                LogUtil::registerStatus($this->__('Done! Created the group.'));
            }
        }

        // This function generated no output
        return System::redirect(ModUtil::url('Groups', 'admin', 'view'));
    }

    /**
     * modify a group
     * This is a standard function that is called whenever an administrator
     * wishes to modify a current group item
     * @author Mark West
     * @param int 'gid' the id of the group to be modified
     * @param int 'objectid' generic object id mapped onto gid if present
     * @return string HTML output string
     */
    public function modify($args)
    {
        // Get parameters from whatever input we need.
        $gid = (int)FormUtil::getPassedValue('gid', isset($args['gid']) ? $args['gid'] : null, 'GET');
        $objectid = (int)FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'GET');

        // At this stage we check to see if we have been passed $objectid
        if (!empty($objectid)) {
            $gid = $objectid;
        }

        // The user API function is called.
        $item = ModUtil::apiFunc('Groups', 'user', 'get',
                array('gid' => $gid));

        if ($item == false) {
            return LogUtil::registerError($this->__('Sorry! No such group found.'), 404);
        }

        // Security check
        if (!SecurityUtil::checkPermission('Groups::', $item['gid'].'::', ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }

        $this->view->setCaching(false);

        // Add a hidden variable for the item id.
        $this->view->assign('gid', $gid);

        // assign the item
        $this->view->assign($item);

        // Setting various defines
        $grouptype = array(
            '0' => $this->__('Core'),
            '1'  => $this->__('Public'),
            '2'  => $this->__('Private'));

        $groupstate = array(
            '0' => $this->__('Closed'),
            '1' => $this->__('Open'));

        $this->view->assign('grouptype',  $grouptype)
                   ->assign('groupstate', $groupstate);

        // Return the output that has been generated by this function
        return $this->view->fetch('groups_admin_modify.tpl');
    }

    /**
     * This is a standard function that is called with the results of the
     * form supplied by groups_admin_modify() to update a current group item
     * @author Mark West
     * @param int 'gid' the id of the group to be modified
     * @param int 'objectid' generic object id mapped onto gid if present
     * @param string 'name' the name of the group to be updated
     * @return bool true if group updated successfully, false otherwise
     */
    public function update($args)
    {
        // Get parameters from whatever input we need.
        $gid = (int)FormUtil::getPassedValue('gid', isset($args['gid']) ? $args['gid'] : null, 'POST');
        $objectid = (int)FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'POST');
        $name = FormUtil::getPassedValue('name', isset($args['name']) ? $args['name'] : null, 'POST');
        $gtype = FormUtil::getPassedValue('gtype', isset($args['gtype']) ? $args['gtype'] : null, 'POST');
        $state = FormUtil::getPassedValue('state', isset($args['state']) ? $args['state'] : null, 'POST');
        $nbumax = FormUtil::getPassedValue('nbumax', isset($args['nbumax']) ? $args['nbumax'] : null, 'POST');
        $description = FormUtil::getPassedValue('description', isset($args['description']) ? $args['description'] : null, 'POST');

        // At this stage we check to see if we have been passed $objectid
        if (!empty($objectid)) {
            $gid = $objectid;
        }

        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Groups', 'admin', 'view'));
        }

        // The API function is called.
        if (ModUtil::apiFunc('Groups', 'admin', 'update',
        array('gid'         => $gid,
        'name'        => $name,
        'gtype'       => $gtype,
        'state'       => $state,
        'nbumax'      => $nbumax,
        'description' => $description))) {
            // Success
            LogUtil::registerStatus($this->__('Done! Saved group changes.'));
        }

        // This function generated no output
        return System::redirect(ModUtil::url('Groups', 'admin', 'view'));
    }

    /**
     * delete group
     * This is a standard function that is called whenever an administrator
     * wishes to delete a current group item.  Note that this function is
     * the equivalent of both of the modify() and update() functions above as
     * it both creates a form and processes its output.  This is fine for
     * simpler functions, but for more complex operations such as creation and
     * modification it is generally easier to separate them into separate
     * functions.  There is no requirement in the Zikula MDG to do one or the
     * other, so either or both can be used as seen appropriate by the module
     * developer
     * @author Mark West
     * @param int 'gid' the id of the item to be deleted
     * @param bool 'confirmation' confirmation that this item can be deleted
     * @param int 'objectid' generic object id mapped onto gid if present
     * @return mixed HTML output string if no confirmation, true if group deleted succesfully, false otherwise
     */
    public function delete($args)
    {
        // Get parameters from whatever input we need.
        $gid = (int)FormUtil::getPassedValue('gid', isset($args['gid']) ? $args['gid'] : null, 'REQUEST');
        $objectid = (int)FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'REQUEST');
        $confirmation = (bool)FormUtil::getPassedValue('confirmation', isset($args['confirmation']) ? $args['confirmation'] : null, 'REQUEST');

        if (!empty($objectid)) {
            $gid = $objectid;
        }

        // The user API function is called.
        $item = ModUtil::apiFunc('Groups', 'user', 'get',
                array('gid' => $gid));

        if ($item == false) {
            LogUtil::registerError($this->__('Sorry! No such group found.'));
            return System::redirect(ModUtil::url('Groups', 'admin', 'main'));
        }

        // Security check
        if (!SecurityUtil::checkPermission('Groups::', $item['gid'].'::', ACCESS_DELETE)) {
            return LogUtil::registerPermissionError();
        }

        // get the user default group - we do not allow its deletion
        $defaultgroup = $this->getVar('defaultgroup');
        if ($item['gid'] == $defaultgroup) {
            LogUtil::registerError($this->__('Error! You cannot delete the default user group.'));
            return System::redirect(ModUtil::url('Groups', 'admin', 'main'));
        }

        // Check for confirmation.
        if (empty($confirmation)) {

            // No confirmation yet - display a suitable form to obtain confirmation
            // of this action from the user

            $this->view->setCaching(false);

            // Add a hidden variable for the item id.
            $this->view->assign('gid', $gid);

            // Return the output that has been generated by this function
            return $this->view->fetch('groups_admin_delete.tpl');
        }

        // If we get here it means that the user has confirmed the action

        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Groups', 'admin', 'view'));
        }

        // The API function is called.
        if (ModUtil::apiFunc('Groups', 'admin', 'delete',
        array('gid' => $gid))) {
            // Success
            LogUtil::registerStatus($this->__('Done! Deleted the group.'));
        }

        // This function generated no output
        return System::redirect(ModUtil::url('Groups', 'admin', 'view'));
    }

    /**
     * This is a standard function to display members of a group
     * @author Mark West
     * @link http://www.markwest.me.uk/
     * @param int 'gid' the id of the group to list membership for
     * @param int 'objectid' generic object id mapped onto gid if present
     * @return string HTML output string
     */
    public function groupmembership($args)
    {
        // Get parameters from whatever input we need.
        $gid = (int)FormUtil::getPassedValue('gid', isset($args['gid']) ? $args['gid'] : null, 'GET');
        $objectid = (int)FormUtil::getPassedValue('objectid', isset($args['objectid']) ? $args['objectid'] : null, 'GET');
        $startnum = (int)FormUtil::getPassedValue('startnum', isset($args['startnum']) ? $args['startnum'] : null, 'GET');
        $letter = FormUtil::getPassedValue('letter', isset($args['letter']) ? $args['letter'] : null, 'GET');

        if (!empty($objectid)) {
            $gid = $objectid;
        }

        // The user API function is called.
        $item = ModUtil::apiFunc('Groups', 'user', 'get',
                array('gid' => $gid));

        // check for a letter parameter
        if (empty($letter) && strlen($letter) != 1) {
            $letter = 'A';
        }

        // Security check
        if (!SecurityUtil::checkPermission('Groups::', $item['gid'].'::', ACCESS_EDIT)) {
            return LogUtil::registerPermissionError();
        }

        $this->view->setCaching(false);

        // assign the group to the template
        $this->view->assign($item);

        // The user API function is called.
        $item = ModUtil::apiFunc('Groups', 'user', 'get',
                array('gid'      => $gid,
                'startnum' => $startnum,
                'numitems' => $this->getVar('itemsperpage')));

        $users = $item['members'];

        $currentUid = UserUtil::getVar('uid');
        $defaultGroup = $this->getVar('defaultgroup', 0);
        $primaryAdminGroup = $this->getVar('primaryadmingroup', 0);

        $groupmembers = array();

        if (is_array($users) && SecurityUtil::checkPermission('Groups::', $item['gid'].'::', ACCESS_EDIT)) {
            foreach ($users as $user) {
                $options = array();
                if (($user['uid'] == $currentUid)
                    && (($item['gid'] == $defaultGroup) || ($item['gid'] == $primaryAdminGroup)))
                {
                    $options[] = array();
                } else {
                    $options[] = array(
                        'url'     => ModUtil::url('Groups', 'admin', 'removeuser', array('gid'    => $item['gid'],
                        'uid'     => $user['uid'],
                        'authid'  => SecurityUtil::generateAuthKey())),
                        'imgfile' => 'edit_remove.gif',
                        'title'   => $this->__('Remove user from group')
                    );
                }
                $groupmembers[] = array(
                    'uname'   => UserUtil::getVar('uname', $user['uid']),
                    'name'    => UserUtil::getVar('name', $user['uid']),
                    'uid'     => $user['uid'],
                    'options' => $options
                );
            }
        }

        // sort alphabetically.
        $sortAarr = array();
        foreach($groupmembers as $res) {
            $sortAarr[] = strtolower($res['uname']);
        }
        array_multisort($sortAarr, SORT_ASC, $groupmembers);

        $this->view->assign('groupmembers', $groupmembers);

        // The user API function is called.
        $item = ModUtil::apiFunc('Groups', 'user', 'get',
                array('gid' => $gid));

        // Number of items to display per page
        $row = array();

        switch($letter) {
            case '?':
            // read usernames beginning with special chars or numbers
                $regexpfield = 'uname';
                $regexpression = '^[[:punct:][:digit:]]';
                break;
            case '*':
            // read allusers
                $regexpfield = '';
                $regexpression = '';
                break;
            default:
                $regexpfield = 'uname';
                $regexpression = '^' . $letter;
        }
        $users = UserUtil::getAll('uname', 'ASC', -1, -1, '', $regexpfield, $regexpression);

        $allusers = array();
        foreach ($users as $user) {
            if ($user['uid'] == 0 || strtolower($user['uname']) == 'anonymous' || strtolower($user['uname']) == 'guest'  || $user['uname'] == ModUtil::getVar('Users', 'anonymous'))  continue;
            $alias = '';
            if (!empty($user['name'])) {
                $alias = ' (' . $user['name'] . ')';
            }
            $allusers[$user['uid']] = $user['uname'] . $alias;
        }

        // Now lets remove the users that are currently part of the group
        // flip the array so we have the user id's as the key
        // this makes the array the same is the group members array
        // from the get function
        $flippedusers = array_flip($allusers);
        // now lets diff the array
        $diffedusers = array_diff($flippedusers, array_keys($item['members']));
        // now flip the array back
        $allusers = array_flip($diffedusers);
        // sort the users by user name
        natcasesort($allusers);

        // assign the users not in the group to the template
        $this->view->assign('uids', $allusers);

        // Assign the values for the smarty plugin to produce a pager
        $this->view->assign('pager', array('numitems'     => ModUtil::apiFunc('Groups', 'user', 'countgroupmembers', array('gid' => $gid)),
                                           'itemsperpage' => $this->getVar( 'itemsperpage')));

        // Return the output that has been generated by this function
        return $this->view->fetch('groups_admin_groupmembership.tpl');
    }

    /**
     * This is a standard function to add a user to a group
     * @author Mark West
     * @param int 'gid' the id of the group
     * @param mixed 'uid' the id of the user (int) or an array of userids
     * @return string true is user added succesfully, false otherwise
     */
    public function adduser($args)
    {
        // Get parameters from whatever input we need.
        $gid = (int)FormUtil::getPassedValue('gid', isset($args['gid']) ? $args['gid'] : null, 'POST');
        $uid = FormUtil::getPassedValue('uid', isset($args['uid']) ? $args['uid'] : null, 'POST');

        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Groups', 'admin', 'view'));
        }

        // The API function is called.
        if (is_array($uid)) {
            foreach($uid as $id) {
                if (!ModUtil::apiFunc('Groups', 'admin', 'adduser',
                array('gid' => $gid,
                'uid' => $id))) {
                    // Failure
                    LogUtil::registerError($this->__('Error! A problem occurred and the user was not added to the group.'));
                }
            }
        } else {
            if (ModUtil::apiFunc('Groups', 'admin', 'adduser',
            array('gid' => $gid,
            'uid' => $uid))) {
                // Success
                LogUtil::registerStatus($this->__('Done! The user was added to the group.'));
            }
        }

        // This function generated no output
        return System::redirect(ModUtil::url('Groups', 'admin', 'groupmembership', array('gid' => $gid)));
    }

    /**
     * This is a standard function to add a user to a group
     * @author Mark West
     * @param int 'gid' the id of the group
     * @param int 'uid' the id of the user
     * @return string true is user added succesfully, false otherwise
     */
    public function removeuser($args)
    {
        // Get parameters from whatever input we need.
        $gid = (int)FormUtil::getPassedValue('gid', isset($args['gid']) ? $args['gid'] : null, 'GET');
        $uid = (int)FormUtil::getPassedValue('uid', isset($args['uid']) ? $args['uid'] : null, 'GET');

        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Groups', 'admin', 'view'));
        }

        // The API function is called.
        if (ModUtil::apiFunc('Groups', 'admin', 'removeuser',
        array('gid' => $gid,
        'uid' => $uid))) {
            // Success
            LogUtil::registerStatus($this->__('Done! The user was removed from the group.'));
        } else {
            LogUtil::registerError($this->__('Error! A problem occurred while attempting to remove the user. The user has not been removed from the group.'));
        }

        // This function generated no output
        return System::redirect(ModUtil::url('Groups', 'admin', 'groupmembership', array('gid' => $gid)));
    }

    /**
     * display a list of group applications
     *
     */
    public function userpending()
    {
        $gid = (int)FormUtil::getPassedValue('gid', null, 'GET');
        $userid = (int)FormUtil::getPassedValue('userid', null, 'GET');
        $action = FormUtil::getPassedValue('action', null, 'GET');

        if (empty($gid) || empty($userid)) {
            return LogUtil::registerArgsError(ModUtil::url('Groups', 'admin', 'main'));
        }

        if ($action != 'deny' && $action != 'accept') {
            return LogUtil::registerArgsError(ModUtil::url('Groups', 'admin', 'main'));
        }

        $appinfo = ModUtil::apiFunc('Groups', 'admin', 'getapplicationinfo',
                array('gid'    => $gid,
                      'userid' => $userid));

        if (ModUtil::available('Messages')) {
            $sendoptions = array(
                0 => $this->__('None'),
                1 => $this->__('Private message'),
                2 => $this->__('E-mail'));
        } else {
            $sendoptions = array(
                0 => $this->__('None'),
                2 => $this->__('E-mail'));
        }

        $this->view->setCaching(false);

        $this->view->assign('userid',      $userid)
                   ->assign('gid',         $gid)
                   ->assign('action',      $action)
                   ->assign('sendoptions', $sendoptions)
                   ->assign('application', $appinfo['application']);

        return $this->view->fetch('groups_admin_userpending.tpl');
    }

    /**
     * update group applications
     *
     */
    public function userupdate()
    {
        $action = FormUtil::getPassedValue('action', null, 'POST');

        if ($action != 'deny' && $action != 'accept') {
            return LogUtil::registerArgsError(ModUtil::url('Groups', 'admin', 'main'));
        }

        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Groups', 'admin', 'main'));
        }

        $tag = FormUtil::getPassedValue('tag', null, 'POST');
        $sendtag = FormUtil::getPassedValue('sendtag', null, 'POST');
        $reason = FormUtil::getPassedValue('reason', null, 'POST');
        $gid = (int)FormUtil::getPassedValue('gid', null, 'POST');
        $userid = (int)FormUtil::getPassedValue('userid', null, 'POST');

        if (empty($tag) || empty($gid) || empty($userid)) {
            return LogUtil::registerArgsError(ModUtil::url('Groups', 'admin', 'main'));
        }

        $group = ModUtil::apiFunc('Groups', 'user', 'get', array('gid' => $gid));

        if ($action == 'deny') {

            $reasontitle = $this->__f('Concerning your %s group membership application', $group['name']);

            if (empty($reason)) {
                // Get Default TEXT
                $reason = $this->__('Sorry! This is a message to inform you with regret that your application for membership of the aforementioned private group has been rejected.');
            }

        } elseif ($action == 'accept') {

            $reasontitle = $this->__f('Done! The user has been added to the %s group.', $group['name']);

            if (empty($reason)) {
                // Get Default TEXT
                $reason = $this->__('Done! Your application has been accepted. You have been granted all the privileges assigned to the group of which you are now member.');
            }

        }

        $result = ModUtil::apiFunc('Groups', 'admin', 'pendingaction',
                array('userid'      => $userid,
                'gid'         => $gid,
                'sendtag'     => $sendtag,
                'reason'      => $reason,
                'reasontitle' => $reasontitle,
                'action'      => $action));

        if (!$result) {
            if ($action == 'deny') {
                LogUtil::registerError($this->__("Error! Could not execute 'Reject' action."));
            } else {
                LogUtil::registerError($this->__("Error! Could not execute 'Accept' action."));
            }
            return System::redirect(ModUtil::url('Groups', 'admin', 'main'));
        }

        if ($action == 'accept') {
            LogUtil::registerStatus($this->__('Done! The user was added to the group.'));
        } else {
            LogUtil::registerStatus($this->__("Done! The user's application for group membership has been rejected."));
        }

        return System::redirect(ModUtil::url('Groups', 'admin', 'main'));
    }

    /**
     * This is a standard function to modify the configuration parameters of the
     * module
     * @author Mark West
     * @since 1.14
     * @return string HTML string
     */
    public function modifyconfig()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Groups::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }

        $this->view->setCaching(false);

        // assign the module vars
        $modvars = $this->getVars();
        $this->view->assign($modvars);

        $this->view->assign('defaultgroupid', $modvars['defaultgroup']);

        // get all groups from the API
        $groups = ModUtil::apiFunc('Groups', 'user', 'getall');

        // build an array suitable for html_options
        $groupslist = array();
        foreach ($groups as $group) {
            $groupslist[$group['gid']] = $group['name'];
        }

        // assign the list of existing groups
        $this->view->assign('groups', $groupslist);

        // Return the output that has been generated by this function
        return $this->view->fetch('groups_admin_modifyconfig.tpl');
    }

    /**
     * This is a standard function to update the configuration parameters of the
     * module given the information passed back by the modification form
     * @author Mark West
     * @since 1.14
     * @return bool true
     */
    public function updateconfig()
    {
        // Security check
        if (!SecurityUtil::checkPermission('Groups::', '::', ACCESS_ADMIN)) {
            return LogUtil::registerPermissionError();
        }

        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('Groups', 'admin', 'view'));
        }

        // Update module variables.
        $itemsperpage = (int)FormUtil::getPassedValue('itemsperpage', 25, 'POST');
        $this->setVar('itemsperpage', $itemsperpage);

        $defaultgroupid = (int)FormUtil::getPassedValue('defaultgroupid', 1, 'POST');
        // convert id to name
        $group = ModUtil::apiFunc('Groups', 'user', 'get', array('gid' => $defaultgroupid));
        if($group == false) {
            LogUtil::registerError($this->__('Error! Could not save the module configuration.'));
            return System::redirect(ModUtil::url('Groups', 'admin', 'view'));
        }
        $this->setVar('defaultgroup', $group['gid']);

        $mailwarning = (bool)FormUtil::getPassedValue('mailwarning', false, 'POST');
        $this->setVar('mailwarning', $mailwarning);

        $hideclosed = (bool)FormUtil::getPassedValue('hideclosed', false, 'POST');
        $this->setVar('hideclosed', $hideclosed);

        // Let any other modules know that the modules configuration has been updated
        $this->callHooks('module','updateconfig','Groups', array('module' => 'Groups'));

        // the module configuration has been updated successfuly
        LogUtil::registerStatus($this->__('Done! Saved module configuration.'));

        // This function generated no output
        return System::redirect(ModUtil::url('Groups', 'admin', 'view'));
    }
}
