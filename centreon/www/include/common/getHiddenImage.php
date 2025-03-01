<?php
/*
 * Copyright 2005-2015 Centreon
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 * 
 * This program is free software; you can redistribute it and/or modify it under 
 * the terms of the GNU General Public License as published by the Free Software 
 * Foundation ; either version 2 of the License.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with 
 * this program; if not, see <http://www.gnu.org/licenses>.
 * 
 * Linking this program statically or dynamically with other modules is making a 
 * combined work based on this program. Thus, the terms and conditions of the GNU 
 * General Public License cover the whole combination.
 * 
 * As a special exception, the copyright holders of this program give Centreon 
 * permission to link this program with independent modules to produce an executable, 
 * regardless of the license terms of these independent modules, and to copy and 
 * distribute the resulting executable under terms of Centreon choice, provided that 
 * Centreon also meet, for each linked independent module, the terms  and conditions 
 * of the license of that module. An independent module is a module which is not 
 * derived from this program. If you modify this program, you may extend this 
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 * 
 * For more information : contact@centreon.com
 * 
 */

require_once __DIR__ . "/../../../config/centreon.config.php";
require_once __DIR__ . "/../../class/centreonSession.class.php";
require_once __DIR__ . "/../../class/centreon.class.php";
require_once __DIR__ . "/../../class/centreonDB.class.php";

CentreonSession::start();

$pearDB = new CentreonDB();

$session = $pearDB->query("SELECT * FROM `session` WHERE `session_id` = '" . session_id() . "'");
if (!$session->rowCount()) {
    exit;
}

$logos_path = "../../img/media/";

if (isset($_GET["id"]) && $_GET["id"] && is_numeric($_GET["id"])) {
    $query = "SELECT dir_name, img_path FROM view_img_dir, view_img, view_img_dir_relation vidr " .
        "WHERE view_img_dir.dir_id = vidr.dir_dir_parent_id AND vidr.img_img_id = img_id AND img_id = :img_id";
    $statement = $pearDB->prepare($query);
    $statement->bindValue(':img_id', $_GET["id"], \PDO::PARAM_INT);
    $statement->execute();
    while ($img = $statement->fetch(\PDO::FETCH_ASSOC)) {
        $imgDirName = basename($img["dir_name"]);
        $imgName = basename($img["img_path"]);
        $imgPath = $logos_path . $imgDirName . "/" . $imgName;
        if (!is_file($imgPath)) {
            $imgPath = _CENTREON_PATH_ . 'www/img/media/' . $imgDirName . "/" . $imgName;
        }
        if (is_file($imgPath)) {
            $mimeType = finfo_file(finfo_open(), $imgPath, FILEINFO_MIME_TYPE);
            $fileExtension = substr($imgName, strrpos($imgName, '.') + 1);
            try {
                switch ($mimeType) {
                    case 'image/jpeg':
                        /**
                         * use @ to avoid PHP Warning log and instead log a more suitable error in centreon-web.log
                         */
                        $image = @imagecreatefromjpeg($imgPath);
                        if (! $image || ! imagejpeg($image)) {
                            CentreonLog::create()->error(
                                CentreonLog::TYPE_BUSINESS_LOG,
                                "Unable to validate image, your image may be corrupted",
                                [
                                    'mime_type' => $mimeType,
                                    'filename' => $imgName,
                                    'extension' => $fileExtension
                                ]
                            );
                            throw new Exception("Failed to create image from JPEG");
                        }
                        break;
                    case 'image/png':
                        /**
                         * use @ to avoid PHP Warning log and instead log a more suitable error in centreon-web.log
                         */
                        $image = @imagecreatefrompng($imgPath);
                        if (! $image || ! imagepng($image)) {
                            CentreonLog::create()->error(
                                CentreonLog::TYPE_BUSINESS_LOG,
                                "Unable to validate image, your image may be corrupted",
                                [
                                    'mime_type' => $mimeType,
                                    'filename' => $imgName,
                                    'extension' => $fileExtension
                                ]
                            );
                            throw new Exception("Failed to create image from PNG");
                        }
                        break;
                    case 'image/gif':
                        /**
                         * use @ to avoid PHP Warning log and instead log a more suitable error in centreon-web.log
                         */
                        $image = @imagecreatefromgif($imgPath);
                        if (! $image || ! imagegif($image)) {
                            CentreonLog::create()->error(
                                CentreonLog::TYPE_BUSINESS_LOG,
                                "Unable to validate image, your image may be corrupted",
                                [
                                    'mime_type' => $mimeType,
                                    'filename' => $imgName,
                                    'extension' => $fileExtension
                                ]
                            );
                            throw new Exception("Failed to create image from GIF");
                        }
                        break;
                    case 'image/svg+xml':
                        $image = file_get_contents($imgPath);
                        header('Content-Type: image/svg+xml');
                        print $image;
                        break;
                    default:
                        throw new Exception("Unsupported image type: $mimeType");
                        break;
                };
            } catch (Throwable $e) {
                print $e->getMessage();
            }
        } else {
            print "File not found";
        }
    }
}
