<?php
/*-------------------------------------------------------+
| ESR Codes Extension                                    |
| Copyright (C) 2016-2019 SYSTOPIA                       |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

/*
* Settings metadata file
*/

return array(
  'de.systopia.esr.contact' => array(
      'group_name'  => 'de.systopia.esr',
      'group'       => 'de.systopia.esr.contact',
      'name'        => 'de.systopia.esr.contact',
      'type'        => 'Array',
      'add'         => '4.6',
      'is_domain'   => 1,
      'is_contact'  => 0,
      'description' => 'Last entered values in the ESR contact task',
  ),
  'de.systopia.esr.membership' => array(
      'group_name'  => 'de.systopia.esr',
      'group'       => 'de.systopia.esr.membership',
      'name'        => 'de.systopia.esr.membership',
      'type'        => 'Array',
      'add'         => '4.6',
      'is_domain'   => 1,
      'is_contact'  => 0,
      'description' => 'Last entered values in the ESR membership task',
  ),
);
