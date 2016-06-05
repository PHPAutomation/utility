<?php
/**
 * lib/CiscoConfig.php
 *
 * This is a cisco configuration download tool for SNMP manipulation.
 *
 * PHP version 7
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
 * @author    Tzer-Ming Liu (tzerming@gmail.com)
 * @author    John Lavoie
 * @copyright 2009,2013-2016 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 */
namespace metaclassing;

class CiscoConfig
{
    public $Error;
    public $debug = false;

    private $Host;
    private $Community;

    private $RandomNumber;

    private $ConfigCopyOID = [
        'ccCopyProtocol'                 => '.1.3.6.1.4.1.9.9.96.1.1.1.1.2',
        'ccCopySourceFileType'           => '.1.3.6.1.4.1.9.9.96.1.1.1.1.3',
        'ccCopyDestFileType'             => '.1.3.6.1.4.1.9.9.96.1.1.1.1.4',
        'ccCopyServerAddress'            => '.1.3.6.1.4.1.9.9.96.1.1.1.1.5',
        'ccCopyFileName'                 => '.1.3.6.1.4.1.9.9.96.1.1.1.1.6',
        'ccCopyUserName'                 => '.1.3.6.1.4.1.9.9.96.1.1.1.1.7',
        'ccCopyUserPassword'             => '.1.3.6.1.4.1.9.9.96.1.1.1.1.8',
        'ccCopyNotificationOnCompletion' => '.1.3.6.1.4.1.9.9.96.1.1.1.1.9',
        'ccCopyState'                    => '.1.3.6.1.4.1.9.9.96.1.1.1.1.10',
        'ccCopyTimeStarted'              => '.1.3.6.1.4.1.9.9.96.1.1.1.1.11',
        'ccCopyTimeCompleted'            => '.1.3.6.1.4.1.9.9.96.1.1.1.1.12',
        'ccCopyFailCause'                => '.1.3.6.1.4.1.9.9.96.1.1.1.1.13',
        'ccCopyEntryRowStatus'           => '.1.3.6.1.4.1.9.9.96.1.1.1.1.14',
        'ccCopyServerAddressType'        => '.1.3.6.1.4.1.9.9.96.1.1.1.1.15',
        'ccCopyServerAddressRev1'        => '.1.3.6.1.4.1.9.9.96.1.1.1.1.16',
    ];

    private $CopyProtocol = [
        'tftp' => 1,
        'ftp'  => 2,
        'rcp'  => 3,
        'scp'  => 4,
        'sftp' => 5,
    ];

    private $ConfigFileType = [
        'networkFile'         => 1,
        'iosFile'             => 2,
        'startupConfig'       => 3,
        'runningConfig'       => 4,
        'terminal'            => 5,
        'fabricStartupConfig' => 6,
    ];

    private $RowStatus = [
        'active'        => 1,
        'notInService'  => 2,
        'notReady'      => 3,
        'createAndGo'   => 4,
        'createAndWait' => 5,
        'destroy'       => 6,
    ];

    private $ConfigCopyState = [
        1 => 'waiting',
        2 => 'running',
        3 => 'successful',
        4 => 'failed',
    ];

    private $ConfigCopyFailCause = [
        1 => 'unknown',
        2 => 'badFileName',
        3 => 'timeout',
        4 => 'noMem',
        5 => 'noConfig',
        6 => 'unsupportedProtocol',
        7 => 'someConfigApplyFailed',
        8 => 'systemNotReady',
        9 => 'requestAborted',
    ];

    public function __construct($Host, $Community)
    {
        //check snmp module
        if (!@extension_loaded('snmp')) {
            if (!@dl('snmp.so')) {
                die('Unable to Load SNMP Module');
            }
        }

        snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

        $this->Host = $Host;
        $this->Community = $Community;
        $this->RandomNumber = rand(1, 65535);
        //$this->RandomNumber	= rand();
    }

    public function __destruct()
    {
        // Destroy Config Copy Entry
        //$this->SetCopyEntryRowStatus( $this->RowStatus['destroy'] );
    }

    private function SetCopyEntryRowStatus($RowStatus)
    {
        $Host = $this->Host;
        $Community = $this->Community;

        if ($this->debug) {
            printf("\nSetting Config Copy Entry:\n");
            printf("CISCO-CONFIG-COPY-MIB::ccCopyEntryRowStatus.%d to %s ...\n",
                $this->RandomNumber,
                array_search($RowStatus, $this->RowStatus));
        }

        //Setting Copy Entry
        $oid = $this->ConfigCopyOID['ccCopyEntryRowStatus'].
            '.'.$this->RandomNumber;

        if (snmpset($Host, $Community, $oid, 'i', $RowStatus, 2000000, 2)) {
            // edited to add 2 second timeout each (microseconds) and 2 attempt

            if ($this->debug) {
                printf('CISCO-CONFIG-COPY-MIB::');
                printf("ccCopyEntryRowStatus.%d ... Response OK\n",
                    $this->RandomNumber);
            }

            return true;
        } else {
            $this->Error = 'CISCO-CONFIG-COPY-MIB::ccCopyEntryRowStatus No Response or Response Error';

            return false;
        }
    }

    private function SetSourceFileType($ConfigFileType)
    {
        $Host = $this->Host;
        $Community = $this->Community;

        if ($this->debug) {
            printf("\nSetting Source File Type:\n");
            printf("CISCO-CONFIG-COPY-MIB::ccCopySourceFileType.%d to %s ...\n",
                $this->RandomNumber,
                array_search($ConfigFileType, $this->ConfigFileType));
        }

        // Set Source File Type Value to running-config
        $oid = $this->ConfigCopyOID['ccCopySourceFileType'].
            '.'.$this->RandomNumber;
//		echo "ConfigFileType: $ConfigFileType\n";
        if (snmpset($Host, $Community, $oid, 'i', $ConfigFileType)) {
            if ($this->debug) {
                printf('CISCO-CONFIG-COPY-MIB::');
                printf("ccCopySourceFileType.%d ... Response OK\n",
                    $this->RandomNumber);
            }

            return true;
        } else {
            $this->Error = 'CISCO-CONFIG-COPY-MIB::ccCopySourceFileType No Response or Response Error';

            return false;
        }
    }

    private function SetDestFileType($ConfigFileType)
    {
        $Host = $this->Host;
        $Community = $this->Community;

        if ($this->debug) {
            printf("\nSetting Destination File Type:\n");
            printf("CISCO-CONFIG-COPY-MIB::ccCopyDestFileType.%d to %s ...\n",
                $this->RandomNumber,
                array_search($ConfigFileType, $this->ConfigFileType));
        }

        // Set Destination File Type Value to networkFile
        $oid = $this->ConfigCopyOID['ccCopyDestFileType'].
            '.'.$this->RandomNumber;
        if (snmpset($Host, $Community, $oid, 'i', $ConfigFileType)) {
            if ($this->debug) {
                printf('CISCO-CONFIG-COPY-MIB::');
                printf("ccCopyDestFileType.%d ... Response OK\n",
                    $this->RandomNumber);
            }

            return true;
        } else {
            $this->Error = 'CISCO-CONFIG-COPY-MIB::ccCopyDestFileType No Response or Response Error';

            return false;
        }
    }

    private function SetCopyProtocol($Protocol)
    {
        $Host = $this->Host;
        $Community = $this->Community;

        if ($this->debug) {
            printf("\nSetting Copy Procotol:\n");
            printf("CISCO-CONFIG-COPY-MIB::ccCopyProtocol.%d to %s ...\n",
                $this->RandomNumber,
                array_search($Protocol, $this->CopyProtocol));
        }

        // Set Copy Protocol
        $oid = $this->ConfigCopyOID['ccCopyProtocol'].
            '.'.$this->RandomNumber;
        if (@snmpset($Host, $Community, $oid, 'i', $Protocol)) {
            if ($this->debug) {
                printf('CISCO-CONFIG-COPY-MIB::');
                printf("ccCopyProtocol.%d ... Response OK\n",
                    $this->RandomNumber);
            }

            return true;
        } else {
            $this->Error = 'unsupported Protocol';

            return false;
        }
    }

    private function SetCopyServerAddress($Address)
    {
        $Host = $this->Host;
        $Community = $this->Community;

        if ($this->debug) {
            printf("\nSetting Server IP Address:\n");
            printf("CISCO-CONFIG-COPY-MIB::ccCopyServerAddress.%d to %s ...\n",
                $this->RandomNumber,
                $Address);
        }

        // Set Server IP Address
        $oid = $this->ConfigCopyOID['ccCopyServerAddress'].
            '.'.$this->RandomNumber;
        if (snmpset($Host, $Community, $oid, 'a', $Address)) {
            if ($this->debug) {
                printf('CISCO-CONFIG-COPY-MIB::');
                printf("ccCopyServerAddress.%d ... Response OK\n",
                    $this->RandomNumber);
            }

            return true;
        } else {
            $this->Error = 'CISCO-CONFIG-COPY-MIB::ccCopyServerAddress No Response or Response Error';

            return false;
        }
    }

    private function SetCopyFileName($DestFileName)
    {
        $Host = $this->Host;
        $Community = $this->Community;

        if ($this->debug) {
            printf("\nSetting Destination FileName:\n");
            printf("CISCO-CONFIG-COPY-MIB::ccCopyFileName.%d to %s ...\n",
                $this->RandomNumber,
                $DestFileName);
        }

        // Set Destination File Name
        $oid = $this->ConfigCopyOID['ccCopyFileName'].
            '.'.$this->RandomNumber;
        if (snmpset($Host, $Community, $oid, 's', $DestFileName)) {
            if ($this->debug) {
                printf('CISCO-CONFIG-COPY-MIB::');
                printf("ccCopyFileName.%d ... Response OK\n",
                    $this->RandomNumber);
            }

            return true;
        } else {
            $this->Error = 'CISCO-CONFIG-COPY-MIB::ccCopyFileName No Response or Response Error';

            return false;
        }
    }

    private function SetCopyUserName($UserName)
    {
        $Host = $this->Host;
        $Community = $this->Community;

        if ($this->debug) {
            printf("\nSetting Login UserName:\n");
            printf("CISCO-CONFIG-COPY-MIB::ccCopyUserName.%d to %s ...\n",
                $this->RandomNumber,
                $UserName);
        }

        // Set Login User Name
        $oid = $this->ConfigCopyOID['ccCopyUserName'].
            '.'.$this->RandomNumber;
        if (snmpset($Host, $Community, $oid, 's', $UserName)) {
            if ($this->debug) {
                printf('CISCO-CONFIG-COPY-MIB::');
                printf("ccCopyUserName.%d ... Response OK\n",
                    $this->RandomNumber);
            }

            return true;
        } else {
            $this->Error = 'CISCO-CONFIG-COPY-MIB::ccCopyUserName No Response or Response Error';

            return false;
        }
    }

    private function SetCopyUserPassword($UserPassword)
    {
        $Host = $this->Host;
        $Community = $this->Community;

        if ($this->debug) {
            printf("\nSetting Login Password:\n");
            printf("CISCO-CONFIG-COPY-MIB::ccCopyUserPassword.%d to %s ...\n",
                $this->RandomNumber,
                $UserPassword);
        }

        // Set Login Password
        $oid = $this->ConfigCopyOID['ccCopyUserPassword'].
            '.'.$this->RandomNumber;
        if (snmpset($Host, $Community, $oid, 's', $UserPassword)) {
            if ($this->debug) {
                printf('CISCO-CONFIG-COPY-MIB::');
                printf("ccCopyUserPassword.%d ... Response OK\n",
                    $this->RandomNumber);
            }

            return true;
        } else {
            $this->Error = 'CISCO-CONFIG-COPY-MIB::ccCopyUserPassword No Response or Response Error';

            return false;
        }
    }

    private function CheckCopyStats()
    {
        $Host = $this->Host;
        $Community = $this->Community;

        if ($this->debug) {
            printf("\nChecking Config Copy State:\n");
        }

        $oid = $this->ConfigCopyOID['ccCopyState'].
            '.'.$this->RandomNumber;
        $i = 0;
        do {
            $State = snmpget($Host, $Community, $oid);

            if ($this->debug) {
                printf('CISCO-CONFIG-COPY-MIB::');
                printf("ccCopyState.%d ... Response %s\n",
                    $this->RandomNumber,
                    $this->ConfigCopyState[$State]);
//					var_dump($State);
//					var_dump($this->ConfigCopyState);
            }

            if (intval($State) == 3) {
                return true;
            }

            if (intval($State) == 4) {
                $Error = snmpget($Host, $Community,
                    $this->ConfigCopyOID['ccCopyFailCause']
                    .'.'.$this->RandomNumber);
                $this->Error = $this->ConfigCopyFailCause[$Error];

                return false;
            }
            sleep(1);
        } while ($i++ < 10);
    }

    private function GetCopyFailCause()
    {
        $Host = $this->Host;
        $Community = $this->Community;

        $Error = snmpget($Host, $Community,
            $this->ConfigCopyOID['ccCopyFailCause']
            .'.'.$this->RandomNumber);

        return $this->ConfigCopyFailCause[$Error];
    }

    public function WriteMemory()
    {
        $RowStatus = $this->RowStatus;
        $ConfigFileType = $this->ConfigFileType;

        //Create Copy Entry and Wait
        if (!$this->SetCopyEntryRowStatus($RowStatus['createAndWait'])) {
            return false;
        }

        // Set Source File Type Value to running-config
        if (!$this->SetSourceFileType($ConfigFileType['runningConfig'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($RowStatus['destroy']);

            return false;
        }

        // Set Destination File Type Value to startup-config
        if (!$this->SetDestFileType($ConfigFileType['startupConfig'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($RowStatus['destroy']);

            return false;
        }

        //Do it
        if (!$this->SetCopyEntryRowStatus($RowStatus['active'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($RowStatus['destroy']);

            return false;
        }

        if (!$this->CheckCopyStats()) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($RowStatus['destroy']);

            return false;
        }

        // Destroy Config Copy Entry
        $this->SetCopyEntryRowStatus($RowStatus['destroy']);

        return true;
    }

    public function WriteNetwork($FileName, $Protocol, $Server,
        $Login = null, $Password = null)
    {

        // Check Protocol
        if (!array_key_exists($Protocol, $this->CopyProtocol)) {
            $this->Error = 'not support protocol';

            return false;
        }

        //Create Copy Entry and Wait
        if (!$this->SetCopyEntryRowStatus($this->RowStatus['createAndWait'])) {
            return false;
        }

        // Set Source File Type Value to runningConfig
        if (!$this->SetSourceFileType($this->ConfigFileType['runningConfig'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Destination File Type Value to networkFile
        if (!$this->SetDestFileType($this->ConfigFileType['networkFile'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Copy Protocol
        if (!$this->SetCopyProtocol($this->CopyProtocol[$Protocol])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Server IP Address
        if (!$this->SetCopyServerAddress($Server)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Destination File Name
        if (!$this->SetCopyFileName($FileName)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if ($Protocol != 'tftp') {
            if (!$this->SetCopyUserName($Login)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }

            if (!$this->SetCopyUserPassword($Password)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }
        }

        //Do it
        if (!$this->SetCopyEntryRowStatus($this->RowStatus['active'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if (!$this->CheckCopyStats()) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Destroy Config Copy Entry
        $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

        return true;
    }

    public function Copy_StartupConfig_Network($FileName, $Protocol,
        $Server, $Login = null, $Password = null)
    {

        // Check Protocol
        if (!array_key_exists($Protocol, $this->CopyProtocol)) {
            $this->Error = 'not support protocol';

            return false;
        }

        //Create Copy Entry and Wait
        if (!$this->SetCopyEntryRowStatus($this->RowStatus['createAndWait'])) {
            return false;
        }

        // Set Source File Type Value to startupConfig
        if (!$this->SetSourceFileType($this->ConfigFileType['startupConfig'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Destination File Type Value to networkFile
        if (!$this->SetDestFileType($this->ConfigFileType['networkFile'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Copy Protocol
        if (!$this->SetCopyProtocol($this->CopyProtocol[$Protocol])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Server IP Address
        if (!$this->SetCopyServerAddress($Server)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set File Name
        if (!$this->SetCopyFileName($FileName)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if ($Protocol != 'tftp') {
            if (!$this->SetCopyUserName($Login)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }

            if (!$this->SetCopyUserPassword($Password)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }
        }

        //Do it
        if (!$this->SetCopyEntryRowStatus($this->RowStatus['active'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if (!$this->CheckCopyStats()) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Destroy Config Copy Entry
        $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

        return true;
    }

    public function Copy_Network_StartupConfig($FileName, $Protocol,
        $Server, $Login = null, $Password = null)
    {

        // Check Protocol
        if (!array_key_exists($Protocol, $this->CopyProtocol)) {
            $this->Error = 'not support protocol';

            return false;
        }

        //Create Copy Entry and Wait
//		if( !$this->SetCopyEntryRowStatus( $this->RowStatus['createAndWait']) ) {
//			return FALSE;
//		}

        // Set Copy Protocol
        if (!$this->SetCopyProtocol($this->CopyProtocol[$Protocol])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Source File Type Value to startupConfig
        if (!$this->SetSourceFileType($this->ConfigFileType['networkFile'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Destination File Type Value to networkFile
        if (!$this->SetDestFileType($this->ConfigFileType['startupConfig'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Server IP Address
        if (!$this->SetCopyServerAddress($Server)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Source File Name
        if (!$this->SetCopyFileName($FileName)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if ($Protocol != 'tftp') {
            if (!$this->SetCopyUserName($Login)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }

            if (!$this->SetCopyUserPassword($Password)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }
        }

        //Do it
        if (!$this->SetCopyEntryRowStatus($this->RowStatus['active'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if (!$this->CheckCopyStats()) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Destroy Config Copy Entry
        $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

        return true;
    }

    public function Copy_RunningConfig_Network($FileName, $Protocol,
        $Server, $Login = null, $Password = null)
    {
        // Check Protocol
        if (!array_key_exists($Protocol, $this->CopyProtocol)) {
            $this->Error = 'not support protocol';

            return false;
        }

        //Create Copy Entry and Wait
        if (!$this->SetCopyEntryRowStatus($this->RowStatus['createAndWait'])) {
            return false;
        }

        // Set Copy Protocol
        if (!$this->SetCopyProtocol($this->CopyProtocol[$Protocol])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Source File Type Value to RunningConfig
        if (!$this->SetSourceFileType($this->ConfigFileType['runningConfig'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Destination File Type Value to networkFile
        if (!$this->SetDestFileType($this->ConfigFileType['networkFile'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Server IP Address
        if (!$this->SetCopyServerAddress($Server)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Source File Name
        if (!$this->SetCopyFileName($FileName)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if ($Protocol != 'tftp') {
            if (!$this->SetCopyUserName($Login)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }

            if (!$this->SetCopyUserPassword($Password)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }
        }

        //Do it
        if (!$this->SetCopyEntryRowStatus($this->RowStatus['active'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if (!$this->CheckCopyStats()) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Destroy Config Copy Entry
        $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

        return true;
    }

    public function Copy_Network_RunningConfig($FileName, $Protocol,
        $Server, $Login = null, $Password = null)
    {

        // Check Protocol
        if (!array_key_exists($Protocol, $this->CopyProtocol)) {
            $this->Error = 'not support protocol';

            return false;
        }

        //Create Copy Entry and Wait
        if (!$this->SetCopyEntryRowStatus($this->RowStatus['createAndWait'])) {
            return false;
        }

        // Set Copy Protocol
        if (!$this->SetCopyProtocol($this->CopyProtocol[$Protocol])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Source File Type Value to networkFile
        if (!$this->SetSourceFileType($this->ConfigFileType['networkFile'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Destination File Type Value to runningConfig
        if (!$this->SetDestFileType($this->ConfigFileType['runningConfig'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Server IP Address
        if (!$this->SetCopyServerAddress($Server)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Set Source File Name
        if (!$this->SetCopyFileName($FileName)) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if ($Protocol != 'tftp') {
            if (!$this->SetCopyUserName($Login)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }

            if (!$this->SetCopyUserPassword($Password)) {
                // Destroy Config Copy Entry
                $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

                return false;
            }
        }

        //Do it
        if (!$this->SetCopyEntryRowStatus($this->RowStatus['active'])) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        if (!$this->CheckCopyStats()) {
            // Destroy Config Copy Entry
            $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

            return false;
        }

        // Destroy Config Copy Entry
        $this->SetCopyEntryRowStatus($this->RowStatus['destroy']);

        return true;
    }
}
