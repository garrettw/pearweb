<?php
/*
   +----------------------------------------------------------------------+
   | PEAR Web site version 1.0                                            |
   +----------------------------------------------------------------------+
   | Copyright (c) 2001-2003 The PHP Group                                |
   +----------------------------------------------------------------------+
   | This source file is subject to version 2.02 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available at through the world-wide-web at                           |
   | http://www.php.net/license/2_02.txt.                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Authors:                                                             |
   +----------------------------------------------------------------------+
   $Id$
*/

response_header('Credits');
?>

<h2>Credits</h2>

<h3>PEAR website team</h3>

<ul>
  <li><?php echo user_link("ssb"); ?></li>
  <li><?php echo user_link("cox"); ?></li>
  <li><?php echo user_link("mj"); ?></li>
  <li><?php echo user_link("toby"); ?></li>
  <li><?php echo user_link("cmv"); ?></li>
  <li><?php echo user_link("richard");?></li>
</ul>

<small>(In alphabetic order)</small>

<p>The website team can be reached at
<?php echo make_mailto_link("pear-webmaster@lists.php.net"); ?>.</p>

<h3>PEAR documentation team</h3>

<p>The authors of the documentation are listed on a
<?php print_link("/manual/en/authors.php", "special page"); ?> in 
the manual. The team can be reached via the mailing list 
<?php echo make_mailto_link("pear-doc@lists.php.net"); ?> 
(<?php echo make_link("/support.php", "Subscription Information"); ?>)
.</p>

<?php
response_footer();
?>
