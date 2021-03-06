<?php






/**
 * Copyright (C) 2010  Markus Malkusch <markus@malkusch.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 * @package classes
 * @subpackage validator
 * @author Markus Malkusch <markus@malkusch.de>
 * @copyright Copyright (C) 2010 Markus Malkusch
 * @author hhilbert 2013-03-12 changed transformation mapping
 */
class BAV_Validator_D1 extends BAV_Validator {


    static private
	/**
	 * @var Array
	 */
	$_transformation = array(
        0 => 4363380,
        1 => 4363381,
        2 => 4363382,
        3 => 4363383,
        4 => 4363384,
        5 => 4363385,
        6 => 4363386,
        7 => 4363387,
        9 => 4363389
	);

    
    protected
    /**
     * @var String
     */
    $transformedAccount = '',
    /**
     * @var BAV_Validator_00
     */
    $validator;
    
    
    public function __construct(BAV_Bank $bank) {
        parent::__construct($bank);
        
        $this->validator = new BAV_Validator_00($bank);
    }


    protected function validate() {
        $transformationIndex = $this->_getTransformationIndex();
        if (! array_key_exists($transformationIndex, self::$_transformation)) {
            return;

        }
        $transformationPrefix = self::$_transformation[$transformationIndex];
        $this->validator->setNormalizedSize(9 + strlen($transformationPrefix));
        $this->transformedAccount 
            = $transformationPrefix . substr($this->account, 1);
    }
    
    
    /**
     * @return bool
     */
    protected function getResult() {
        return 
            array_key_exists(
                $this->_getTransformationIndex(),
                self::$_transformation
            )
            &&
            $this->validator->isValid($this->transformedAccount);
    }


    /**
     * @return int
     */
    private function _getTransformationIndex() {
        return $this->account{0};
    }
    

}