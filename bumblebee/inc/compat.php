<?php
/**
* Compatability with various different PHP versions
*
* PHP5 changes the way the world revolves... well, it changes the way
* objects are handled so that $object is always a reference to an
* object. The consequence is that in PHP5, this:
*     $object = new Foo();
*     $copy = $object;
* is equivalent to 
*     $object = new Foo();
*     $copy = &$object;
* in PHP4. In PHP5, the = operator is copy by reference not copy by value.
*
* Conveniently, PHP5 provides you with the clone keyword to get around this.
* Inconveniently, that same keyword is not in PHP4 so you can't just
* use it to get around your problems.
*
* If the version of PHP being used here is earlier than 5.0, then use
* a PHP-only inplementation of the clone keyword (as a function) from the
* PEAR PHP_Compat library:
*
*            http://pear.php.net/package/PHP_Compat
*
* So now, instead of using the above construct to copy the $object, you
* use:
*     $object = new Foo();
*     $copy = clone($object);
*
* Note that in normal PHP5, you would not include the parentheses as clone is 
* a keyword not a function, but for the PHP4 compatability to work, it has to be
* written as a function. PHP5 will just throw away the parentheses, pretending 
* that they are for (an unneeded) grouping not a function call.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

if (version_compare(phpversion(), '5.0') === -1) {
  /** php-compat implementation of clone for PHP4 */
  include_once 'Compat/Function/clone.php';
}

?> 
