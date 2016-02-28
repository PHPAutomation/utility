<?php
/**
 * lib/Utility.php.
 *
 * This class contains a bunch of public static member functions with various uses
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  default
 *
 * @author    John Lavoie
 * @copyright 2016 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 */
namespace metaclassing;

class Utility
{
    public function __construct()
    {
        throw new \Exception("Do not create instances of this object, call public static member functions like \Metaclassing\Utility::someDumbThing(params)");
    }

    public function isJson($string)
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
