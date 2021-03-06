<?php
/**
* @version 			SEBLOD 3.x More
* @package			SEBLOD (App Builder & CCK) // SEBLOD nano (Form Builder)
* @url				https://www.seblod.com
* @editor			Octopoos - www.octopoos.com
* @copyright		Copyright (C) 2009 - 2017 SEBLOD. All Rights Reserved.
* @license 			GNU General Public License version 2 or later; see _LICENSE.php
**/

defined( '_JEXEC' ) or die;

// Plugin
class plgCCK_Field_LinkDownload extends JCckPluginLink
{
	protected static $type	=	'download';
	
	// -------- -------- -------- -------- -------- -------- -------- -------- // Prepare
	
	// onCCK_Field_LinkPrepareContent
	public static function onCCK_Field_LinkPrepareContent( &$field, &$config = array() )
	{
		if ( self::$type != $field->link ) {
			return;
		}
		
		// Prepare
		$link	=	parent::g_getLink( $field->link_options );
		
		// Set
		$field->link	=	'';
		self::_link( $link, $field, $config );
	}
	
	// _link
	protected static function _link( $link, &$field, &$config )
	{
		// Prepare
		$content			=	$link->get( 'content', '' );
		$content_fieldname	=	$link->get( 'content_fieldname', '' );
		$file_fieldname		=	$link->get( 'file_fieldname', '' );
		$link_class			=	$link->get( 'class', '' );
		$link_more			=	( $config['client'] == 'intro' /*|| $config['client'] == 'list' || $config['client'] == 'item'*/ ) ? '&client='.$config['client'] : '';
		$xi					=	0;

		// Set
		if ( is_array( $field->value ) ) {
			$collection			=	$field->name;
			
			foreach ( $field->value as $f ) {
				$query			=	'SELECT a.hits FROM #__cck_core_downloads AS a WHERE a.id = '.(int)$config['id'].' AND a.field = "'.(string)$f->name.'" AND a.collection = "'.(string)$collection.'" AND a.x = '.(int)$xi;
				$field->hits	=	(int)JCckDatabase::loadResult( $query ); //@
				
				$link_more2		=	$link_more.'&collection='.$collection.'&xi='.$xi;
				$f->link		=	'index.php?option=com_cck&task=download'.$link_more2.'&file='.$f->name.'&id='.$config['id'];
				$f->link_class	=	$link_class ? $link_class : ( isset( $f->link_class ) ? $f->link_class : '' );
				$xi++;
			}
			$field->link		=	'#';	//todo
		} else {
			$collection			=	'';
			$field_name			=	( $file_fieldname ) ? $file_fieldname : $field->name;
			$pk					=	$config['id'];

			if ( $content == '2' ) {
				$field->link	=	'';
				$pk				=	0;
				
				parent::g_addProcess( 'beforeRenderContent', self::$type, $config, array( 'name'=>$field->name, 'content_fieldname'=>$content_fieldname, 'file_fieldname'=>$field_name, 'link_more'=>$link_more, 'pk'=>$pk ) );
			} else {
				$query			=	'SELECT a.hits FROM #__cck_core_downloads AS a WHERE a.id = '.(int)$pk.' AND a.field = "'.(string)$field_name.'" AND a.collection = "'.(string)$collection.'" AND a.x = '.(int)$xi;
				$field->hits	=	(int)JCckDatabase::loadResult( $query ); //@
				$field->link	=	'index.php?option=com_cck&task=download'.$link_more.'&file='.$file_fieldname.'&id='.$pk;
			}

			$field->link_class	=	$link_class ? $link_class : ( isset( $field->link_class ) ? $field->link_class : '' );
		}
	}

	// onCCK_Field_LinkBeforeRenderContent
	public static function onCCK_Field_LinkBeforeRenderContent( $process, &$fields, &$storages, &$config = array() )
	{
		$name		=	$process['name'];
		$fieldname	=	$process['content_fieldname'];
		$pk			=	isset( $fields[$fieldname] ) ? (int)$fields[$fieldname]->value : $process['pk'];

		if ( $pk ) {
			$fields[$name]->link	=	'index.php?option=com_cck&task=download'.$process['link_more'].'&file='.$process['file_fieldname'].'&id='.$pk;
			$target					=	 $fields[$name]->typo_target;

			if ( isset( $fields[$name]->typo_mode ) && $fields[$name]->typo_mode ) {
				$target	=	'typo';
			}

			if ( $fields[$name]->link ) {
				JCckPluginLink::g_setHtml( $fields[$name], $target );
			}
			if ( $fields[$name]->typo ) {
				$html						=	( isset( $fields[$name]->html ) ) ? $fields[$name]->html : '';
				if ( strpos( $fields[$name]->typo, $fields[$name]->$target ) === false ) {
					$fields[$name]->typo	=	$html;
				} else {
					$fields[$name]->typo	=	str_replace( $fields[$name]->$target, $html, $fields[$name]->typo );
				}
			}
		}
	}
}
?>