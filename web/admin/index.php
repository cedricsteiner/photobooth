<?php
/* -------------------------------------------------------------------
 * 
 * Author: Blupixel IT Srl
 * Last Modifcation Date: 23 Jan 2017
 * Website: www.blupixelit.eu
 * support at: support@blupixelit.eu
 * 
 * Copyright (c) 2017 Blupixel IT Srl - Trento (Italy)
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
?>
<!DOCTYPE>
<head>
</head>
<html>
	<body>
		<?php
        // include the class
        include('IniEditor.class.php');

		// initialize the class object
		$ini_editor = new IniEditor();

		// include Javascript and CSS from jQuery and Bootstrap CDN
		echo IniEditor::getCssJsInclude();
		
		// include class CSS (use your own if you prefer)
		echo IniEditor::getCSS();

		// set folder where to put backups before saving the new version of the file (folder needs write permissions)
		$ini_editor->setBackupFolder('backups');
		
		// set different Scanner Mode (optional)
		$ini_editor->setScannerMode(INI_SCANNER_RAW);
		
		// set the path of the file you want to edit or view
		$ini_editor->setIniFile('config.ini');
		
		// set to true to allow edit of the config file (default is true)
		$ini_editor->enableEdit(true);
				
		// set to true to allow add of sections and conf in the config file (default is true)
		$ini_editor->enableAdd(true);
		
		// set to true to allow delete of conf in the config file (default is true)
		$ini_editor->enableDelete(true);

		// print the form. Use $ini_editor->getForm() to store it in a variable
		$ini_editor->printForm();

		?>
	</body>
</html>
