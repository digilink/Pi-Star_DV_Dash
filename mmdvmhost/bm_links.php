<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/config/config.php';          // MMDVMDash Config
include_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/tools.php';        // MMDVMDash Tools
include_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/functions.php';    // MMDVMDash Functions
include_once $_SERVER['DOCUMENT_ROOT'].'/config/language.php';        // Translation Code

// Check if DMR is Enabled
$testMMDVModeDMR = getConfigItem("DMR", "Enable", $mmdvmconfigs);

if ( $testMMDVModeDMR == 1 ) {
  //Load the dmrgateway config file
  $dmrGatewayConfigFile = '/etc/dmrgateway';
  if (fopen($dmrGatewayConfigFile,'r')) { $configdmrgateway = parse_ini_file($dmrGatewayConfigFile, true); }

  // Get the current DMR Master from the config
  $dmrMasterHost = getConfigItem("DMR Network", "Address", $mmdvmconfigs);
  if ( $dmrMasterHost == '127.0.0.1' ) { $dmrMasterHost = $configdmrgateway['DMR Network 1']['Address']; }

  // Store the DMR Master IP, we will need this for the JSON lookup
  $dmrMasterHostIP = $dmrMasterHost;

  // Make sure the master is a BrandMeister Master
  $dmrMasterFile = fopen("/usr/local/etc/DMR_Hosts.txt", "r");
  while (!feof($dmrMasterFile)) {
                $dmrMasterLine = fgets($dmrMasterFile);
                $dmrMasterHostF = preg_split('/\s+/', $dmrMasterLine);
                if ((strpos($dmrMasterHostF[0], '#') === FALSE) && ($dmrMasterHostF[0] != '')) {
                        if ($dmrMasterHost == $dmrMasterHostF[2]) { $dmrMasterHost = str_replace('_', ' ', $dmrMasterHostF[0]); }
                }
  }

  if (substr($dmrMasterHost, 0, 2) == "BM") {
  // DMR ID, we will need this for the JSON lookup
  $dmrID = getConfigItem("DMR", "Id", $mmdvmconfigs);

  // Use BM API to get information about current TGs
  $json = json_decode(file_get_contents("https://api.brandmeister.network/v1.0/repeater/?action=profile&q=$dmrID", true));
  if (isset($json->reflector->reflector)) { $bmReflectorDef = $json->reflector->reflector; } else { $bmReflectorDef = "Not Set"; }
  if (isset($json->reflector->interval)) { $bmReflectorInterval = $json->reflector->interval; } else {$bmReflectorInterval = "Not Set"; }
  if (isset($json->reflector->active)) { $bmReflectorActive = $json->reflector->active; } else { $bmReflectorActive = "None"; }
  if (isset($json->staticSubscriptions)) { $bmStaticTGListJson = $json->staticSubscriptions;
                                                foreach($bmStaticTGListJson as $staticTG) {
                                                        $bmStaticTGList .= $staticTG->talkgroup." ";
                                                }
                                        $bmStaticTGList = wordwrap($bmStaticTGList, 15, "<br />\n");
                                        } else { $bmStaticTGList = "None"; }
  if (isset($json->dynamicSubscription)) { $bmDynamicTGList = $json->dynamicSubscription;
                                                foreach($bmDynamicTGListJson as $dynamicTG) {
                                                        $bmDynamicTGList .= $dynamicTG->talkgroup." ";
                                                }
                                        $bmDynamicTGList = wordwrap($bmDynamicTGList, 15, "<br />\n");
                                        } else { $bmDynamicTGList = "None"; }

  echo '<b>Active BrandMeister Connections</b>
  <table>
    <tr>
      <th><a class=tooltip href="#">DMR Master<span><b>Connected Master</b></span></a></th>
      <th><a class=tooltip href="#">Default Ref<span><b>Default Reflector</b></span></a></th>
      <th><a class=tooltip href="#">Timeout<span><b>Configured Timeout</b></span></a></th>
      <th><a class=tooltip href="#">Active Ref<span><b>Active Reflector</b></span></a></th>
      <th><a class=tooltip href="#">Static TGs<span><b>Statically linked talkgroups</b></span></a></th>
      <th><a class=tooltip href="#">Dynamic TGs<span><b>Dynamically linked talkgroups</b></span></a></th>
    </tr>'."\n";

  echo '    <tr>'."\n";
  echo '      <td>'.$dmrMasterHost.'</td>';
  echo '<td>'.$bmReflectorDef.'</td>';
  echo '<td>'.$bmReflectorInterval.'</td>';
  echo '<td>'.$bmReflectorActive.'</td>';
  echo '<td>'.$bmStaticTGList.'</td>';
  echo '<td>'.$bmDynamicTGList.'</td>';
  echo '    </tr>'."\n";

  echo '  </table>'."\n";
  echo '  <br />'."\n";
  }
}
?>