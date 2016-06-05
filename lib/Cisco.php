<?php
/**
 * lib/Cisco.php.
 *
 * This is a bunch of helper functions used with Cisco equipment and output parsing
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
 * @author    Ryan Honeyman
 * @author    John Lavoie
 * @copyright 2009-2016 @authors
 * @license   http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 3.0
 */
namespace metaclassing;

class Cisco
{
    private $iface;
    private $config;
    private $data;
    private $device;
    private $abbr = [
      'Eth' => 'Ethernet',
      'Fa'  => 'FastEthernet',
      'Gi'  => 'GigabitEthernet',
      'Te'  => 'TenGigabitEthernet',
      'Lo'  => 'Loopback',
      'Po'  => 'Port-channel',
   ];

    public function __construct()
    {
    }

    //
    // these are all the public STATIC functions that dont require an instance //
    //

    public static function findManagementInterface($CONFIG)
    {
        if (!is_array($CONFIG)) {
            //      print "CONFIG IS NOT AN ARRAY, FIXING!\n";
            $CONFIG = preg_split('/\r\n|\r|\n/', $CONFIG);
        }

        $POSSIBLE = [];
        if (!Utility::is_assoc($CONFIG)) {
            foreach ($CONFIG as $LINE) {
                $LINE = trim($LINE);
                if (preg_match('/.*source-interface.* (\S+)/', $LINE, $REG)) {
                    if (isset($POSSIBLE[$REG[1]])) {
                        $POSSIBLE[$REG[1]]++;
                    } else {
                        $POSSIBLE[$REG[1]] = 1;
                    }
                }
            }
        } elseif (Utility::is_assoc($CONFIG)) {
            foreach ($CONFIG as $LINE => $VALUE) {
                $LINE = trim($LINE);
                if (preg_match('/.*source-interface (\S+)/', $LINE, $REG)) {
                    if (isset($POSSIBLE[$REG[1]])) {
                        $POSSIBLE[$REG[1]]++;
                    } else {
                        $POSSIBLE[$REG[1]] = 1;
                    }
                }
            }
        }
        arsort($POSSIBLE);
        foreach ($POSSIBLE as $MGMT_INT => $HITCOUNT) {
            return $MGMT_INT;   // Hack, find the first KEY of the newly sorted array!
        }
    }

    public static function checkIosVersion($model, $version)
    {
        $color = 'red';

        if ((preg_match('/.*WS-C2350.*/', $model, $reg1))  && (preg_match('/.*lanlitek9-mz.122-52.SE.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*WS-C2360.*/', $model, $reg1))  && (preg_match('/.*lanlitek9-mz.122-52.SE.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*WS-C3560.*/', $model, $reg1))  && (preg_match('/.*ipservicesk9-mz.122-55.SE.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*WS-C3750.*/', $model, $reg1))  && (preg_match('/.*ipservicesk9-mz.122-55.SE.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*WS-C45.*/', $model, $reg1))    && (preg_match('/.*cat4500e-universalk9.SPA.03.01.01.SG.150-1.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*WS-C4948.*/', $model, $reg1))  && (preg_match('/.*cat4500-entservicesk9-mz.122-54.SG1.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*WS-C650.*/', $model, $reg1))   && (preg_match('/.*adv.*k9.*.122-33.SXI.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*ME-C6524.*/', $model, $reg1))  && (preg_match('/.*adv.*k9.*.122-33.SXI.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*CISCO760.*/', $model, $reg1))  && (preg_match('/.*adventerprisek9-mz.151-1.S.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*ASR100.*/', $model, $reg1))    && (preg_match('/.*adventerprisek9.03.02.02.S.151-1.S2.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*ASA55.*/', $model, $reg1))     && (preg_match('/.*asa82.-k8.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*CISCO29.*/', $model, $reg1))   && (preg_match('/.*k9-mz.SPA.152-4.M4.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*CISCO28.*/', $model, $reg1))   && (preg_match('/.*k9-mz.SPA.152-4.M4.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*CISCO38.*/', $model, $reg1))   && (preg_match('/.*k9-mz.SPA.152-4.M4.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*CISCO39.*/', $model, $reg1))   && (preg_match('/.*k9-mz.SPA.152-4.M4.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/.*CISCO720.*/', $model, $reg1))  && (preg_match('/.*adv.*k9-mz.151-4.M.*/', $version, $reg2))) {
            $color = 'green';
        }
        if ((preg_match('/^720.*/', $model, $reg1))    && (preg_match('/.*adv.*k9-mz.151-4.M.*/', $version, $reg2))) {
            $color = 'green';
        }

        return $color;
    }

    public static function checkInputError($command_output)
    {
        $outputlines = explode("\r\n", $command_output);
        foreach ($outputlines as $line) {
            if (preg_match('/^%.*/', $line, $reg)) {
                return 0;
            }
            if (preg_match('/^ERROR.*/', $line, $reg)) {
                return 0;
            }
            if (preg_match('/^Type help.*/', $line, $reg)) {
                return 0;
            }
            if (preg_match('/^Unknown command.*/', $line, $reg)) {
                return 0;
            }
        }

        return 1;
    }

    public static function inventoryToModel($show_inventory)
    {
        $model = 'Unknown';
        $invlines = explode("\r\n", $show_inventory);
        foreach ($invlines as $line) {
            // LEGACY PERL CODE: $x =~ /^\s*PID:\s(\S+).*SN:\s+(\S+)\s*$/;
            if (preg_match('/.*PID:\s(\S+)\s.*/', $line, $reg)) {
                $model = $reg[1];

                return $model;
            }
            // Aruba WLC's:
            // SC Model#                    : Aruba7030-US
            $REGEX = '/^SC Model.*: (.+)$/';
            if (preg_match($REGEX, $line, $REG)) {
                $model = $reg[1];

                return $model;
            }
        }

        return $model;
    }

    public static function versionToModel($show_version)
    {
        $model = 'Unknown';
        $verlines = explode("\r\n", $show_version);
        foreach ($verlines as $line) {
            if (preg_match('/.*isco\s+(WS-\S+)\s.*/', $line, $reg)) {
                $model = $reg[1];

                return $model;
            }
            if (preg_match('/.*isco\s+(OS-\S+)\s.*/', $line, $reg)) {
                $model = $reg[1];

                return $model;
            }
            if (preg_match('/.*ardware:\s+(\S+),.*/', $line, $reg)) {
                $model = $reg[1];

                return $model;
            }
            if (preg_match('/.*ardware:\s+(\S+).*/', $line, $reg)) {
                $model = $reg[1];

                return $model;
            }
            if (preg_match('/^cisco\s+(\S+)\s+.*/', $line, $reg)) {
                $model = $reg[1];

                return $model;
            }
        }

        return $model;
    }

    public static function inventoryToSerial($show_inventory)
    {
        $serial = 'Unknown';
        $invlines = explode("\r\n", $show_inventory);
        foreach ($invlines as $line) {
            // LEGACY PERL CODE: $x =~ /^\s*PID:\s(\S+).*SN:\s+(\S+)\s*$/;
            if (preg_match('/.*PID:\s(\S+).*SN:\s+(\S+)\s*$/', $line, $reg)) {
                $serial = $reg[2];

                return $serial;
            }
        }

        return $serial;
    }

    public static function parseNestedListToArray($CONFIG)
    {
        $RETURN = [];
        $RETURN = \metaclassing\Cisco::filterConfig($CONFIG);                 // Filter our config to strip out unimportant bits
        $RETURN = parse_nested_list_to_array($RETURN);          // Parse the filtered config to an array
        return $RETURN;                                         // And return it
    }

    public static function filterConfig($CONFIG)
    {
        $LINES_IN = preg_split('/\r\n|\r|\n/', $CONFIG);
        $LINES_OUT = [];
        $SKIP = '';
        $HOSTNAME = '';
        foreach ($LINES_IN as $LINE) {
            // Filter out the BANNER MOTD lines
            if (preg_match("/banner \S+ (\S+)/", $LINE, $REG)) {
                // If we encounter a banner motd or banner motd line

                $SKIP = $REG[1];
                continue;     // Skip until we see this character
            }
            if ($SKIP != '' && trim($LINE) == $SKIP) {
                // If $SKIP is set AND we detect the end of our skip character

                $SKIP = '';
                continue;     // Stop skipping and unset the character
            }
            if ($SKIP != '') {
                continue;
            }   // Skip until we stop skipping

            // Find the hostname to identify our prompt
            if (preg_match("/^hostname (\S+)/", $LINE, $REG)) {
                $HOSTNAME = $REG[1];
            }
            // Filter out the prompt at the end if it exists
            if ($HOSTNAME != '' && preg_match("/^{$HOSTNAME}.+/", $LINE, $REG)) {
                continue;
            }

            // Ignore a bunch of unimportant often-changing lines that clutter up the config repository
            if (
                (trim($LINE) == '')   ||  //  Ignore blank and whitespace-only lines
                (trim($LINE) == 'exit')   ||  //  Ignore exit lines (mostly provisioning lines)
                (preg_match('/.*no shut.*/', $LINE, $REG))   ||  //  no shut/no shutdown lines from provisioning tool
                (preg_match('/.*no enable.*/', $LINE, $REG))   ||  //  from provisioning tool
                (preg_match('/.*enable secret.*/', $LINE, $REG))   ||  //  from provisioning tool
                (preg_match('/.*ip domain.lookup.*/', $LINE, $REG))   ||  //  from provisioning tool
                (preg_match('/.*ip domain.name.*/', $LINE, $REG))   ||  //  from provisioning tool
                (preg_match('/.*crypto key generate rsa.*/', $LINE, $REG))   ||  //  from provisioning tool
                (preg_match('/.*log-adjacency-changes.*/', $LINE, $REG))   ||  //  from provisioning tool
                (trim($LINE) == 'aqm-register-fnf')   ||  //  no idea where this comes from
                (trim($LINE) == 'aaa session-id common')   ||  //  from provisioning tool
                (trim($LINE) == 'ip routing')   ||  //  from provisioning tool
                (trim($LINE) == 'cdp enable')   ||  //  from provisioning tool
                (trim($LINE) == 'no ip directed-broadcast')   ||  //  from provisioning tool
                (trim($LINE) == 'no service finger')   ||  //  from provisioning tool
                (trim($LINE) == 'no service udp-small-servers')   ||  //  from provisioning tool
                (trim($LINE) == 'no service tcp-small-servers')   ||  //  from provisioning tool
                (trim($LINE) == 'no service config')   ||  //  from provisioning tool
                (trim($LINE) == 'no clock timezone')   ||  //  from provisionnig tool
//              ( trim($LINE) == "end"                                  )   ||  //  skip end, we dont need this yet
                (trim($LINE) == '<pre>' || trim($LINE) == '</pre>')   ||  //  skip <PRE> and </PRE> output from html scrapes
                (substr(trim($LINE), 0, 1) == '!')   ||  //  skip conf t lines
                (substr(trim($LINE), 0, 4) == 'exit')   ||  //  skip conf lines beginning with the word exit
                (preg_match('/.*config t.*/', $LINE, $REG))   ||  //  skip show run
                (preg_match('/.*show run.*/', $LINE, $REG))   ||  //  and show start
                (preg_match('/.*show startup.*/', $LINE, $REG))   ||  //  show run config topper
                (preg_match('/^version .*/', $LINE, $REG))   ||  //  version 12.4 configuration format
                (preg_match('/^boot-\S+-marker.*/', $LINE, $REG))   ||  //  boot start and end markers
                (preg_match('/^Building configur.*/', $LINE, $REG))   ||  //  ntp clock period in seconds is constantly changing
                (preg_match('/^ntp clock-period.*/', $LINE, $REG))   ||  //  nvram config last messed up
                (preg_match('/^Current configuration.*/', $LINE, $REG))   ||  //  current config size
                (preg_match('/.*NVRAM config last up.*/', $LINE, $REG))   ||  //  nvram config last saved
                (preg_match('/.*uncompressed size*/', $LINE, $REG))   ||  //  uncompressed config size
                (preg_match('/^!Time.*/', $LINE, $REG))       //  time comments
               ) {
                continue;
            }

            // If we have UTC and its NOT the configuration last changed line, ignore it.
            if (
                (preg_match('/.* UTC$/', $LINE, $REG)) &&
                !(preg_match('/^.*onfig.*/', $LINE, $REG))
               ) {
                continue;
            }

            // If we have CST and its NOT the configuration last changed line, ignore it.
            if (
                (preg_match('/.* CST$/', $LINE, $REG)) &&
                !(preg_match('/^.*onfig.*/', $LINE, $REG))
               ) {
                continue;
            }

            // If we have CDT and its NOT the configuration last changed line, ignore it.
            if (
                (preg_match('/. *CDT$/', $LINE, $REG)) &&
                !(preg_match('/^.*onfig.*/', $LINE, $REG))
               ) {
                continue;
            }

            // If we find a control code like ^C replace it with ascii ^C
            $LINE = str_replace(chr(3), '^C', $LINE);

            // If we find the prompt, break out of this function, end of command output detected
            if (isset($DELIMITER) && preg_match($DELIMITER, $LINE, $REG)) {
                break;
            }

            // If we find a line with a tacacs key in it, HIDE THE KEY!
            if (preg_match('/(\s*server-private 10.252.12.10. timeout 2 key) .*/', $LINE, $REG)) {
                $LINE = $REG[1];    // Strip out the KEYS from a server-private line!
            }

            $LINE = rtrim($LINE);   // Trim whitespace off the right end!
            array_push($LINES_OUT, $LINE);
        }

        // REMOVE blank lines from the leading part of the array and REINDEX the array
        while ($LINES_OUT[0] == ''  && count($LINES_OUT) > 2) {
            array_shift($LINES_OUT);
        }

        // REMOVE blank lines from the end of the array and REINDEX the array
        while (end($LINES_OUT) == ''    && count($LINES_OUT) > 2) {
            array_pop($LINES_OUT);
        }

        // Ensure there is one blank line at EOF. Subversion bitches about this for some reason.
        array_push($LINES_OUT, '');

        $CONFIG = implode("\n", $LINES_OUT);

        return $CONFIG;
    }

    public static function downloadConfig($DEVICE)
    {
        $TIME = time();
        $DESTINATION = "/config/{$DEVICE['id']}.{$TIME}.config";
        $TFTPFILE = "/tftpboot/config/{$DEVICE['id']}.{$TIME}.config";
        $DEVICE['name'] = preg_replace("/\//", '-', $DEVICE['name']); // Replace slashes in device names with hyphens!
        $CONFIGREPO = BASEDIR."/config/{$DEVICE['name']}";
        // TRY SNMP TFTP config grab FIRST because its FAST(er) than CLI!
        $Config = new CiscoConfig($DEVICE['ip'], SNMP_RW);
        $Config->WriteNetwork($DESTINATION, 'tftp', TFTPIP);
        if ($Config->Error) {
            // If we hit an error, fall back to CLI!
            special_output(' SNMP error, trying Telnet/SSH:');
            $INFOBJECT = Information::retrieve($DEVICE['id']);
            $INFOBJECT->scan();
            $SHOW_RUN = $INFOBJECT->data['run'];
            $DELIMITER = $INFOBJECT->data['pattern'];
            unset($INFOBJECT); // Save some memory
        } else {
            $SHOW_RUN = file_get_contents($TFTPFILE);
        }

        $RUNLEN = strlen($SHOW_RUN);
        if ($RUNLEN < 800) {
            // If we cant get the config at all, or length is less than 800 bytes, fuckit and die.

            special_output(" config is $RUNLEN bytes, Could not get config!\n");

            return 0;
        } else {
            special_output(" Got $RUNLEN bytes!");
        }

        $LINES_IN = preg_split('/\r\n|\r|\n/', $SHOW_RUN);

        $LINES_OUT = [];
        foreach ($LINES_IN as $LINE) {
            // Ignore a bunch of unimportant often-changing lines that clutter up the config repository
            if (
                (preg_match('/.*show run.*/', $LINE, $REG)) ||
                (preg_match('/.*show startup.*/', $LINE, $REG)) ||
                (preg_match('/^Building configur.*/', $LINE, $REG)) ||
                (preg_match('/^ntp clock-period.*/', $LINE, $REG)) ||
                (preg_match('/^Current configuration.*/', $LINE, $REG)) ||
                (preg_match('/.*NVRAM config last up.*/', $LINE, $REG)) ||
                (preg_match('/.*uncompressed size*/', $LINE, $REG)) ||
                (preg_match('/^!Time.*/', $LINE, $REG))
               ) {
                continue;
            }

            // If we have UTC and its NOT the configuration last changed line, ignore it.
            if (
                (preg_match('/.* UTC$/', $LINE, $REG)) &&
                !(preg_match('/^.*onfig.*/', $LINE, $REG))
               ) {
                continue;
            }

            // If we have CST and its NOT the configuration last changed line, ignore it.
            if (
                (preg_match('/.* CST$/', $LINE, $REG)) &&
                !(preg_match('/^.*onfig.*/', $LINE, $REG))
               ) {
                continue;
            }

            // If we have CDT and its NOT the configuration last changed line, ignore it.
            if (
                (preg_match('/. *CDT$/', $LINE, $REG)) &&
                !(preg_match('/^.*onfig.*/', $LINE, $REG))
               ) {
                continue;
            }

            // If we find a control code like ^C replace it with ascii ^C
            $LINE = str_replace(chr(3), '^C', $LINE);

            // If we find ": end", break out of this function, end of command output detected
            if (
                (preg_match('/^: end$/', $LINE, $REG))
               ) {
                break;
            }

            // If we find the prompt, break out of this function, end of command output detected
            if (isset($DELIMITER) && preg_match($DELIMITER, $LINE, $REG)) {
                break;
            }

            $LINE = rtrim($LINE);   // Trim whitespace off the right end!
            array_push($LINES_OUT, $LINE);
        }

        // REMOVE blank lines from the leading part of the array and REINDEX the array
        while ($LINES_OUT[0] == ''  && count($LINES_OUT) > 2) {
            array_shift($LINES_OUT);
        }

        // REMOVE blank lines from the end of the array and REINDEX the array
        while (end($LINES_OUT) == ''    && count($LINES_OUT) > 2) {
            array_pop($LINES_OUT);
        }

        // Ensure there is one blank line at EOF. Subversion bitches about this for some reason.
        array_push($LINES_OUT, '');

        $SHOW_RUN = implode("\n", $LINES_OUT);
        $FH = fopen($CONFIGREPO, 'w');
        fwrite($FH, $SHOW_RUN);
        fclose($FH);

        return 1;
    }

    //
    // these are all the non-static functions that require an instance //
    //

   public function abbreviate($interface)
   {
       $patterns = ['/loopback/i', '/tengigabitethernet/i', '/gigabitethernet/i', '/gig /i',
                        '/port-channel/i', '/fastethernet/i', '/ethernet/i', ];
       $abbrev = ['Lo', 'Te', 'Gi', 'Gi', 'Po', 'Fa', 'Eth'];

       return preg_replace($patterns, $abbrev, $interface);
   }

    public function dnsabbreviate($INTERFACE)
    {
        $INTERFACE = strtolower($INTERFACE);
        $INTERFACE = preg_replace('/gigabitethernet/', 'gig', $INTERFACE);
        $INTERFACE = preg_replace('/gigabiteth/', 'gig', $INTERFACE);
        $INTERFACE = preg_replace("/gi\//", "gig\/", $INTERFACE);
        $INTERFACE = preg_replace('/fastethernet/', 'fa', $INTERFACE);
        $INTERFACE = preg_replace("/port\-channel/", 'po', $INTERFACE);
        $INTERFACE = preg_replace('/loopback/', 'lo', $INTERFACE);
        $INTERFACE = preg_replace('/ethernet/', 'eth', $INTERFACE);
        $INTERFACE = preg_replace('/tunnel/', 'tun', $INTERFACE);
        $INTERFACE = preg_replace('/bvi/', 'bvi', $INTERFACE);
        $INTERFACE = preg_replace('/atm/', 'atm', $INTERFACE);
        $INTERFACE = preg_replace('/serial/', 'se', $INTERFACE);
        $INTERFACE = preg_replace('/dialer/', 'di', $INTERFACE);
        $INTERFACE = preg_replace('/multilink/', 'mu', $INTERFACE);
        $INTERFACE = preg_replace("/\//", '-', $INTERFACE);
        $INTERFACE = preg_replace("/\./", '-', $INTERFACE);
        $INTERFACE = preg_replace('/:/', '-', $INTERFACE);

        return $INTERFACE;
    }

    public function debreviate($value)
    {
        if (!isset($value)) {
            return;
        }

        return $this->abbr[$value];
    }

    public function unabbreviate($interface)
    {
        preg_match('/^([A-Za-z]+)(.*)\s*$/', $interface, $match);
        $abbriname = $this->debreviate($match[1]);
        $fulliname = ($abbriname) ? $abbriname.$match[2] : $interface;

        return $fulliname;
    }

    public function dnsinterface($value)
    {
        if (!isset($value)) {
            return;
        }
    }

    public function data($host, $key, $value = null)
    {
        if (!isset($host) || !isset($key)) {
            return;
        }
        if (is_null($value)) {
            return $this->data[$host][$key];
        } else {
            $this->data[$host][$key] = $value;
        }
    }

    public function device($host, $key, $value = null)
    {
        if (!isset($host) || !isset($key)) {
            return;
        }
        if (is_null($value)) {
            return $this->device[$host][$key];
        } else {
            $this->device[$host][$key] = $value;
        }
    }

    public function config($key, $value = null)
    {
        if (!isset($key)) {
            return;
        }
        if (is_null($value)) {
            return $this->config[$key];
        } else {
            $this->config[$key] = $value;
        }
    }

    public function iface($key, $value = null)
    {
        if (!isset($key)) {
            return;
        }
        if (is_null($value)) {
            return $this->iface[$key];
        } else {
            $this->iface[$key] = $value;
        }
    }

    public function parse_config_interface($iname)
    {
        if (!$iname) {
            return;
        }
        $build = [];
        $idata = $this->config['interface'][$iname];

        foreach ($idata as $setting) {
            if (preg_match('/^switchport mode (\S+)/', $setting, $match)) {
                $this->iface[$iname]['mode'] = $match[1];
            } elseif (preg_match('/^switchport access vlan (\d+)/', $setting, $match)) {
                $this->iface[$iname]['vlan.access'] = $match[1];
            } elseif (preg_match('/^switchport trunk allowed vlan (?:add)?(.*)\s*$/', $setting, $match)) {
                $vlans = explode(',', $match[1]);
                if (!isset($this->iface[$iname]['vlan.trunk'])) {
                    $this->iface[$iname]['vlan.trunk'] = [];
                }
                $this->iface[$iname]['vlan.trunk'] =
               array_merge($this->iface[$iname]['vlan.trunk'], (array) $vlans);
            } elseif (preg_match('/^description (.*)\s*$/', $setting, $match)) {
                $this->iface[$iname]['desc'] = $match[1];
            } elseif (preg_match('/^shutdown\s*$/', $setting, $match)) {
                $this->iface[$iname]['shut'] = 1;
            }
//TODO this needs to be turned into an array for stupid interfaces with more than one IP address.
         if (preg_match('/^ip address (\S+) (\S+)/', $setting, $match)) {        // IOS
            $this->iface[$iname]['ipv4addr'] = $match[1];
             $this->iface[$iname]['ipv4mask'] = $match[2];
         }
            if (preg_match('/^ipv4 address (\S+) (\S+)/', $setting, $match)) {    // IOS-XR
            $this->iface[$iname]['ipv4addr'] = $match[1];
                $this->iface[$iname]['ipv4mask'] = $match[2];
            }
            if (preg_match('/^ip address (\S+)\/(\S+)/', $setting, $match)) {    // NXOS
            $this->iface[$iname]['ipv4addr'] = $match[1];
            }
        }

        if (($this->iface[$iname]['mode'] == 'trunk') && (isset($this->iface[$iname]['vlan.trunk']))) {
            $this->iface[$iname]['vlan'] = implode(',', $this->iface[$iname]['vlan.trunk']);
        } else {
            $this->iface[$iname]['vlan'] = $this->iface[$iname]['vlan.access'];
        }

      //## initialize status
      $this->iface[$iname]['status'] = 'unknown';
    }

    public function parse_config($info)
    {
        if (!$info) {
            return;
        }
        foreach ($info as $line) {
            if (preg_match('/^interface\s+(\S+)/i', $line, $match)) {
                $current = $match[1];
                $this->config['interface'][$current] = [];
            } elseif (preg_match('/^!\s*$/', $line, $match)) {
                unset($current);
            } elseif (isset($current)) {
                $modline = preg_replace('/^\s*(.*?)\s*$/', '$1', $line);
                array_push($this->config['interface'][$current], $modline);
            }
        }

        foreach ($this->config['interface'] as $iname => $idata) {
            $this->parse_config_interface($iname);
        }
    }

    public function parse_version($info)
    {
        if (!$info) {
            return;
        }
        foreach ($info as $line) {
            if (preg_match('/^interface\s+(\S+)/i', $line, $match)) {
                $current = $match[1];
                $this->config['interface'][$current] = [];
            }
        }
    }

    public function parse_show_interface($info)
    {
        if (!$info) {
            return;
        }
        foreach ($info as $line) {
            if (preg_match('/^\s*5 minute \S+ rate (\d+) (\S+)\//', $line, $match)) {
                $bits_bytes = $match[2];
                $rate = ($bits_bytes == 'bytes') ? ($match[1] * 8) : $match[1];
                $return['rate'] += $rate;
            }
        }

        return $return;
    }

    public function parse_show_ip_interface_brief($info)
    {
        if (!$info) {
            return;
        }
        foreach ($info as $line) {
            if (preg_match('/^(\S+)\s+([\d\.]+)\s+/', $line, $match)) {
                $int = $match[1];
                $ip = $match[2];
                $return[$int]['ip'] = $ip;
            }
        }

        return $return;
    }

/* DO NOT USE THIS FUNCTION! *\
   public function parse_interface_status($info) {
      foreach ($info as $line) {
            if (!$status) { $status = "unknown"; }

#            print "$int, $name, $status, $vlan, $duplex, $speed\n";

            $int = preg_replace($abbrname,$fullname,$int);

            $this->iface[$int]['status'] = $status;
         }
      }
   }
/**/

   public function parse_interface_status($input)
   {
       if (!$input) {
           return;
       }

       $data = $this->parse_space_delimited_command($input, $index);

       foreach ($data as $interface) {
           $int = $this->unabbreviate($interface['port']);

//       $name   = $interface['name']; // This is pulled from the config. dont use it.
//       $vlan   = $interface['vlan']; // This is pulled from the config too... we override it here.

         $this->iface[$int]['status'] = $interface['status'];
           $this->iface[$int]['duplex'] = $interface['duplex'];
           $this->iface[$int]['speed'] = $interface['speed'];
           $this->iface[$int]['type'] = $interface['type'];
       }
   }

    public function parse_show_interface_status($input, $index = null)
    {
        $data = $this->parse_space_delimited_command($input, $index);
        $return = [];
        foreach ($data as $int => $info) {
            $return[$this->abbreviate($int)] = $info;
        }

        return $return;
    }

    public function parse_space_delimited_command($input, $index = null)
    {
        //## Build array from lines of data.
      //##================================
      $list = explode("\n", $input);

      //## Remove the first line since it only contains the command for showing output.
      //##=============================================================================
      array_shift($list);

      //## Extract the next line of the array and use as the header.
      //## Skip any lines that start with a 'dash' (assumed text formatting)
      //## or lines that are completely blank.
      //##==================================================================
      while (preg_match('/^[\-\s]*$/', $list[0]) && isset($list[0])) {
          $foo = array_shift($list);
      }
        $header = array_shift($list);

      //## CISCO BUG FIX (show interface status):
      //## On most IOS devices the Speed header does not left justify align with the
      //## listed speed.  We need to detect this and replace the proper string.
      //##==========================================================================

      if (preg_match('/Duplex  Speed Type.*$/', $header)) {
          $header = preg_replace("/\ Speed/", 'Speed', $header);
      }

        list($pattern, $patternmap) = $this->build_header_pattern($header);

      //## If no index was provided, it's safe to assume that the first value should
      //## be the index for the data.
      //##==========================================================================
      if (!$index) {
          $index = $patternmap[1];
      }

      //## Begin pattern matching against the input data.
      //## Skip all lines that are just dashs/whitespace.
      //##===============================================

      foreach ($list as $line) {
          if (preg_match('/^[\-\s]*$/', $line)) {
              continue;
          }
          if (preg_match($pattern, $line, $match)) {
              foreach ($patternmap as $pos => $name) {
                  $results[$name] = trim($match[$pos]);
              }
              $return[$results[$index]] = $results;
          }
      }

        return $return;
    }

    public function build_header_pattern($header)
    {
        //## Split the fields on whitespace.
      //##================================
      $fieldlist = preg_split('/\s+/', $header);

      //## Build our list of fields, excluding any fields that were blank.
      //##================================================================
      $fields = [];
        foreach ($fieldlist as $field) {
            if ($field) {
                $fields[] = $field;
            }
        }

      //## Loop through the fields and determine what position they occur in the header.
      //## Find the length of each field by subtracting the last position from the current.
      //##=================================================================================
      $count = 0;
        foreach ($fields as $field) {
            if (!$field) {
                continue;
            }

            $info[$count]['position'] = strpos($header, $field);
            $info[$count]['name'] = $field;

            $prev = $info[$count - 1];

            $length[$prev['name']] = $info[$count]['position'] - $prev['position'];

            $count++;
        }

      //## The first value we assigned was irrelevent, so drop it.
      //##========================================================
      array_shift($length);

      //## We need to add the last value to our list, since it has an to end-of-line length
      $length[$fields[count($fields) - 1]] = '*';

      //## Assemble the values from the length array and build a match string
      //## to feed to a preg_match command.
      //##===================================================================
      $pattern = '/^';

        $count = 1;
        foreach ($length as $name => $value) {
            $patternmap[$count] = strtolower($name);
            $pattern .= (($value == '*') ? '(.*)' : '(.{'.$value.'})');
            $count++;
        }
        $pattern .= '$/';

        return [$pattern, $patternmap];
    }

    public function parse_show_etherchannel_detail($input)
    {
        $return = [];

        if (!$input) {
            return $return;
        }

        $group = '';

        foreach (explode("\n", $input) as $line) {
            if (preg_match('/^Group:\s+(\d+)\s*$/', $line, $match)) {
                $group = $match[1];
            } elseif (preg_match('/^Port:\s+(\S+)\s*$/', $line, $match)) {
                $port = $match[1];
                $return['Port-channel'.$group]['members'][] = $port;
            }
        }

        return $return;
    }

    public function parse_mac_address_table($data)
    {
        foreach ($data as $line) {
            /* *\
NORMAL show mac! |  20    0027.0d35.1a08    DYNAMIC     Gi0/49
6500 show mac!   |*  194  0021.281a.d7fa   dynamic  Yes          0   Gi4/10
nxos show mac!   |* 1        5cf3.fcef.a47e    dynamic   20         F    F  Po139
/**/
//        if (preg_match('/^\s*(\d+)\s+(\S+).*?(\S+)\s*$/',$line,$match))
         if (preg_match('/^\D*(\d+)\s+(\S+).*?(\S+)\s*$/', $line, $match)) {
             list($all, $vlan, $mac, $iname) = $match;
             preg_match('/^([A-Za-z]+)(.*)\s*$/', $iname, $match);
             $abbriname = $this->debreviate($match[1]);
             $fulliname = ($abbriname) ? $abbriname.$match[2] : $iname;
             $return[$fulliname][$mac] = $vlan;
         }
        }

        return $return;
    }
}
