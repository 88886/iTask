<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: admincp.php 2365 2014-02-23 16:26:27Z coolmoo $
*/
define('iPHP_DEBUG', true);
// define('iPHP_SESSION','SESSION');
require dirname(__file__) . '/iCMS.php';
iPHP::app('admincp.class','static');
admincp::run();
