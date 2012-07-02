<?php
/* @var $core Core */

if (!$core->get_session()->is_logged_in()) {
	AjaxModul::return_code(AjaxModul::ERROR_NOT_LOGGEDIN, NULL, true, t("Not logged in"));
}


$params = new ParamStruct();
$params->add_param("fid", PDT_INT, 0);

$params->fill();

//If we provided a file id it determines that we want to delete it. so do it.
if (!empty($params->fid)) {

	$file_obj = new MainFileObj($params->fid);

	if ($file_obj->load_success() == false) {
		AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, NULL, true, t("File not found"));
	}
	if ($file_obj->owner == $core->get_session()->current_user()->username && $file_obj->delete()) {
		AjaxModul::return_code(AjaxModul::SUCCESS, NULL, true, t("File deleted"));
	}
	AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, NULL, true, t("Could not delete file"));
}

$allowed_extensions = array();
$uploader = new qqFileUploader();

$result = $uploader->handle_upload(new MainFileObj());
if ($result !== AjaxModul::SUCCESS) {
	$message = "";
	switch ($result) {
		case qqFileUploader::STATUS_CANCELLED:
			$message = t("Could not save uploaded file. The upload was cancelled, or server error encountered.");
			break;
		case qqFileUploader::STATUS_EMPTY_FILES:
			$message = t("File is empty.");
			break;
		case qqFileUploader::STATUS_INVALID_EXTENSION:
			$message = t("File has an invalid extension, it should be one of @allowed_extensions.", array(
				'@allowed_extensions' => implode(", ", $allowed_extensions)
				));
			break;
		case qqFileUploader::STATUS_NOT_WRITEABLE:
			$message = t("Server error. Upload directory isn't writable.");
			break;
		case qqFileUploader::STATUS_NO_FILES:
			$message = t("No files were uploaded.");
			break;
		case qqFileUploader::STATUS_TO_BIG:
			$message = t("File is too large.");
			break;
		case qqFileUploader::STATUS_MAX_SIZE_TO_BIG:
			$message = t("upload_max_size and/or post_max_size to small.");
			break;
		case qqFileUploader::STATUS_SQL_ERROR:
			$message = t("File could not stored into database.");
			break;
		default:
			$message = t("Unknown error.");
	}
	AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, NULL, true, $message);
}

$return = array(
	'fid' => $uploader->get_fid()
);
$obj = new MainFileObj($uploader->get_fid());
$obj->status(MainFileObj::STATUS_PERM);
$obj->save();
AjaxModul::return_code(AjaxModul::SUCCESS, $return, true);
