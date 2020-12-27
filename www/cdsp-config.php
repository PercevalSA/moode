<?php
/**
 * moOde audio player (C) 2014 Tim Curtis
 * http://moodeaudio.org
 *
 * This Program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3, or (at your option)
 * any later version.
 *
 * This Program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * 2021-MM-DD TC moOde 7.x.x
 *
 */

require_once dirname(__FILE__) . '/inc/playerlib.php';
require_once dirname(__FILE__) . '/inc/cdsp.php';

playerSession('open', '' ,'');
$cdsp = new CamillaDsp($_SESSION['camilla'], $_SESSION['cardnum']);

/**
 * Post parameter processing
 */

// Check
if (isset($_POST['save']) && $_POST['save'] == '1') {
	if (isset($_POST['cdsp-mode'])) {
		playerSession('write', 'camilla', $_POST['cdsp-mode']);
		$cdsp->selectConfig($_POST['cdsp-mode']);
		if ($_SESSION['cdsp_fix_playback'] == 'Yes' ) {
			$cdsp->setPlaybackDevice($_SESSION['cardnum']);
		}
		//todo: update active configuration if needed
	}

	if (isset($_POST['cdsp_playbackdevice'])) {
		$patchPlaybackDevice = $_POST['cdsp_playbackdevice'];
		playerSession('write', 'cdsp_fix_playback', $patchPlaybackDevice == "1" ? "Yes" : "No");
		if ($_SESSION['cdsp_fix_playback'] == 'Yes' ) {
		   $cdsp->setPlaybackDevice($_SESSION['cardnum']);
		}
		//todo: implement update configuration with device if needed
	}
}

// Check
else if (isset($_POST['cdsp-config']) && isset($_POST['check']) && $_POST['check'] == '1') {
	$result = $cdsp->checkConfigFile($_POST['cdsp-config']);

	if($result == NULL) {
		$_SESSION['notify']['title'] =   htmlentities('Pipeline configuration \"' . $_POST['cdsp-config'] . '\" is valid');
	}else {
		$_SESSION['notify']['title'] = htmlentities('Pipeline configuration \"' . $_POST['cdsp-config'] . '\" is NOT valid');

		$message = '';
		foreach ($result as $line) {
			$message .= htmlentities($line). '<br/>';
		}
		$_SESSION['notify']['msg'] = $message;
	}

}
// Import (Upload)
else if (isset($_FILES['pipelineconfig']) && isset($_POST['import']) && $_POST['import'] == '1') {
	$configFileName = $cdsp->getConfigsLocationsFileName() . $_FILES["pipelineconfig"]["name"];
	move_uploaded_file($_FILES["pipelineconfig"]["tmp_name"], $configFileName);
	$_SESSION['notify']['title'] =  htmlentities('Import \"' . $_FILES["pipelineconfig"]["name"] . '\" completed');
}
// Export (Download)
else if (isset($_POST['cdsp-config']) && isset($_POST['export']) && $_POST['export'] == '1') {
	$configFileName = $cdsp->getConfigsLocationsFileName() . $_POST['cdsp-config'];

	header("Content-Description: File Transfer");
	header("Content-Type: application/yaml");
	header("Content-Disposition: attachment; filename=\"". $_POST['cdsp-config'] ."\"");

	readfile ($configFileName);
 	exit();
}
// Remove
else if (isset($_POST['cdsp-config']) && isset($_POST['remove']) && $_POST['remove'] == '1') {
	$configFileName = $cdsp->getConfigsLocationsFileName() . $_POST['cdsp-config'];
	unlink($configFileName);
	$_SESSION['notify']['title'] = htmlentities('Remove configuration \"' . $_POST['cdsp-config'] . '\" completed');
}
// Import (Upload)
else if (isset($_FILES['coeffsfile']) && isset($_POST['import']) && $_POST['import'] == '1') {
	$configFileName = $cdsp->getCoeffsLocation() . $_FILES["coeffsfile"]["name"];
	move_uploaded_file($_FILES["coeffsfile"]["tmp_name"], $configFileName);
	$_SESSION['notify']['title'] =  htmlentities('Import \"' . $_FILES["coeffsfile"]["name"] . '\" completed');
}
// Export (Download)
else if (isset($_POST['cdsp-coeffs']) && isset($_POST['export']) && $_POST['export'] == '1') {
	$configFileName = $cdsp->getCoeffsLocation() . $_POST['cdsp-coeffs'];

	header("Content-Description: File Transfer");
	header("Content-Type: application/binary");
	header("Content-Disposition: attachment; filename=\"". $_POST['cdsp-coeffs'] ."\"");

	readfile ($configFileName);
 	exit();
}
// Remove
else if (isset($_POST['cdsp-coeffs']) && isset($_POST['remove']) && $_POST['remove'] == '1') {
	$configFileName = $cdsp->getCoeffsLocation() . $_POST['cdsp-coeffs'];
	unlink($configFileName);
	$_SESSION['notify']['title'] = htmlentities('Remove configuration \"' . $_POST['cdsp-coeffs'] . '\" completed');
}

/**
 * Generate data for html templating
 */

$configs = $cdsp->getAvailableConfigs();
foreach ($configs as $config_file=>$config_name) {
	$selected = ($_SESSION['camilla'] == $config_file) ? 'selected' : '';
	$_select['cdsp_mode'] .= sprintf("<option value='%s' %s>%s</option>\n", $config_file, $selected, $config_name);
	if ($selected == 'selected') {
	// 	$_selected_mode = $config_file;
	}
}

$configs = $cdsp->getAvailableConfigsRaw();
$_selected = NULL;
foreach ($configs as $config_file=>$config_name) {
	$selected = ($_POST['cdsp_config'] == $config_file || (isset($_POST['cdsp_config']) == false && $_selected == NULL) ) ? 'selected' : '';
	$_select['cdsp_configs'] .= sprintf("<option value='%s' %s>%s</option>\n", $config_file, $selected, $config_name);
	if ($selected == 'selected') {
		$_selected = $selected;
		$_selected_configuration = $config_file;
	}
}

$configs = $cdsp->getAvailableCoeffs();
$_selected_coeff = NULL;
foreach ($configs as $config_file=>$config_name) {
	$selected = ($_POST['cdsp-coeffs'] == $config_file || (isset($_POST['cdsp-coeffs']) == false && $_selected_coeff == NULL) ) ? 'selected' : '';
	$_select['cdsp_coeffs'] .= sprintf("<option value='%s' %s>%s</option>\n", $config_file, $selected, $config_name);
	if ($selected == 'selected') {
		$_selected_coeff = $config_file;
		// $_selected_configuration = $config_file;
	}
}

$_select['cdsp_selected_configuration'] = $_selected_configuration;
$_select['cdsp_patch_playback_device1'] .= "<input type=\"radio\" name=\"cdsp_playbackdevice\" id=\"toggle-cdsp-playbackdevice1\" value=\"1\" " . (($_SESSION['cdsp_fix_playback'] == 'Yes') ? "checked=\"checked\"" : "") . ">\n";
$_select['cdsp_patch_playback_device0'] .= "<input type=\"radio\" name=\"cdsp_playbackdevice\" id=\"toggle-cdsp-playbackdevice2\" value=\"0\" " . (($_SESSION['cdsp_fix_playback'] == 'No') ? "checked=\"checked\"" : "") . ">\n";

$_select['version'] = $cdsp->version();

session_write_close();

waitWorker(1, 'cdsp-config');

$tpl = "cdsp-config.html";
$section = basename(__FILE__, '.php');
storeBackLink($section, $tpl);

include('header.php');
eval("echoTemplate(\"" . getTemplate("templates/$tpl") . "\");");
include('footer.php');
