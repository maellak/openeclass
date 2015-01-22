<?php

/* ========================================================================
 * Open eClass 
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== 
 */

define('QTYPE_SINGLE', 1);
define('QTYPE_FILL', 2);
define('QTYPE_MULTIPLE', 3);
define('QTYPE_LABEL', 4);
define('QTYPE_SCALE', 5);

function validate_qtype($qtype)
{
    $qtype = intval($qtype);
    if (in_array($qtype, array(QTYPE_SINGLE, QTYPE_MULTIPLE, QTYPE_FILL, QTYPE_LABEL, QTYPE_SCALE))) {
        return $qtype;
    } else {
        return QTYPE_LABEL;
    }
}
