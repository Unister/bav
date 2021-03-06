<?php









/**
 * This class is responsable for I/O and formating which helps the BAV_DataBackend_File.
 *
 *
 * Copyright (C) 2006  Markus Malkusch <markus@malkusch.de>
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
 * @subpackage dataBackend
 * @author Markus Malkusch <markus@malkusch.de>
 * @copyright Copyright (C) 2006 Markus Malkusch
 */
class BAV_FileParser extends BAV {


    const FILE_ENCODING           = 'ISO-8859-15';
    const BANKID_OFFSET           = 0;
    const BANKID_LENGTH           = 8;
    const ISMAIN_OFFSET           = 8;
    const ISMAIN_LENGTH           = 1;
    const NAME_OFFSET             = 9;
    const NAME_LENGTH             = 58;
    const POSTCODE_OFFSET         = 67;
    const POSTCODE_LENGTH         = 5;
    const CITY_OFFSET             = 72;
    const CITY_LENGTH             = 35;
    const SHORTTERM_OFFSET        = 107;
    const SHORTTERM_LENGTH        = 27;
    const PAN_OFFSET              = 134;
    const PAN_LENGTH              = 5;
    const BIC_OFFSET              = 139;
    const BIC_LENGTH              = 11;
    const TYPE_OFFSET             = 150;
    const TYPE_LENGTH             = 2;
    const ID_OFFSET               = 152;
    const ID_LENGTH               = 6;
    const EXTINCT_OFFSET          = 159;
    const EXTINCT_LENGTH          = 1;
    const SUCCESSOR_BANKID_OFFSET = 160;
    const SUCCESSOR_BANKID_LENGTH = 8;
    const IBANRULENUMBER_OFFSET   = 168;
    const IBANRULENUMBER_LENGTH   = 4;
    const IBANRULEVERSION_OFFSET  = 172;
    const IBANRULEVERSION_LENGTH  = 2;


    private
    /**
     * @var resource
     */
    $fp,
    /**
     * @var string
     */
    $file = '',
    /**
     * @var int,
     */
    $lines = 0,
    /**
     * @var int
     */
    $lineLength = 0;


    /**
     * @param String $file The data source
     */
    public function __construct($file = null) {
        $this->file = is_null($file)
                    ? __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "banklist.txt"
                    : $file;
    }
    /**
     * @throws BAV_FileParserException_IO
     * @throws BAV_FileParserException_FileNotExists
     */
    private function init() {
        if (is_resource($this->fp)) {
            return;

        }
        $this->fp = @fopen($this->file, 'r');
        if (! is_resource($this->fp)) {
            if (! file_exists($this->file)) {
                throw new BAV_FileParserException_FileNotExists($this->file);

            } else {
                throw new BAV_FileParserException_IO();

            }

        }


        $dummyLine = fgets($this->fp, 1024);
        if (! $dummyLine) {
            throw new BAV_FileParserException_IO();

        }
        $this->lineLength = strlen($dummyLine);

        clearstatcache(); // filesize() seems to be 0 sometimes
        $filesize = filesize($this->file);
        if (! $filesize) {
            throw new BAV_FileParserException_IO(
                "Could not read filesize for '$this->file'."
            );

        }
        $this->lines = floor(($filesize - 1) / $this->lineLength);
    }
    /**
     * @throws BAV_FileParserException_IO
     * @throws BAV_FileParserException_FileNotExists
     * @return int
     */
    public function getLines() {
        $this->init();
        return $this->lines;
    }
    /**
     * @throws BAV_FileParserException_IO
     * @throws BAV_FileParserException_FileNotExists
     */
    public function rewind() {
        if (fseek($this->getFileHandle(), 0) === -1) {
            throw new BAV_FileParserException_IO();

        }
    }
    /**
     * @throws BAV_FileParserException_IO
     * @throws BAV_FileParserException_FileNotExists
     * @param int $line
     * @param int $offset
     */
    public function seekLine($line, $offset = 0) {
        if (fseek($this->getFileHandle(), $line * $this->lineLength + $offset) === -1) {
            throw new BAV_FileParserException_IO();

        }
    }
    /**
     * @throws BAV_FileParserException_IO
     * @throws BAV_FileParserException_FileNotExists
     * @param int $line
     * @return string
     */
    public function readLine($line) {
        $this->seekLine($line);
        return self::$encoding->convert(fread($this->getFileHandle(), $this->lineLength), self::FILE_ENCODING);
    }
    /**
     * @throws BAV_FileParserException_IO
     * @throws BAV_FileParserException_FileNotExists
     * @param int $line
     * @return string
     */
    public function getBankID($line) {
        $this->seekLine($line, self::BANKID_OFFSET);
        return self::$encoding->convert(fread($this->getFileHandle(), self::BANKID_LENGTH), self::FILE_ENCODING);
    }
    /**
     * @throws BAV_FileParserException_FileNotExists
     * @throws BAV_FileParserException_IO
     * @return resource
     */
    public function getFileHandle() {
        $this->init();
        return $this->fp;
    }
    /**
     * @throws BAV_FileParserException_FileNotExists
     * @throws BAV_FileParserException_IO
     * @return int
     */
    public function getLineLength() {
        $this->init();
        return $this->lineLength;
    }
    /**
     */
    public function close($bWithDelete = false)
    {
        if (is_resource($this->fp))
        {
            fclose($this->fp);
            $this->fp = null;
        }
        if($bWithDelete)
        {
            unlink($this->file);
        }
    }

    /**
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * @throws BAV_FileParserException_ParseError
     * @param string $line
     * @return BAV_Bank
     */
    public function getBank(BAV_DataBackend $dataBackend, $line) {
        if (self::$encoding->strlen($line) < self::TYPE_OFFSET + self::TYPE_LENGTH) {
            throw new BAV_FileParserException_ParseError("Invalid line length in Line $line.");

        }
        $type   = self::$encoding->substr($line, self::TYPE_OFFSET, self::TYPE_LENGTH);
        $extinct = self::$encoding->substr($line, self::EXTINCT_OFFSET, self::EXTINCT_LENGTH);
        $bankID = self::$encoding->substr($line, self::BANKID_OFFSET, self::BANKID_LENGTH);
        $ibanRuleNumber = intval(self::$encoding->substr($line, self::IBANRULENUMBER_OFFSET, self::IBANRULENUMBER_LENGTH));
        $ibanRuleVersion = intval(self::$encoding->substr($line, self::IBANRULEVERSION_OFFSET, self::IBANRULEVERSION_LENGTH));
        $successorBankID = self::$encoding->substr($line, self::SUCCESSOR_BANKID_OFFSET, self::SUCCESSOR_BANKID_LENGTH);
        if ($successorBankID == '00000000') {
            $successorBankID = '';
        }
        return new BAV_Bank($dataBackend, $bankID, $type, $extinct, $ibanRuleNumber, $ibanRuleVersion, $successorBankID);
    }
    /**
     * @throws BAV_FileParserException_ParseError
     * @param string $line
     * @return BAV_Agency
     */
    public function getAgency(BAV_Bank $bank, $line) {
        if (self::$encoding->strlen($line) < self::ID_OFFSET + self::ID_LENGTH) {
            throw new BAV_FileParserException_ParseError("Invalid line length.");

        }
        $id   = trim(self::$encoding->substr($line, self::ID_OFFSET, self::ID_LENGTH));
        $name = trim(self::$encoding->substr($line, self::NAME_OFFSET, self::NAME_LENGTH));
        $shortTerm = trim(self::$encoding->substr($line, self::SHORTTERM_OFFSET, self::SHORTTERM_LENGTH));
        $city = trim(self::$encoding->substr($line, self::CITY_OFFSET, self::CITY_LENGTH));
        $postcode = self::$encoding->substr($line, self::POSTCODE_OFFSET, self::POSTCODE_LENGTH);
        $bic = trim(self::$encoding->substr($line, self::BIC_OFFSET, self::BIC_LENGTH));
        $pan = trim(self::$encoding->substr($line, self::PAN_OFFSET, self::PAN_LENGTH));
        return new BAV_Agency($id, $bank, $name, $shortTerm, $city, $postcode, $bic, $pan);
    }
    /**
     * @throws BAV_FileParserException_ParseError
     * @param string $line
     * @return bool
     */
    public function isMainAgency($line) {
        if (self::$encoding->strlen($line) < self::TYPE_OFFSET + self::TYPE_LENGTH) {
            throw new BAV_FileParserException_ParseError("Invalid line length.");

        }
        return self::$encoding->substr($line, self::ISMAIN_OFFSET, 1) === '1';
    }
    /**
     * @return string
     */
    public function getFile() {
        return $this->file;
    }


}

?>
