<?php
/**
* @version 			SEBLOD Updater 1.x
* @package			SEBLOD Updater Add-on for SEBLOD 3.x
* @url				http://www.seblod.com
* @editor			Octopoos - www.octopoos.com
* @copyright		Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
* @license 			GNU General Public License version 2 or later; see _LICENSE.php
**/

defined( '_JEXEC' ) or die;

JLoader::register( 'InstallerModelUpdate', JPATH_ADMINISTRATOR.'/components/com_installer/models/update.php' );

// Model
class CCK_UpdaterModelCCK_Updater extends InstallerModelUpdate
{
	// update
	public function update( $pks )
	{
		$count		=	count( $pks );
		$params		=	JComponentHelper::getParams( 'com_cck_updater' );
		$properties	=	array( 'client', 'description', 'element', 'infourl', 'maintainer', 'maintainerurl', 'name', 'type', 'version' );
		$secret		=	$params->get( 'secret_key', '' );
		if ( !$secret ) {
			return 0;
		}

		$lang		=	JFactory::getLanguage();
		$lang->load( 'com_installer' );

		$domain		=	JUri::getInstance()->getHost();
		$vars		=	'&secret_key='.$secret.'&domain='.$domain;
		foreach ( $pks as $pk ) {
			$update		=	new JUpdate;
			$instance	=	JTable::getInstance( 'update' );
			$instance->load( $pk );

			// --------
			$http	=	JHttpFactory::getHttp();
			$resp	=	$http->get( $instance->detailsurl );

			if ( 200 != $resp->code ) {
				JLog::add(JText::sprintf( 'JLIB_UPDATER_ERROR_EXTENSION_OPEN_URL', $instance->detailsurl ), JLog::WARNING, 'jerror' );
				$count--;
				continue;
			}
			$xml	=	JCckDev::fromXML( $resp->body, false );
			foreach ( $properties as $p ) {
				$update->set( (string)$p, (string)$xml->update->$p );	
			}
			if ( isset( $xml->update->downloads->downloadurl ) ) {
				$url	=	(string)$xml->update->downloads->downloadurl;
				if ( strpos( $url, 'http://www.seblod.com/' ) === false ) {
					$count--;
					continue;
				}
				$url	.=	$vars;
				$url	=	str_replace( 'http://', 'https://', $url );
				$update->set( 'downloadurl', $url );
			}
			// --------

			if ( $this->install( $update ) ) {
				$instance->delete( $pk );
			} else {
				$count--;
			}
		}

		return $count;
	}

	// install
	private function install( $update )
	{
		$app	=	JFactory::getApplication();
		$url	=	$update->get( 'downloadurl' );
		
		if ( empty( $url ) ) {
			JError::raiseWarning('', JText::_( 'COM_INSTALLER_INVALID_EXTENSION_UPDATE' ) );
			return false;
		}

		// Download
		$p_file	=	JInstallerHelper::downloadPackage( $url );
		if ( !$p_file ) {
			JError::raiseWarning( '', JText::sprintf( 'COM_INSTALLER_PACKAGE_DOWNLOAD_FAILED', $url ) );
			return false;
		}

		$config		=	JFactory::getConfig();
		$tmp_dest	=	$config->get( 'tmp_path' );

		// Unpack
		$package	=	JInstallerHelper::unpack( $tmp_dest . '/' . $p_file );

		// Get an installer instance
		$installer	= JInstaller::getInstance();
		$update->set( 'type', $package['type'] );

		// Install
		if ( !$installer->update( $package['dir'] ) ) {
			$msg	=	JText::sprintf( 'COM_INSTALLER_MSG_UPDATE_ERROR', JText::_( 'COM_INSTALLER_TYPE_TYPE_' . strtoupper( $package['type'] ) ) );
			$result	=	false;
		} else {
			$msg 	=	JText::sprintf( 'COM_INSTALLER_MSG_UPDATE_SUCCESS', JText::_( 'COM_INSTALLER_TYPE_TYPE_' . strtoupper( $package['type'] ) ) );
			$result	=	true;
		}

		$this->type	=	$package['type'];

		$app->enqueueMessage( $msg );

		if ( !is_file( $package['packagefile'] ) ) {
			$package['packagefile']	=	$config->get( 'tmp_path' ) . '/' . $package['packagefile'];
		}

		JInstallerHelper::cleanupInstall( $package['packagefile'], $package['extractdir'] );

		return $result;
	}
}
?>