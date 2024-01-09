<?php

/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            https://www.tassos.gr
 * @copyright       Copyright © 2023 Tassos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

namespace NRFramework\Widgets;

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use NRFramework\Helpers\Widgets\GalleryManager as GalleryManagerHelper;
use NRFramework\Image;

/**
 *  Gallery Manager
 */
class GalleryManager extends Widget
{
	/**
	 * Widget default options
	 *
	 * @var array
	 */
	protected $widget_options = [
		// The input name
		'name' => '',

		// The field ID associated to this Gallery Manager, used to retrieve the field settings on AJAX actions
		'field_id' => null,

		// The item ID associated to this Gallery Manager, used to retrieve the field settings on AJAX actions
		'item_id' => null,

		/**
		 * Max file size in MB.
		 * 
		 * Defults to 0 (no limit).
		 */
		'max_file_size' => 0,

		/**
		 * How many files we can upload.
		 * 
		 * Defaults to 0 (no limit).
		 */
		'limit_files' => 0,

		// Allowed upload file types
		'allowed_file_types' => '.jpg, .jpeg, .png, .gif, .webp, image/webp',

		/**
		 * Original Image
		 */
		// Should the original uploaded image be resized?
		'original_image_resize' => false,

		// Main image width
		'original_image_resize_width' => 1920,

		/**
		 * Thumbnails
		 */
		// Thumbnails width
		'thumb_width' => 300,

		// Thumbnails height
		'thumb_height' => null,

		// Thumbnails resize method (crop, stretch, fit)
		'thumb_resize_method' => 'crop'
	];

	public function __construct($options = [])
	{
		parent::__construct($options);

		// Set gallery items
		$this->options['gallery_items'] = is_array($this->options['value']) ? $this->options['value'] : [];

		// Set css class for readonly state
		if ($this->options['readonly'])
		{
			$this->options['css_class'] .= ' readonly';
		}

		// Adds a css class when the gallery contains at least one item
		if (count($this->options['gallery_items']))
		{
			$this->options['css_class'] .= ' dz-has-items';
		}

		// Load translation strings
        \JText::script('NR_GALLERY_MANAGER_CONFIRM_REGENERATE_IMAGES');
        \JText::script('NR_GALLERY_MANAGER_CONFIRM_DELETE_ALL_SELECTED');
        \JText::script('NR_GALLERY_MANAGER_CONFIRM_DELETE_ALL');
        \JText::script('NR_GALLERY_MANAGER_CONFIRM_DELETE');
        \JText::script('NR_GALLERY_MANAGER_FILE_MISSING');
        \JText::script('NR_GALLERY_MANAGER_REACHED_FILES_LIMIT');
	}

	/**
	 * The upload task called by the AJAX hanler
	 *
	 * @return  void
	 */
	protected function ajax_upload()
	{
		$input = \JFactory::getApplication()->input;

		// Make sure we have a valid field id
		if (!$field_id = $input->getInt('field_id'))
		{
			$this->exitWithMessage('NR_GALLERY_MANAGER_FIELD_ID_ERROR');
		}

		// Make sure we have a valid file passed
		if (!$file = $input->files->get('file'))
		{
			$this->exitWithMessage('NR_GALLERY_MANAGER_ERROR_INVALID_FILE');
		}

		if (!$field_data = \NRFramework\Helpers\CustomField::getData($field_id))
		{
			$this->exitWithMessage('NR_GALLERY_MANAGER_INVALID_FIELD_DATA');
		}

		// get the media uploader file data, values are passed when we upload a file using the Media Uploader
		$media_uploader_file_data = [
			'is_media_uploader_file' => $input->get('media_uploader', false) == '1',
			'media_uploader_filename' => $input->getString('media_uploader_filename', '')
		];

		// In case we allow multiple uploads the file parameter is a 2 levels array.
		$first_property = array_pop($file);
		if (is_array($first_property))
		{
			$file = $first_property;
		}

		$style = $field_data->get('style', 'masonry');

		$uploadSettings = [
			'allow_unsafe' => false,
			'allowed_types' => $field_data->get('allowed_file_types', $this->widget_options['allowed_file_types']),
			'style' => $style
		];

		// Add watermark
		if ($field_data->get('watermark.type', 'disabled') !== 'disabled')
		{
			$uploadSettings['watermark'] = (array) $field_data->get('watermark', []);
			$uploadSettings['watermark']['image'] = !empty($uploadSettings['watermark']['image']) ? explode('#', JPATH_SITE . DIRECTORY_SEPARATOR . $uploadSettings['watermark']['image'])[0] : null;
			$uploadSettings['watermark']['apply_on_thumbnails'] = $field_data->get('watermark.apply_on_thumbnails', false) === '1';
		}

		$resize_method = $field_data->get('resize_method', 'crop');
		$thumb_height = null;
		switch ($style)
		{
			case 'grid':
				$thumb_height = $field_data->get('thumb_height', null);
				break;
			case 'slideshow':
				$thumb_height = $field_data->get('slideshow_thumb_height', null);
				$resize_method = $field_data->get('slideshow_resize_method', 'crop');
				break;
			case 'zjustified':
				$thumb_height = $field_data->get('justified_item_height', 200);
				break;
		}

		// resize image settings
		$resizeSettings = [
			'thumb_height' => $thumb_height,
			'thumb_resize_method' => $resize_method,
			'original_image_resize' => $style === 'slideshow' ? true : $field_data->get('original_image_resize', false),
			'original_image_resize_width' => $field_data->get('original_image_resize_width', 1920),
			'original_image_resize_height' => $style === 'slideshow' ? $field_data->get('original_image_resize_height', 1920) : null
		];

		if (in_array($style, ['grid', 'masonry', 'slideshow']))
		{
			$resizeSettings['thumb_width'] = $style === 'slideshow' ? $field_data->get('slideshow_thumb_width', 300) : $field_data->get('thumb_width', 300);
		}

		// Upload the file and resize the images as required
		if (!$uploaded_filenames = GalleryManagerHelper::upload($file, $uploadSettings, $media_uploader_file_data, $resizeSettings))
		{
			$this->exitWithMessage('NR_GALLERY_MANAGER_ERROR_CANNOT_UPLOAD_FILE');
		}

		echo json_encode([
			'source' => $uploaded_filenames['source'],
			'original' => $uploaded_filenames['original'],
			'thumbnail' => $uploaded_filenames['thumbnail'],
			'is_media_uploader_file' => $media_uploader_file_data['is_media_uploader_file']
		]);
	}

	/**
	 * The delete task called by the AJAX hanlder
	 *
	 * @return void
	 */
	protected function ajax_delete()
	{
		$input = \JFactory::getApplication()->input;

		// Get source image path.
		$source = $input->getString('source');

		// Make sure we have a valid file passed
		if (!$original = $input->getString('original'))
		{
			$this->exitWithMessage('NR_GALLERY_MANAGER_ERROR_INVALID_FILE');
		}

		// Make sure we have a valid file passed
		if (!$thumbnail = $input->getString('thumbnail'))
		{
			$this->exitWithMessage('NR_GALLERY_MANAGER_ERROR_INVALID_FILE');
		}

		// Make sure we have a valid field id
		if (!$field_id = $input->getInt('field_id'))
		{
			$this->exitWithMessage('NR_GALLERY_MANAGER_FIELD_ID_ERROR');
		}

		if (!$field_data = \NRFramework\Helpers\CustomField::getData($field_id))
		{
			$this->exitWithMessage('NR_GALLERY_MANAGER_INVALID_FIELD_DATA');
		}

		// Delete the source, original, and thumbnail file
		$deleted = GalleryManagerHelper::deleteFile($source, $original, $thumbnail);
		
		echo json_encode(['success' => $deleted]);
	}

	/**
	 * This task allows us to regenerate the images.
	 *
	 * @return void
	 */
	protected function ajax_regenerate_images()
	{
		$input = \JFactory::getApplication()->input;

		// Make sure we have a valid field id
		if (!$field_id = $input->getInt('field_id'))
		{
			echo json_encode(['success' => false, 'message' => \JText::_('NR_GALLERY_MANAGER_FIELD_ID_ERROR')]);
			die();
		}

		// Make sure we have a valid item id
		if (!$item_id = $input->getInt('item_id'))
		{
			echo json_encode(['success' => false, 'message' => \JText::_('NR_GALLERY_MANAGER_ITEM_ID_ERROR')]);
			die();
		}

		if (!$field_data = \NRFramework\Helpers\CustomField::getData($field_id))
		{
			echo json_encode(['success' => false, 'message' => \JText::_('NR_GALLERY_MANAGER_INVALID_FIELD_DATA')]);
			die();
		}

		$style = $field_data->get('style', 'masonry');
		
		$resize_method = $field_data->get('resize_method', 'crop');
		$thumb_height = null;
		switch ($style)
		{
			case 'grid':
				$thumb_height = $field_data->get('thumb_height', null);
				break;
			case 'slideshow':
				$thumb_height = $field_data->get('slideshow_thumb_height', null);
				$resize_method = $field_data->get('slideshow_resize_method', 'crop');
				break;
			case 'zjustified':
				$thumb_height = $field_data->get('justified_item_height', 200);
				break;
		}

		$resizeSettings = [
			'thumb_height' => $thumb_height,
			'thumb_resize_method' => $resize_method
		];

		if (in_array($style, ['grid', 'masonry', 'slideshow']))
		{
			$resizeSettings['thumb_width'] = $style === 'slideshow' ? $field_data->get('slideshow_thumb_width', 300) : $field_data->get('thumb_width', 300);
		}

		$watermarkSettings = [];
		// Add watermark
		if ($field_data->get('watermark.type', 'disabled') !== 'disabled')
		{
			$watermarkSettings = (array) $field_data->get('watermark', []);
			$watermarkSettings['image'] = !empty($watermarkSettings['image']) ? explode('#', JPATH_SITE . DIRECTORY_SEPARATOR . $watermarkSettings['image'])[0] : null;
			$watermarkSettings['apply_on_thumbnails'] = $field_data->get('watermark.apply_on_thumbnails', false) === '1';
		}
		$watermarkEnabled = isset($watermarkSettings['type']) && $watermarkSettings['type'] !== 'disabled';
		$thumbnailWatermarkEnabled = isset($watermarkSettings['type']) && $watermarkSettings['type'] !== 'disabled' && $watermarkSettings['apply_on_thumbnails'];

		$items = $input->get('items', null, 'ARRAY');
		$items = json_decode($items[0], true);

		$ds = DIRECTORY_SEPARATOR;

		// Parse all images
		if (is_array($items) && count($items))
		{
			foreach ($items as &$item)
			{
				$sourceImage = isset($item['source']) ? $item['source'] : '';
				$originalImage = isset($item['original']) ? $item['original'] : '';
				$thumbnailImage = isset($item['thumbnail']) ? $item['thumbnail'] : '';
				$thumbnailImagePath = implode($ds, [JPATH_ROOT, $thumbnailImage]);
				
				$sourceImagePath = $sourceImage ? implode($ds, [JPATH_ROOT, $sourceImage]) : false;
				$sourceImageExists = \JFile::exists($sourceImagePath);
				$originalImagePath = implode($ds, [JPATH_ROOT, $originalImage]);
				$originalImageExists = \JFile::exists($originalImagePath);

				// If source image does not exist, watermark is enabled, create it by clothing the original image
				if (!$sourceImageExists && $watermarkEnabled && $originalImage && \JFile::exists($originalImagePath))
				{
					// Create source from original image
					$sourceImagePath = \NRFramework\File::copy($originalImagePath, $originalImagePath, false, true);
					$sourceImageExists = true;

					// Modify the database entry and add "source" image to item
					// We just need the relative path to file
					$_sourceImagePath = str_replace(JPATH_ROOT . DIRECTORY_SEPARATOR, '', $sourceImagePath);
					$item['source'] = $_sourceImagePath;
					$_originalImagePath = str_replace(JPATH_ROOT . DIRECTORY_SEPARATOR, '', $originalImagePath);
					GalleryManagerHelper::setItemFieldSource($item_id, $field_id, $_sourceImagePath, $_originalImagePath);
				}
				
				if (!$originalImageExists)
				{
					continue;
				}

				if (!$sourceImageExists)
				{
					$sourceImagePath = $originalImagePath;
				}

				/**
				 * Handle original image.
				 */
				$resize_original_image = $style === 'slideshow' ? true : $field_data->get('original_image_resize', false);
				$originalImageSourcePath = $originalImagePath;
				$original_image_resize_width = $field_data->get('original_image_resize_width', 1920);
				$original_image_resize_height = $style === 'slideshow' ? $field_data->get('original_image_resize_height', 1920) : null;

				// Generate original image by using the source image
				if ($style === 'slideshow')
				{
					$originalImagePath = Image::resize($sourceImagePath, $original_image_resize_width, $original_image_resize_height, 70, 'crop', $originalImagePath);
				}
				else
				{
					$originalImagePath = Image::resizeAndKeepAspectRatio($sourceImagePath, $original_image_resize_width, 70, $originalImagePath);
				}

				$originalImageSourcePath = $originalImagePath;
				
				if ($watermarkEnabled)
				{
					$payload = array_merge($watermarkSettings, ['source' => $originalImageSourcePath, 'destination' => $originalImagePath]);
					Image::applyWatermark($payload);
				}

				/**
				 * Handle thumbnail image.
				 */
				// Generate thumbnail image by using the source image
				GalleryManagerHelper::generateThumbnail($sourceImagePath, $thumbnailImagePath, $resizeSettings, null, false);

				// Apply watermark to thumbnail image
				if ($watermarkEnabled && $thumbnailWatermarkEnabled)
				{
					$payload = array_merge($watermarkSettings, ['source' => $thumbnailImagePath]);
					Image::applyWatermark($payload);
				}
			}
		}

		echo json_encode(['success' => true, 'message' => \JText::_('NR_GALLERY_MANAGER_IMAGES_REGENERATED'), 'items' => $items]);
	}

	/**
	 * Exits the page with given message.
	 * 
	 * @param   string  $translation_string
	 * 
	 * @return  void
	 */
	private function exitWithMessage($translation_string)
	{
		http_response_code('500');
		die(\JText::_($translation_string));
	}
}